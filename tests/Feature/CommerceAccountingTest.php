<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\User;
use App\Services\AccountingReportService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class CommerceAccountingTest extends TestCase
{
    private User $admin;
    private Branch $branch;
    private Branch $otherBranch;
    private Product $product;
    private Stock $stock;

    protected function setUp(): void
    {
        parent::setUp();
        (require database_path('migrations/2026_09_12_000200_create_wave2_tables.php'))->up();
        (require database_path('migrations/2026_09_12_000500_create_expenses_table.php'))->up();
        (require database_path('migrations/2026_10_03_000002_create_sales_module_tables.php'))->up();
        Schema::create('branches', function (Blueprint $t) { $t->increments('id'); $t->string('name'); $t->boolean('status')->default(1); $t->timestamps(); });
        Schema::create('users', function (Blueprint $t) { $t->increments('id'); $t->string('name'); $t->string('email'); $t->string('password'); $t->string('role'); $t->string('status'); $t->integer('branch_id')->nullable(); $t->timestamp('created_at')->nullable(); });
        Schema::create('role_permissions', function (Blueprint $t) { $t->increments('id'); $t->string('role'); $t->string('permission'); $t->timestamps(); });
        Schema::create('payment_methods', function (Blueprint $t) { $t->increments('id'); $t->string('method_name'); $t->integer('chart_account_id'); $t->string('status'); });
        Schema::create('payment_transaction_types', function (Blueprint $t) { $t->increments('id'); $t->string('type_name'); $t->boolean('is_active'); $t->timestamp('created_at'); });
        Schema::create('payment_transactions', function (Blueprint $t) {
            $t->increments('id'); $t->integer('branch_id'); $t->integer('customer_id')->nullable(); $t->integer('chart_account_id');
            $t->integer('transaction_type_id'); $t->string('transaction_number'); $t->string('receipt_number'); $t->string('reference');
            $t->decimal('amount', 15, 2); $t->decimal('net_amount', 15, 2); $t->decimal('total_amount', 15, 2);
            $t->decimal('total_amount_paid', 15, 2); $t->decimal('total_amount_outstanding', 15, 2);
            $t->integer('payment_method_id'); $t->string('status'); $t->dateTime('transaction_date');
            $t->string('reconciliation_status'); $t->integer('created_by'); $t->integer('approved_by'); $t->dateTime('approval_date'); $t->timestamps();
        });
        Schema::create('chart_of_accounts', function (Blueprint $t) { $t->increments('id'); $t->string('account_code'); $t->string('account_name'); $t->string('account_type'); $t->boolean('is_active')->default(1); });
        Schema::create('journal_entries', function (Blueprint $t) { $t->increments('id'); $t->string('reference_no'); $t->string('description'); $t->date('transaction_date'); $t->integer('created_by'); $t->timestamp('created_at'); });
        Schema::create('ledger_entries', function (Blueprint $t) { $t->increments('id'); $t->integer('journal_id'); $t->integer('account_id'); $t->decimal('debit', 15, 2); $t->decimal('credit', 15, 2); $t->timestamp('created_at'); });
        (require database_path('migrations/2026_10_03_000003_add_branch_to_legacy_journals.php'))->up();
        Schema::create('members', function (Blueprint $t) { $t->increments('id'); $t->integer('branch_id'); $t->string('first_name'); $t->string('last_name'); $t->string('telephone1')->nullable(); });
        Schema::create('groups', function (Blueprint $t) { $t->increments('id'); $t->integer('branch_id'); $t->string('group_name'); $t->string('telephone')->nullable(); });
        $this->branch = Branch::create(['name' => 'Main', 'status' => true]);
        $this->otherBranch = Branch::create(['name' => 'Other', 'status' => true]);
        $this->admin = User::create(['name' => 'Operator', 'email' => 'operator@example.com', 'password' => bcrypt('password'), 'role' => 'supper_admin', 'status' => 'active', 'branch_id' => $this->branch->id]);
        $this->product = Product::create(['name' => 'Honey', 'sku' => 'HONEY', 'is_active' => true]);
        $this->stock = Stock::create(['product_id' => $this->product->id, 'branch_id' => $this->branch->id, 'quantity' => 10, 'total_stock' => 10, 'selling_price' => 10.35, 'cost_price' => 5.10]);
        DB::table('chart_of_accounts')->insert([
            ['id' => 1, 'account_code' => '1000', 'account_name' => 'Cash', 'account_type' => 'Asset', 'is_active' => 1],
            ['id' => 2, 'account_code' => '4000', 'account_name' => 'Income', 'account_type' => 'Income', 'is_active' => 1],
            ['id' => 3, 'account_code' => '5000', 'account_name' => 'Expense', 'account_type' => 'Expense', 'is_active' => 1],
        ]);
        DB::table('payment_methods')->insert(['id' => 1, 'method_name' => 'Cash', 'chart_account_id' => 1, 'status' => 'active']);
        $this->actingAs($this->admin);
    }

    private function payload(array $overrides = []): array
    {
        return array_replace(['request_token' => (string) Str::uuid(), 'branch_id' => $this->branch->id,
            'payment_method_id' => 1, 'payment_status' => 'paid', 'discount' => '0.10',
            'items' => [['id' => $this->product->id, 'quantity' => 3]]], $overrides);
    }

    public function test_paid_sale_uses_server_price_records_cost_and_exactly_once_receipt(): void
    {
        $payload = $this->payload();
        $payload['items'][0]['price'] = 0;
        $this->postJson(route('sales.store'), $payload)->assertOk()->assertJsonPath('total', '30.95')->assertJsonPath('balance_amount', '0.00');
        $this->postJson(route('sales.store'), $payload)->assertOk();
        $this->assertSame(7, (int) $this->stock->fresh()->quantity);
        $this->assertDatabaseCount('sales', 1); $this->assertDatabaseCount('sale_items', 1); $this->assertDatabaseCount('payment_transactions', 1);
        $this->assertDatabaseHas('sale_items', ['price' => 10.35, 'cost_price' => 5.10, 'total' => 31.05]);
        $this->assertDatabaseHas('payment_transactions', ['status' => 'APPROVED', 'total_amount_paid' => 30.95, 'reference' => Sale::first()->invoice_no]);
        $this->get(route('sales.show', Sale::first()))->assertOk()->assertSee('30.95');
        $this->get(route('sales.index'))->assertOk()->assertSee('Honey');
    }

    public function test_credit_and_partial_payment_require_customer_and_exact_amounts(): void
    {
        $this->postJson(route('sales.store'), $this->payload(['payment_status' => 'oncredit']))->assertUnprocessable()->assertJsonValidationErrors('customer_id');
        $customer = Customer::create(['branch_id' => $this->branch->id, 'name' => 'Customer']);
        $this->postJson(route('sales.store'), $this->payload(['payment_status' => 'partially paid', 'customer_id' => $customer->id, 'amount_paid' => '10.01']))->assertOk()->assertJsonPath('balance_amount', '20.94');
        $this->postJson(route('sales.store'), $this->payload(['payment_status' => 'oncredit', 'customer_id' => $customer->id]))->assertOk()->assertJsonPath('balance_amount', '30.95');
        $this->assertDatabaseCount('payment_transactions', 1);
        $this->postJson(route('sales.store'), $this->payload(['payment_status' => 'partially paid', 'customer_id' => $customer->id, 'amount_paid' => 50]))->assertUnprocessable()->assertJsonValidationErrors('amount_paid');
        $this->assertDatabaseCount('sales', 2);
    }

    public function test_expired_insufficient_and_unlinked_account_fail_atomically(): void
    {
        $this->postJson(route('sales.store'), $this->payload(['items' => [['id' => $this->product->id, 'quantity' => 11]]]))->assertUnprocessable();
        $this->stock->update(['expiry_date' => today()->subDay()]);
        $this->postJson(route('sales.store'), $this->payload())->assertUnprocessable();
        $this->stock->update(['expiry_date' => null]);
        DB::table('payment_methods')->where('id', 1)->update(['chart_account_id' => 999]);
        $this->postJson(route('sales.store'), $this->payload())->assertUnprocessable();
        $this->assertSame(10, (int) $this->stock->fresh()->quantity);
        $this->assertDatabaseCount('sales', 0); $this->assertDatabaseCount('sale_items', 0); $this->assertDatabaseCount('pos_sale_requests', 0);
    }

    public function test_submission_replay_is_bound_to_operator_branch_and_payload(): void
    {
        $payload = $this->payload(); $this->postJson(route('sales.store'), $payload)->assertOk();
        $payload['discount'] = 1; $this->postJson(route('sales.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors('request_token');
        $other = User::create(['name' => 'Other', 'email' => 'other@example.com', 'password' => bcrypt('password'), 'role' => 'supper_admin', 'status' => 'active', 'branch_id' => $this->branch->id]);
        $this->actingAs($other)->postJson(route('sales.store'), $payload)->assertForbidden();
        $this->assertDatabaseCount('sales', 1); $this->assertSame(7, (int) $this->stock->fresh()->quantity);
    }

    public function test_members_and_groups_resolve_to_branch_customer_records(): void
    {
        DB::table('members')->insert(['id' => 1, 'branch_id' => $this->branch->id, 'first_name' => 'Jane', 'last_name' => 'Member']);
        DB::table('groups')->insert(['id' => 1, 'branch_id' => $this->branch->id, 'group_name' => 'Bee Group']);
        foreach (['member_1', 'group_1'] as $payer) {
            $this->postJson(route('sales.store'), $this->payload(['customer_id' => $payer, 'payment_status' => 'oncredit']))->assertOk();
        }
        $this->assertDatabaseHas('customers', ['name' => 'Jane Member', 'customer_type' => 'member']);
        $this->assertDatabaseHas('customers', ['name' => 'Bee Group', 'customer_type' => 'group']);
        $this->getJson(route('sales.products', ['branch_id' => $this->branch->id]))->assertOk()->assertJsonFragment(['id' => 'member_1']);
    }

    private function entry(int $branch, string $date, int $debit, int $credit, float $amount): void
    {
        $journal = DB::table('journal_entries')->insertGetId(['reference_no' => (string) Str::uuid(), 'description' => 'Fixture', 'transaction_date' => $date, 'created_by' => $this->admin->id, 'created_at' => now(), 'branch_id' => $branch]);
        DB::table('ledger_entries')->insert([
            ['journal_id' => $journal, 'account_id' => $debit, 'debit' => $amount, 'credit' => 0, 'created_at' => now()],
            ['journal_id' => $journal, 'account_id' => $credit, 'debit' => 0, 'credit' => $amount, 'created_at' => now()],
        ]);
    }

    public function test_accounting_statements_include_opening_earnings_and_exclude_other_branches(): void
    {
        $this->entry($this->branch->id, '2026-09-01', 1, 2, 100);
        $this->entry($this->branch->id, '2026-10-02', 3, 1, 30);
        $this->entry($this->otherBranch->id, '2026-10-02', 1, 2, 999);
        $service = app(AccountingReportService::class);
        $trial = $service->statement('trial', '2026-10-01', '2026-10-03', $this->branch->id);
        $this->assertSame(100.0, $trial['totals']['Debit balances']); $this->assertSame(0.0, $trial['totals']['Difference']);
        $balance = $service->statement('balance', '2026-10-01', '2026-10-03', $this->branch->id);
        $this->assertSame(70.0, $balance['totals']['Assets']); $this->assertSame(70.0, $balance['totals']['Equity']); $this->assertSame(0.0, $balance['totals']['Difference']);
        $cash = $service->statement('cashflow', '2026-10-01', '2026-10-03', $this->branch->id);
        $this->assertSame(100.0, $cash['totals']['Opening cash']); $this->assertSame(70.0, $cash['totals']['Closing cash']);
        $this->assertSame(30.0, $cash['totals']['Outflows']); $this->assertSame(0.0, $cash['totals']['Inflows']);
        foreach (['ledger', 'trial', 'balance', 'cashflow', 'ledger-income'] as $tab) {
            $this->get(route('financial-reports.index', ['tab' => $tab, 'from' => '2026-10-01', 'to' => '2026-10-03', 'branch' => $this->branch->id]))->assertOk();
            $this->get(route('financial-reports.export', ['tab' => $tab, 'from' => '2026-10-01', 'to' => '2026-10-03', 'branch' => $this->branch->id]))->assertOk();
        }
    }

    public function test_accounting_branch_scope_is_unavailable_without_branch_attribution(): void
    {
        Schema::table('journal_entries', fn (Blueprint $t) => $t->dropIndex('journal_entries_branch_id_index'));
        Schema::table('journal_entries', fn (Blueprint $t) => $t->dropColumn('branch_id'));
        $statement = app(AccountingReportService::class)->statement('trial', '2026-10-01', '2026-10-03', $this->branch->id);
        $this->assertFalse($statement['available']); $this->assertStringContainsString('cannot attribute', $statement['message']);
    }

    public function test_expense_posts_balanced_legacy_ledger_and_is_visible_in_reports(): void
    {
        $this->postJson(route('expenses.store'), ['title' => 'Transport', 'amount' => '30.15', 'expense_date' => '2026-10-02', 'branch_id' => $this->branch->id, 'category' => 'Transportation', 'payment_method' => 'Cash'])->assertCreated();
        $this->assertDatabaseCount('ledger_entries', 2); $this->assertDatabaseCount('journal_entries', 1);
        $this->assertDatabaseHas('ledger_entries', ['debit' => 30.15, 'credit' => 0]);
        $statement = app(AccountingReportService::class)->statement('ledger-income', '2026-10-01', '2026-10-03', $this->branch->id);
        $this->assertSame(30.15, $statement['totals']['Expenses']);
        $expense = Expense::first();
        $this->deleteJson(route('expenses.destroy', $expense))->assertUnprocessable();
        $this->assertDatabaseCount('ledger_entries', 2);
    }
}
