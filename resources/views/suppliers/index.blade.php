@extends('layouts.app')

@section('title', 'Suppliers')

@section('content')
<div class="dash-wrap">

    <div class="dash-head">
        <div>
            <h1 class="dash-name">Suppliers</h1>
            <div class="dash-date">{{ number_format($stats['total']) }} supplier(s)</div>
        </div>
        <button type="button" class="btn btn-primary" onclick="openSupplierModal()">
            <i class="fas fa-plus"></i> Add Supplier
        </button>
    </div>

    <div class="dash-grid mb-4">
        <div class="dash-card">
            <div class="dash-card-top">
                <span class="dash-card-title">Total</span>
                <span class="dash-card-icon"><i class="fas fa-truck"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format($stats['total']) }}</div>
        </div>
        <div class="dash-card">
            <div class="dash-card-top">
                <span class="dash-card-title">Members</span>
                <span class="dash-card-icon"><i class="fas fa-user-check"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format($stats['members']) }}</div>
        </div>
        <div class="dash-card">
            <div class="dash-card-top">
                <span class="dash-card-title">Non-members</span>
                <span class="dash-card-icon"><i class="fas fa-user"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format($stats['non_members']) }}</div>
        </div>
    </div>

    <div class="dash-panel">
        <div class="dash-panel-head">
            <span><i class="fas fa-filter"></i> Filter Suppliers</span>
        </div>
        <div class="dash-panel-body">
            <form method="GET" action="{{ route('suppliers.index') }}" class="row g-3">
                <div class="col-md-5">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Name, contact, phone, email...">
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
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select">
                        <option value="">All Types</option>
                        <option value="member" @selected($typeFilter === 'member')>Member</option>
                        <option value="non_member" @selected($typeFilter === 'non_member')>Non-member</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
                    <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="dash-panel mt-4">
        <div class="dash-panel-head">
            <span><i class="fas fa-list"></i> Supplier List</span>
            <span class="text-muted small">{{ $suppliers->total() }} result(s)</span>
        </div>
        <div class="dash-panel-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Name</th>
                            <th>Contact Person</th>
                            <th>Branch</th>
                            <th>Phone</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($suppliers as $supplier)
                            <tr>
                                <td><span class="badge bg-{{ $supplier->supplier_type === 'member' ? 'primary' : 'warning' }}">{{ $supplier->supplier_type === 'member' ? 'Member' : 'Non-member' }}</span></td>
                                <td><strong>{{ $supplier->name }}</strong></td>
                                <td>{{ $supplier->contact_name ?? '-' }}</td>
                                <td>{{ optional($supplier->branch)->name ?? '-' }}</td>
                                <td>{{ $supplier->phone ?? '-' }}</td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" title="Edit"
                                        data-url="{{ route('suppliers.update', $supplier) }}"
                                        data-type="{{ $supplier->supplier_type }}"
                                        data-branch="{{ $supplier->branch_id ?? '' }}"
                                        data-name="{{ $supplier->name }}"
                                        data-contact="{{ $supplier->contact_name ?? '' }}"
                                        data-phone="{{ $supplier->phone ?? '' }}"
                                        data-email="{{ $supplier->email ?? '' }}"
                                        data-address="{{ $supplier->address ?? '' }}"
                                        onclick="openSupplierEdit(this)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form action="{{ route('suppliers.destroy', $supplier) }}" method="POST" class="d-inline ypa-confirm-delete" data-confirm-title="Delete supplier?" data-confirm-message="Delete {{ $supplier->name }}? This cannot be undone.">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No suppliers found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{ $suppliers->links() }}

</div>

<div class="modal fade" id="supplierModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="supplierForm" method="POST" action="{{ route('suppliers.store') }}">
                @csrf
                <input type="hidden" name="_method" id="supplierMethod" value="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="supplierModalTitle">Add Supplier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" id="supplierName" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact Person</label>
                            <input type="text" name="contact_name" id="supplierContact" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Type</label>
                            <select name="supplier_type" id="supplierType" class="form-select" required>
                                <option value="non_member">Non-member</option>
                                <option value="member">Member</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Branch</label>
                            <select name="branch_id" id="supplierBranch" class="form-select">
                                <option value="">-- None --</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" id="supplierPhone" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="supplierEmail" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address</label>
                            <textarea name="address" id="supplierAddress" rows="2" class="form-control"></textarea>
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
    function openSupplierModal() {
        var form = document.getElementById('supplierForm');
        form.action = "{{ route('suppliers.store') }}";
        document.getElementById('supplierMethod').value = 'POST';
        document.getElementById('supplierModalTitle').textContent = 'Add Supplier';
        form.reset();
        if (window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('supplierModal')).show();
        }
    }

    function openSupplierEdit(btn) {
        var form = document.getElementById('supplierForm');
        form.action = btn.dataset.url;
        document.getElementById('supplierMethod').value = 'PUT';
        document.getElementById('supplierModalTitle').textContent = 'Edit Supplier';
        document.getElementById('supplierName').value = btn.dataset.name || '';
        document.getElementById('supplierContact').value = btn.dataset.contact || '';
        document.getElementById('supplierType').value = btn.dataset.type || 'non_member';
        document.getElementById('supplierBranch').value = btn.dataset.branch || '';
        document.getElementById('supplierPhone').value = btn.dataset.phone || '';
        document.getElementById('supplierEmail').value = btn.dataset.email || '';
        document.getElementById('supplierAddress').value = btn.dataset.address || '';
        if (window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('supplierModal')).show();
        }
    }
</script>
@endpush
@endsection
