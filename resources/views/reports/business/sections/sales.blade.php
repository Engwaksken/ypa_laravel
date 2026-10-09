<div class="dash-card mb-4">
    <div class="dash-card-top"><span class="dash-card-title">Sales Totals</span><span class="dash-card-icon"><i class="fas fa-shopping-cart"></i></span></div>
    <div class="dash-card-value">{{ $money($data['revenue_collected']) }}</div>
    <div class="dash-card-sub">{{ number_format($data['sales_count']) }} transactions &middot; {{ $money($data['pending_amount']) }} pending</div>
</div>

<div class="dash-panel">
    <div class="dash-panel-head">
        <span><i class="fas fa-receipt"></i> Transactions</span>
    </div>
    <div class="dash-panel-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Branch</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sectionData['rows'] ?? [] as $order)
                        <tr>
                            <td class="fw-bold">{{ $order->order_number }}</td>
                            <td>{{ $order->created_at?->format('M d, Y H:i') }}</td>
                            <td>{{ $order->customer_name }}</td>
                            <td>{{ $order->branch->name ?? '-' }}</td>
                            <td>{{ $order->payment_method }}</td>
                            <td><span class="badge text-bg-{{ $order->status === 'completed' ? 'success' : ($order->status === 'cancelled' ? 'danger' : 'warning') }}">{{ $order->status }}</span></td>
                            <td class="text-end fw-bold">{{ $money($order->total_amount) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">No transactions for this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>