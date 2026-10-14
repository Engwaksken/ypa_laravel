@php
    $receipt = $document['receipt'];
@endphp
<section class="appendix-page receipt-page">
    <table class="receipt-heading">
        <tr>
            <td class="logo-cell">@if($document['logo'])<img src="{{ $document['logo'] }}" alt="YPA Logo">@endif</td>
            <td class="receipt-title"><h2>{{ $receipt['rows']->isNotEmpty() ? 'OFFICIAL PAYMENT RECEIPT' : 'PAYMENT SUMMARY' }}</h2><span class="muted">{{ ucfirst((string) $contract->contract_for) }} Contract</span></td>
            <td class="contract-number"><span class="muted">CONTRACT NO.</span><br><strong>{{ $contract->contract_number }}</strong></td>
        </tr>
    </table>
    <p><strong>{{ strtolower((string) $contract->contract_for) === 'group' ? 'Group Code' : 'Membership Number' }}:</strong> {{ $document['partyCode'] ?: '-' }}</p>
    <p><strong>Name:</strong> {{ $document['partyName'] ?: '-' }}</p>
    <p><strong>Total Scheduled:</strong> UGX {{ number_format($receipt['scheduled'], 2) }}</p>
    <p><strong>Total Paid:</strong> UGX {{ number_format($receipt['paid'], 2) }}</p>
    <p><strong>Outstanding:</strong> UGX {{ number_format($receipt['outstanding'], 2) }}</p>
    @if($receipt['rows']->isEmpty())
        <p class="muted">No approved payment transactions are recorded for this contract. This summary is not proof of payment.</p>
    @else
    <table class="receipt-table">
        <thead><tr><th style="width:3%">#</th><th style="width:11%">Date</th><th>Total</th><th>Membership</th><th>Registration</th><th style="width:7%">Admin</th><th>Contract</th><th style="width:10%">Method</th><th>Reference</th></tr></thead>
        <tbody>
        @foreach($receipt['rows'] as $row)
            <tr><td>{{ $loop->iteration }}</td><td>{{ $row['date']?->format('jS M, Y') ?? '-' }}</td><td>{{ number_format($row['total'], 2) }}</td><td>{{ $row['membership'] > 0 ? number_format($row['membership'], 2) : '-' }}</td><td>{{ $row['registration'] > 0 ? number_format($row['registration'], 2) : '-' }}</td><td>{{ $row['admin'] > 0 ? number_format($row['admin'], 2) : '-' }}</td><td>{{ $row['contract'] > 0 ? number_format($row['contract'], 2) : '-' }}</td><td>{{ $row['method'] ?: '-' }}</td><td class="reference">{{ $row['reference'] ?: '-' }}</td></tr>
        @endforeach
        </tbody>
    </table>
    @endif
</section>
