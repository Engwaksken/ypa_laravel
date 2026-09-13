@extends('layouts.app')

@section('title', 'Stock')

@section('content')
@php
    $canCreate = auth()->user()?->hasPermission('stock_create') ?? false;
    $canEdit = auth()->user()?->hasPermission('stock_edit') ?? false;
    $canDelete = auth()->user()?->hasPermission('stock_delete') ?? false;
@endphp

<div class="dash-wrap">

    <div class="dash-head">
        <div>
            <h1 class="dash-name">Stock</h1>
            <div class="dash-date">{{ number_format($stats['units']) }} unit(s) across {{ number_format($stats['lines']) }} line(s)</div>
        </div>
        @if($canCreate)
            <button type="button" class="btn btn-primary" onclick="openStockModal()">
                <i class="fas fa-plus"></i> Add Stock
            </button>
        @endif
    </div>

    <div class="dash-grid mb-4">
        <div class="dash-card">
            <div class="dash-card-top">
                <span class="dash-card-title">Stock Lines</span>
                <span class="dash-card-icon"><i class="fas fa-warehouse"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format($stats['lines']) }}</div>
        </div>
        <div class="dash-card">
            <div class="dash-card-top">
                <span class="dash-card-title">Total Units</span>
                <span class="dash-card-icon"><i class="fas fa-boxes-stacked"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format($stats['units']) }}</div>
        </div>
        <div class="dash-card">
            <div class="dash-card-top">
                <span class="dash-card-title">Low Stock</span>
                <span class="dash-card-icon"><i class="fas fa-exclamation-triangle"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format($stats['low']) }}</div>
        </div>
        <div class="dash-card">
            <div class="dash-card-top">
                <span class="dash-card-title">Expiring (30d)</span>
                <span class="dash-card-icon"><i class="fas fa-calendar-times"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format($stats['expiring']) }}</div>
        </div>
    </div>

    <div class="dash-panel">
        <div class="dash-panel-head">
            <span><i class="fas fa-filter"></i> Filter Stock</span>
        </div>
        <div class="dash-panel-body">
            <form method="GET" action="{{ route('stock.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Product name or SKU...">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Branch</label>
                    <select name="branch" class="form-select">
                        <option value="">All Branches</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((string) $branchFilter === (string) $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Supplier</label>
                    <select name="supplier" class="form-select">
                        <option value="">All Suppliers</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" @selected((string) $supplierFilter === (string) $supplier->id)>{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Alert</label>
                    <select name="alert" class="form-select">
                        <option value="">All</option>
                        <option value="low" @selected($alertFilter === 'low')>Low stock</option>
                        <option value="expiry" @selected($alertFilter === 'expiry')>Expiring soon</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
                    <a href="{{ route('stock.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="dash-panel mt-4">
        <div class="dash-panel-head">
            <span><i class="fas fa-list"></i> Stock List</span>
            <span class="text-muted small">{{ $stock->total() }} result(s)</span>
        </div>
        <div class="dash-panel-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Branch</th>
                            <th>Supplier</th>
                            <th>Quantity</th>
                            <th>Cost</th>
                            <th>Selling</th>
                            <th>Expiry</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($stock as $line)
                            <tr>
                                <td><strong>{{ optional($line->product)->name ?? '-' }}</strong><br><small class="text-muted">{{ optional($line->product)->sku ?? '' }}</small></td>
                                <td>{{ optional($line->branch)->name ?? '-' }}</td>
                                <td>{{ optional($line->supplier)->name ?? '-' }}</td>
                                <td>
                                    <span class="badge bg-{{ (int) $line->quantity <= 10 ? 'danger' : 'success' }}">{{ number_format((int) $line->quantity) }}</span>
                                    <small class="text-muted">{{ $line->unit_type ?? '' }}</small>
                                </td>
                                <td>{{ $line->cost_price !== null ? number_format((float) $line->cost_price, 2) : '-' }}</td>
                                <td>{{ $line->selling_price !== null ? number_format((float) $line->selling_price, 2) : '-' }}</td>
                                <td>{{ $line->expiry_date ? $line->expiry_date->format('M d, Y') : '-' }}</td>
                                <td class="text-end">
                                    @if($canEdit)
                                        <button type="button" class="btn btn-sm btn-outline-secondary" title="Edit"
                                            data-url="{{ route('stock.update', $line) }}"
                                            data-supplier="{{ $line->supplier_id ?? '' }}"
                                            data-product="{{ $line->product_id }}"
                                            data-branch="{{ $line->branch_id }}"
                                            data-quantity="{{ $line->quantity }}"
                                            data-unit="{{ $line->unit_type ?? '' }}"
                                            data-cost="{{ $line->cost_price ?? '' }}"
                                            data-selling="{{ $line->selling_price ?? '' }}"
                                            data-expiry="{{ $line->expiry_date ? $line->expiry_date->format('Y-m-d') : '' }}"
                                            onclick="openStockEdit(this)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    @endif
                                    @if($canDelete)
                                        <form action="{{ route('stock.destroy', $line) }}" method="POST" class="d-inline ypa-confirm-delete" data-confirm-title="Delete stock entry?" data-confirm-message="Delete this stock entry? This cannot be undone.">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No stock found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{ $stock->links() }}

