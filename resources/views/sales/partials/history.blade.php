<div class="dash-panel mt-4">
    <div class="dash-panel-head"><span><i class="fas fa-clock-rotate-left me-2 text-primary"></i>Recent invoices</span><span class="small text-muted">{{ $sales->total() }} invoice(s)</span></div>
    <div class="dash-panel-body p-0"><div class="table-responsive">
        <table class="table table-hover align-middle mb-0"><caption class="visually-hidden">Recent invoices for permitted branches</caption>
            <thead><tr><th scope="col">Invoice</th><th scope="col">Date</th><th scope="col">Customer</th><th scope="col">Payment status</th><th scope="col" class="text-end">Total</th><th scope="col" class="text-end">Balance</th><th scope="col">Receipt</th></tr></thead>
            <tbody>@forelse($sales as $sale)
                @php($statusTone = match (strtolower((string) $sale->payment_status)) { 'paid' => 'success', 'partially paid' => 'warning', 'oncredit' => 'danger', default => 'secondary' })
                <tr><td><strong>{{ $sale->invoice_no }}</strong></td><td>{{ $sale->sale_date }}</td><td>{{ $sale->customer_name ?: 'Walk-in customer' }}</td><td><span class="badge bg-{{ $statusTone }}">{{ ucfirst($sale->payment_status) }}</span></td><td class="text-end fw-semibold">{{ $money($sale->total_amount) }}</td><td class="text-end">{{ $money($sale->balance_amount) }}</td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('sales.show', $sale->id) }}" aria-label="View receipt for invoice {{ $sale->invoice_no }}"><i class="fas fa-receipt me-1"></i>Receipt</a></td></tr>
            @empty<tr><td colspan="7" class="text-center text-muted py-4">No invoices found.</td></tr>@endforelse</tbody>
        </table>
    </div></div>
    @if($sales->hasPages())<div class="dash-panel-body">{{ $sales->withQueryString()->links() }}</div>@endif
</div>
