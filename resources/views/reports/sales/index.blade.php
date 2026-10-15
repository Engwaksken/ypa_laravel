@extends('layouts.app')
@section('title', 'Sales Report')
@section('content')
@php
    $money = fn ($amount) => 'UGX ' . number_format((float) ($amount ?? 0));
    $q = fn (array $extra) => route('sales-reports.index', array_merge(
        array_filter(['from' => $from, 'to' => $to, 'branch' => $branchId > 0 ? $branchId : null, 'source' => $source]),
        $extra
    ));
@endphp
<div class="dash-wrap">

    <div class="dash-head">
        <div>
            <div class="dash-greeting">Reports</div>
            <h1 class="dash-name">Sales Report</h1>
            <div class="dash-date">
                {{ $branchId === 0 ? 'All Branches' : $branchId }} &middot;
                {{ \Carbon\Carbon::parse($from)->format('M d, Y') }} &ndash; {{ \Carbon\Carbon::parse($to)->format('M d, Y') }}
            </div>
        </div>
        @if(app(\App\Services\PermissionService::class)->can('export_sales_report'))
        <a href="{{ route('sales-reports.export', ['from' => $from, 'to' => $to, 'branch' => $branchId > 0 ? $branchId : null, 'source' => $source]) }}" class="btn btn-outline-secondary">
            <i class="fas fa-file-csv"></i> Export CSV
        </a>
        @endif
    </div>

    <div class="alert alert-info">
        @if($source === 'pos')
            Showing POS receipts only. Collected equals receipt total less outstanding balance. Product amounts are before receipt discounts and tax.
        @else
            Showing order records only. Collected is inferred from order status and is not proof of payment. POS receipts are reported separately.
        @endif
    </div>

    <div class="dash-panel mb-4">
        <div class="dash-panel-head"><span><i class="fas fa-filter"></i> Filters</span></div>
        <div class="dash-panel-body">
            <form method="GET" action="{{ route('sales-reports.index') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">From</label>
                    <input type="date" name="from" value="{{ $from }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">To</label>
                    <input type="date" name="to" value="{{ $to }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Branch</label>
                    <select name="branch" class="form-select">
                        <option value="">All Branches</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" @selected($branchId === (int) $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="sales-source" class="form-label">Source</label>
                    <select id="sales-source" name="source" class="form-select">
                        @if($posAvailable)<option value="pos" @selected($source === 'pos')>POS receipts</option>@endif
                        <option value="orders" @selected($source === 'orders')>Orders</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Apply</button>
                    <a href="{{ route('sales-reports.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="dash-grid d-grid-5 mb-4">
        <div class="dash-card">
            <div class="dash-card-top"><span class="dash-card-title">Transactions</span><span class="dash-card-icon"><i class="fas fa-shopping-cart"></i></span></div>
            <div class="dash-card-value">{{ number_format($data['totals']['orders']) }}</div>
        </div>
        <div class="dash-card">
            <div class="dash-card-top"><span class="dash-card-title">Revenue</span><span class="dash-card-icon"><i class="fas fa-coins"></i></span></div>
            <div class="dash-card-value">{{ $money($data['totals']['revenue']) }}</div>
        </div>
        <div class="dash-card">
            <div class="dash-card-top"><span class="dash-card-title">Avg / Transaction</span><span class="dash-card-icon"><i class="fas fa-divide"></i></span></div>
            <div class="dash-card-value">{{ $money($data['totals']['avg']) }}</div>
        </div>
        <div class="dash-card">
            <div class="dash-card-top"><span class="dash-card-title">Collected</span><span class="dash-card-icon"><i class="fas fa-check-circle"></i></span></div>
            <div class="dash-card-value text-success">{{ $money($data['totals']['collected']) }}</div>
        </div>
        <div class="dash-card">
            <div class="dash-card-top"><span class="dash-card-title">Pending</span><span class="dash-card-icon"><i class="fas fa-clock"></i></span></div>
            <div class="dash-card-value text-warning">{{ $money($data['totals']['pending']) }}</div>
        </div>
    </div>

    <ul class="nav nav-tabs sales-report-tabs" id="salesReportTabs" role="tablist" aria-label="Sales report breakdown">
        <li class="nav-item" role="presentation"><button class="nav-link active" id="daily-tab" data-bs-toggle="tab" data-bs-target="#daily-panel" type="button" role="tab" aria-controls="daily-panel" aria-selected="true"><i class="fas fa-chart-line me-2"></i>Daily Trend <span class="report-tab-count">{{ count($data['daily']) }}</span></button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" id="products-tab" data-bs-toggle="tab" data-bs-target="#products-panel" type="button" role="tab" aria-controls="products-panel" aria-selected="false"><i class="fas fa-boxes-stacked me-2"></i>Top Products <span class="report-tab-count">{{ count($data['topProducts']) }}</span></button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" id="branches-tab" data-bs-toggle="tab" data-bs-target="#branches-panel" type="button" role="tab" aria-controls="branches-panel" aria-selected="false"><i class="fas fa-building me-2"></i>By Branch <span class="report-tab-count">{{ count($data['byBranch']) }}</span></button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" id="payments-tab" data-bs-toggle="tab" data-bs-target="#payments-panel" type="button" role="tab" aria-controls="payments-panel" aria-selected="false"><i class="fas fa-credit-card me-2"></i>By Payment Method <span class="report-tab-count">{{ count($data['byPayment']) }}</span></button></li>
    </ul>
    <div class="tab-content sales-report-content" id="salesReportTabContent">
        <section class="tab-pane fade show active" id="daily-panel" role="tabpanel" aria-labelledby="daily-tab" tabindex="0">
            <div class="dash-panel"><div class="dash-panel-head"><span><i class="fas fa-chart-line me-2 text-primary"></i>Daily Trend</span><span class="small text-muted">{{ count($data['daily']) }} day(s)</span></div><div class="dash-panel-body p-0"><div class="table-responsive">
                <table class="table table-hover align-middle mb-0"><thead><tr><th>Date</th><th class="text-end">Transactions</th><th class="text-end">Revenue</th><th class="text-end">Collected</th><th class="text-end">Pending</th></tr></thead><tbody>
                    @forelse($data['daily'] as $d)<tr><td>{{ \Carbon\Carbon::parse($d['date'])->format('M d, Y') }}</td><td class="text-end">{{ number_format($d['orders']) }}</td><td class="text-end">{{ $money($d['revenue']) }}</td><td class="text-end text-success">{{ $money($d['collected']) }}</td><td class="text-end text-warning">{{ $money($d['pending']) }}</td></tr>@empty<tr><td colspan="5" class="text-center text-muted py-4">No sales data for this period.</td></tr>@endforelse
                </tbody></table>
            </div></div></div>
        </section>
        <section class="tab-pane fade" id="products-panel" role="tabpanel" aria-labelledby="products-tab" tabindex="0">
            <div class="dash-panel"><div class="dash-panel-head"><span><i class="fas fa-boxes-stacked me-2 text-primary"></i>Top Products</span><span class="small text-muted">Top {{ count($data['topProducts']) }} by revenue</span></div><div class="dash-panel-body p-0"><div class="table-responsive">
                <table class="table table-hover align-middle mb-0"><thead><tr><th>Product</th><th class="text-end">Qty</th><th class="text-end">Revenue</th></tr></thead><tbody>
                    @forelse($data['topProducts'] as $p)<tr><td class="fw-bold">{{ $p['product_name'] }}</td><td class="text-end">{{ number_format($p['qty']) }}</td><td class="text-end">{{ $money($p['revenue']) }}</td></tr>@empty<tr><td colspan="3" class="text-center text-muted py-4">No products sold for this period.</td></tr>@endforelse
                </tbody></table>
            </div></div></div>
        </section>
        <section class="tab-pane fade" id="branches-panel" role="tabpanel" aria-labelledby="branches-tab" tabindex="0">
            <div class="dash-panel"><div class="dash-panel-head"><span><i class="fas fa-building me-2 text-primary"></i>Sales by Branch</span><span class="small text-muted">{{ count($data['byBranch']) }} branch(es)</span></div><div class="dash-panel-body p-0"><div class="table-responsive">
                <table class="table table-hover align-middle mb-0"><thead><tr><th>Branch</th><th class="text-end">Transactions</th><th class="text-end">Revenue</th><th class="text-end">Collected</th></tr></thead><tbody>
                    @forelse($data['byBranch'] as $b)<tr><td class="fw-semibold">{{ $b['branch'] }}</td><td class="text-end">{{ number_format($b['orders']) }}</td><td class="text-end">{{ $money($b['revenue']) }}</td><td class="text-end text-success">{{ $money($b['collected']) }}</td></tr>@empty<tr><td colspan="4" class="text-center text-muted py-4">No branch data for this period.</td></tr>@endforelse
                </tbody></table>
            </div></div></div>
        </section>
        <section class="tab-pane fade" id="payments-panel" role="tabpanel" aria-labelledby="payments-tab" tabindex="0">
            <div class="dash-panel"><div class="dash-panel-head"><span><i class="fas fa-credit-card me-2 text-primary"></i>Sales by Payment Method</span><span class="small text-muted">{{ count($data['byPayment']) }} method(s)</span></div><div class="dash-panel-body p-0"><div class="table-responsive">
                <table class="table table-hover align-middle mb-0"><thead><tr><th>Method</th><th class="text-end">Transactions</th><th class="text-end">Revenue</th></tr></thead><tbody>
                    @forelse($data['byPayment'] as $m)<tr><td class="fw-semibold">{{ $m['method'] }}</td><td class="text-end">{{ number_format($m['orders']) }}</td><td class="text-end">{{ $money($m['revenue']) }}</td></tr>@empty<tr><td colspan="3" class="text-center text-muted py-4">No payment data for this period.</td></tr>@endforelse
                </tbody></table>
            </div></div></div>
        </section>
    </div>

</div>
@endsection
@push('styles')
<style>
.sales-report-tabs{gap:6px;border-bottom:1px solid #dbe3ec;margin:0 0 1.25rem;padding:0 8px;overflow-x:auto;flex-wrap:nowrap;scrollbar-width:thin}.sales-report-tabs .nav-item{flex:0 0 auto}.sales-report-tabs .nav-link{white-space:nowrap;border:0;border-bottom:3px solid transparent;border-radius:8px 8px 0 0;padding:12px 15px;color:#64748b;font-weight:700}.sales-report-tabs .nav-link:hover{color:var(--ds-brand);background:#f4f8fc}.sales-report-tabs .nav-link.active{color:var(--ds-brand);background:#fff;border-bottom-color:var(--ds-brand)}.report-tab-count{display:inline-grid;place-items:center;min-width:22px;height:22px;margin-left:4px;padding:0 6px;border-radius:999px;background:#eef2f7;color:#475569;font-size:.72rem}.sales-report-content>.tab-pane{outline:none}.sales-report-content .dash-panel{overflow:hidden}
</style>
@endpush
