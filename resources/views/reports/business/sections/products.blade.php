<div class="dash-panel">
    <div class="dash-panel-head">
        <span><i class="fas fa-box-open"></i> Product Catalogue</span>
        <span class="text-muted small">{{ number_format($sectionData['total'] ?? 0) }} product(s)</span>
    </div>
    <div class="dash-panel-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Category</th>
                        <th class="text-end">Stock Qty</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sectionData['rows'] ?? [] as $product)
                        <tr>
                            <td class="fw-bold">{{ $product->name }}</td>
                            <td>{{ $product->sku ?: '-' }}</td>
                            <td><span class="badge text-bg-light">{{ $product->category->name ?? '-' }}</span></td>
                            <td class="text-end">{{ number_format((int) ($product->total_quantity ?? 0)) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">No products found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>