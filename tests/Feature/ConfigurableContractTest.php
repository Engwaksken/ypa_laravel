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
        (require database_path('migrations/2026_10_17_000001_create_contract_amendments_table.php'))->up();
        $service =\Mockery::mock(ContractService::class)->makePartial();
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

    public function test_signed_contract_edit_requires_a_reason_and_is_recorded_as_an_amendment(): void
    {
        $project = $this->project(); $data = $this->data($project);
        $this->post(route('contracts.store'), $data)->assertRedirect();
        $contract = Contract::firstOrFail();
        $contract->update(['workflow_step' => 1, 'workflow_status' => 'pending_accountant']);
        $data['project_inputs']['ponds'] = 6;

        $this->putJson(route('contracts.update', $contract), $data)->assertUnprocessable()->assertJsonValidationErrors('amendment_reason');
        $this->assertSame(4, $contract->fresh()->project_calculation['inputs']['ponds']);
        $this->assertDatabaseCount('contract_amendments', 0);

        $data['amendment_reason'] = 'Client added two more ponds, agreed on site visit.';
        $this->put(route('contracts.update', $contract), $data)->assertRedirect(route('contracts.show', $contract));
        $fresh = $contract->fresh();
        $this->assertSame(6, $fresh->project_calculation['inputs']['ponds']);
        $this->assertSame(1, (int) $fresh->workflow_step);
        $this->assertSame('pending_accountant', $fresh->workflow_status);

        Schema::create('contract_templates', function (Blueprint $table) { $table->id(); $table->string('template_name')->nullable(); });
        Schema::table('contract_item_harvests', fn (Blueprint $table) => $table->unsignedInteger('contract_item_id')->nullable());
        $this->get(route('contracts.edit', $contract))->assertOk()
            ->assertSee('Reason for change')->assertSee('name="amendment_reason"', false)->assertSee('Save Amendment');

        $amendment = \App\Models\ContractAmendment::firstOrFail();
        $this->assertSame($data['amendment_reason'], $amendment->reason);
        $this->assertSame('pending_accountant', $amendment->workflow_status);
        $this->assertArrayHasKey('project_calculation', $amendment->changes);
    }

    public function test_items_are_updated_in_place_and_harvested_items_cannot_be_removed(): void
    {
        Schema::table('contract_items', function (Blueprint $table) {
            foreach (['item_name', 'item_type', 'unit_name'] as $key) $table->string($key)->nullable();
            foreach (['quantity', 'unit_price', 'total_price', 'monthly_return', 'monthly_payout_amount', 'total_hives'] as $key) $table->decimal($key, 15, 2)->nullable();
            $table->unsignedInteger('project_id')->nullable(); $table->unsignedInteger('item_order')->nullable(); $table->timestamps();
        });
        Schema::table('contract_item_harvests', fn (Blueprint $table) => $table->unsignedInteger('contract_item_id')->nullable());

        $project = $this->project(); $data = $this->data($project);
        $data['items'] = [['item_name' => 'Pond A', 'quantity' => 1, 'unit_price' => 10], ['item_name' => 'Pond B', 'quantity' => 2, 'unit_price' => 10]];
        $this->post(route('contracts.store'), $data)->assertRedirect();
        $contract = Contract::firstOrFail();
        [$pondA, $pondB] = $contract->items()->orderBy('id')->pluck('id')->all();
        DB::table('contract_item_harvests')->insert(['contract_id' => $contract->id, 'contract_item_id' => $pondA]);

        $data['items'] = [['id' => $pondA, 'item_name' => 'Pond A (renamed)', 'quantity' => 3, 'unit_price' => 10], ['item_name' => 'Pond C', 'quantity' => 1, 'unit_price' => 5]];
        $this->put(route('contracts.update', $contract), $data)->assertRedirect();
        $items = $contract->items()->orderBy('id')->get();
        $this->assertSame([$pondA, 'Pond A (renamed)', '30.00'], [$items[0]->id, $items[0]->item_name, $items[0]->total_price]);
        $this->assertSame(['Pond A (renamed)', 'Pond C'], $items->pluck('item_name')->all());
        $this->assertDatabaseMissing('contract_items', ['id' => $pondB]);

        $data['items'] = [['item_name' => 'Pond C', 'quantity' => 1, 'unit_price' => 5]];
        $this->putJson(route('contracts.update', $contract), $data)->assertUnprocessable()->assertJsonValidationErrors('items');
        $this->assertDatabaseHas('contract_items', ['id' => $pondA]);
    }

    public function test_draft_with_payments_can_be_edited_and_terminated_contracts_cannot(): void
    {
        $project = $this->project(); $data = $this->data($project);
        $this->post(route('contracts.store'), $data)->assertRedirect();
        $contract = Contract::firstOrFail();
        DB::table('payment_transactions')->insert(['contract_id' => $contract->id, 'status' => 'APPROVED', 'amount' => 500, 'contract_amount_paid' => 500]);
        $data['project_inputs']['ponds'] = 6;

        $this->put(route('contracts.update', $contract), $data)->assertRedirect();
        $this->assertSame(6, $contract->fresh()->project_calculation['inputs']['ponds']);
        $this->assertSame('500.00', $contract->fresh()->total_paid);
        $this->assertDatabaseCount('contract_amendments', 0);

        $contract->update(['status' => 'TERMINATED']);
        $data['amendment_reason'] = 'Attempting to change a closed contract.';
        $this->putJson(route('contracts.update', $contract), $data)->assertUnprocessable()->assertJsonValidationErrors('contract');
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
