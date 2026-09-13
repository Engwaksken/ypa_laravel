@extends('layouts.app')

@section('title', 'Branches')

@section('content')
<div class="dash-wrap">

    <div class="dash-head">
        <div>
            <h1 class="dash-name">Branches</h1>
            <div class="dash-date">{{ number_format($stats['total']) }} branch(es)</div>
        </div>
        <button type="button" class="btn btn-primary" onclick="openBranchModal()">
            <i class="fas fa-plus"></i> Add Branch
        </button>
    </div>

    <div class="dash-grid mb-4">
        <div class="dash-card">
            <div class="dash-card-top">
                <span class="dash-card-title">Total</span>
                <span class="dash-card-icon"><i class="fas fa-building"></i></span>
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
    </div>

    <div class="dash-panel">
        <div class="dash-panel-head">
            <span><i class="fas fa-filter"></i> Filter Branches</span>
        </div>
        <div class="dash-panel-body">
            <form method="GET" action="{{ route('branches.index') }}" class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Name or location...">
                </div>
                <div class="col-md-4 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
                    <a href="{{ route('branches.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="dash-panel mt-4">
        <div class="dash-panel-head">
            <span><i class="fas fa-list"></i> Branch List</span>
            <span class="text-muted small">{{ $branches->total() }} result(s)</span>
        </div>
        <div class="dash-panel-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Location</th>
                            <th>Contact</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($branches as $branch)
                            <tr>
                                <td><strong>{{ $branch->name }}</strong></td>
                                <td>{{ $branch->location ?? '-' }}</td>
                                <td>{{ $branch->contact ?? '-' }}</td>
                                <td>{{ $branch->branch_email ?? '-' }}</td>
                                <td><span class="badge bg-{{ $branch->status ? 'success' : 'secondary' }}">{{ $branch->status ? 'Active' : 'Inactive' }}</span></td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" title="Edit"
                                        data-url="{{ route('branches.update', $branch) }}"
                                        data-name="{{ $branch->name }}"
                                        data-location="{{ $branch->location ?? '' }}"
                                        data-contact="{{ $branch->contact ?? '' }}"
                                        data-email="{{ $branch->branch_email ?? '' }}"
                                        data-active="{{ $branch->status ? '1' : '0' }}"
                                        onclick="openBranchEdit(this)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form action="{{ route('branches.destroy', $branch) }}" method="POST" class="d-inline ypa-confirm-delete" data-confirm-title="Delete branch?" data-confirm-message="Delete {{ $branch->name }}? This cannot be undone.">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No branches found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{ $branches->links() }}

</div>

<div class="modal fade" id="branchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="branchForm" method="POST" action="{{ route('branches.store') }}">
                @csrf
                <input type="hidden" name="_method" id="branchMethod" value="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="branchModalTitle">Add Branch</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" id="branchName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" id="branchLocation" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contact</label>
                        <input type="text" name="contact" id="branchContact" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="branch_email" id="branchEmail" class="form-control">
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="status" id="branchActive" value="1" class="form-check-input" checked>
                        <label class="form-check-label" for="branchActive">Active</label>
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
    function openBranchModal() {
        var form = document.getElementById('branchForm');
        form.action = "{{ route('branches.store') }}";
        document.getElementById('branchMethod').value = 'POST';
        document.getElementById('branchModalTitle').textContent = 'Add Branch';
        form.reset();
        document.getElementById('branchActive').checked = true;
        if (window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('branchModal')).show();
        }
    }

    function openBranchEdit(btn) {
        var form = document.getElementById('branchForm');
        form.action = btn.dataset.url;
        document.getElementById('branchMethod').value = 'PUT';
        document.getElementById('branchModalTitle').textContent = 'Edit Branch';
        document.getElementById('branchName').value = btn.dataset.name || '';
        document.getElementById('branchLocation').value = btn.dataset.location || '';
        document.getElementById('branchContact').value = btn.dataset.contact || '';
        document.getElementById('branchEmail').value = btn.dataset.email || '';
        document.getElementById('branchActive').checked = btn.dataset.active === '1';
        if (window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('branchModal')).show();
        }
    }
</script>
@endpush
@endsection
