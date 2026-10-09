<div class="dash-panel">
    <div class="dash-panel-head">
        <span><i class="fas fa-boxes"></i> Inventory Position</span>
        <span class="text-muted small">Value shown is remaining stock at cost price</span>
    </div>
    <div class="dash-panel-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Branch</th>
                        <th class="text-end">In Stock</th>
                        <th class="text-end">Sold</th>
                        <th class="text-end">Remaining</th>
                        <th class="text-end">Value</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sectionData['rows'] ?? [] as $row)
                        <tr>
                            <td class="fw-bold">{{ $row['product']->name ?? '-' }}</td>
                            <td>{{ $row['product']->sku ?? '-' }}</td>
                            <td>{{ $row['branch_name'] }}</td>
                            <td class="text-end">{{ number_format($row['total_stock']) }}</td>
                            <td class="text-end text-warning">{{ number_format($row['sold_qty']) }}</td>
                            <td class="text-end">{{ number_format($row['remaining']) }}</td>
                            <td class="text-end fw-bold">{{ $money($row['value']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">No stock records found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>