<?php

namespace Tests\Feature;

use App\Http\Controllers\HarvestDueController;
use App\Http\Controllers\ReceivableController;
use App\Models\Branch;
use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\Member;
use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use App\Models\PaymentTransactionType;
use App\Models\Project;
use App\Models\Receivable;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuditFixesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchema();
    }

    protected function createSchema(): void
    {
        // The production tables already exist in MySQL; the test database is
        // sqlite :memory:, so reproduce the minimal schema the flows touch.
        Schema::dropIfExists('users');
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 150)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('password', 255)->nullable();
            $table->string('role', 50)->default('customer');
            $table->string('status', 20)->default('active');
            $table->timestamp('created_at')->useCurrent();
            $table->unsignedInteger('branch_id')->nullable()->default(1);
            $table->string('profile_pic', 255)->nullable();
            $table->string('verification_code', 6)->nullable();
            $table->dateTime('code_expires')->nullable();
            $table->string('remember_token', 100)->nullable();
        });

        Schema::dropIfExists('role_permissions');
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('role', 50)->nullable();
            $table->string('permission', 150)->nullable();
            $table->timestamps();
        });

        Schema::dropIfExists('projects');
        Schema::create('projects', function (Blueprint $table) {
            $table->increments('id');
            $table->string('project_name', 150)->nullable();
            $table->string('project_code', 50)->nullable();
            $table->timestamps();
        });

        Schema::dropIfExists('members');
        Schema::create('members', function (Blueprint $table) {
            $table->increments('id');
            $table->string('first_name', 100)->nullable();
            $table->string('last_name', 100)->nullable();
            $table->string('other_name', 100)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('phone', 30)->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->string('membership_status', 30)->nullable();
            $table->timestamps();
        });

        Schema::dropIfExists('groups');
        Schema::create('groups', function (Blueprint $table) {
            $table->increments('id');
            $table->string('group_name', 150)->nullable();
            $table->string('group_code', 50)->nullable();
            $table->timestamps();
        });

        Schema::dropIfExists('branches');
        Schema::create('branches', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 150)->nullable();
            $table->timestamps();
        });

        Schema::dropIfExists('contracts');
        Schema::create('contracts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('contract_number', 100)->nullable();
            $table->unsignedInteger('member_id')->nullable();
            $table->unsignedInteger('group_id')->nullable();
            $table->unsignedInteger('project_id')->nullable();
            $table->unsignedInteger('branch_id')->nullable();
            $table->string('project_code', 50)->nullable();
            $table->unsignedInteger('payment_method_id')->nullable();
            $table->string('payment_frequency', 30)->nullable();
            $table->string('status', 30)->default('DRAFT');
            $table->string('workflow_status', 30)->default('draft');
            $table->unsignedTinyInteger('workflow_step')->default(0);
            $table->decimal('contract_amount', 18, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->nullable();
            $table->decimal('total_payable', 15, 2)->default(0);
            $table->decimal('total_paid', 15, 2)->default(0);
            $table->decimal('total_outstanding', 15, 2)->default(0);
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->decimal('outstanding_balance', 10, 2)->default(0);
            $table->decimal('contract_outstanding', 12, 2)->default(0);
            $table->decimal('contract_amount_paid', 15, 2)->default(0);
            $table->date('signing_date')->nullable();
            $table->integer('duration')->default(0);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('contract_for', 10)->default('member');
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::dropIfExists('payment_methods');
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->increments('id');
            $table->string('method_name', 100)->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });

        Schema::dropIfExists('payment_transaction_types');
        Schema::create('payment_transaction_types', function (Blueprint $table) {
            $table->increments('id');
            $table->string('type_name', 100)->nullable();
            $table->string('description', 255)->nullable();
            $table->tinyInteger('is_active')->default(1);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::dropIfExists('payment_transactions');
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('transaction_number', 100)->nullable();
            $table->string('account_or_mobile', 150)->nullable();
            $table->string('payment_type', 50)->nullable();
            $table->string('payer_type', 20)->nullable();
            $table->unsignedInteger('member_id')->nullable();
            $table->unsignedInteger('group_id')->nullable();
            $table->unsignedInteger('contract_id')->nullable();
            $table->unsignedInteger('chart_account_id')->nullable();
            $table->unsignedInteger('project_id')->nullable();
            $table->unsignedInteger('branch_id')->nullable();
            $table->unsignedInteger('transaction_type_id')->nullable();
            $table->dateTime('transaction_date')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('penalties', 15, 2)->default(0);
            $table->decimal('processing_fee', 15, 2)->default(0);
            $table->decimal('other_charges', 15, 2)->default(0);
            $table->decimal('total_fees', 15, 2)->default(0);
            $table->string('deduction_description', 255)->nullable();
            $table->decimal('net_amount', 15, 2)->default(0);
            $table->unsignedInteger('payment_method_id')->nullable();
            $table->decimal('membership_fee', 15, 2)->default(0);
            $table->decimal('membership_fee_paid', 15, 2)->default(0);
            $table->decimal('membership_outstanding', 15, 2)->default(0);
            $table->decimal('registration_fee', 15, 2)->default(0);
            $table->decimal('registration_fee_paid', 15, 2)->default(0);
            $table->decimal('registration_outstanding', 15, 2)->default(0);
            $table->decimal('admin_fee', 15, 2)->default(0);
            $table->decimal('admin_fee_paid', 15, 2)->default(0);
            $table->decimal('admin_fee_outstanding', 15, 2)->default(0);
            $table->decimal('contract_amount', 15, 2)->default(0);
            $table->decimal('contract_amount_paid', 15, 2)->default(0);
            $table->decimal('contract_amount_outstanding', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('total_amount_paid', 15, 2)->default(0);
            $table->decimal('total_amount_outstanding', 15, 2)->default(0);
            $table->string('reference', 150)->nullable();
            $table->string('receipt_number', 100)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('PENDING');
            $table->string('reconciliation_status', 30)->default('UNRECONCILED');
            $table->unsignedInteger('reconciled_by')->nullable();
            $table->dateTime('reconciled_date')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('approved_by')->nullable();
            $table->dateTime('approval_date')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::dropIfExists('contract_items');
        Schema::create('contract_items', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('contract_id')->nullable();
            $table->unsignedInteger('project_id')->nullable();
            $table->string('item_name', 255)->nullable();
            $table->string('item_type', 255)->nullable();
            $table->decimal('quantity', 15, 2)->default(0);
            $table->string('unit_name', 50)->nullable();
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('total_price', 15, 2)->default(0);
            $table->decimal('monthly_return', 15, 2)->default(0);
            $table->decimal('monthly_payout_amount', 15, 2)->default(0);
            $table->decimal('total_hives', 15, 2)->default(0);
            $table->decimal('balance_amount', 15, 2)->default(0);
            $table->decimal('balance_quantity', 15, 2)->default(0);
            $table->decimal('projected_harvest_amount', 15, 2)->default(0);
            $table->decimal('projected_harvest_balance', 15, 2)->default(0);
            $table->decimal('harvest_amount', 15, 2)->default(0);
            $table->decimal('harvest_quantity', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('paid_quantity', 15, 2)->default(0);
            $table->integer('paid_count')->default(0);
            $table->decimal('harvested_amount', 15, 2)->default(0);
            $table->decimal('harvested_quantity', 15, 2)->default(0);
            $table->date('last_harvest_date')->nullable();
            $table->integer('item_order')->default(0);
            $table->timestamps();
        });

        Schema::dropIfExists('harvests');
        Schema::create('harvests', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('branch_id')->nullable();
            $table->unsignedInteger('contract_id')->nullable();
            $table->unsignedInteger('member_id')->nullable();
            $table->unsignedInteger('group_id')->nullable();
            $table->string('owner_type', 20)->nullable();
            $table->string('harvest_type', 50)->nullable();
            $table->date('harvest_date')->nullable();
            $table->integer('periods_due')->default(1);
            $table->decimal('projected_harvest_amount', 15, 2)->default(0);
            $table->decimal('seasonal_harvest_amount', 15, 2)->nullable();
            $table->decimal('seasonal_harvest_quantity', 15, 2)->nullable();
            $table->decimal('amount_harvested', 15, 2)->default(0);
            $table->decimal('quantity_harvested', 15, 2)->default(0);
            $table->integer('number_of_goats_harvested')->nullable();
            $table->decimal('balance_amount', 15, 2)->nullable();
            $table->decimal('balance_quantity', 15, 2)->nullable();
            $table->string('status', 30)->default('Pending');
            $table->string('approval_stage', 30)->default('review');
            $table->string('payment_method', 50)->nullable();
            $table->string('payment_reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->decimal('total_fees', 15, 2)->default(0);
            $table->decimal('net_amount', 15, 2)->default(0);
            $table->string('project_kind', 100)->nullable();
            $table->string('project_name_snap', 255)->nullable();
            $table->unsignedInteger('approved_by')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->string('rejection_reason', 255)->nullable();
            $table->string('rejection_comment', 255)->nullable();
            $table->unsignedInteger('rejected_by')->nullable();
            $table->dateTime('rejected_at')->nullable();
            $table->unsignedInteger('paid_by')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->unsignedInteger('reviewed_by')->nullable();
            $table->string('review_comment', 255)->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->string('approve_comment', 255)->nullable();
            $table->dateTime('approval_date')->nullable();
            $table->timestamps();
        });

        Schema::dropIfExists('contract_item_harvests');
        Schema::create('contract_item_harvests', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('contract_item_id')->nullable();
            $table->unsignedInteger('harvest_id')->nullable();
            $table->unsignedInteger('contract_id')->nullable();
            $table->string('project_kind', 100)->nullable();
            $table->string('harvest_type', 50)->nullable();
            $table->string('harvest_mode', 50)->nullable();
            $table->date('harvest_date')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->integer('periods_due')->default(1);
            $table->decimal('withdrawable_amount', 15, 2)->default(0);
            $table->decimal('withdrawable_quantity', 15, 2)->default(0);
            $table->decimal('projected_harvest_amount', 15, 2)->default(0);
            $table->decimal('projected_harvest_quantity', 15, 2)->default(0);
            $table->decimal('amount_payable', 15, 2)->default(0);
            $table->decimal('gross_entitlement', 15, 2)->default(0);
            $table->decimal('amount_harvested', 15, 2)->default(0);
            $table->decimal('quantity_harvested', 15, 2)->default(0);
            $table->decimal('balance_before_amount', 15, 2)->default(0);
            $table->decimal('balance_after_amount', 15, 2)->default(0);
            $table->decimal('balance_before_quantity', 15, 2)->default(0);
            $table->decimal('balance_after_quantity', 15, 2)->default(0);
            $table->decimal('net_amount', 15, 2)->default(0);
            $table->integer('number_of_goats_harvested')->nullable();
            $table->string('bee_sub_type', 100)->nullable();
            $table->string('goat_contract_mode', 100)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('Pending Review');
            $table->string('approval_stage', 30)->default('review');
            $table->unsignedInteger('payment_transaction_id')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::dropIfExists('contract_terminations');
        Schema::create('contract_terminations', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('contract_id')->nullable();
            $table->date('termination_date')->nullable();
            $table->text('reason')->nullable();
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->decimal('contract_amount', 15, 2)->default(0);
            $table->decimal('project_projection', 15, 2)->default(0);
            $table->decimal('deduction_base', 15, 2)->default(0);
            $table->string('deduction_base_source', 50)->nullable();
            $table->decimal('deduction_rate', 15, 2)->default(0);
            $table->decimal('deduction_amount', 15, 2)->default(0);
            $table->decimal('refund_amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::dropIfExists('receivables');
        Schema::create('receivables', function (Blueprint $table) {
            $table->increments('id');
            $table->string('reference_no', 100)->nullable();
            $table->date('received_date')->nullable();
            $table->unsignedInteger('member_id')->nullable();
            $table->unsignedInteger('group_id')->nullable();
            $table->unsignedInteger('branch_id')->nullable();
            $table->string('payer_name', 150)->nullable();
            $table->string('payer_phone', 30)->nullable();
            $table->string('receiver_email', 150)->nullable();
            $table->string('payer_type', 30)->nullable();
            $table->string('group_name', 180)->nullable();
            $table->string('category', 100)->nullable();
            $table->string('receivable_type', 150)->nullable();
            $table->string('other_type', 150)->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('amount_payable', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('net_amount_payable', 15, 2)->default(0);
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->decimal('outstanding_balance', 15, 2)->default(0);
            $table->string('income_type', 30)->nullable();
            $table->string('payment_method', 50)->nullable();
            $table->string('payment_reference', 150)->nullable();
            $table->string('status', 30)->default('Pending');
            $table->text('description')->nullable();
            $table->date('last_payment_date')->nullable();
            $table->unsignedInteger('payment_transaction_id')->nullable();
            $table->unsignedInteger('chart_account_id')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::dropIfExists('receivable_payments');
        Schema::create('receivable_payments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('receivable_id')->nullable();
            $table->date('payment_date')->nullable();
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->string('payment_method', 50)->nullable();
            $table->string('payment_reference', 150)->nullable();
            $table->string('receipt_number', 100)->nullable();
            $table->string('invoice_number', 100)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::dropIfExists('meetings');
        Schema::create('meetings', function (Blueprint $table) {
            $table->increments('id');
            $table->string('meeting_type', 100)->nullable();
            $table->string('meeting_title', 255)->nullable();
            $table->date('meeting_date')->nullable();
            $table->time('meeting_time')->nullable();
            $table->string('location', 255)->nullable();
            $table->text('agenda')->nullable();
            $table->longText('minutes')->nullable();
            $table->integer('attendance_count')->default(0);
            $table->string('status', 30)->default('Scheduled');
            $table->string('chaired_by', 150)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::dropIfExists('meeting_invites');
        Schema::create('meeting_invites', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('meeting_id');
            $table->unsignedInteger('user_id');
            $table->string('invite_status', 30)->default('Pending');
            $table->dateTime('invited_at')->nullable();
            $table->dateTime('reminder_sent_at')->nullable();
            $table->timestamps();
        });

        Schema::dropIfExists('meeting_attendance');
        Schema::create('meeting_attendance', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('meeting_id');
            $table->unsignedInteger('member_id');
            $table->string('status', 30)->default('Present');
            $table->timestamp('check_in_time')->useCurrent();
            $table->text('notes')->nullable();
            $table->dateTime('attended_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    protected function admin(): User
    {
        return User::create([
            'name' => 'Test Admin',
            'email' => 'admin@audit.test',
            'password' => Hash::make('secret123'),
            'role' => 'admin',
            'status' => 'active',
        ]);
    }

    protected function memberUser(): User
    {
        return User::create([
            'name' => 'Test Member',
            'email' => 'member@audit.test',
            'password' => Hash::make('secret123'),
            'role' => 'member',
            'status' => 'active',
        ]);
    }

    protected function contract(User $admin): Contract
    {
        $project = Project::create(['project_name' => 'Bees', 'project_code' => 'PRJ-001']);
        $member = Member::create(['first_name' => 'Jane', 'last_name' => 'Doe']);
        $branch = Branch::create(['name' => 'HQ']);

        return Contract::create([
            'contract_number' => 'CT-001',
            'member_id' => $member->id,
            'project_id' => $project->id,
            'branch_id' => $branch->id,
            'project_code' => 'PRJ-001',
            'payment_frequency' => 'MONTHLY',
            'signing_date' => '2026-01-01',
            'duration' => 12,
            'contract_amount' => 1000,
            'total_amount' => 1000,
            'total_payable' => 1000,
            'amount_paid' => 0,
            'outstanding_balance' => 1000,
            'status' => 'ACTIVE',
            'workflow_status' => 'approved',
            'contract_for' => 'member',
            'created_by' => $admin->id,
        ]);
    }

    #[Test]
    public function member_role_is_forbidden_from_financial_modules(): void
    {
        $this->actingAs($this->memberUser());

        $this->get(route('payments.index'))->assertForbidden();
        $this->get(route('harvests.index'))->assertForbidden();
        $this->get(route('termination.index'))->assertForbidden();
        $this->get(route('receivables.index'))->assertForbidden();
        $this->get(route('contracts.index'))->assertForbidden();
    }

    #[Test]
    public function contract_payment_is_created_as_pending_without_touching_contract_totals(): void
    {
        $admin = $this->admin();
        $contract = $this->contract($admin);
        $method = PaymentMethod::create(['method_name' => 'Cash', 'status' => 'active']);
        PaymentTransactionType::create(['type_name' => 'Contract Payment', 'is_active' => 1]);

        $this->actingAs($admin);

        $this->post(route('payments.store'), [
            'contract_id' => $contract->id,
            'payment_method_id' => $method->id,
            'amount' => 500,
            'payment_date' => '2026-02-01',
        ])->assertRedirect();

        $payment = PaymentTransaction::first();
        $this->assertNotNull($payment);
        $this->assertSame('PENDING', $payment->status);
        $this->assertNull($payment->approved_by);
        $this->assertNull($payment->approval_date);

        // PENDING payments must not move the contract's paid/outstanding cache.
        $fresh = $contract->fresh();
        $this->assertEquals(0, (float) $fresh->amount_paid);
        $this->assertEquals(1000, (float) $fresh->outstanding_balance);
    }

    #[Test]
    public function contract_payment_over_the_outstanding_balance_is_rejected(): void
    {
        $admin = $this->admin();
        $contract = $this->contract($admin);
        $method = PaymentMethod::create(['method_name' => 'Cash', 'status' => 'active']);

        $this->actingAs($admin);

        $this->post(route('payments.store'), [
            'contract_id' => $contract->id,
            'payment_method_id' => $method->id,
            'amount' => 1500,
            'payment_date' => '2026-02-01',
        ])->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('payment_transactions', 0);
    }

    #[Test]
    public function harvest_state_machine_is_strict_one_way(): void
    {
        $admin = $this->admin();
        $contract = $this->contract($admin);
        $item = ContractItem::create([
            'contract_id' => $contract->id,
            'item_name' => 'Bees',
            'balance_amount' => 1000,
            'projected_harvest_balance' => 1000,
            'projected_harvest_amount' => 1000,
        ]);

        $this->actingAs($admin);

        $this->post(route('harvests.store'), [
            'contract_id' => $contract->id,
            'contract_item_id' => $item->id,
            'harvest_type' => 'Cash',
            'harvest_date' => '2026-03-01',
            'amount_harvested' => 400,
        ])->assertRedirect();

        $harvest = \App\Models\Harvest::first();
        $this->assertNotNull($harvest);
        $this->assertSame('review', $harvest->approval_stage);

        // review -> approve -> pay
        $this->post(route('harvests.review', $harvest))->assertRedirect();
        $this->assertSame('reviewed', $harvest->fresh()->approval_stage);

        $this->post(route('harvests.approve', $harvest))->assertRedirect();
        $this->assertSame('approved', $harvest->fresh()->approval_stage);

        // no re-review / re-approve after approval
        $this->post(route('harvests.review', $harvest))->assertSessionHasErrors('harvest');
        $this->post(route('harvests.approve', $harvest))->assertSessionHasErrors('harvest');

        $this->post(route('harvests.pay', $harvest), ['payment_method' => 'Cash'])->assertRedirect();
        $this->assertSame('paid', $harvest->fresh()->approval_stage);

        // no re-review / re-approve after paid
        $this->post(route('harvests.review', $harvest))->assertSessionHasErrors('harvest');
        $this->post(route('harvests.approve', $harvest))->assertSessionHasErrors('harvest');
    }

    #[Test]
    public function harvest_pay_is_rejected_until_approved(): void
    {
        $admin = $this->admin();
        $contract = $this->contract($admin);
        $item = ContractItem::create([
            'contract_id' => $contract->id,
            'item_name' => 'Bees',
            'balance_amount' => 1000,
            'projected_harvest_balance' => 1000,
        ]);

        $this->actingAs($admin);

        $this->post(route('harvests.store'), [
            'contract_id' => $contract->id,
            'contract_item_id' => $item->id,
            'harvest_type' => 'Cash',
            'harvest_date' => '2026-03-01',
            'amount_harvested' => 400,
        ])->assertRedirect();

        $harvest = \App\Models\Harvest::first();

        $this->post(route('harvests.pay', $harvest), ['payment_method' => 'Cash'])
            ->assertSessionHasErrors('harvest');
    }

    #[Test]
    public function harvest_amount_cannot_exceed_item_balance(): void
    {
        $admin = $this->admin();
        $contract = $this->contract($admin);
        $item = ContractItem::create([
            'contract_id' => $contract->id,
            'item_name' => 'Bees',
            'balance_amount' => 1000,
            'projected_harvest_balance' => 1000,
        ]);

        $this->actingAs($admin);

        $this->post(route('harvests.store'), [
            'contract_id' => $contract->id,
            'contract_item_id' => $item->id,
            'harvest_type' => 'Cash',
            'harvest_date' => '2026-03-01',
            'amount_harvested' => 1500,
        ])->assertSessionHasErrors('amount_harvested');

        $this->assertDatabaseCount('harvests', 0);
    }

    #[Test]
    public function termination_enforces_caps_active_only_and_no_duplicates(): void
    {
        $admin = $this->admin();
        $contract = $this->contract($admin);

        $this->actingAs($admin);

        // deduction_amount > deduction_base
        $this->post(route('termination.store'), [
            'contract_id' => $contract->id,
            'termination_date' => '2026-04-01',
            'reason' => 'Test',
            'deduction_base' => 1000,
            'deduction_amount' => 1200,
            'refund_amount' => 100,
        ])->assertSessionHasErrors('deduction_amount');

        // refund_amount > deduction_base - deduction_amount
        $this->post(route('termination.store'), [
            'contract_id' => $contract->id,
            'termination_date' => '2026-04-01',
            'reason' => 'Test',
            'deduction_base' => 1000,
            'deduction_amount' => 400,
            'refund_amount' => 700,
        ])->assertSessionHasErrors('refund_amount');

        // valid termination
        $this->post(route('termination.store'), [
            'contract_id' => $contract->id,
            'termination_date' => '2026-04-01',
            'reason' => 'Test',
            'deduction_base' => 1000,
            'deduction_amount' => 400,
            'refund_amount' => 600,
        ])->assertRedirect();

        $this->assertDatabaseHas('contract_terminations', ['contract_id' => $contract->id]);

        // duplicate termination rejected
        $this->post(route('termination.store'), [
            'contract_id' => $contract->id,
            'termination_date' => '2026-04-02',
            'reason' => 'Again',
            'deduction_base' => 1000,
            'deduction_amount' => 400,
            'refund_amount' => 600,
        ])->assertSessionHasErrors('contract_id');

        // non-ACTIVE contract rejected
        $draft = Contract::create([
            'contract_number' => 'CT-002',
            'project_id' => 1,
            'project_code' => 'PRJ-001',
            'payment_frequency' => 'MONTHLY',
            'signing_date' => '2026-01-01',
            'duration' => 12,
            'contract_amount' => 500,
            'total_amount' => 500,
            'status' => 'DRAFT',
            'workflow_status' => 'draft',
            'contract_for' => 'member',
            'created_by' => $admin->id,
        ]);

        $this->post(route('termination.store'), [
            'contract_id' => $draft->id,
            'termination_date' => '2026-04-01',
            'reason' => 'Test',
            'deduction_base' => 500,
            'deduction_amount' => 200,
            'refund_amount' => 300,
        ])->assertSessionHasErrors('contract_id');
    }

    #[Test]
    public function receivable_pay_is_rejected_when_cancelled_or_fully_paid(): void
    {
        $admin = $this->admin();
        $receivable = Receivable::create([
            'reference_no' => 'RCV-001',
            'received_date' => '2026-01-01',
            'payer_name' => 'Test Payer',
            'payer_type' => 'Non-Member',
            'category' => 'Services',
            'receivable_type' => 'Consulting',
            'amount_payable' => 500,
            'discount' => 0,
            'net_amount_payable' => 500,
            'amount_paid' => 0,
            'outstanding_balance' => 500,
            'income_type' => 'Income',
            'payment_method' => 'Cash',
            'status' => 'Pending',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin);

        // Payment fields are not fillable; set them explicitly so the test
        // actually exercises the cancelled / fully-paid guards (previously
        // the updates were silently dropped and the test passed for the wrong
        // reason — outstanding_balance stayed at its DB default of 0).
        $receivable->forceFill([
            'amount_paid' => 0,
            'outstanding_balance' => 500,
            'payment_method' => 'Cash',
            'status' => 'Pending',
        ])->save();

        // cancelled -> rejected
        $receivable->forceFill(['status' => 'Cancelled'])->save();
        $this->post(route('receivables.pay', $receivable), [
            'payment_date' => '2026-02-01',
            'amount_paid' => 100,
            'payment_method' => 'Cash',
        ])->assertSessionHasErrors('amount_paid');

        // fully paid -> rejected
        $receivable->forceFill(['status' => 'Pending', 'amount_paid' => 500, 'outstanding_balance' => 0])->save();
        $this->post(route('receivables.pay', $receivable), [
            'payment_date' => '2026-02-01',
            'amount_paid' => 100,
            'payment_method' => 'Cash',
        ])->assertSessionHasErrors('amount_paid');

        $this->assertDatabaseCount('receivable_payments', 0);
    }

    #[Test]
    public function payment_self_approval_is_rejected(): void
    {
        $admin = $this->admin();
        $contract = $this->contract($admin);
        $method = PaymentMethod::create(['method_name' => 'Cash', 'status' => 'active']);
        PaymentTransactionType::create(['type_name' => 'Contract Payment', 'is_active' => 1]);

        $this->actingAs($admin);

        $this->post(route('payments.store'), [
            'contract_id' => $contract->id,
            'payment_method_id' => $method->id,
            'amount' => 500,
            'payment_date' => '2026-02-01',
        ])->assertRedirect();

        $payment = PaymentTransaction::first();

        // The user who recorded the payment cannot approve or reject it.
        $this->post(route('payments.approve', $payment))->assertSessionHasErrors('payment');
        $this->post(route('payments.reject', $payment), ['notes' => 'No'])->assertSessionHasErrors('payment');

        // A different user can approve it.
        $admin2 = User::create([
            'name' => 'Test Admin 2',
            'email' => 'admin2@audit.test',
            'password' => Hash::make('secret123'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->actingAs($admin2);
        $this->post(route('payments.approve', $payment))->assertRedirect();

        $fresh = $payment->fresh();
        $this->assertSame('APPROVED', $fresh->status);
        $this->assertSame($admin2->id, $fresh->approved_by);
    }

    #[Test]
    public function termination_ignores_inflated_amount_paid(): void
    {
        $admin = $this->admin();
        $contract = $this->contract($admin);
        $contract->update(['amount_paid' => 500]);

        $this->actingAs($admin);

        // amount_paid / deduction_base are derived server-side from the
        // contract; inflated posted values must be ignored.
        $this->post(route('termination.store'), [
            'contract_id' => $contract->id,
            'termination_date' => '2026-04-01',
            'reason' => 'Test',
            'amount_paid' => 999999,
            'deduction_base' => 999999,
            'deduction_amount' => 100,
            'refund_amount' => 400,
        ])->assertRedirect();

        $termination = \App\Models\ContractTermination::first();
        $this->assertNotNull($termination);
        $this->assertEquals(500, (float) $termination->amount_paid);
        $this->assertEquals(500, (float) $termination->deduction_base);
        $this->assertEquals(100, (float) $termination->deduction_amount);
        $this->assertEquals(400, (float) $termination->refund_amount);
    }

    #[Test]
    public function harvest_over_commitment_is_capped(): void
    {
        $admin = $this->admin();
        $contract = $this->contract($admin);
        $item = ContractItem::create([
            'contract_id' => $contract->id,
            'item_name' => 'Bees',
            'balance_amount' => 1000,
            'projected_harvest_balance' => 1000,
        ]);

        $this->actingAs($admin);

        $this->post(route('harvests.store'), [
            'contract_id' => $contract->id,
            'contract_item_id' => $item->id,
            'harvest_type' => 'Cash',
            'harvest_date' => '2026-03-01',
            'amount_harvested' => 400,
        ])->assertRedirect();

        // 400 is already committed; 700 would exceed the 1000 balance.
        $this->post(route('harvests.store'), [
            'contract_id' => $contract->id,
            'contract_item_id' => $item->id,
            'harvest_type' => 'Cash',
            'harvest_date' => '2026-03-02',
            'amount_harvested' => 700,
        ])->assertSessionHasErrors('amount_harvested');

        // 600 fits exactly (400 + 600 = 1000).
        $this->post(route('harvests.store'), [
            'contract_id' => $contract->id,
            'contract_item_id' => $item->id,
            'harvest_type' => 'Cash',
            'harvest_date' => '2026-03-03',
            'amount_harvested' => 600,
        ])->assertRedirect();

        $this->assertDatabaseCount('harvests', 2);
    }

    #[Test]
    public function workflow_fields_persist_after_fillable_tightening(): void
    {
        $admin = $this->admin();
        $contract = $this->contract($admin);
        $method = PaymentMethod::create(['method_name' => 'Cash', 'status' => 'active']);
        PaymentTransactionType::create(['type_name' => 'Contract Payment', 'is_active' => 1]);

        $this->actingAs($admin);

        $this->post(route('payments.store'), [
            'contract_id' => $contract->id,
            'payment_method_id' => $method->id,
            'amount' => 500,
            'payment_date' => '2026-02-01',
        ])->assertRedirect();

        $payment = PaymentTransaction::first();

        $admin2 = User::create([
            'name' => 'Test Admin 2',
            'email' => 'admin2@audit.test',
            'password' => Hash::make('secret123'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->actingAs($admin2);
        $this->post(route('payments.approve', $payment))->assertRedirect();

        $fresh = $payment->fresh();
        $this->assertSame('APPROVED', $fresh->status);
        $this->assertSame($admin2->id, $fresh->approved_by);
        $this->assertNotNull($fresh->approval_date);

        // Harvest workflow fields persist through review -> approve.
        $item = ContractItem::create([
            'contract_id' => $contract->id,
            'item_name' => 'Bees',
            'balance_amount' => 1000,
            'projected_harvest_balance' => 1000,
        ]);

        $this->post(route('harvests.store'), [
            'contract_id' => $contract->id,
            'contract_item_id' => $item->id,
            'harvest_type' => 'Cash',
            'harvest_date' => '2026-03-01',
            'amount_harvested' => 400,
        ])->assertRedirect();

        $harvest = \App\Models\Harvest::first();
        $this->assertSame('review', $harvest->approval_stage);

        $this->post(route('harvests.review', $harvest))->assertRedirect();
        $this->assertSame('reviewed', $harvest->fresh()->approval_stage);
        $this->assertSame($admin2->id, $harvest->fresh()->reviewed_by);

        $this->post(route('harvests.approve', $harvest))->assertRedirect();
        $this->assertSame('approved', $harvest->fresh()->approval_stage);
        $this->assertSame($admin2->id, $harvest->fresh()->approved_by);
    }

    #[Test]
    public function receivable_update_does_not_500_and_preserves_payment_fields(): void
    {
        $admin = $this->admin();
        $receivable = Receivable::create([
            'reference_no' => 'RCV-002',
            'received_date' => '2026-01-01',
            'payer_name' => 'Test Payer',
            'payer_type' => 'Non-Member',
            'category' => 'Services',
            'receivable_type' => 'Consulting',
            'amount_payable' => 500,
            'discount' => 0,
            'net_amount_payable' => 500,
            'income_type' => 'Income',
            'created_by' => $admin->id,
        ]);
        // Payment fields are not fillable; set them explicitly to simulate a
        // receivable that has already received money.
        $receivable->forceFill([
            'amount_paid' => 200,
            'outstanding_balance' => 300,
            'payment_method' => 'Cash',
            'payment_reference' => 'REF-ORIGINAL',
            'status' => 'Pending',
        ])->save();

        $this->actingAs($admin);

        // Regression: update() used to 500 because payload() read
        // $data['payment_method'] after update() unset it.
        $this->put(route('receivables.update', $receivable), [
            'received_date' => '2026-01-01',
            'payer_type' => 'Non-Member',
            'payer_name' => 'Test Payer',
            'category' => 'Services',
            'receivable_type' => 'Consulting',
            'amount_payable' => 600,
            'discount' => 0,
            'amount_paid' => 999,
            'payment_method' => 'Bank',
            'payment_reference' => 'REF-HACKED',
            'status' => 'Cancelled',
        ])->assertRedirect();

        $fresh = $receivable->fresh();
        // Money already received is preserved; outstanding recomputed from the
        // new payable figure.
        $this->assertEquals(200, (float) $fresh->amount_paid);
        $this->assertEquals(400, (float) $fresh->outstanding_balance);
        // Payment fields cannot be changed through update().
        $this->assertSame('Cash', $fresh->payment_method);
        $this->assertSame('REF-ORIGINAL', $fresh->payment_reference);
        // Status is derived from payment math, not user input.
        $this->assertSame('Pending', $fresh->status);
    }

    #[Test]
    public function receivable_store_links_payment_transaction(): void
    {
        $admin = $this->admin();
        PaymentMethod::create(['method_name' => 'Cash', 'status' => 'active']);

        $this->actingAs($admin);

        $this->post(route('receivables.store'), [
            'received_date' => '2026-01-01',
            'payer_type' => 'Non-Member',
            'payer_name' => 'Test Payer',
            'category' => 'Services',
            'receivable_type' => 'Consulting',
            'amount_payable' => 500,
            'discount' => 0,
            'amount_paid' => 200,
            'payment_method' => 'Cash',
        ])->assertRedirect();

        $receivable = Receivable::first();
        $this->assertNotNull($receivable);
        // Regression: payment_transaction_id was silently dropped by update()
        // because it is not fillable.
        $this->assertNotNull($receivable->payment_transaction_id);
        $this->assertDatabaseCount('payment_transactions', 1);
        $this->assertDatabaseCount('receivable_payments', 1);
    }

    #[Test]
    public function harvest_pay_persists_item_paid_tracking(): void
    {
        $admin = $this->admin();
        $contract = $this->contract($admin);
        $item = ContractItem::create([
            'contract_id' => $contract->id,
            'item_name' => 'Bees',
            'balance_amount' => 1000,
            'projected_harvest_balance' => 1000,
        ]);

        $this->actingAs($admin);

        $this->post(route('harvests.store'), [
            'contract_id' => $contract->id,
            'contract_item_id' => $item->id,
            'harvest_type' => 'Cash',
            'harvest_date' => '2026-03-01',
            'amount_harvested' => 400,
        ])->assertRedirect();

        $harvest = \App\Models\Harvest::first();
        $this->post(route('harvests.review', $harvest))->assertRedirect();
        $this->post(route('harvests.approve', $harvest))->assertRedirect();
        $this->post(route('harvests.pay', $harvest), ['payment_method' => 'Cash'])->assertRedirect();

        $fresh = $item->fresh();
        // Regression: applyItemBalance() used update() and these fields are
        // not fillable, so paid tracking was silently dropped.
        $this->assertEquals(1, (int) $fresh->paid_count);
        $this->assertEquals(400, (float) $fresh->paid_amount);
        $this->assertStringStartsWith('2026-03-01', (string) $fresh->last_harvest_date);
        $this->assertEquals(600, (float) $fresh->balance_amount);
    }

    #[Test]
    public function receivable_pay_and_harvest_due_record_are_throttled(): void
    {
        $admin = $this->admin();
        $receivable = Receivable::create([
            'reference_no' => 'RCV-003',
            'received_date' => '2026-01-01',
            'payer_name' => 'Test Payer',
            'payer_type' => 'Non-Member',
            'category' => 'Services',
            'receivable_type' => 'Consulting',
            'amount_payable' => 500,
            'net_amount_payable' => 500,
            'income_type' => 'Income',
            'created_by' => $admin->id,
        ]);
        $contract = $this->contract($admin);
        ContractItem::create([
            'contract_id' => $contract->id,
            'item_name' => 'Bees',
            'balance_amount' => 1000,
            'projected_harvest_balance' => 1000,
        ]);

        // Route-level throttle (web.php).
        $payRoute = app('router')->getRoutes()->getByName('receivables.pay');
        $this->assertNotNull($payRoute);
        $this->assertContains('throttle:30,1', $payRoute->gatherMiddleware());

        $recordRoute = app('router')->getRoutes()->getByName('harvest-due.record');
        $this->assertNotNull($recordRoute);
        $this->assertContains('throttle:30,1', $recordRoute->gatherMiddleware());

        // Controller-level throttle (defense in depth, matches batch pattern).
        $receivableThrottle = collect(ReceivableController::middleware())
            ->first(fn ($m) => $m instanceof Middleware && $m->middleware === 'throttle:30,1');
        $this->assertNotNull($receivableThrottle);
        $this->assertContains('pay', $receivableThrottle->only);

        $harvestDueThrottle = collect(HarvestDueController::middleware())
            ->first(fn ($m) => $m instanceof Middleware && $m->middleware === 'throttle:30,1');
        $this->assertNotNull($harvestDueThrottle);
        $this->assertContains('record', $harvestDueThrottle->only);
    }

    #[Test]
    public function receivable_store_derives_status_from_payment_math(): void
    {
        $admin = $this->admin();
        PaymentMethod::create(['method_name' => 'Cash', 'status' => 'active']);

        $this->actingAs($admin);

        // User-supplied 'Cancelled' must be ignored; status derives from math.
        $this->post(route('receivables.store'), [
            'received_date' => '2026-01-01',
            'payer_type' => 'Non-Member',
            'payer_name' => 'Test Payer',
            'category' => 'Services',
            'receivable_type' => 'Consulting',
            'amount_payable' => 500,
            'discount' => 0,
            'amount_paid' => 0,
            'payment_method' => 'Cash',
            'status' => 'Cancelled',
        ])->assertRedirect();

        $this->assertSame('Pending', Receivable::first()->status);

        // Fully paid -> Received.
        $this->post(route('receivables.store'), [
            'received_date' => '2026-01-02',
            'payer_type' => 'Non-Member',
            'payer_name' => 'Test Payer 2',
            'category' => 'Services',
            'receivable_type' => 'Consulting',
            'amount_payable' => 300,
            'discount' => 0,
            'amount_paid' => 300,
            'payment_method' => 'Cash',
        ])->assertRedirect();

        $this->assertSame('Received', Receivable::orderByDesc('id')->first()->status);
    }

    #[Test]
    public function payment_with_null_creator_cannot_be_approved(): void
    {
        $admin = $this->admin();
        $payment = PaymentTransaction::forceCreate([
            'transaction_number' => 'TXN-NULL-1',
            'payment_type' => 'contract_payment',
            'transaction_date' => now(),
            'amount' => 100,
            'status' => 'PENDING',
            'reconciliation_status' => 'UNRECONCILED',
            'created_by' => null,
        ]);

        $this->actingAs($admin);

        $this->post(route('payments.approve', $payment))->assertSessionHasErrors('payment');
        $this->post(route('payments.reject', $payment), ['notes' => 'No'])->assertSessionHasErrors('payment');
        $this->assertSame('PENDING', $payment->fresh()->status);
    }

    #[Test]
    public function contract_payment_type_lookup_is_idempotent(): void
    {
        $admin = $this->admin();
        $contract = $this->contract($admin);
        $method = PaymentMethod::create(['method_name' => 'Cash', 'status' => 'active']);
        // No 'Contract Payment' type seeded — the helper must create it once.

        $this->actingAs($admin);

        $this->post(route('payments.store'), [
            'contract_id' => $contract->id,
            'payment_method_id' => $method->id,
            'amount' => 500,
            'payment_date' => '2026-02-01',
        ])->assertRedirect();

        $this->post(route('payments.store'), [
            'contract_id' => $contract->id,
            'payment_method_id' => $method->id,
            'amount' => 500,
            'payment_date' => '2026-02-02',
        ])->assertRedirect();

        $this->assertDatabaseCount('payment_transaction_types', 1);
        $this->assertDatabaseHas('payment_transaction_types', ['type_name' => 'Contract Payment']);
    }

    #[Test]
    public function receivable_update_preserves_amount_received(): void
    {
        $admin = $this->admin();
        PaymentMethod::create(['method_name' => 'Cash', 'status' => 'active']);

        $this->actingAs($admin);

        // Create with an initial payment: amount = amount_paid = 200.
        $this->post(route('receivables.store'), [
            'received_date' => '2026-01-01',
            'payer_type' => 'Non-Member',
            'payer_name' => 'Test Payer',
            'category' => 'Services',
            'receivable_type' => 'Consulting',
            'amount_payable' => 500,
            'discount' => 0,
            'amount_paid' => 200,
            'payment_method' => 'Cash',
        ])->assertRedirect();

        $receivable = Receivable::first();
        $this->assertEquals(200, (float) $receivable->amount);
        $this->assertNotNull($receivable->payment_transaction_id);

        // Update payable/discount/description — `amount` must not be zeroed.
        // payment_method is required by the request but update() unsets it.
        $this->put(route('receivables.update', $receivable), [
            'received_date' => '2026-01-01',
            'payer_type' => 'Non-Member',
            'payer_name' => 'Test Payer',
            'category' => 'Services',
            'receivable_type' => 'Consulting',
            'amount_payable' => 600,
            'discount' => 50,
            'payment_method' => 'Cash',
            'description' => 'Updated description',
        ])->assertRedirect();

        $fresh = $receivable->fresh();
        // Regression: update() used to reset amount to 0 because payload()
        // derives it from amount_paid, which update() unsets.
        $this->assertEquals(200, (float) $fresh->amount);
        $this->assertEquals(200, (float) $fresh->amount_paid);
        $this->assertEquals(350, (float) $fresh->outstanding_balance); // 600 - 50 - 200
        $this->assertNotNull($fresh->payment_transaction_id);
        $this->assertSame('Updated description', $fresh->description);
    }

    #[Test]
    public function meeting_invites_and_attendance_persist(): void
    {
        $admin = $this->admin();
        $member = Member::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'user_id' => $admin->id,
        ]);
        // No user account -> cannot be invited under the schema (user_id NOT NULL).
        $memberWithoutUser = Member::create([
            'first_name' => 'John',
            'last_name' => 'Smith',
            'user_id' => null,
        ]);

        $meeting = \App\Models\Meeting::forceCreate([
            'meeting_type' => 'General',
            'meeting_title' => 'Test Meeting',
            'meeting_date' => '2026-01-01',
            'status' => 'Scheduled',
            'created_by' => $admin->id,
        ]);

        $service = app(\App\Services\MeetingService::class);

        // Invites: member with a user account is invited; member without one
        // is skipped (meeting_invites.user_id is NOT NULL).
        $service->saveInvites($meeting->id, [$member->id, $memberWithoutUser->id]);

        $this->assertDatabaseCount('meeting_invites', 1);
        $this->assertDatabaseHas('meeting_invites', [
            'meeting_id' => $meeting->id,
            'user_id' => $admin->id,
            'invite_status' => 'Pending',
        ]);

        // Attendance: attended members get status Present.
        $service->saveAttendance($meeting->id, [$member->id]);

        $this->assertDatabaseHas('meeting_attendance', [
            'meeting_id' => $meeting->id,
            'member_id' => $member->id,
            'status' => 'Present',
        ]);

        // Removing the member from the list marks them Absent.
        $service->saveAttendance($meeting->id, []);

        $this->assertDatabaseHas('meeting_attendance', [
            'meeting_id' => $meeting->id,
            'member_id' => $member->id,
            'status' => 'Absent',
        ]);
    }

    #[Test]
    public function meeting_invites_and_attendance_persist_via_ajax(): void
    {
        $admin = $this->admin();
        $member = Member::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'user_id' => $admin->id,
        ]);

        $meeting = \App\Models\Meeting::forceCreate([
            'meeting_type' => 'General',
            'meeting_title' => 'Ajax Meeting',
            'meeting_date' => '2026-01-02',
            'status' => 'Scheduled',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin);

        $this->post(route('meetings.ajax.save_invites'), [
            'meeting_id' => $meeting->id,
            'member_ids' => [$member->id],
        ])->assertJson(['success' => true]);

        $this->assertDatabaseHas('meeting_invites', [
            'meeting_id' => $meeting->id,
            'user_id' => $admin->id,
            'invite_status' => 'Pending',
        ]);

        $this->post(route('meetings.ajax.save_attendance'), [
            'meeting_id' => $meeting->id,
            'attendees' => [$member->id],
        ])->assertJson(['success' => true]);

        $this->assertDatabaseHas('meeting_attendance', [
            'meeting_id' => $meeting->id,
            'member_id' => $member->id,
            'status' => 'Present',
        ]);

        // get_invites resolves the member id through the user account.
        $this->get(route('meetings.ajax.get_invites', ['id' => $meeting->id]))
            ->assertJsonPath('invites.0.member_id', $member->id)
            ->assertJsonPath('invites.0.invited', true);

        $this->get(route('meetings.ajax.get_attendance', ['id' => $meeting->id]))
            ->assertJsonPath('attendance.0.member_id', $member->id)
            ->assertJsonPath('attendance.0.attended', true);
    }
}