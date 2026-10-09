@extends('layouts.app')
@section('title', 'Low Stock Report')
@section('content')
@php
    $money = fn ($amount) => 'UGX ' . number_format((float) ($amount ?? 0));
    $q = fn (array $extra) => route('low-stock.index', array_merge(
        array_filter(['branch' => $branchId > 0 ? $branchId : null, 'threshold' => $threshold, 'category' => $category ?? null]),
        $extra
    ));
    $selectedCategory = request('category');
@endphp
<div class="dash-wrap">

    <div class="dash-head">
        <div>
            <div class="dash-greeting">Reports</div>
            <h1 class="dash-name">Low Stock Report</h1>
            <div class="dash-date">
                {{ $branchId === 0 ? 'All Branches' : $branchId }} &middot;
                Threshold: {{ $threshold }}
            </div>
        </div>
        @if(app(\App\Services\PermissionService::class)->can('export_low_stock_report'))
        <a href="{{ route('low-stock.export', array_filter(['branch' => $branchId > 0 ? $branchId : null, 'threshold' => $threshold, 'category' => request('category')])) }}" class="btn btn-outline-secondary">
            <i class="fas fa-file-csv"></i> Export CSV
        </a>
        @endif
    </div>

    <div class="dash-panel mb-4">
        <div class="dash-panel-head"><span><i class="fas fa-filter"></i> Filters</span></div>
        <div class="dash-panel-body">
            <form method="GET" action="{{ route('low-stock.index') }}" class="row g-3 align-items-end">
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
                    <label class="form-label">Threshold</label>
                    <input type="number" name="threshold" value="{{ $threshold }}" min="1" max="1000" class="form-control">
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
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Apply</button>
                    <a href="{{ route('low-stock.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="dash-grid d-grid-4 mb-4">
        <div class="dash-card border-danger">
            <div class="dash-card-top"><span class="dash-card-title">Out of Stock</span><span class="dash-card-icon"><i class="fas fa-times-circle"></i></span></div>
            <div class="dash-card-value text-danger">{{ number_format($data['counts']['out']) }}</div>
        </div>
        <div class="dash-card border-warning">
            <div class="dash-card-top"><span class="dash-card-title">Critical (≤ 5)</span><span class="dash-card-icon"><i class="fas fa-exclamation-triangle"></i></span></div>
            <div class="dash-card-value text-warning">{{ number_format($data['counts']['critical']) }}</div>
        </div>
        <div class="dash-card border-info">
            <div class="dash-card-top"><span class="dash-card-title">Low (≤ {{ $threshold }})</span><span class="dash-card-icon"><i class="fas fa-arrow-down"></i></span></div>
            <div class="dash-card-value text-info">{{ number_format($data['counts']['low']) }}</div>
        </div>
        <div class="dash-card border-primary">
            <div class="dash-card-top"><span class="dash-card-title">Total Alerts</span><span class="dash-card-icon"><i class="fas fa-list"></i></span></div>
            <div class="dash-card-value">{{ number_format($data['counts']['total']) }}</div>
        </div>
    </div>

    <div class="dash-panel">
        <div class="dash-panel-head"><span><i class="fas fa-list"></i> Low Stock Items</span></div>
        <div class="dash-panel-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>SKU</th>
                            <th>Category</th>
                            <th>Branch</th>
                            <th class="text-end">In Stock</th>
                            <th class="text-end">Sold</th>
                            <th class="text-end">Remaining</th>
                            <th>Status</th>
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
                                <td class="text-end">{{ number_format($row['total_stock']) }}</td>
                                <td class="text-end text-warning">{{ number_format($row['sold_qty']) }}</td>
                                <td class="text-end fw-bold">{{ number_format($row['remaining']) }}</td>
                                <td>
                                    @if($row['status'] === 'Out of Stock')
                                        <span class="badge text-bg-danger"><i class="fas fa-times"></i> Out of Stock</span>
                                    @elseif($row['status'] === 'Critical')
                                        <span class="badge text-bg-warning text-dark"><i class="fas fa-exclamation"></i> Critical</span>
                                    @else
                                        <span class="badge text-bg-info"><i class="fas fa-arrow-down"></i> Low</span>
                                    @endif
                                </td>
                                <td class="text-end">{{ $money($row['value']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-muted py-4">No low stock items found. All products are well stocked.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection
