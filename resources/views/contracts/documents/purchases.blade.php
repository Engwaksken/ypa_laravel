@php
    $unitQuantity = $contract->items->sum('quantity');
    $purchaseRows = [
        ['Date', $contract->signing_date?->format('jS F, Y') ?? '-'],
        ['Purchased Quantity', $unitQuantity > 0 ? number_format($unitQuantity, 2) . ' ' . ($contract->items->first()?->unit_name ?? '') : '-'],
        ['Contract Amount Paid (UGX)', 'UGX ' . number_format((float) ($contract->contract_amount_paid ?? $totals['total_paid']), 2)],
        ['Harvest Quantity', $contract->expected_quantity !== null ? $contract->expected_quantity . ' ' . $contract->expected_units : '-'],
        ['Amount at Harvest (UGX)', $contract->projected_harvest_amount !== null ? 'UGX ' . number_format((float) $contract->projected_harvest_amount, 2) : '-'],
        ['Remaining Harvest Balance (UGX)', $contract->projected_harvest_balance !== null ? 'UGX ' . number_format((float) $contract->projected_harvest_balance, 2) : '-'],
    ];
@endphp
<section class="appendix-page section">
    <h3 class="appendix-title">A TABLE SHOWING PURCHASES AND HARVESTS</h3>
    <table class="finance">
        <thead><tr><th>No.</th><th>Item</th><th>Values (Amount UGX)</th></tr></thead>
        <tbody>@foreach($purchaseRows as $row)<tr><td class="row-number">{{ $loop->iteration }}</td><td><strong>{{ $row[0] }}</strong></td><td class="amount">{{ $row[1] }}</td></tr>@endforeach</tbody>
    </table>
    @foreach($document['integrityWarnings'] ?? [] as $warning)
        <p style="margin-top:12px;color:#92400e;"><strong>Financial review:</strong> {{ $warning }}</p>
    @endforeach
</section>
