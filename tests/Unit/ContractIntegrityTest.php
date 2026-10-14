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

    public function test_draft_can_be_edited_but_signed_or_active_contracts_cannot(): void
    {
        $service = new ContractIntegrityService;
        $service->assertEditable(new Contract(['status' => 'DRAFT', 'workflow_status' => 'draft', 'workflow_step' => 0]));
        foreach ([['workflow_step' => 1], ['workflow_status' => 'approved'], ['status' => 'ACTIVE'], ['status' => 'TERMINATED']] as $attributes) {
            try {
                $service->assertEditable(new Contract($attributes));
                $this->fail('Protected contract was editable.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('contract', $exception->errors());
            }
        }
    }
}
