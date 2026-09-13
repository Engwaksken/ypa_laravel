@extends('layouts.app')

@section('title', 'Products')

@section('content')
@php
    $canCreate = auth()->user()?->hasPermission('products_create') ?? false;
    $canEdit = auth()->user()?->hasPermission('products_edit') ?? false;
    $canDelete = auth()->user()?->hasPermission('products_delete') ?? false;
@endphp

<div class="dash-wrap">

    <div class="dash-head">
        <div>
            <h1 class="dash-name">Products</h1>
            <div class="dash-date">{{ number_format($stats['total']) }} product(s) · {{ number_format($stats['categories']) }} categories</div>
        </div>
        @if($canCreate)
            <button type="button" class="btn btn-primary" onclick="openProductModal()">
                <i class="fas fa-plus"></i> Add Product
            </button>
        @endif
    </div>

    <div class="dash-grid mb-4">
        <div class="dash-card">
            <div class="dash-card-top">
                <span class="dash-card-title">Total</span>
                <span class="dash-card-icon"><i class="fas fa-box"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format($stats['total']) }}</div>
        </div>
        <div class="dash-card">
            <div class="dash-card-top">
                <span class="dash-card-title">Active</span>
                <span class="dash-card-icon"><i class="fas fa-circle-check"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format($stats['active']) }}</div>
        </div>
        <div class="dash-card">
            <div class="dash-card-top">
                <span class="dash-card-title">Inactive</span>
                <span class="dash-card-icon"><i class="fas fa-circle-pause"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format($stats['inactive']) }}</div>
        </div>
        <div class="dash-card">
            <div class="dash-card-top">
                <span class="dash-card-title">Categories</span>
                <span class="dash-card-icon"><i class="fas fa-tags"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format($stats['categories']) }}</div>
        </div>
    </div>

    <div class="dash-panel">
        <div class="dash-panel-body">
            <ul class="nav nav-tabs ypa-tabs mb-4" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $activeTab !== 'categories' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tab-products" type="button" role="tab">Products</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $activeTab === 'categories' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tab-categories" type="button" role="tab">Categories</button>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade {{ $activeTab !== 'categories' ? 'show active' : '' }}" id="tab-products" role="tabpanel">
                    <form method="GET" action="{{ route('products.index') }}" class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Search</label>
                            <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Name or SKU...">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Category</label>
                            <select name="category" class="form-select">
                                <option value="">All Categories</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" @selected((string) $categoryFilter === (string) $category->id)>{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Branch</label>
                            <select name="branch" class="form-select">
                                <option value="">All Branches</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected((string) $branchFilter === (string) $branch->id)>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
                            <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">Reset</a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>SKU</th>
                                    <th>Category</th>
                                    <th>Branch</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($products as $product)
                                    <tr>
                                        <td><strong>{{ $product->name }}</strong></td>
                                        <td>{{ $product->sku ?? '-' }}</td>
                                        <td>{{ optional($product->category)->name ?? '-' }}</td>
                                        <td>{{ optional($product->branch)->name ?? '-' }}</td>
                                        <td><span class="badge bg-{{ $product->is_active ? 'success' : 'secondary' }}">{{ $product->is_active ? 'Active' : 'Inactive' }}</span></td>
                                        <td class="text-end">
                                            @if($canEdit)
                                                <button type="button" class="btn btn-sm btn-outline-secondary" title="Edit"
                                                    data-url="{{ route('products.update', $product) }}"
                                                    data-name="{{ $product->name }}"
                                                    data-sku="{{ $product->sku ?? '' }}"
                                                    data-branch="{{ $product->branch_id ?? '' }}"
                                                    data-category="{{ $product->category_id ?? '' }}"
                                                    data-description="{{ $product->description ?? '' }}"
                                                    data-active="{{ $product->is_active ? '1' : '0' }}"
                                                    onclick="openProductEdit(this)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            @endif
                                            @if($canDelete)
                                                <form action="{{ route('products.destroy', $product) }}" method="POST" class="d-inline ypa-confirm-delete" data-confirm-title="Delete product?" data-confirm-message="Delete {{ $product->name }}? This cannot be undone.">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">No products found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{ $products->links() }}
                </div>

                <div class="tab-pane fade {{ $activeTab === 'categories' ? 'show active' : '' }}" id="tab-categories" role="tabpanel">
                    @if($canCreate)
                        <div class="mb-3">
                            <button type="button" class="btn btn-primary" onclick="openCategoryModal()">
                                <i class="fas fa-plus"></i> Add Category
                            </button>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($categories as $category)
                                    <tr>
                                        <td><strong>{{ $category->name }}</strong></td>
                                        <td>{{ \Illuminate\Support\Str::limit($category->description ?? '', 80) ?: '-' }}</td>
                                        <td><span class="badge bg-{{ $category->is_active ? 'success' : 'secondary' }}">{{ $category->is_active ? 'Active' : 'Inactive' }}</span></td>
                                        <td class="text-end">
                                            @if($canEdit)
                                                <button type="button" class="btn btn-sm btn-outline-secondary" title="Edit"
                                                    data-url="{{ route('categories.update', $category) }}"
                                                    data-name="{{ $category->name }}"
                                                    data-description="{{ $category->description ?? '' }}"
                                                    data-active="{{ $category->is_active ? '1' : '0' }}"
                                                    onclick="openCategoryEdit(this)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            @endif
                                            @if($canDelete)
                                                <form action="{{ route('categories.destroy', $category) }}" method="POST" class="d-inline ypa-confirm-delete" data-confirm-title="Delete category?" data-confirm-message="Delete {{ $category->name }}? This cannot be undone.">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">No categories found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<div class="modal fade" id="productModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="productForm" method="POST" action="{{ route('products.store') }}">
                @csrf
                <input type="hidden" name="_method" id="productMethod" value="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="productModalTitle">Add Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" id="productName" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">SKU</label>
                            <input type="text" name="sku" id="productSku" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Category</label>
                            <select name="category_id" id="productCategory" class="form-select">
                                <option value="">-- None --</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Branch</label>
                            <select name="branch_id" id="productBranch" class="form-select">
                                <option value="">-- None --</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" id="productDescription" rows="2" class="form-control"></textarea>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input type="checkbox" name="is_active" id="productActive" value="1" class="form-check-input" checked>
                                <label class="form-check-label" for="productActive">Active</label>
                            </div>
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