</div>

<div class="modal fade" id="stockModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="stockForm" method="POST" action="{{ route('stock.store') }}">
                @csrf
                <input type="hidden" name="_method" id="stockMethod" value="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="stockModalTitle">Add Stock</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Product</label>
                            <select name="product_id" id="stockProduct" class="form-select" required>
                                <option value="">-- Select --</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}">{{ $product->name }}{{ $product->sku ? ' (' . $product->sku . ')' : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Branch</label>
                            <select name="branch_id" id="stockBranch" class="form-select" required>
                                <option value="">-- Select --</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Supplier</label>
                            <select name="supplier_id" id="stockSupplier" class="form-select">
                                <option value="">-- None --</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Quantity</label>
                            <input type="number" name="quantity" id="stockQuantity" class="form-control" min="0" value="0" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Unit</label>
                            <input type="text" name="unit_type" id="stockUnit" class="form-control" placeholder="pcs, kg...">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Cost Price</label>
                            <input type="number" name="cost_price" id="stockCost" class="form-control" step="0.01" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Selling Price</label>
                            <input type="number" name="selling_price" id="stockSelling" class="form-control" step="0.01" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Expiry Date</label>
                            <input type="date" name="expiry_date" id="stockExpiry" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function openStockModal() {
        var form = document.getElementById('stockForm');
        form.action = "{{ route('stock.store') }}";
        document.getElementById('stockMethod').value = 'POST';
        document.getElementById('stockModalTitle').textContent = 'Add Stock';
        form.reset();
        if (window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('stockModal')).show();
        }
    }

    function openStockEdit(btn) {
        var form = document.getElementById('stockForm');
        form.action = btn.dataset.url;
        document.getElementById('stockMethod').value = 'PUT';
        document.getElementById('stockModalTitle').textContent = 'Edit Stock';
        document.getElementById('stockProduct').value = btn.dataset.product || '';
        document.getElementById('stockBranch').value = btn.dataset.branch || '';
        document.getElementById('stockSupplier').value = btn.dataset.supplier || '';
        document.getElementById('stockQuantity').value = btn.dataset.quantity || '0';
        document.getElementById('stockUnit').value = btn.dataset.unit || '';
        document.getElementById('stockCost').value = btn.dataset.cost || '';
        document.getElementById('stockSelling').value = btn.dataset.selling || '';
        document.getElementById('stockExpiry').value = btn.dataset.expiry || '';
        if (window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('stockModal')).show();
        }
    }
</script>
@endpush
@endsection
