<div class="dash-panel">
    <div class="dash-panel-head">
        <span><i class="fas fa-users"></i> Customers</span>
        <span class="text-muted small">{{ number_format($sectionData['total'] ?? 0) }} registered customer(s)</span>
    </div>
    <div class="dash-panel-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Type</th>
                        <th class="text-end">Orders</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sectionData['rows'] ?? [] as $customer)
                        <tr>
                            <td class="fw-bold">{{ $customer->name }}</td>
                            <td>{{ $customer->phone ?: '-' }}</td>
                            <td>{{ $customer->email ?: '-' }}</td>
                            <td><span class="badge text-bg-secondary">{{ $customer->customer_type }}</span></td>
                            <td class="text-end">{{ number_format($customer->order_count) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No customer activity for this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>