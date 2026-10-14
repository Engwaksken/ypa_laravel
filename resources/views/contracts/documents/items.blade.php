@if($contract->items->isNotEmpty())
<section class="appendix-page section">
    <h3>CONTRACT ITEMS</h3>
    <table class="items">
        <thead><tr><th style="width:3%">#</th><th>Item</th><th style="width:8%">Type</th><th style="width:7%">Qty</th><th style="width:7%">Unit</th><th>Total</th><th>Expected Qty</th><th>Harvest / Unit</th><th>Harvest Balance</th></tr></thead>
        <tbody>
        @foreach($contract->items as $item)
            <tr><td>{{ $loop->iteration }}</td><td>{{ $item->item_name ?: ($item->project?->project_name ?? '-') }}</td><td>{{ $item->item_type ?: '-' }}</td><td>{{ $item->quantity }}</td><td>{{ $item->unit_name ?: '-' }}</td><td>UGX {{ number_format((float) $item->total_price, 2) }}</td><td>{{ $item->expected_quantity ?? $item->projected_quantity ?? $item->harvest_quantity ?? '-' }} {{ $item->expected_units }}</td><td>{{ ($item->harvest_per_unit ?? $item->harvest_per_bag) !== null ? 'UGX ' . number_format((float) ($item->harvest_per_unit ?? $item->harvest_per_bag), 2) : '-' }}</td><td>{{ ($item->projected_harvest_balance ?? $item->balance_amount) !== null ? 'UGX ' . number_format((float) ($item->projected_harvest_balance ?? $item->balance_amount), 2) : '-' }}</td></tr>
        @endforeach
        </tbody>
    </table>
</section>
@endif
