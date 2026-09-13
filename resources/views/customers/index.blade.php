@extends('layouts.app')

@section('title', 'Customers')

@section('content')
<div class="dash-wrap">

    <div class="dash-head">
        <div>
            <h1 class="dash-name">Customers</h1>
            <div class="dash-date">{{ number_format($stats['total']) }} customer(s)</div>
        </div>
        <button type="button" class="btn btn-primary" onclick="openCustomerModal()">
            <i class="fas fa-plus"></i> Add Customer
        </button>
    </div>

    <div class="dash-grid mb-4">
        <div class="dash-card">
            <div class="dash-card-top">
                <span class="dash-card-title">Total</span>
                <span class="dash-card-icon"><i class="fas fa-users"></i></span>
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
            <span><i class="fas fa-filter"></i> Filter Customers</span>
        </div>
        <div class="dash-panel-body">
            <form method="GET" action="{{ route('customers.index') }}" class="row g-3">
                <div class="col-md-5">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Name, phone, email...">
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
                    <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="dash-panel mt-4">
        <div class="dash-panel-head">
            <span><i class="fas fa-list"></i> Customer List</span>
            <span class="text-muted small">{{ $customers->total() }} result(s)</span>
        </div>
        <div class="dash-panel-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Name</th>
                            <th>Branch</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customers as $customer)
                            <tr>
                                <td><span class="badge bg-{{ $customer->customer_type === 'member' ? 'primary' : 'warning' }}">{{ $customer->customer_type === 'member' ? 'Member' : 'Non-member' }}</span></td>
                                <td><strong>{{ $customer->name }}</strong></td>
                                <td>{{ optional($customer->branch)->name ?? '-' }}</td>
                                <td>{{ $customer->phone ?? '-' }}</td>
                                <td>{{ $customer->email ?? '-' }}</td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" title="Edit"
                                        data-url="{{ route('customers.update', $customer) }}"
                                        data-type="{{ $customer->customer_type }}"
                                        data-branch="{{ $customer->branch_id ?? '' }}"
                                        data-name="{{ $customer->name }}"
                                        data-phone="{{ $customer->phone ?? '' }}"
                                        data-email="{{ $customer->email ?? '' }}"
                                        data-address="{{ $customer->address ?? '' }}"
                                        onclick="openCustomerEdit(this)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form action="{{ route('customers.destroy', $customer) }}" method="POST" class="d-inline ypa-confirm-delete" data-confirm-title="Delete customer?" data-confirm-message="Delete {{ $customer->name }}? This cannot be undone.">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No customers found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{ $customers->links() }}

</div>

<div class="modal fade" id="customerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="customerForm" method="POST" action="{{ route('customers.store') }}">
                @csrf
                <input type="hidden" name="_method" id="customerMethod" value="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="customerModalTitle">Add Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" id="customerName" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Type</label>
                            <select name="customer_type" id="customerType" class="form-select" required>
                                <option value="non_member">Non-member</option>
                                <option value="member">Member</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Branch</label>
                            <select name="branch_id" id="customerBranch" class="form-select">
                                <option value="">-- None --</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" id="customerPhone" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="customerEmail" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address</label>
                            <textarea name="address" id="customerAddress" rows="2" class="form-control"></textarea>
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
    function openCustomerModal() {
        var form = document.getElementById('customerForm');
        form.action = "{{ route('customers.store') }}";
        document.getElementById('customerMethod').value = 'POST';
        document.getElementById('customerModalTitle').textContent = 'Add Customer';
        form.reset();
        if (window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('customerModal')).show();
        }
    }

    function openCustomerEdit(btn) {
        var form = document.getElementById('customerForm');
        form.action = btn.dataset.url;
        document.getElementById('customerMethod').value = 'PUT';
        document.getElementById('customerModalTitle').textContent = 'Edit Customer';
        document.getElementById('customerName').value = btn.dataset.name || '';
        document.getElementById('customerType').value = btn.dataset.type || 'non_member';
        document.getElementById('customerBranch').value = btn.dataset.branch || '';
        document.getElementById('customerPhone').value = btn.dataset.phone || '';
        document.getElementById('customerEmail').value = btn.dataset.email || '';
        document.getElementById('customerAddress').value = btn.dataset.address || '';
        if (window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('customerModal')).show();
        }
    }
</script>
@endpush
@endsection
