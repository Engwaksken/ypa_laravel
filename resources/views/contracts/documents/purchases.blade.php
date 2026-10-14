<section class="section">
    <h3>Purchases and Harvests</h3>
    <table class="finance">
        <tr><td>Purchase / Contract Amount</td><td class="amount">UGX {{ number_format($totals['total_payable']) }}</td></tr>
        @if($contract->expected_quantity !== null)<tr><td>Expected Harvest Quantity</td><td class="amount">{{ $contract->expected_quantity }} {{ $contract->expected_units }}</td></tr>@endif
        @if($contract->projected_harvest_amount !== null)<tr><td>Recorded Projected Harvest Amount</td><td class="amount">UGX {{ number_format((float) $contract->projected_harvest_amount) }}</td></tr>@endif
        @if($contract->projected_harvest_balance !== null)<tr><td>Recorded Harvest Balance</td><td class="amount">UGX {{ number_format((float) $contract->projected_harvest_balance) }}</td></tr>@endif
    </table>
</section>
@if($contract->items->isNotEmpty())
<section class="section">
    <h3>Detailed Contract Items</h3>
    <table class="items"><thead><tr><th>Item</th><th>Type</th><th>Quantity</th><th>Unit Price</th><th>Monthly Return</th></tr></thead><tbody>
        @foreach($contract->items as $item)<tr><td>{{ $item->item_name }}</td><td>{{ $item->item_type }}</td><td>{{ $item->quantity }} {{ $item->unit_name }}</td><td>UGX {{ number_format((float) $item->unit_price) }}</td><td>UGX {{ number_format((float) $item->monthly_return) }}</td></tr>@endforeach
    </tbody></table>
</section>
@endif
