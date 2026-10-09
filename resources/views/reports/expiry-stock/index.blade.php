@extends('layouts.app')
@section('title', 'Expiry Stock Report')
@section('content')
@php
    $money = fn ($amount) => 'UGX ' . number_format((float) ($amount ?? 0));
    $q = fn (array $extra) => route('expiry-stock.index', array_merge(
        array_filter(['branch' => $branchId > 0 ? $branchId : null, 'days' => $days, 'category' => $category ?? null, 'status' => $status ?? null]),
        $extra
    ));
    $selectedCategory = request('category');
    $selectedStatus = request('status');
@endphp
<div class="dash-wrap">

    <div class="dash-head">
        <div>
            <div class="dash-greeting">Reports</div>
            <h1 class="dash-name">Expiry Stock Report</h1>
            <div class="dash-date">
                {{ $branchId === 0 ? 'All Branches' : $branchId }} &middot;
                Window: {{ $days }} days
            </div>
        </div>
        @if(app(\App\Services\PermissionService::class)->can('export_expiry_stock_report'))
        <a href="{{ route('expiry-stock.export', array_filter(['branch' => $branchId > 0 ? $branchId : null, 'days' => $days, 'category' => request('category'), 'status' => request('status')])) }}" class="btn btn-outline-secondary">
            <i class="fas fa-file-csv"></i> Export CSV
        </a>
        @endif
    </div>

    <div class="dash-panel mb-4">
        <div class="dash-panel-head"><span><i class="fas fa-filter"></i> Filters</span></div>
        <div class="dash-panel-body">
            <form method="GET" action="{{ route('expiry-stock.index') }}" class="row g-3 align-items-end">
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
                    <label class="form-label">Days Window</label>
                    <input type="number" name="days" value="{{ $days }}" min="1" max="365" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select">
                        <option value="">All Categories</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" @selected($selectedCategory == $cat->id)>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="Expired" @selected($selectedStatus === 'Expired')>Expired</option>
                        <option value="Critical" @selected($selectedStatus === 'Critical')>Critical (≤7 days)</option>
                        <option value="Warning" @selected($selectedStatus === 'Warning')>Warning (8–{{ $days }} days)</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Apply</button>
                    <a href="{{ route('expiry-stock.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="dash-grid d-grid-4 mb-4">
        <div class="dash-card border-danger">
            <div class="dash-card-top"><span class="dash-card-title">Expired</span><span class="dash-card-icon"><i class="fas fa-skull-crossbones"></i></span></div>
            <div class="dash-card-value text-danger">{{ number_format($data['counts']['expired']) }}</div>
        </div>
        <div class="dash-card border-warning">
            <div class="dash-card-top"><span class="dash-card-title">Critical (≤ 7 days)</span><span class="dash-card-icon"><i class="fas fa-exclamation-triangle"></i></span></div>
            <div class="dash-card-value text-warning">{{ number_format($data['counts']['critical']) }}</div>
        </div>
        <div class="dash-card border-info">
            <div class="dash-card-top"><span class="dash-card-title">Warning (8–{{ $days }} days)</span><span class="dash-card-icon"><i class="fas fa-bell"></i></span></div>
            <div class="dash-card-value text-info">{{ number_format($data['counts']['warning']) }}</div>
        </div>
        <div class="dash-card border-primary">
            <div class="dash-card-top"><span class="dash-card-title">Total Flagged</span><span class="dash-card-icon"><i class="fas fa-list"></i></span></div>
            <div class="dash-card-value">{{ number_format($data['counts']['total']) }}</div>
        </div>
    </div>

    <div class="dash-panel">
        <div class="dash-panel-head"><span><i class="fas fa-list"></i> Expiring Stock</span></div>
        <div class="dash-panel-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>SKU</th>
                            <th>Category</th>
                            <th>Branch</th>
                            <th>Expiry Date</th>
                            <th class="text-end">Days Left</th>
                            <th>Status</th>
                            <th class="text-end">Qty</th>
                            <th class="text-end">Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data['rows'] as $row)
                            <tr>
                                <td class="fw-bold">{{ $row['product']->name ?? '-' }}</td>
                                <td>{{ $row['product']->sku ?? '-' }}</td>
                                <td><span class="badge text-bg-light">{{ $row['product']->category->name ?? '-' }}</span></td>
                                <td>{{ $row['branch_name'] }}</td>
                                <td>{{ $row['expiry_date']->format('M d, Y') }}</td>
                                <td class="text-end fw-bold {{ $row['days_remaining'] < 0 ? 'text-danger' : ($row['days_remaining'] <= 7 ? 'text-warning' : '') }}">
                                    {{ $row['days_remaining'] }}
                                </td>
                                <td>
                                    @if($row['status'] === 'Expired')
                                        <span class="badge text-bg-danger"><i class="fas fa-skull-crossbones"></i> Expired</span>
                                    @elseif($row['status'] === 'Critical')
                                        <span class="badge text-bg-warning text-dark"><i class="fas fa-exclamation"></i> Critical</span>
                                    @else
                                        <span class="badge text-bg-info"><i class="fas fa-bell"></i> Warning</span>
                                    @endif
                                </td>
                                <td class="text-end">{{ number_format($row['quantity']) }}</td>
                                <td class="text-end">{{ $money($row['value']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-muted py-4">No expiring stock within {{ $days }} days.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection
