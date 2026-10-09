<div class="dash-grid d-grid-2 mb-4">
    <div class="dash-card">
        <div class="dash-card-top"><span class="dash-card-title">Manual Expenses</span><span class="dash-card-icon"><i class="fas fa-receipt"></i></span></div>
        <div class="dash-card-value">{{ $money($sectionData['total'] ?? 0) }}</div>
    </div>
    <div class="dash-card">
        <div class="dash-card-top"><span class="dash-card-title">Harvest Payouts</span><span class="dash-card-icon"><i class="fas fa-seedling"></i></span></div>
        <div class="dash-card-value">{{ $money($data['harvest_payouts'] ?? 0) }}</div>
    </div>
</div>

<div class="dash-panel">
    <div class="dash-panel-head">
        <span><i class="fas fa-list"></i> Expense Details</span>
    </div>
    <div class="dash-panel-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Payment</th>
                        <th>Branch</th>
                        <th class="text-end">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sectionData['rows'] ?? [] as $expense)
                        <tr>
                            <td>{{ $expense->expense_date?->format('M d, Y') }}</td>
                            <td class="fw-bold">{{ $expense->title }}</td>
                            <td><span class="badge text-bg-secondary">{{ $expense->category }}</span></td>
                            <td>{{ $expense->payment_method }}</td>
                            <td>{{ $expense->branch->name ?? '-' }}</td>
                            <td class="text-end fw-bold">{{ $money($expense->amount) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No manual expenses in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>