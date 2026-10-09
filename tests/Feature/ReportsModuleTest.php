<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Category;
use App\Models\ContractItem;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Harvest;
use App\Models\Member;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\Project;
use App\Models\RolePermission;
use App\Models\Stock;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReportsModuleTest extends TestCase
{
    protected $branch1, $branch2, $admin, $member, $category, $supplier, $product1, $product2, $customer, $order1, $order2, $project;
    protected function setUp(): void
    {
        parent::setUp();

        (require database_path('migrations/2026_10_03_000001_create_order_stock_reservations_table.php'))->up();

        // Users
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 150)->nullable();
                $table->string('email', 150)->nullable();
                $table->string('password', 255)->nullable();
                $table->string('role', 50)->default('member');
                $table->string('status', 20)->default('active');
                $table->timestamp('created_at')->useCurrent();
                $table->unsignedInteger('branch_id')->nullable()->default(1);
                $table->string('profile_pic', 255)->nullable();
                $table->string('verification_code', 6)->nullable();
                $table->dateTime('code_expires')->nullable();
                $table->string('remember_token', 100)->nullable();
            });
        }

        // Branches
        if (!Schema::hasTable('branches')) {
            Schema::create('branches', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->string('location')->nullable();
                $table->string('contact')->nullable();
                $table->string('branch_email')->nullable();
                $table->boolean('status')->default(true);
                $table->timestamps();
            });
        }
        if (\DB::table('branches')->count() === 0) {
            Branch::create(['name' => 'Main Branch']);
            Branch::create(['name' => 'Branch B']);
        }
        $this->branch1 = Branch::first();
        $this->branch2 = Branch::skip(1)->first();

        // Role permissions
        if (!Schema::hasTable('role_permissions')) {
            Schema::create('role_permissions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('role');
                $table->string('permission');
                $table->timestamps();
            });
        }

        $perms = [
            'view_business_report', 'export_business_report',
            'view_sales_report', 'export_sales_report',
            'view_stock_report', 'export_stock_report',
            'view_low_stock_report', 'export_low_stock_report',
            'view_expiry_stock_report', 'export_expiry_stock_report',
            'financial_reports', 'export_financial_reports',
            'reports_export',
            'all_branches', 'view_all_branches',
        ];
        foreach ($perms as $p) {
            RolePermission::firstOrCreate(['role' => 'manager', 'permission' => $p]);
        }

        // Admin user
        if (!Schema::hasTable('members')) {
            Schema::create('members', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name')->nullable();
                $table->string('phone')->nullable();
                $table->string('email')->nullable();
                $table->unsignedInteger('branch_id')->nullable();
                $table->timestamps();
            });
        }

        $this->admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'role' => 'manager',
            'branch_id' => $this->branch1->id,
            'status' => 'active',
        ]);

        $this->member = User::factory()->create([
            'name' => 'Regular Member',
            'email' => 'member@test.com',
            'role' => 'member',
            'branch_id' => $this->branch1->id,
            'status' => 'active',
        ]);

        // Category
        if (!Schema::hasTable('categories')) {
            Schema::create('categories', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('category_name', 150);
                $table->text('description')->nullable();
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent();
            });
        }
        $this->category = Category::forceCreate(['name' => 'Vegetables']);

        // Supplier
        if (!Schema::hasTable('suppliers')) {
            Schema::create('suppliers', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('member_id')->nullable();
                $table->string('supplier_type')->nullable();
                $table->unsignedInteger('branch_id')->nullable();
                $table->string('name');
                $table->string('contact_name')->nullable();
                $table->string('phone')->nullable();
                $table->string('email')->nullable();
                $table->string('address')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }
        $this->supplier = Supplier::forceCreate([
            'name' => 'Test Supplier',
            'branch_id' => $this->branch1->id,
        ]);

        // Products
        if (!Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->string('sku')->nullable();
                $table->unsignedInteger('category_id')->nullable();
                $table->unsignedInteger('branch_id')->nullable();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
        $this->product1 = Product::forceCreate([
            'name' => 'Tomatoes',
            'sku' => 'TOM-001',
            'category_id' => $this->category->id,
        ]);
        $this->product2 = Product::forceCreate([
            'name' => 'Onions',
            'sku' => 'ONI-001',
            'category_id' => $this->category->id,
        ]);

        // Stock
        if (!Schema::hasTable('stock')) {
            Schema::create('stock', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('supplier_id')->nullable();
                $table->unsignedInteger('product_id')->nullable();
                $table->unsignedInteger('branch_id')->nullable();
                $table->decimal('total_stock', 15, 4)->default(0);
                $table->decimal('quantity', 15, 4)->default(0);
                $table->string('unit_type')->nullable();
                $table->decimal('cost_price', 15, 4)->default(0);
                $table->date('expiry_date')->nullable();
                $table->decimal('selling_price', 15, 4)->default(0);
                $table->timestamp('created_at')->useCurrent();
            });
        }
        Stock::forceCreate([
            'product_id' => $this->product1->id,
            'branch_id' => $this->branch1->id,
            'supplier_id' => $this->supplier->id,
            'total_stock' => 100,
            'quantity' => 98,
            'cost_price' => 500,
            'selling_price' => 800,
            'expiry_date' => now()->addDays(10)->toDateString(),
        ]);
        Stock::forceCreate([
            'product_id' => $this->product2->id,
            'branch_id' => $this->branch1->id,
            'supplier_id' => $this->supplier->id,
            'total_stock' => 5,
            'quantity' => 0,
            'cost_price' => 300,
            'selling_price' => 500,
            'expiry_date' => now()->addDays(5)->toDateString(),
        ]);
        Stock::forceCreate([
            'product_id' => $this->product2->id,
            'branch_id' => $this->branch2->id,
            'supplier_id' => $this->supplier->id,
            'total_stock' => 0,
            'cost_price' => 300,
            'selling_price' => 500,
            'expiry_date' => now()->addDays(20)->toDateString(),
        ]);

        // Customer
        if (!Schema::hasTable('customers')) {
            Schema::create('customers', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('member_id')->nullable();
                $table->string('customer_type')->nullable();
                $table->unsignedInteger('branch_id')->nullable();
                $table->string('name')->nullable();
                $table->string('phone')->nullable();
                $table->string('email')->nullable();
                $table->string('address')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }
        $this->customer = Customer::forceCreate([
            'name' => 'Test Customer',
            'branch_id' => $this->branch1->id,
        ]);

        // Orders
        if (!Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('order_number');
                $table->unsignedInteger('customer_id')->nullable();
                $table->string('customer_name');
                $table->string('phone')->nullable();
                $table->string('email')->nullable();
                $table->string('delivery_location')->nullable();
                $table->unsignedInteger('branch_id')->nullable();
                $table->string('payment_method');
                $table->decimal('total_amount', 15, 4)->default(0);
                $table->integer('orders_count')->default(0);
                $table->string('status')->default('pending');
                $table->text('notes')->nullable();
                $table->boolean('is_guest_order')->default(false);
                $table->unsignedInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
        $this->order1 = Order::forceCreate([
            'order_number' => 'ORD-001',
            'customer_id' => $this->customer->id,
            'customer_name' => 'Test Customer',
            'branch_id' => $this->branch1->id,
            'payment_method' => 'Cash',
            'total_amount' => 1600,
            'status' => 'completed',
            'created_at' => now()->subDays(2),
        ]);
        $this->order2 = Order::forceCreate([
            'order_number' => 'ORD-002',
            'customer_id' => $this->customer->id,
            'customer_name' => 'Test Customer',
            'branch_id' => $this->branch1->id,
            'payment_method' => 'Mobile Money',
            'total_amount' => 2500,
            'status' => 'pending',
            'created_at' => now()->subDays(1),
        ]);

        // Order Items
        if (!Schema::hasTable('order_items')) {
            Schema::create('order_items', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('order_id');
                $table->unsignedInteger('product_id')->nullable();
                $table->string('product_name');
                $table->string('sku')->nullable();
                $table->decimal('quantity', 15, 4)->default(0);
                $table->decimal('price', 15, 4)->default(0);
                $table->decimal('subtotal', 15, 4)->default(0);
            });
        }
        OrderItem::forceCreate([
            'order_id' => $this->order1->id,
            'product_id' => $this->product1->id,
            'product_name' => 'Tomatoes',
            'sku' => 'TOM-001',
            'quantity' => 2,
            'price' => 800,
            'subtotal' => 1600,
        ]);
        OrderItem::forceCreate([
            'order_id' => $this->order2->id,
            'product_id' => $this->product2->id,
            'product_name' => 'Onions',
            'sku' => 'ONI-001',
            'quantity' => 5,
            'price' => 500,
            'subtotal' => 2500,
        ]);

        // Payment Transactions
        if (!Schema::hasTable('payment_transactions')) {
            Schema::create('payment_transactions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('project_id')->nullable();
                $table->unsignedInteger('branch_id')->nullable();
                $table->decimal('amount', 15, 4)->default(0);
                $table->unsignedInteger('payment_method_id')->nullable();
                $table->dateTime('transaction_date')->nullable();
                $table->decimal('total_amount_paid', 15, 4)->nullable();
                $table->string('receipt_number')->nullable();
                $table->string('transaction_number')->nullable();
                $table->string('payer_type')->nullable();
                $table->string('status')->default('PENDING');
                $table->softDeletes();
                $table->timestamps();
            });
        }
        PaymentTransaction::forceCreate([
            'project_id' => 1,
            'branch_id' => $this->branch1->id,
            'amount' => 1600,
            'transaction_date' => now()->subDays(2),
            'total_amount_paid' => 1600,
            'status' => 'APPROVED',
            'created_at' => now()->subDays(2),
        ]);

        // Expenses
        if (!Schema::hasTable('expenses')) {
            Schema::create('expenses', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('title');
                $table->string('category');
                $table->decimal('amount', 15, 4)->default(0);
                $table->date('expense_date');
                $table->string('payment_method');
                $table->unsignedInteger('branch_id')->nullable();
                $table->timestamps();
            });
        }
        Expense::forceCreate([
            'title' => 'Transport',
            'category' => 'Logistics',
            'amount' => 50000,
            'expense_date' => now()->subDays(3)->toDateString(),
            'payment_method' => 'Cash',
            'branch_id' => $this->branch1->id,
        ]);

        // Harvest
        if (!Schema::hasTable('harvests')) {
            Schema::create('harvests', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('member_id')->nullable();
                $table->unsignedInteger('branch_id')->nullable();
                $table->string('status')->default('pending');
                $table->string('approval_stage')->nullable();
                $table->decimal('net_amount', 15, 4)->nullable();
                $table->decimal('amount_harvested', 15, 4)->nullable();
                $table->decimal('equivalent_ugx', 15, 4)->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->date('harvest_date')->nullable();
                $table->timestamps();
            });
        }
        $harvestMember = Member::forceCreate(['name' => 'Harvest Member', 'branch_id' => $this->branch1->id]);
        Harvest::forceCreate([
            'member_id' => $harvestMember->id,
            'branch_id' => $this->branch1->id,
            'status' => 'paid',
            'approval_stage' => 'paid',
            'net_amount' => 100000,
            'paid_at' => now()->subDays(1),
        ]);

        // Project
        if (!Schema::hasTable('projects')) {
            Schema::create('projects', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('project_name');
                $table->unsignedInteger('branch_id')->nullable();
                $table->timestamps();
            });
        }
        $this->project = Project::forceCreate([
            'project_name' => 'Greenhouse Project',
            'branch_id' => $this->branch1->id,
            'created_at' => now()->subDays(5),
        ]);

        Schema::create('contracts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('branch_id')->nullable();
        });
        \DB::table('contracts')->insert(['id' => 1, 'branch_id' => $this->branch1->id]);

        // Contract Item
        if (!Schema::hasTable('contract_items')) {
            Schema::create('contract_items', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('project_id')->nullable();
                $table->unsignedInteger('contract_id')->nullable();
                $table->decimal('purchase_price', 15, 4)->default(0);
                $table->decimal('quantity', 15, 4)->default(0);
                $table->timestamps();
            });
        }
        ContractItem::forceCreate([
            'project_id' => $this->project->id,
            'contract_id' => 1,
            'purchase_price' => 200000,
            'quantity' => 1,
            'created_at' => now()->subDays(4),
        ]);
    }

    protected function actingAdmin()
    {
        $this->actingAs($this->admin);
    }

    protected function actingMember()
    {
        $this->actingAs($this->member);
    }

    public function test_guest_redirected_on_all_reports()
    {
        foreach (['business-report.index', 'sales-reports.index', 'stock-report.index', 'low-stock.index', 'expiry-stock.index', 'financial-reports.index'] as $route) {
            $response = $this->get(route($route));
            $response->assertRedirect(route('login'));
        }
    }

    public function test_admin_can_access_business_report()
    {
        $this->actingAdmin();
        $response = $this->get(route('business-report.index'));
        $response->assertOk()->assertViewIs('reports.business.index');
    }

    public function test_admin_can_access_sales_report()
    {
        $this->actingAdmin();
        $response = $this->get(route('sales-reports.index'));
        $response->assertOk()->assertViewIs('reports.sales.index');
    }

    public function test_admin_can_access_stock_report()
    {
        $this->actingAdmin();
        $response = $this->get(route('stock-report.index'));
        $response->assertOk()->assertViewIs('reports.stock.index');
    }

    public function test_admin_can_access_low_stock_report()
    {
        $this->actingAdmin();
        $response = $this->get(route('low-stock.index'));
        $response->assertOk()->assertViewIs('reports.low-stock.index');
    }

    public function test_admin_can_access_expiry_stock_report()
    {
        $this->actingAdmin();
        $response = $this->get(route('expiry-stock.index'));
        $response->assertOk()->assertViewIs('reports.expiry-stock.index');
    }

    public function test_admin_can_access_financial_report()
    {
        $this->actingAdmin();
        $response = $this->get(route('financial-reports.index'));
        $response->assertOk()->assertViewIs('reports.financial.index');
    }

    public function test_member_cannot_access_reports()
    {
        $this->actingMember();
        foreach (['business-report.index', 'sales-reports.index', 'stock-report.index', 'low-stock.index', 'expiry-stock.index', 'financial-reports.index'] as $route) {
            $response = $this->get(route($route));
            $response->assertForbidden();
        }
    }

    public function test_business_report_filters()
    {
        $this->actingAdmin();
        $response = $this->get(route('business-report.index', ['from' => now()->subDays(7)->toDateString(), 'to' => now()->toDateString(), 'branch' => $this->branch1->id]));
        $response->assertOk();
    }

    public function test_sales_report_filters()
    {
        $this->actingAdmin();
        $response = $this->get(route('sales-reports.index', ['from' => now()->subDays(7)->toDateString(), 'to' => now()->toDateString(), 'branch' => $this->branch1->id]));
        $response->assertOk();
    }

    public function test_stock_report_filters()
    {
        $this->actingAdmin();
        $response = $this->get(route('stock-report.index', ['branch' => $this->branch1->id, 'category' => $this->category->id]));
        $response->assertOk();
    }

    public function test_low_stock_report_filters()
    {
        $this->actingAdmin();
        $response = $this->get(route('low-stock.index', ['branch' => $this->branch1->id, 'threshold' => 10]));
        $response->assertOk();
    }

    public function test_expiry_stock_report_filters()
    {
        $this->actingAdmin();
        $response = $this->get(route('expiry-stock.index', ['branch' => $this->branch1->id, 'days' => 30]));
        $response->assertOk();
    }

    public function test_financial_report_tabs()
    {
        $this->actingAdmin();
        foreach (['overview', 'income', 'projects', 'branches'] as $tab) {
            $response = $this->get(route('financial-reports.index', ['tab' => $tab]));
            $response->assertOk();
        }
    }

    public function test_business_report_export()
    {
        $this->actingAdmin();
        $response = $this->get(route('business-report.export'));
        $response->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->assertDownload('business_report_overview_' . now()->startOfMonth()->toDateString() . '_' . now()->toDateString() . '.csv');
    }

    public function test_sales_report_export()
    {
        $this->actingAdmin();
        $response = $this->get(route('sales-reports.export'));
        $response->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_stock_report_export()
    {
        $this->actingAdmin();
        $response = $this->get(route('stock-report.export'));
        $response->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_low_stock_report_export()
    {
        $this->actingAdmin();
        $response = $this->get(route('low-stock.export'));
        $response->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_expiry_stock_report_export()
    {
        $this->actingAdmin();
        $response = $this->get(route('expiry-stock.export'));
        $response->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_financial_report_export()
    {
        $this->actingAdmin();
        $response = $this->get(route('financial-reports.export'));
        $response->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_business_report_kpis_present()
    {
        $this->actingAdmin();
        $response = $this->get(route('business-report.index'));
        $response->assertSee('Sales');
        $response->assertSee('Revenue Collected');
        $response->assertSee('Pending');
        $response->assertSee('Stock Value');
    }

    public function test_sales_report_daily_trend()
    {
        $this->actingAdmin();
        $response = $this->get(route('sales-reports.index'));
        $response->assertSee('Daily Trend');
        $response->assertSee('Top Products');
    }

    public function test_stock_report_totals()
    {
        $this->actingAdmin();
        $response = $this->get(route('stock-report.index'));
        $response->assertSee('In Stock');
        $response->assertSee('Sold');
        $response->assertSee('Remaining');
    }

    public function test_low_stock_report_alerts()
    {
        $this->actingAdmin();
        $response = $this->get(route('low-stock.index', ['threshold' => 10]));
        $response->assertSee('Out of Stock');
        $response->assertSee('Critical');
        $response->assertSee('Low');
    }

    public function test_expiry_stock_report_alerts()
    {
        $this->actingAdmin();
        $response = $this->get(route('expiry-stock.index', ['days' => 30]));
        $response->assertSee('Expired');
        $response->assertSee('Critical');
        $response->assertSee('Warning');
    }

    public function test_financial_report_overview_net()
    {
        $this->actingAdmin();
        $response = $this->get(route('financial-reports.index', ['tab' => 'overview']));
        $response->assertSee('Net Position');
    }
    public function test_cancelled_checkout_releases_exact_stock_once_and_legacy_order_does_not(): void
    {
        $stock = Stock::where('product_id', $this->product1->id)->first();
        $this->postJson(route('place-order.store'), $this->checkoutPayload([
            ['id' => $this->product1->id, 'quantity' => 3],
        ]))->assertOk();
        $order = Order::latest('id')->first();
        $this->assertSame(95, (int) $stock->fresh()->quantity);
        $this->admin->update(['role' => 'superadmin']);
        $this->actingAdmin();
        $this->patchJson(route('orders.status', $order), ['status' => 'cancelled'])->assertOk();
        $this->assertSame(98, (int) $stock->fresh()->quantity);
        $this->patchJson(route('orders.status', $order), ['status' => 'cancelled'])->assertUnprocessable();
        $this->assertSame(98, (int) $stock->fresh()->quantity);
        $this->order1->update(['status' => 'pending']);
        $this->patchJson(route('orders.status', $this->order1), ['status' => 'cancelled'])->assertOk();
        $this->assertSame(98, (int) $stock->fresh()->quantity);
        $this->assertDatabaseMissing('order_stock_reservations', ['order_id' => $order->id, 'released_at' => null]);
    }

    public function test_checkout_expired_or_unpriced_stock_is_rejected_without_partial_records(): void
    {
        $stock = Stock::where('product_id', $this->product1->id)->first();
        $stock->update(['expiry_date' => now()->subDay()]);
        $payload = $this->checkoutPayload([['id' => $this->product1->id, 'quantity' => 1]]);
        $this->postJson(route('place-order.store'), $payload)->assertUnprocessable();
        $stock->update(['expiry_date' => null, 'selling_price' => 0]);
        $this->postJson(route('place-order.store'), $payload)->assertUnprocessable();
        $this->assertDatabaseCount('orders', 2);
        $this->assertDatabaseCount('customers', 1);
        $this->assertDatabaseCount('order_stock_reservations', 0);
        $this->assertSame(98, (int) $stock->fresh()->quantity);
    }

    public function test_order_numbers_remain_unique_after_an_order_is_deleted(): void
    {
        $payload = $this->checkoutPayload([['id' => $this->product1->id, 'quantity' => 1]]);
        $first = $this->postJson(route('place-order.store'), $payload)->assertOk()->json('order_number');
        $second = $this->postJson(route('place-order.store'), $payload)->assertOk()->json('order_number');
        Order::where('order_number', $first)->delete();
        $third = $this->postJson(route('place-order.store'), $payload)->assertOk()->json('order_number');
        $this->assertNotSame($second, $third);
        $this->assertLessThanOrEqual(50, strlen($third));
    }

    public function test_reserved_stock_cannot_be_deleted_or_reassigned_and_completion_does_not_rededuct(): void
    {
        $stock = Stock::where('product_id', $this->product1->id)->first();
        $this->postJson(route('place-order.store'), $this->checkoutPayload([
            ['id' => $this->product1->id, 'quantity' => 3],
        ]))->assertOk();
        $order = Order::latest('id')->first();
        $this->admin->update(['role' => 'superadmin']);
        $this->actingAdmin();
        $this->deleteJson(route('stock.destroy', $stock))->assertUnprocessable();
        $this->putJson(route('stock.update', $stock), [
            'product_id' => $this->product2->id, 'branch_id' => $this->branch1->id, 'quantity' => 95,
        ])->assertUnprocessable();
        $this->patchJson(route('orders.status', $order), ['status' => 'processing'])->assertOk();
        $this->patchJson(route('orders.status', $order), ['status' => 'completed'])->assertOk();
        $this->assertSame(95, (int) $stock->fresh()->quantity);
        $this->patchJson(route('orders.status', $order), ['status' => 'cancelled'])->assertUnprocessable();
        $this->assertSame(95, (int) $stock->fresh()->quantity);
    }

    public function test_branch_restricted_order_access_and_stock_mutation_are_forbidden(): void
    {
        $this->admin->update(['role' => 'restricted_operator']);
        foreach (['manage_orders', 'stock_edit'] as $permission) {
            RolePermission::create(['role' => 'restricted_operator', 'permission' => $permission]);
        }
        app(\App\Services\PermissionService::class)->clearEffectiveCache();
        $this->actingAdmin();
        $otherOrder = $this->order2;
        $otherOrder->update(['branch_id' => $this->branch2->id, 'status' => 'pending']);
        $this->get(route('orders.show', $otherOrder))->assertForbidden();
        $this->patchJson(route('orders.status', $otherOrder), ['status' => 'cancelled'])->assertForbidden();
        $this->get(route('orders.index'))->assertOk()->assertViewHas('stats', fn ($stats) => $stats['total'] === 1);
        $stock = Stock::where('branch_id', $this->branch2->id)->first();
        $this->putJson(route('stock.update', $stock), [
            'product_id' => $stock->product_id, 'branch_id' => $this->branch1->id, 'quantity' => 1,
        ])->assertForbidden();
        $this->assertSame((int) $this->branch2->id, (int) $stock->fresh()->branch_id);
    }

    public function test_checkout_email_does_not_link_an_unrelated_customer(): void
    {
        $this->customer->update(['email' => 'existing@example.com', 'phone' => 'different-phone']);
        $payload = $this->checkoutPayload([['id' => $this->product1->id, 'quantity' => 1]]);
        $payload['email'] = 'existing@example.com';
        $this->postJson(route('place-order.store'), $payload)->assertOk();
        $this->assertNotSame($this->customer->id, Order::latest('id')->first()->customer_id);
    }

    public function test_unassigned_business_user_cannot_list_or_mutate_zero_branch_records(): void
    {
        $this->admin->update(['role' => 'unassigned_operator']);
        RolePermission::create(['role' => 'unassigned_operator', 'permission' => 'manage_orders']);
        app(\App\Services\PermissionService::class)->clearEffectiveCache();
        $this->order1->update(['branch_id' => 0, 'status' => 'pending']);
        foreach ([null, 0, -1] as $branchId) {
            $this->admin->update(['branch_id' => $branchId]);
            $this->actingAdmin();
            $this->get(route('orders.index'))->assertForbidden();
            $this->patchJson(route('orders.status', $this->order1), ['status' => 'cancelled'])->assertForbidden();
        }
        $this->assertSame('pending', $this->order1->fresh()->status);
    }

    protected function checkoutPayload(array $items): array
    {
        return ['customer_name' => 'Checkout Customer', 'phone' => '+256700123456',
            'delivery_location' => 'Kampala', 'branch_id' => $this->branch1->id,
            'payment_method' => 'cash-on-delivery', 'items' => json_encode($items)];
    }

    public function test_checkout_accepts_formdata_json_cart_and_deducts_stock(): void
    {
        $stock = Stock::where('product_id', $this->product1->id)->first();
        $this->postJson(route('place-order.store'), $this->checkoutPayload([
            ['id' => $this->product1->id, 'quantity' => 3],
        ]))->assertOk()->assertJsonPath('success', true)->assertJsonPath('total', '2400.00');
        $this->assertSame(95, (int) $stock->fresh()->quantity);
        $this->assertDatabaseCount('orders', 3);
    }

    public function test_checkout_rejects_duplicate_products_without_changing_stock(): void
    {
        $this->postJson(route('place-order.store'), $this->checkoutPayload([
            ['id' => $this->product1->id, 'quantity' => 60],
            ['id' => $this->product1->id, 'quantity' => 60],
        ]))->assertUnprocessable()->assertJsonValidationErrors('items.0.id');
        $this->assertSame(98, (int) Stock::where('product_id', $this->product1->id)->first()->quantity);
        $this->assertDatabaseCount('orders', 2);
    }

    public function test_checkout_insufficient_stock_rolls_back_customer_and_order(): void
    {
        $this->postJson(route('place-order.store'), $this->checkoutPayload([
            ['id' => $this->product1->id, 'quantity' => 99],
        ]))->assertUnprocessable();
        $this->assertDatabaseCount('customers', 1);
        $this->assertDatabaseCount('orders', 2);
        $this->assertSame(98, (int) Stock::where('product_id', $this->product1->id)->first()->quantity);
    }

    public function test_checkout_rejects_inactive_branch_and_product_and_malformed_cart(): void
    {
        $payload = $this->checkoutPayload([['id' => $this->product1->id, 'quantity' => 1]]);
        $this->branch1->update(['status' => false]);
        $this->postJson(route('place-order.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors('branch_id');
        $this->branch1->update(['status' => true]);
        $this->product1->update(['is_active' => false]);
        $this->postJson(route('place-order.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors('items.0.id');
        $payload['items'] = '{bad json';
        $this->postJson(route('place-order.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors('items');
    }

    public function test_public_checkout_and_business_modal_pages_render(): void
    {
        $this->get(route('place-order'))->assertOk()->assertSee('orderResultTitle');
        $this->admin->update(['role' => 'superadmin']);
        $this->actingAdmin();
        foreach (['products.index', 'stock.index', 'customers.index', 'suppliers.index', 'branches.index', 'orders.index'] as $route) {
            $this->get(route($route))->assertOk();
        }
    }

    public function test_financial_net_does_not_double_count_project_collections(): void
    {
        $this->actingAdmin();
        $this->get(route('financial-reports.index', ['from' => now()->subDays(10)->toDateString(), 'to' => now()->toDateString()]))
            ->assertOk()->assertViewHas('data', fn ($data) => $data['revenue'] === 1600.0 && $data['projects'] === 1600.0 && $data['net'] === -148400.0);
    }

    public function test_financial_branch_tab_excludes_other_branches(): void
    {
        $this->actingAdmin();
        $this->get(route('financial-reports.index', ['tab' => 'branches', 'branch' => $this->branch1->id]))
            ->assertOk()->assertViewHas('data', fn ($data) => $data['branchesDetail']->count() === 1 && $data['branchesDetail']->first()['name'] === 'Main Branch');
    }

    public function test_stock_reports_use_current_quantity_for_each_branch(): void
    {
        $this->actingAdmin();
        $this->get(route('stock-report.index', ['branch' => $this->branch1->id]))
            ->assertOk()->assertViewHas('data', fn ($data) => $data['totals']['remaining'] === 98);
        $this->get(route('stock-report.index', ['branch' => $this->branch2->id]))
            ->assertOk()->assertViewHas('data', fn ($data) => $data['totals']['remaining'] === 0);
    }

    public function test_expiry_report_includes_expired_and_classifies_future_dates(): void
    {
        Stock::where('product_id', $this->product2->id)->where('branch_id', $this->branch2->id)
            ->update(['expiry_date' => now()->subDay()->toDateString(), 'quantity' => 1]);
        $this->actingAdmin();
        $this->get(route('expiry-stock.index'))->assertOk()->assertViewHas('data', function ($data) {
            return $data['counts'] === ['expired' => 1, 'critical' => 1, 'warning' => 1, 'total' => 3];
        });
    }

    public function test_business_report_all_sections_and_exports_render(): void
    {
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        $this->actingAdmin();
        foreach (\App\Http\Controllers\BusinessReportController::SECTIONS as $section) {
            $this->get(route('business-report.index', ['section' => $section, 'branch' => $this->branch1->id]))->assertOk();
            $response = $this->get(route('business-report.export', ['section' => $section, 'branch' => $this->branch1->id]));
            $response->assertOk();
            $this->assertNotEmpty($response->streamedContent());
        }
        foreach (['income', 'projects', 'branches'] as $tab) {
            $this->get(route('financial-reports.index', ['tab' => $tab, 'branch' => $this->branch1->id]))->assertOk();
            $response = $this->get(route('financial-reports.export', ['tab' => $tab, 'branch' => $this->branch1->id]));
            $response->assertOk();
            $this->assertNotEmpty($response->streamedContent());
        }
    }

    public function test_pos_report_preserves_branch_scope_and_partial_payment_balances(): void
    {
        (require database_path('migrations/2026_10_03_000002_create_sales_module_tables.php'))->up();
        $saleId = \DB::table('sales')->insertGetId([
            'user_id' => $this->admin->id, 'branch_id' => $this->branch1->id,
            'invoice_no' => 'POS-REPORT-ONE', 'total_amount' => 1000, 'balance_amount' => 250,
            'payment_method' => 'cash', 'payment_status' => 'partially paid', 'sale_date' => now()->toDateString(),
        ]);
        \DB::table('sale_items')->insert([
            'sale_id' => $saleId, 'product_id' => $this->product1->id, 'quantity' => 2, 'price' => 500, 'total' => 1000,
        ]);
        \DB::table('sales')->insert([
            'user_id' => $this->admin->id, 'branch_id' => $this->branch2->id,
            'invoice_no' => 'POS-REPORT-TWO', 'total_amount' => 9000, 'balance_amount' => 0,
            'payment_method' => 'cash', 'payment_status' => 'paid', 'sale_date' => now()->toDateString(),
        ]);
        $this->actingAdmin();
        $response = $this->get(route('sales-reports.index', ['source' => 'pos', 'branch' => $this->branch1->id]));
        $response->assertOk()->assertViewHas('source', 'pos');
        $totals = $response->viewData('data')['totals'];
        $this->assertSame(1, $totals['orders']);
        $this->assertSame(1000.0, $totals['revenue']);
        $this->assertSame(750.0, $totals['collected']);
        $this->assertSame(250.0, $totals['pending']);
        $this->assertSame(2, $response->viewData('data')['topProducts']->first()['qty']);
        $response->assertSee(route('sales-reports.export', ['from' => $response->viewData('from'), 'to' => $response->viewData('to'), 'branch' => $this->branch1->id, 'source' => 'pos']));
        $export = $this->get(route('sales-reports.export', ['source' => 'pos', 'branch' => $this->branch1->id]));
        $export->assertOk();
        $lines = preg_split('/\r?\n/', trim($export->streamedContent()));
        $this->assertSame(['1', '1,000.00', '750.00', '250.00'], array_slice(str_getcsv($lines[1]), 1));
    }

    public function test_pos_report_requires_its_schema_and_rejects_invalid_source(): void
    {
        $this->actingAdmin();
        $this->get(route('sales-reports.index', ['source' => 'pos']))->assertStatus(422);
        $this->get(route('sales-reports.index', ['source' => 'invalid']))->assertStatus(422);
    }

    public function test_report_export_buttons_target_download_routes_and_keep_branch_filter(): void
    {
        $this->actingAdmin();
        foreach (['sales-reports', 'stock-report', 'low-stock', 'expiry-stock', 'business-report', 'financial-reports'] as $report) {
            $response = $this->get(route($report.'.index', ['branch' => $this->branch1->id]));
            $response->assertOk()->assertSee('href="'.route($report.'.export'), false);
            preg_match('/href="('.preg_quote(route($report.'.export'), '/').'[^"\s]*)"/', $response->getContent(), $matches);
            parse_str((string) parse_url(html_entity_decode($matches[1]), PHP_URL_QUERY), $filters);
            $this->assertSame((string) $this->branch1->id, $filters['branch'] ?? null, $report);
        }
    }

}
