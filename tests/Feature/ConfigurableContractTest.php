<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Project;
use App\Models\User;
use App\Services\ContractService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ConfigurableContractTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('users', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('email'); $table->string('password');
            $table->string('role'); $table->string('status'); $table->timestamps();
        });
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id(); $table->string('role'); $table->string('permission'); $table->timestamps();
        });
        foreach (['members', 'groups', 'branches', 'payment_methods'] as $name) {
            Schema::create($name, fn (Blueprint $table) => $table->id());
        }
        DB::table('members')->insert(['id' => 1]); DB::table('branches')->insert(['id' => 1]);
        Schema::create('projects', function (Blueprint $table) {
            $table->id(); $table->string('project_name'); $table->string('project_code');
            $table->decimal('registration_fee')->default(100); $table->decimal('administrative_fee')->default(50); $table->timestamps();
        });
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            foreach (['member_id', 'group_id', 'project_id', 'branch_id', 'payment_method_id', 'created_by', 'updated_by', 'duration', 'workflow_step'] as $key) $table->unsignedInteger($key)->nullable();
            foreach (['contract_number', 'contract_for', 'project_code', 'payment_frequency', 'status', 'workflow_status', 'template_path', 'signing_date', 'start_date', 'end_date'] as $key) $table->string($key)->nullable();
            foreach (['contract_amount', 'total_amount', 'total_payable', 'total_paid', 'total_outstanding', 'amount_paid', 'outstanding_balance', 'contract_outstanding', 'contract_amount_paid', 'monthly_payout_amount', 'projected_harvest_amount', 'expected_quantity'] as $key) $table->decimal($key, 15, 2)->nullable();
            $table->timestamps();
        });
        Schema::create('contract_items', function (Blueprint $table) { $table->id(); $table->unsignedInteger('contract_id'); });
        foreach (['contract_item_harvests', 'contract_harvests'] as $name) {
            Schema::create($name, function (Blueprint $table) { $table->id(); $table->unsignedInteger('contract_id'); });
        }
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id(); $table->unsignedInteger('contract_id'); $table->string('status');
            $table->decimal('contract_amount_paid', 15, 2); $table->decimal('amount', 15, 2);
            $table->softDeletes();
        });
        $migration = require database_path('migrations/2026_10_15_000001_add_configurable_project_calculations.php');
        $migration->up();
        $service = \Mockery::mock(ContractService::class)->makePartial();
        $service->shouldReceive('nextContractNumber')->andReturn('CT-TEST');
        $this->app->instance(ContractService::class, $service);
        $this->actingAs(User::create(['name' => 'Admin', 'email' => 'admin@test.local', 'password' => 'password', 'role' => 'admin', 'status' => 'active']));
    }

    private function project(): Project
    {
        return Project::create(['project_name' => 'Custom fish farming', 'project_code' => 'FISH', 'registration_fee' => 100,
            'administrative_fee' => 50, 'calculation_schema' => [
                'inputs' => [['key' => 'ponds', 'label' => 'Ponds', 'type' => 'number', 'required' => true, 'min' => 1]],
                'formulas' => [['key' => 'cost', 'label' => 'Cost', 'expression' => 'ponds * 500 + registration_fee + administrative_fee', 'target' => 'total_payable']],
            ]]);
    }

    private function data(Project $project): array
    {
        return ['contract_for' => 'member', 'member_id' => 1, 'branch_id' => 1, 'project_id' => $project->id,
            'payment_frequency' => 'MONTHLY', 'duration' => 12, 'signing_date' => '2026-10-15',
            'contract_amount' => 1, 'total_amount' => 1, 'total_paid' => 9999, 'project_inputs' => ['ponds' => 4]];
    }

    public function test_create_contract_recalculates_tampered_totals_and_saves_definition_and_results(): void
    {
        $project = $this->project();
        $this->post(route('contracts.store'), $this->data($project))->assertRedirect();
        $contract = Contract::firstOrFail();
        $this->assertSame('2150.00', $contract->total_payable);
        $this->assertSame('2150.00', $contract->total_outstanding);
        $this->assertSame('0.00', $contract->total_paid);
        $this->assertSame(4, $contract->project_calculation['inputs']['ponds']);
        $this->assertSame(2150, $contract->project_calculation['results']['cost']);
        $this->assertSame($project->calculation_schema, $contract->project_calculation['schema']);
    }

    public function test_update_keeps_saved_definition_and_recalculates_unpaid_draft_inputs(): void
    {
        $project = $this->project(); $data = $this->data($project);
        $this->post(route('contracts.store'), $data)->assertRedirect();
        $contract = Contract::firstOrFail();
        $project->update(['registration_fee' => 900, 'calculation_schema' => ['inputs' => [], 'formulas' => []]]);
        $data['project_inputs']['ponds'] = 6;
        $this->put(route('contracts.update', $contract), $data)->assertRedirect();
        $contract->refresh();
        $this->assertSame('3150.00', $contract->total_payable);
        $this->assertSame('0.00', $contract->total_paid);
        $this->assertSame('3150.00', $contract->total_outstanding);
        $this->assertSame(6, $contract->project_calculation['inputs']['ponds']);
    }

    public function test_update_cannot_rewrite_contracts_with_payments_or_signed_approvals(): void
    {
        $project = $this->project(); $data = $this->data($project);
        $this->post(route('contracts.store'), $data)->assertRedirect();
        $contract = Contract::firstOrFail();
        $data['project_inputs']['ponds'] = 6;
        $contract->update(['workflow_step' => 1, 'workflow_status' => 'pending_accountant']);
        $this->putJson(route('contracts.update', $contract), $data)->assertUnprocessable()->assertJsonValidationErrors('contract');
        $contract->update(['workflow_step' => 0, 'workflow_status' => 'draft']);
        DB::table('payment_transactions')->insert(['contract_id' => $contract->id, 'status' => 'APPROVED', 'amount' => 500, 'contract_amount_paid' => 500]);
        $this->putJson(route('contracts.update', $contract), $data)->assertUnprocessable()->assertJsonValidationErrors('contract');
        $this->assertSame('2150.00', $contract->fresh()->total_payable);
        $this->assertSame(4, $contract->fresh()->project_calculation['inputs']['ponds']);
    }

    public function test_invalid_project_inputs_do_not_create_contracts(): void
    {
        $data = $this->data($this->project()); $data['project_inputs']['ponds'] = 0;
        $this->postJson(route('contracts.store'), $data)->assertUnprocessable()->assertJsonValidationErrors('project_inputs.ponds');
        $this->assertDatabaseCount('contracts', 0);
    }

    public function test_migration_adds_nullable_snapshot_columns_without_changing_legacy_rows(): void
    {
        $this->assertTrue(Schema::hasColumn('projects', 'calculation_schema'));
        $this->assertTrue(Schema::hasColumn('contracts', 'project_calculation'));
        $project = Project::create(['project_name' => 'Legacy', 'project_code' => 'OLD']);
        $this->assertNull($project->calculation_schema);
        $migration = require database_path('migrations/2026_10_15_000001_add_configurable_project_calculations.php');
        $migration->up(); // repeat deployment is safe
        $migration->down();
        $this->assertFalse(Schema::hasColumn('contracts', 'project_calculation'));
    }

    public function test_contract_form_and_saved_detail_views_render_configurable_project_data(): void
    {
        $project = $this->project();
        $html = view('contracts._form', ['contract' => null, 'projects' => collect([$project]),
            'members' => collect(), 'groups' => collect(), 'branches' => collect(), 'paymentMethods' => collect()])->render();
        $this->assertStringContainsString('contractProjectCalculation', $html);
        $this->post(route('contracts.store'), $this->data($project))->assertRedirect();
        $html = view('contracts.documents.project-calculation', ['contract' => Contract::firstOrFail()])->render();
        $this->assertStringContainsString('Ponds', $html);
        $this->assertStringContainsString('2150', $html);
    }

    public function test_switching_to_a_manual_project_clears_old_calculated_projections(): void
    {
        $project = $this->project();
        $schema = $project->calculation_schema;
        $schema['formulas'][] = ['key' => 'monthly', 'label' => 'Monthly', 'expression' => 'cost / duration', 'target' => 'monthly_payout_amount'];
        $project->update(['calculation_schema' => $schema]);
        $this->post(route('contracts.store'), $this->data($project))->assertRedirect();
        $contract = Contract::firstOrFail();
        $this->assertNotNull($contract->monthly_payout_amount);
        $manual = Project::create(['project_name' => 'Manual Project', 'project_code' => 'MANUAL']);
        $data = $this->data($manual); $data['project_inputs'] = []; $data['contract_amount'] = $data['total_amount'] = 1200;
        $this->put(route('contracts.update', $contract), $data)->assertRedirect();
        $contract->refresh();
        $this->assertNull($contract->project_calculation);
        $this->assertNull($contract->monthly_payout_amount);
        $this->assertSame('1200.00', $contract->total_payable);
    }
}
