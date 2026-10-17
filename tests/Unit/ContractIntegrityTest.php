<?php

namespace Tests\Unit;

use App\Models\Contract;
use App\Models\ContractItem;
use App\Services\ContractIntegrityService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ContractIntegrityTest extends TestCase
{
    public function test_matching_harvest_figures_have_no_warning(): void
    {
        $item = new ContractItem;
        $item->forceFill(['expected_quantity' => 108, 'harvest_per_bag' => 199950, 'projected_harvest_amount' => 21594600]);
        $contract = new Contract(['expected_quantity' => 108, 'projected_harvest_amount' => 21594600]);
        $contract->setRelation('items', collect([$item]));
        $this->assertSame([], (new ContractIntegrityService)->warnings($contract));
    }

    public function test_item_conflict_is_flagged_without_changing_stored_amounts(): void
    {
        $item = new ContractItem;
        $item->forceFill(['expected_quantity' => 108, 'harvest_per_bag' => 199950, 'projected_harvest_amount' => 21600000]);
        $contract = new Contract;
        $contract->setRelation('items', collect([$item]));
        $warnings = (new ContractIntegrityService)->warnings($contract);
        $this->assertCount(1, $warnings);
        $this->assertStringContainsString('21,594,600.00', $warnings[0]);
        $this->assertEquals(21600000, $item->projected_harvest_amount);
    }

    public function test_open_contracts_are_editable_and_signed_ones_need_an_amendment(): void
    {
        $service = new ContractIntegrityService;
        $draft = new Contract(['status' => 'DRAFT', 'workflow_status' => 'draft', 'workflow_step' => 0]);
        $service->assertEditable($draft);
        $this->assertFalse($service->requiresAmendment($draft));

        foreach ([['workflow_step' => 1], ['workflow_status' => 'approved'], ['status' => 'ACTIVE'], ['status' => 'COMPLETED']] as $attributes) {
            $contract = new Contract($attributes);
            $service->assertEditable($contract);
            $this->assertTrue($service->requiresAmendment($contract), json_encode($attributes));
        }
    }

    public function test_terminated_and_cancelled_contracts_stay_locked(): void
    {
        foreach (['TERMINATED', 'CANCELLED'] as $status) {
            try {
                (new ContractIntegrityService)->assertEditable(new Contract(['status' => $status]));
                $this->fail($status . ' contract was editable.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('contract', $exception->errors());
            }
        }
    }
}
