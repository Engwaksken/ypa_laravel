@extends('layouts.app')
@section('title', 'Stock Report')
@section('content')
@php
    $money = fn ($amount) => 'UGX ' . number_format((float) ($amount ?? 0));
    $q = fn (array $extra) => route('stock-report.index', array_merge(
        array_filter(['from' => $from, 'to' => $to, 'branch' => $branchId > 0 ? $branchId : null, 'category' => $category ?? null, 'supplier' => $supplier ?? null, 'product' => $product ?? null]),
        $extra
    ));
    $selectedCategory = request('category');
    $selectedSupplier = request('supplier');
    $selectedProduct = request('product');
@endphp
<div class="dash-wrap">

    <div class="dash-head">
        <div>
            <div class="dash-greeting">Reports</div>
            <h1 class="dash-name">Stock Report</h1>
            <div class="dash-date">
                {{ $branchId === 0 ? 'All Branches' : $branchId }}
            </div>
        </div>
        @if(app(\App\Services\PermissionService::class)->can('export_stock_report'))
        <a href="{{ route('stock-report.export', array_filter(['branch' => $branchId > 0 ? $branchId : null, 'category' => request('category'), 'supplier' => request('supplier'), 'product' => request('product')])) }}" class="btn btn-outline-secondary">
            <i class="fas fa-file-csv"></i> Export CSV
        </a>
        @endif
    </div>

    <div class="dash-panel mb-4">
        <div class="dash-panel-head"><span><i class="fas fa-filter"></i> Filters</span></div>
        <div class="dash-panel-body">
            <form method="GET" action="{{ route('stock-report.index') }}" class="row g-3 align-items-end">
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
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select">
                        <option value="">All Categories</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" @selected($selectedCategory == $cat->id)>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Supplier</label>
                    <select name="supplier" class="form-select">
                        <option value="">All Suppliers</option>
                        @foreach($suppliers as $s)
                            <option value="{{ $s->id }}" @selected($selectedSupplier == $s->id)>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Apply</button>
                    <a href="{{ route('stock-report.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="dash-grid d-grid-5 mb-4">
        <div class="dash-card">
            <div class="dash-card-top"><span class="dash-card-title">Products</span><span class="dash-card-icon"><i class="fas fa-box-open"></i></span></div>
            <div class="dash-card-value">{{ number_format($data['totals']['products']) }}</div>
        </div>
        <div class="dash-card">
            <div class="dash-card-top"><span class="dash-card-title">In Stock</span><span class="dash-card-icon"><i class="fas fa-warehouse"></i></span></div>
            <div class="dash-card-value">{{ number_format($data['totals']['in_stock']) }}</div>
        </div>
        <div class="dash-card">
            <div class="dash-card-top"><span class="dash-card-title">Sold</span><span class="dash-card-icon"><i class="fas fa-shopping-cart"></i></span></div>
            <div class="dash-card-value text-warning">{{ number_format($data['totals']['sold']) }}</div>
        </div>
        <div class="dash-card">
            <div class="dash-card-top"><span class="dash-card-title">Remaining</span><span class="dash-card-icon"><i class="fas fa-boxes"></i></span></div>
            <div class="dash-card-value">{{ number_format($data['totals']['remaining']) }}</div>
        </div>
        <div class="dash-card">
            <div class="dash-card-top"><span class="dash-card-title">Stock Value</span><span class="dash-card-icon"><i class="fas fa-coins"></i></span></div>
            <div class="dash-card-value">{{ $money($data['totals']['value']) }}</div>
        </div>
    </div>

    <div class="dash-panel">
        <div class="dash-panel-head"><span><i class="fas fa-list"></i> Stock Details</span></div>
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
                                <td class="text-end">{{ number_format($row['remaining']) }}</td>
                                <td class="text-end fw-bold">{{ $money($row['value']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">No stock records match the filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection
