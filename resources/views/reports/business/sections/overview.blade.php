<div class="dash-panel">
    <div class="dash-panel-head">
        <span><i class="fas fa-chart-pie"></i> Business Overview</span>
        <span class="text-muted small">Period summary</span>
    </div>
    <div class="dash-panel-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <tbody>
                    @foreach([
                        'Total Sales' => number_format($data['sales_count']),
                        'Revenue Collected' => $money($data['revenue_collected']),
                        'Pending Amount' => $money($data['pending_amount']),
                        'Active Customers' => number_format($data['active_customers']),
                        'Registered Customers' => number_format($data['total_customers']),
                        'Total Products' => number_format($data['total_products']),
                        'Total Suppliers' => number_format($data['total_suppliers']),
                        'Stock Quantity' => number_format($data['total_stock_qty']),
                        'Stock Value' => $money($data['total_stock_value']),
                        'Manual Expenses' => $money($data['total_expenses']),
                        'Harvest Payouts' => $money($data['harvest_payouts']),
                        'Net Position' => $money($data['net_position']),
                    ] as $label => $value)
                        <tr>
                            <td class="text-muted">{{ $label }}</td>
                            <td class="text-end fw-bold">{{ $value }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>