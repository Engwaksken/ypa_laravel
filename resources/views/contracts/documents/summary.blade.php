@php
    $isGroup = strtolower((string) $contract->contract_for) === 'group';
    $party = $isGroup ? $contract->group : $contract->member;
    $money = fn ($value) => 'UGX ' . number_format((float) $value);
    $sidecar = $contract->projectData?->data_json ?? [];
@endphp
<div class="meta">
    <strong>{{ ucwords(str_replace('_', ' ', (string) ($contract->workflow_status ?: $contract->status))) }}</strong>
    · Signed {{ $contract->signing_date?->format('d M Y') ?? '-' }}
    · Starts {{ $contract->start_date?->format('d M Y') ?? '-' }}
    · Ends {{ $contract->end_date?->format('d M Y') ?? '-' }}
</div>
<p class="muted">Contract summary — no matching agreement template is configured. This summary does not replace the legal agreement terms.</p>
<section class="section">
    <h3>{{ $isGroup ? 'Group Details' : 'Member Details' }}</h3>
    <table class="details">
        <tr><td><span class="label">{{ $isGroup ? 'Group Name' : 'Full Name' }}</span><strong>{{ $isGroup ? ($party?->group_name ?? '-') : ($party?->full_name ?? '-') }}</strong></td><td><span class="label">{{ $isGroup ? 'Group Code' : 'Membership ID' }}</span>{{ $isGroup ? ($party?->group_code ?? '-') : ($party?->membership_id ?? '-') }}</td></tr>
        <tr><td><span class="label">Phone</span>{{ $isGroup ? ($party?->telephone ?? '-') : ($party?->telephone1 ?? '-') }}</td><td><span class="label">Email</span>{{ $party?->email ?? '-' }}</td></tr>
        <tr><td><span class="label">District</span>{{ $party?->district ?? $contract->district ?? '-' }}</td><td><span class="label">Payer Type</span>{{ $isGroup ? 'Group / Organisation' : 'Individual Member' }}</td></tr>
    </table>
</section>
<section class="section">
    <h3>Contract Details</h3>
    <table class="details">
        <tr><td><span class="label">Project</span>{{ $contract->project->project_name ?? '-' }}</td><td><span class="label">Project Category</span>{{ $contract->project?->category?->category_name ?? '-' }}</td></tr>
        <tr><td><span class="label">Branch</span>{{ $contract->branch->name ?? '-' }}</td><td><span class="label">Payment Frequency</span>{{ $contract->payment_frequency ?: '-' }}</td></tr>
        <tr><td><span class="label">Project Code</span>{{ $contract->project_code ?: ($contract->project->project_code ?? '-') }}</td><td><span class="label">Contract Period</span>{{ $contract->duration_months ?? $contract->duration ?? '-' }} months</td></tr>
    </table>
</section>
@if($sidecar)
<section class="section">
    <h3>Project Breakdown</h3>
    <table class="finance">
        @foreach(['hives' => 'Hives', 'goats_purchased' => 'Goats Purchased', 'goats_offers' => 'Offers', 'maize_acres' => 'Acres', 'maize_cost_per_acre' => 'Cost per Acre', 'cdc_goats_purchased' => 'CDC Goats Purchased', 'planned_kids_units' => 'Planned Kid Units'] as $key => $label)
            @if(isset($sidecar[$key]) && is_scalar($sidecar[$key]))<tr><td>{{ $label }}</td><td class="amount">{{ $sidecar[$key] }}</td></tr>@endif
        @endforeach
    </table>
</section>
@endif
<section class="section">
    <h3>Financial Summary</h3>
    <table class="finance">
        <tr><td>Contract Amount Payable</td><td class="amount">{{ $money($totals['total_payable']) }}</td></tr>
        <tr><td>Amount Paid</td><td class="amount">{{ $money($totals['total_paid']) }}</td></tr>
        <tr><td>Outstanding Balance</td><td class="amount">{{ $money($totals['total_outstanding']) }}</td></tr>
    </table>
    @if($contract->amount_in_words)<p>{{ $contract->amount_in_words }}</p>@endif
</section>
<section class="section">
    <h3>Payment Details</h3>
    <table class="details">
        <tr><td><span class="label">Payment Method</span>{{ $contract->paymentMethod->method_name ?? '-' }}</td><td><span class="label">Reference</span>{{ $contract->payment_reference ?: '-' }}</td></tr>
        <tr><td><span class="label">Payment Date</span>{{ $contract->payment_date?->format('d M Y') ?? '-' }}</td><td><span class="label">Receipt Number</span>{{ $contract->receipt_number ?: '-' }}</td></tr>
    </table>
</section>
@if($contract->items->isNotEmpty())
<section class="section">
    <h3>Contract Items</h3>
    <table class="items"><thead><tr><th>Name</th><th>Quantity</th><th>Unit Price</th><th>Monthly Return</th></tr></thead><tbody>
        @foreach($contract->items as $item)<tr><td>{{ $item->item_name }}</td><td>{{ $item->quantity }}</td><td>{{ $money($item->unit_price) }}</td><td>{{ $money($item->monthly_return) }}</td></tr>@endforeach
    </tbody></table>
</section>
@endif
@if($contract->notes)<section class="section"><h3>Notes &amp; Remarks</h3><p>{!! nl2br(e($contract->notes)) !!}</p></section>@endif
@include('contracts.documents.organization-signatures')
