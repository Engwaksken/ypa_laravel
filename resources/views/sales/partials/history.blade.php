<div class="dash-panel mt-4">
    <div class="dash-panel-head"><span>Recent invoices</span></div>
    <div class="dash-panel-body p-0"><div class="table-responsive">
        <table class="table table-hover align-middle mb-0"><caption class="visually-hidden">Recent invoices for permitted branches</caption>
            <thead><tr><th scope="col">Invoice</th><th scope="col">Date</th><th scope="col">Customer</th><th scope="col">Payment status</th><th scope="col" class="text-end">Total</th><th scope="col" class="text-end">Balance</th><th scope="col">Receipt</th></tr></thead>
            <tbody>@forelse($sales as $sale)
                <tr><td>{{ $sale->invoice_no }}</td><td>{{ $sale->sale_date }}</td><td>{{ $sale->customer_name ?: 'Walk-in customer' }}</td><td>{{ ucfirst($sale->payment_status) }}</td><td class="text-end">{{ $money($sale->total_amount) }}</td><td class="text-end">{{ $money($sale->balance_amount) }}</td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('sales.show', $sale->id) }}" aria-label="View receipt for invoice {{ $sale->invoice_no }}">View receipt</a></td></tr>
            @empty<tr><td colspan="7" class="text-center text-muted py-4">No invoices found.</td></tr>@endforelse</tbody>
        </table>
    </div></div>
    @if($sales->hasPages())<div class="dash-panel-body">{{ $sales->withQueryString()->links() }}</div>@endif
</div>
