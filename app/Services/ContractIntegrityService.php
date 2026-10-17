<?php

namespace App\Services;

use App\Models\Contract;

class ContractIntegrityService
{
    public const CLOSED_STATUSES = ['TERMINATED', 'CANCELLED'];

    /** Closed contracts are final; everything else can be edited (as an amendment once signed). */
    public function assertEditable(Contract $contract): void
    {
        if ($this->isClosed($contract)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'contract' => 'This contract is ' . strtolower((string) $contract->status) . ' and can no longer be edited.',
            ]);
        }
    }

    public function isClosed(Contract $contract): bool
    {
        return in_array(strtoupper((string) $contract->status), self::CLOSED_STATUSES, true);
    }

    /** Edits after signing has started are amendments: they need a reason and are audited. */
    public function requiresAmendment(Contract $contract): bool
    {
        $workflow = strtolower((string) $contract->workflow_status);

        return (int) $contract->workflow_step > 0
            || in_array($workflow, ['pending_accountant', 'pending_director', 'finalising', 'approved'], true)
            || in_array(strtoupper((string) $contract->status), ['APPROVED', 'ACTIVE', 'COMPLETED'], true);
    }

    /** Review stored figures only; never infer a rate from current project settings. */
    public function warnings(Contract $contract): array
    {
        $warnings = [];
        foreach ($contract->items as $index => $item) {
            $quantity = $item->expected_quantity ?? $item->projected_quantity ?? $item->harvest_quantity;
            $rate = $item->harvest_per_unit ?? $item->harvest_per_bag;
            if (! is_numeric($quantity) || ! is_numeric($rate) || $quantity <= 0 || $rate <= 0) continue;
            $expected = round((float) $quantity * (float) $rate, 2);
            $recorded = $item->projected_harvest_amount ?? $item->harvest_amount;
            if (is_numeric($recorded) && abs($expected - (float) $recorded) >= 0.01) {
                $warnings[] = 'Item ' . ($index + 1) . ': harvest quantity × recorded unit rate gives UGX ' . number_format($expected, 2)
                    . ', but the recorded harvest total is UGX ' . number_format((float) $recorded, 2) . '. Review required; stored figures are unchanged.';
            }
        }
        // A contract-level comparison is unambiguous only for a single item.
        if ($contract->items->count() === 1) {
            $item = $contract->items->first();
            $quantity = $contract->expected_quantity ?? $item->expected_quantity ?? $item->projected_quantity ?? $item->harvest_quantity;
            $rate = $item->harvest_per_unit ?? $item->harvest_per_bag;
            if (is_numeric($quantity) && is_numeric($rate) && $quantity > 0 && $rate > 0 && $contract->projected_harvest_amount !== null) {
                $expected = round((float) $quantity * (float) $rate, 2);
                if (abs($expected - (float) $contract->projected_harvest_amount) >= 0.01) {
                    $warnings[] = 'Contract harvest: ' . $quantity . ' × UGX ' . number_format((float) $rate, 2) . ' = UGX ' . number_format($expected, 2)
                        . '; recorded total is UGX ' . number_format((float) $contract->projected_harvest_amount, 2) . '. Review required; stored figures are unchanged.';
                }
            }
        }

        return $warnings;
    }
}
