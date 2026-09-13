<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ExpenseModuleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Users (production shape, from AuthFlowTest).
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

        // Branches (seeded so expenses/harvests can reference them).
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
            \DB::table('branches')->insert([
                ['name' => 'Kampala', 'location' => 'Kampala', 'status' => 1, 'created_at' => now()],
                ['name' => 'Jinja', 'location' => 'Jinja', 'status' => 1, 'created_at' => now()],
            ]);
        }

        // role_permissions (needed by PermissionService).
        if (!Schema::hasTable('role_permissions')) {
            Schema::create('role_permissions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('role');
                $table->string('permission');
                $table->timestamps();
            });
        }

        // Expenses table (matches the expenses migration).
        if (!Schema::hasTable('expenses')) {
            Schema::create('expenses', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->string('title');
                $table->decimal('amount', 15, 2)->default(0);
                $table->date('expense_date')->index();
                $table->string('category', 100)->nullable()->index();
                $table->string('payment_method', 50)->nullable();
                $table->text('description')->nullable();
                $table->string('debit_account_code', 20)->nullable();
                $table->string('credit_account_code', 20)->nullable();
                $table->unsignedBigInteger('journal_id')->nullable()->index();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->unsignedBigInteger('updated_by')->nullable()->index();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->nullable();
            });
        }

        // Minimal harvests table (paid harvest rows are expense liabilities).
        if (!Schema::hasTable('harvests')) {
            Schema::create('harvests', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->unsignedBigInteger('contract_id')->nullable()->index();
                $table->unsignedBigInteger('member_id')->nullable()->index();
                $table->unsignedBigInteger('group_id')->nullable()->index();
                $table->string('owner_type', 20)->nullable();
                $table->string('harvest_type', 100)->nullable();
                $table->date('harvest_date')->nullable();
                $table->decimal('amount_harvested', 15, 2)->default(0);
                $table->decimal('equivalent_ugx', 15, 2)->nullable();
                $table->decimal('net_amount', 15, 2)->nullable();
                $table->decimal('total_fees', 15, 2)->default(0);
                $table->decimal('maintenance_fee', 15, 2)->default(0);
                $table->decimal('balance_amount', 15, 2)->default(0);
                $table->string('harvest_mode', 50)->nullable();
                $table->string('bee_sub_type', 100)->nullable();
                $table->string('project_kind', 100)->nullable();
                $table->string('project_name_snap', 150)->nullable();
                $table->string('status', 30)->nullable()->index();
                $table->string('approval_stage', 30)->nullable();
                $table->dateTime('paid_at')->nullable();
                $table->dateTime('approved_at')->nullable();
                $table->string('payment_method', 50)->nullable();
                $table->string('payment_reference', 100)->nullable();
                $table->text('notes')->nullable();
                $table->string('debit_account_code', 20)->nullable();
                $table->string('credit_account_code', 20)->nullable();
                $table->unsignedBigInteger('journal_id')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->nullable();
            });
        }

        // Loose relations needed by the harvest eager-loads in index/stats.
        if (!Schema::hasTable('members')) {
            Schema::create('members', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->timestamps();
            });
        }
        if (!Schema::hasTable('groups')) {
            Schema::create('groups', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('group_name');
                $table->timestamps();
            });
        }
        if (!Schema::hasTable('contracts')) {
            Schema::create('contracts', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('contract_number')->nullable();
                $table->string('status', 30)->nullable()->index();
                $table->timestamps();
            });
        }
    }

    /* ---------------------------------------------------------------
       Helpers
    --------------------------------------------------------------- */

    protected function createAdmin(): User
    {
        return User::create([
            'name' => 'Test Admin',
            'email' => 'admin_' . uniqid() . '@test.local',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'active',
        ]);
    }

    protected function createMemberUser(): User
    {
        return User::create([
            'name' => 'Test Member',
            'email' => 'member_' . uniqid() . '@test.local',
            'password' => Hash::make('password'),
            'role' => 'member',
            'status' => 'active',
        ]);
    }

    protected function branchId(): int
    {
        return (int) \DB::table('branches')->first()->id;
    }

    protected function expenseData(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Office stationery ' . uniqid(),
            'amount' => 2500,
            'expense_date' => now()->toDateString(),
            'branch_id' => $this->branchId(),
            'category' => 'Supplies',
            'payment_method' => 'Cash',
            'description' => 'Pens and paper',
        ], $overrides);
    }

    protected function createExpense(array $overrides = []): Expense
    {
        $data = $this->expenseData($overrides);

        return Expense::create([
            'title' => $data['title'],
            'amount' => $data['amount'],
            'expense_date' => $data['expense_date'],
            'branch_id' => $data['branch_id'],
            'category' => $data['category'],
            'payment_method' => $data['payment_method'],
            'description' => $data['description'] ?? null,
            'created_by' => 1,
        ]);
    }

    protected function createHarvest(array $overrides = []): int
    {
        $branchId = $this->branchId();
        $memberId = \DB::table('members')->insertGetId([
            'first_name' => 'Grace',
            'last_name' => 'Akello',
            'created_at' => now(),
        ]);
        $contractId = \DB::table('contracts')->insertGetId([
            'contract_number' => 'CTR-' . uniqid(),
            'status' => 'Active',
            'created_at' => now(),
        ]);

        return \DB::table('harvests')->insertGetId(array_merge([
            'branch_id' => $branchId,
            'contract_id' => $contractId,
            'member_id' => $memberId,
            'owner_type' => 'member',
            'harvest_type' => 'goat',
            'harvest_date' => '2026-08-15',
            'amount_harvested' => 10000,
            'equivalent_ugx' => 10000,
            'net_amount' => 9000,
            'total_fees' => 1000,
            'maintenance_fee' => 500,
            'status' => 'paid',
            'approval_stage' => 'paid',
            'paid_at' => '2026-08-16 10:00:00',
            'payment_method' => 'Bank',
            'payment_reference' => 'REF-1234',
            'created_at' => now(),
        ], $overrides));
    }

    protected function csvContent(array $rows, array $headers = ['title', 'amount', 'expense_date', 'category', 'payment_method', 'description']): string
    {
        $lines = [implode(',', $headers)];
        foreach ($rows as $row) {
            $fields = [];
            foreach ($headers as $h) {
                $fields[] = (string) ($row[$h] ?? '');
            }
            $lines[] = implode(',', $fields);
        }

        return implode("\r\n", $lines);
    }

    /* ---------------------------------------------------------------
       Auth guards
    --------------------------------------------------------------- */

    public function test_guest_is_redirected_to_login_for_expenses_index(): void
    {
        $this->get(route('expenses.index'))->assertRedirect(route('login'));
    }

    public function test_admin_can_view_expenses_index(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)->get(route('expenses.index'))->assertOk();
    }

    public function test_member_role_gets_403_on_expenses_index(): void
    {
        $member = $this->createMemberUser();

        $this->actingAs($member)->get(route('expenses.index'))->assertStatus(403);
    }

    /* ---------------------------------------------------------------
       CRUD
    --------------------------------------------------------------- */

    public function test_admin_can_store_expense(): void
    {
        $admin = $this->createAdmin();
        $data = $this->expenseData();

        $this->actingAs($admin)->postJson(route('expenses.store'), $data)
            ->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('expenses', [
            'title' => $data['title'],
            'category' => 'Supplies',
            'amount' => '2500.00',
        ]);

        $expense = Expense::where('title', $data['title'])->first();
        $this->assertEquals($admin->id, $expense->created_by);
        $this->assertEquals('5000', $expense->debit_account_code);
        $this->assertEquals('1000', $expense->credit_account_code);
    }

    public function test_store_expense_validates_required_fields(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)->postJson(route('expenses.store'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'amount', 'expense_date', 'branch_id', 'category', 'payment_method']);
    }

    public function test_store_expense_with_custom_category_resolves_it(): void
    {
        $admin = $this->createAdmin();
        $data = $this->expenseData([
            'category' => 'Other',
            'custom_category' => 'Rider Welfare',
        ]);

        $this->actingAs($admin)->postJson(route('expenses.store'), $data)
            ->assertStatus(201);

        $this->assertDatabaseHas('expenses', ['title' => $data['title'], 'category' => 'Rider Welfare']);
    }

    public function test_admin_can_update_expense(): void
    {
        $admin = $this->createAdmin();
        $expense = $this->createExpense();
        $data = $this->expenseData(['title' => 'Updated cleaning supplies', 'amount' => 1500]);

        $this->actingAs($admin)->putJson(route('expenses.update', $expense), $data)
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'title' => 'Updated cleaning supplies',
            'amount' => '1500.00',
        ]);
    }

    public function test_update_expense_validates_required_fields(): void
    {
        $admin = $this->createAdmin();
        $expense = $this->createExpense();

        $this->actingAs($admin)->putJson(route('expenses.update', $expense), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'amount', 'expense_date', 'branch_id', 'category', 'payment_method']);
    }

    public function test_admin_can_delete_expense(): void
    {
        $admin = $this->createAdmin();
        $expense = $this->createExpense();

        $this->actingAs($admin)->deleteJson(route('expenses.destroy', $expense))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
    }

    public function test_member_role_gets_403_for_expense_writes(): void
    {
        $member = $this->createMemberUser();
        $expense = $this->createExpense();
        $data = $this->expenseData();

        $this->actingAs($member)->postJson(route('expenses.store'), $data)->assertStatus(403);
        $this->actingAs($member)->putJson(route('expenses.update', $expense), $data)->assertStatus(403);
        $this->actingAs($member)->deleteJson(route('expenses.destroy', $expense))->assertStatus(403);
        $this->actingAs($member)->postJson(route('expenses.import'))->assertStatus(403);
    }

    /* ---------------------------------------------------------------
       Listing (manual + paid harvest union)
    --------------------------------------------------------------- */

    public function test_index_shows_manual_and_paid_harvest_rows(): void
    {
        $admin = $this->createAdmin();
        $this->createExpense(['title' => 'Manual Supplies Expense X']);
        $this->createHarvest();

        $this->actingAs($admin)->get(route('expenses.index'))
            ->assertOk()
            ->assertSee('Manual Supplies Expense X')
            ->assertSee('Grace Akello')
            ->assertSee('REF-1234');
    }

    public function test_index_excludes_unpaid_harvest_rows(): void
    {
        $admin = $this->createAdmin();
        $this->createHarvest(['status' => 'pending', 'approval_stage' => 'pending']);

        $this->actingAs($admin)->get(route('expenses.index'))
            ->assertOk()
            ->assertDontSee('Grace Akello')
            ->assertDontSee('REF-1234');
    }

    public function test_index_includes_harvest_paid_via_approval_stage(): void
    {
        $admin = $this->createAdmin();
        $this->createHarvest(['status' => 'awaiting_payment', 'approval_stage' => 'paid']);

        $this->actingAs($admin)->get(route('expenses.index'))
            ->assertOk()
            ->assertSee('Grace Akello');
    }

    public function test_index_filters_by_source(): void
    {
        $admin = $this->createAdmin();
        $this->createExpense(['title' => 'Manual Only Title Y']);
        $this->createHarvest();

        $this->actingAs($admin)->get(route('expenses.index', ['source' => 'manual']))
            ->assertOk()
            ->assertSee('Manual Only Title Y')
            ->assertDontSee('Grace Akello');

        $this->actingAs($admin)->get(route('expenses.index', ['source' => 'harvest']))
            ->assertOk()
            ->assertSee('Grace Akello')
            ->assertDontSee('Manual Only Title Y');
    }

    public function test_index_filters_by_category(): void
    {
        $admin = $this->createAdmin();
        $this->createExpense(['title' => 'CatA Expense', 'category' => 'Supplies']);
        $this->createExpense(['title' => 'CatB Expense', 'category' => 'Transportation']);

        $this->actingAs($admin)->get(route('expenses.index', ['category' => 'Transportation']))
            ->assertOk()
            ->assertSee('CatB Expense')
            ->assertDontSee('CatA Expense');
    }

    public function test_index_filters_by_payment_method(): void
    {
        $admin = $this->createAdmin();
        $this->createExpense(['title' => 'Cash Expense', 'payment_method' => 'Cash']);
        $this->createExpense(['title' => 'Bank Expense', 'payment_method' => 'Bank']);

        $this->actingAs($admin)->get(route('expenses.index', ['payment_method' => 'Bank']))
            ->assertOk()
            ->assertSee('Bank Expense')
            ->assertDontSee('Cash Expense');
    }

    public function test_index_filters_by_search(): void
    {
        $admin = $this->createAdmin();
        $this->createExpense(['title' => 'Findable Needle Expense']);
        $this->createExpense(['title' => 'Unrelated Haystack Expense']);

        $this->actingAs($admin)->get(route('expenses.index', ['search' => 'Needle']))
            ->assertOk()
            ->assertSee('Findable Needle Expense')
            ->assertDontSee('Unrelated Haystack Expense');
    }

    public function test_index_filters_by_branch(): void
    {
        $admin = $this->createAdmin();
        $jinja = (int) \DB::table('branches')->where('name', 'Jinja')->value('id');
        $kampala = (int) \DB::table('branches')->where('name', 'Kampala')->value('id');

        $this->createExpense(['title' => 'Kampala Branch Expense', 'branch_id' => $kampala]);
        $this->createExpense(['title' => 'Jinja Branch Expense', 'branch_id' => $jinja]);

        $this->actingAs($admin)->get(route('expenses.index', ['branch' => $jinja]))
            ->assertOk()
            ->assertSee('Jinja Branch Expense')
            ->assertDontSee('Kampala Branch Expense');
    }

    public function test_index_filters_by_date_range(): void
    {
        $admin = $this->createAdmin();
        $this->createExpense(['title' => 'January Ledger Item', 'expense_date' => '2026-01-10']);
        $this->createExpense(['title' => 'August Ledger Item', 'expense_date' => '2026-08-20']);

        $this->actingAs($admin)->get(route('expenses.index', ['date_from' => '2026-01-01', 'date_to' => '2026-06-30']))
            ->assertOk()
            ->assertSee('January Ledger Item')
            ->assertDontSee('August Ledger Item');
    }

    public function test_index_shows_kpi_cards(): void
    {
        $admin = $this->createAdmin();
        $this->createExpense(['title' => 'KPI Expense', 'amount' => 2500]);
        $this->createHarvest();

        $this->actingAs($admin)->get(route('expenses.index'))
            ->assertOk()
            ->assertSee('UGX 2,500')
            ->assertSee('UGX 9,000');
    }

    /* ---------------------------------------------------------------
       CSV export / template / bulk import
    --------------------------------------------------------------- */

    public function test_admin_can_download_template(): void
    {
        $admin = $this->createAdmin();
        $response = $this->actingAs($admin)->get(route('expenses.template'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $this->assertStringContainsString('title,amount,expense_date,', $response->streamedContent());
    }

    public function test_export_csv_contains_header_and_rows(): void
    {
        $admin = $this->createAdmin();
        $this->createExpense(['title' => 'Exportable Expense Z', 'amount' => 4200]);

        $response = $this->actingAs($admin)->get(route('expenses.export'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $this->assertStringStartsWith('attachment; filename=expenses_', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('Gross Amount', $response->streamedContent());
        $this->assertStringContainsString('Exportable Expense Z', $response->streamedContent());
    }

    public function test_export_respects_branch_filter(): void
    {
        $admin = $this->createAdmin();
        $jinja = (int) \DB::table('branches')->where('name', 'Jinja')->value('id');
        $this->createExpense(['title' => 'Jinja Export Only', 'branch_id' => $jinja]);
        $this->createExpense(['title' => 'Other Branch Export Row']);

        $response = $this->actingAs($admin)->get(route('expenses.export', ['branch' => $jinja]))
            ->assertOk();

        $this->assertStringContainsString('Jinja Export Only', $response->streamedContent());
        $this->assertStringNotContainsString('Other Branch Export Row', $response->streamedContent());
    }

    public function test_export_sanitizes_formula_injection(): void
    {
        $admin = $this->createAdmin();
        $this->createExpense(['title' => '=HYPERLINK("http://evil.com")', 'amount' => 100]);

        $response = $this->actingAs($admin)->get(route('expenses.export'))->assertOk();

        $this->assertStringContainsString("'=HYPERLINK", $response->streamedContent());
    }

    public function test_bulk_import_imports_valid_rows(): void
    {
        $admin = $this->createAdmin();
        $headers = ['title', 'amount', 'expense_date', 'category', 'payment_method', 'description'];

        $csv = $this->csvContent([
            ['title' => 'Imported One', 'amount' => '5000', 'expense_date' => '2026-09-01', 'category' => 'Utilities', 'payment_method' => 'Cash', 'description' => 'Water bill'],
            ['title' => 'Imported Two', 'amount' => '8500', 'expense_date' => '2026-09-02', 'category' => 'Salaries', 'payment_method' => 'Bank', 'description' => ''],
        ], $headers);

        $file = UploadedFile::fake()->createWithContent('import.csv', $csv);

        $this->actingAs($admin)->postJson(route('expenses.import'), [
            'csv_file' => $file,
            'branch_id' => $this->branchId(),
        ])
            ->assertOk()
            ->assertJson(['success' => true, 'imported' => 2, 'skipped' => 0]);

        $this->assertDatabaseHas('expenses', ['title' => 'Imported One', 'amount' => '5000.00']);
        $this->assertDatabaseHas('expenses', ['title' => 'Imported Two', 'amount' => '8500.00']);
    }

    public function test_bulk_import_skips_invalid_rows(): void
    {
        $admin = $this->createAdmin();
        $headers = ['title', 'amount', 'expense_date'];

        $csv = $this->csvContent([
            ['title' => 'Good Row', 'amount' => '3000', 'expense_date' => '2026-09-01'],
            ['title' => '', 'amount' => '3000', 'expense_date' => '2026-09-01'],
            ['title' => 'Bad Date Row', 'amount' => '3000', 'expense_date' => 'not-a-date'],
            ['title' => 'Bad Amount Row', 'amount' => 'abc', 'expense_date' => '2026-09-01'],
        ], $headers);

        $file = UploadedFile::fake()->createWithContent('import.csv', $csv);

        $this->actingAs($admin)->postJson(route('expenses.import'), [
            'csv_file' => $file,
            'branch_id' => $this->branchId(),
        ])
            ->assertOk()
            ->assertJson(['success' => true, 'imported' => 1, 'skipped' => 3]);

        $this->assertDatabaseHas('expenses', ['title' => 'Good Row']);
        $this->assertDatabaseMissing('expenses', ['title' => 'Bad Date Row']);
    }

    public function test_bulk_import_requires_columns(): void
    {
        $admin = $this->createAdmin();
        $csv = "title,category\nRead,Supplies\n";
        $file = UploadedFile::fake()->createWithContent('import.csv', $csv);

        $this->actingAs($admin)->postJson(route('expenses.import'), [
            'csv_file' => $file,
        ])
            ->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_bulk_import_rejects_non_csv_file(): void
    {
        $admin = $this->createAdmin();
        $file = UploadedFile::fake()->createWithContent('import.xlsx', 'x');

        $this->actingAs($admin)->postJson(route('expenses.import'), [
            'csv_file' => $file,
        ])
            ->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_bulk_import_requires_file(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)->postJson(route('expenses.import'), [])
            ->assertStatus(422);
    }
}