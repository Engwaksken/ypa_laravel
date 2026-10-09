<div class="dash-panel">
    <div class="dash-panel-head">
        <span><i class="fas fa-file-invoice"></i> Purchases (Stock Receipts)</span>
        <span class="text-muted small">Stock entries in period</span>
    </div>
    <div class="dash-panel-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Supplier</th>
                        <th>Branch</th>
                        <th>Date</th>
                        <th class="text-end">Quantity</th>
                        <th class="text-end">Unit Cost</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sectionData['rows'] ?? [] as $row)
                        <tr>
                            <td>{{ $row['supplier_name'] }}</td>
                            <td>{{ $row['branch_name'] }}</td>
                            <td>{{ $row['created_at'] }}</td>
                            <td class="text-end">{{ number_format($row['quantity']) }}</td>
                            <td class="text-end">{{ $money($row['cost_price']) }}</td>
                            <td class="text-end fw-bold">{{ $money($row['total']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No stock receipts in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>