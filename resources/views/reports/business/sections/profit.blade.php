<div class="dash-grid d-grid-4 mb-4">
    <div class="dash-card">
        <div class="dash-card-top"><span class="dash-card-title">Gross Revenue</span><span class="dash-card-icon"><i class="fas fa-coins"></i></span></div>
        <div class="dash-card-value">{{ $money($sectionData['totals']['total_revenue'] ?? 0) }}</div>
    </div>
    <div class="dash-card">
        <div class="dash-card-top"><span class="dash-card-title">Cost of Goods</span><span class="dash-card-icon"><i class="fas fa-truck"></i></span></div>
        <div class="dash-card-value">{{ $money($sectionData['totals']['total_cost'] ?? 0) }}</div>
    </div>
    <div class="dash-card">
        <div class="dash-card-top"><span class="dash-card-title">Gross Profit</span><span class="dash-card-icon"><i class="fas fa-chart-line"></i></span></div>
        <div class="dash-card-value">{{ $money($sectionData['totals']['total_profit'] ?? 0) }}</div>
    </div>
    <div class="dash-card">
        <div class="dash-card-top"><span class="dash-card-title">Margin</span><span class="dash-card-icon"><i class="fas fa-percent"></i></span></div>
        <div class="dash-card-value">{{ number_format($sectionData['totals']['total_margin'] ?? 0, 1) }}%</div>
    </div>
</div>

<div class="dash-panel">
    <div class="dash-panel-head">
        <span><i class="fas fa-tags"></i> Product Profitability</span>
    </div>
    <div class="dash-panel-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th class="text-end">Qty Sold</th>
                        <th class="text-end">Revenue</th>
                        <th class="text-end">Cost</th>
                        <th class="text-end">Profit</th>
                        <th class="text-end">Margin</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sectionData['rows'] ?? [] as $row)
                        <tr>
                            <td class="fw-bold">{{ $row['product_name'] }}</td>
                            <td class="text-end">{{ number_format((int) $row['qty_sold']) }}</td>
                            <td class="text-end">{{ $money($row['revenue']) }}</td>
                            <td class="text-end">{{ $money($row['cost']) }}</td>
                            <td class="text-end fw-bold {{ ($row['profit'] ?? 0) < 0 ? 'text-danger' : '' }}">{{ $money($row['profit']) }}</td>
                            <td class="text-end">{{ number_format((float) $row['margin'], 1) }}%</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No sales in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>