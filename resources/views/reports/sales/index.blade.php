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

    <div class="row g-4">
        <div class="col-lg-7 mb-4">
            <div class="dash-panel h-100">
                <div class="dash-panel-head"><span><i class="fas fa-chart-line"></i> Daily Trend</span></div>
                <div class="dash-panel-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr><th>Date</th><th class="text-end">Transactions</th><th class="text-end">Revenue</th><th class="text-end">Collected</th><th class="text-end">Pending</th></tr>
                            </thead>
                            <tbody>
                                @forelse($data['daily'] as $d)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($d['date'])->format('M d, Y') }}</td>
                                        <td class="text-end">{{ number_format($d['orders']) }}</td>
                                        <td class="text-end">{{ $money($d['revenue']) }}</td>
                                        <td class="text-end text-success">{{ $money($d['collected']) }}</td>
                                        <td class="text-end text-warning">{{ $money($d['pending']) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted py-4">No sales data.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5 mb-4">
            <div class="dash-panel h-100">
                <div class="dash-panel-head"><span><i class="fas fa-star"></i> Top Products</span></div>
                <div class="dash-panel-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead><tr><th>Product</th><th class="text-end">Qty</th><th class="text-end">Revenue</th></tr></thead>
                            <tbody>
                                @forelse($data['topProducts'] as $p)
                                    <tr>
                                        <td class="fw-bold">{{ $p['product_name'] }}</td>
                                        <td class="text-end">{{ number_format($p['qty']) }}</td>
                                        <td class="text-end">{{ $money($p['revenue']) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted py-4">No products sold.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6 mb-4">
            <div class="dash-panel h-100">
                <div class="dash-panel-head"><span><i class="fas fa-building"></i> By Branch</span></div>
                <div class="dash-panel-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead><tr><th>Branch</th><th class="text-end">Transactions</th><th class="text-end">Revenue</th><th class="text-end">Collected</th></tr></thead>
                            <tbody>
                                @forelse($data['byBranch'] as $b)
                                    <tr>
                                        <td>{{ $b['branch'] }}</td>
                                        <td class="text-end">{{ number_format($b['orders']) }}</td>
                                        <td class="text-end">{{ $money($b['revenue']) }}</td>
                                        <td class="text-end text-success">{{ $money($b['collected']) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-4">No branch data.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="dash-panel h-100">
                <div class="dash-panel-head"><span><i class="fas fa-credit-card"></i> By Payment Method</span></div>
                <div class="dash-panel-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead><tr><th>Method</th><th class="text-end">Transactions</th><th class="text-end">Revenue</th></tr></thead>
                            <tbody>
                                @forelse($data['byPayment'] as $m)
                                    <tr>
                                        <td>{{ $m['method'] }}</td>
                                        <td class="text-end">{{ number_format($m['orders']) }}</td>
                                        <td class="text-end">{{ $money($m['revenue']) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted py-4">No payment data.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
