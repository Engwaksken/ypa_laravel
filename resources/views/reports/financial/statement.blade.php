<div class="dash-panel">
    <div class="dash-panel-head"><span>{{ $tabLabels[$tab] ?? ucfirst($tab) }}</span></div>
    <div class="dash-panel-body">
        @if(!$statement['available'])
            <div class="alert alert-warning mb-0" role="status">{{ $statement['message'] }}</div>
        @else
            @if(!empty($statement['message']))<p class="text-muted">{{ $statement['message'] }}</p>@endif
            <div class="alert alert-info small" role="note">These statements include posted ledger entries only. Historical sales, expenses or payments without a ledger posting are not included. Reconcile opening balances and unposted records before using these statements for statutory reporting. Branch reports exclude entries without an assigned branch.</div>
            @if(in_array($tab, ['balance', 'trial'], true))<p class="small text-muted">Balances as at {{ $to }}. Opening entries before the selected period are included where applicable.</p>@endif
            @if($tab === 'cashflow')<p class="small text-muted">Movements in cash and bank accounts linked to payment methods. Opening balance includes entries before {{ $from }}.</p>@endif
            <div class="table-responsive"><table class="table table-hover align-middle">
                <caption class="visually-hidden">{{ $tabLabels[$tab] ?? ucfirst($tab) }} for the selected branch and dates</caption>
                <thead><tr>@foreach($statement['columns'] as $column)<th scope="col" class="{{ in_array($column['type'], ['money', 'number'], true) ? 'text-end' : '' }}">{{ $column['label'] }}</th>@endforeach</tr></thead>
                <tbody>@forelse($statement['rows'] as $row)<tr>
                    @foreach($statement['columns'] as $column)
                        @php($value = $row[$column['key']] ?? '')
                        <td class="{{ in_array($column['type'], ['money', 'number'], true) ? 'text-end' : '' }}">{{ $column['type'] === 'money' ? $money($value) : $value }}</td>
                    @endforeach
                </tr>@empty<tr><td colspan="{{ max(1, count($statement['columns'])) }}" class="text-center text-muted py-4">No posted ledger entries match this report.</td></tr>@endforelse</tbody>
            </table></div>
            @if(!empty($statement['totals']))
                <dl class="row mb-0 border-top pt-3">@foreach($statement['totals'] as $label => $amount)<dt class="col-sm-8">{{ ucwords(str_replace('_', ' ', $label)) }}</dt><dd class="col-sm-4 text-sm-end">{{ $money($amount) }}</dd>@endforeach</dl>
            @endif
        @endif
    </div>
</div>