<div class="modal fade" id="categoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="categoryForm" method="POST" action="{{ route('categories.store') }}">
                @csrf
                <input type="hidden" name="_method" id="categoryMethod" value="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="categoryModalTitle">Add Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" id="categoryName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="categoryDescription" rows="2" class="form-control"></textarea>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="is_active" id="categoryActive" value="1" class="form-check-input" checked>
                        <label class="form-check-label" for="categoryActive">Active</label>
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
    function showModal(id) {
        var el = document.getElementById(id);
        if (window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(el).show();
        }
    }

    function openProductModal() {
        var form = document.getElementById('productForm');
        form.action = "{{ route('products.store') }}";
        document.getElementById('productMethod').value = 'POST';
        document.getElementById('productModalTitle').textContent = 'Add Product';
        form.reset();
        document.getElementById('productActive').checked = true;
        showModal('productModal');
    }

    function openProductEdit(btn) {
        var form = document.getElementById('productForm');
        form.action = btn.dataset.url;
        document.getElementById('productMethod').value = 'PUT';
        document.getElementById('productModalTitle').textContent = 'Edit Product';
        document.getElementById('productName').value = btn.dataset.name || '';
        document.getElementById('productSku').value = btn.dataset.sku || '';
        document.getElementById('productBranch').value = btn.dataset.branch || '';
        document.getElementById('productCategory').value = btn.dataset.category || '';
        document.getElementById('productDescription').value = btn.dataset.description || '';
        document.getElementById('productActive').checked = btn.dataset.active === '1';
        showModal('productModal');
    }

    function openCategoryModal() {
        var form = document.getElementById('categoryForm');
        form.action = "{{ route('categories.store') }}";
        document.getElementById('categoryMethod').value = 'POST';
        document.getElementById('categoryModalTitle').textContent = 'Add Category';
        form.reset();
        document.getElementById('categoryActive').checked = true;
        showModal('categoryModal');
    }

    function openCategoryEdit(btn) {
        var form = document.getElementById('categoryForm');
        form.action = btn.dataset.url;
        document.getElementById('categoryMethod').value = 'PUT';
        document.getElementById('categoryModalTitle').textContent = 'Edit Category';
        document.getElementById('categoryName').value = btn.dataset.name || '';
        document.getElementById('categoryDescription').value = btn.dataset.description || '';
        document.getElementById('categoryActive').checked = btn.dataset.active === '1';
        showModal('categoryModal');
    }
</script>
@endpush
@endsection
