@extends('layouts.app')

@section('title', 'Mobilizers')

@section('content')
@php
    $permission = app(\App\Services\PermissionService::class);
    $canCreate = $permission->can('mobilizers_create');
    $canEdit = $permission->can('mobilizers_edit');
    $canDelete = $permission->can('mobilizers_delete');
    $mobilizerStatuses = ['Active', 'Inactive', 'Suspended', 'Terminated'];
@endphp

<div class="dash-wrap">

    <div class="dash-head">
        <div>
            <h1 class="dash-name">Mobilizers</h1>
            <div class="dash-date">{{ number_format($stats['total']) }} mobilizer(s)</div>
        </div>
        @if($canCreate)
            <button type="button" class="btn btn-primary" onclick="openMobilizerModal()">
                <i class="fas fa-user-plus"></i> Add Mobilizer
            </button>
        @endif
    </div>

    <div class="dash-grid d-grid-5 mb-4">
        <div class="dash-card accent-primary">
            <div class="dash-card-top">
                <span class="dash-card-title">Total</span>
                <span class="dash-card-icon"><i class="fas fa-users-cog"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format($stats['total']) }}</div>
        </div>
        <div class="dash-card accent-success">
            <div class="dash-card-top">
                <span class="dash-card-title">Active</span>
                <span class="dash-card-icon"><i class="fas fa-user-check"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format($stats['active']) }}</div>
        </div>
        <div class="dash-card accent-muted">
            <div class="dash-card-top">
                <span class="dash-card-title">Inactive</span>
                <span class="dash-card-icon"><i class="fas fa-user-slash"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format($stats['inactive']) }}</div>
        </div>
        <div class="dash-card accent-warning">
            <div class="dash-card-top">
                <span class="dash-card-title">Suspended</span>
                <span class="dash-card-icon"><i class="fas fa-user-xmark"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format($stats['suspended']) }}</div>
        </div>
        <div class="dash-card accent-danger">
            <div class="dash-card-top">
                <span class="dash-card-title">Terminated</span>
                <span class="dash-card-icon"><i class="fas fa-user-minus"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format($stats['terminated']) }}</div>
        </div>
    </div>

    <div class="dash-panel">
        <div class="dash-panel-head">
            <span><i class="fas fa-filter"></i> Filter Mobilizers</span>
        </div>
        <div class="dash-panel-body">
            <form method="GET" action="{{ route('mobilizers.index') }}" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Name, phone, email, position...">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Department</label>
                    <select name="department" class="form-select">
                        <option value="">All Departments</option>
                        @foreach($departmentArr as $dept)
                            <option value="{{ $dept }}" @selected($departmentFilter === $dept)>{{ $dept }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        @foreach($mobilizerStatuses as $st)
                            <option value="{{ $st }}" @selected($statusFilter === $st)>{{ $st }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Region</label>
                    <select name="region" class="form-select">
                        <option value="">All Regions</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->name }}" @selected($regionFilter === $branch->name)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
                    <a href="{{ route('mobilizers.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="dash-panel mt-4">
        <div class="dash-panel-head">
            <span><i class="fas fa-list"></i> Mobilizer List</span>
            <span class="text-muted small">{{ $mobilizers->total() }} result(s)</span>
        </div>
        <div class="dash-panel-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Contact</th>
                            <th>Email</th>
                            <th>Department</th>
                            <th>Position</th>
                            <th>Branch</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($mobilizers as $mobilizer)
                            @php
                                $badge = match($mobilizer->status) {
                                    'Active' => 'success',
                                    'Inactive' => 'secondary',
                                    'Suspended' => 'warning',
                                    'Terminated' => 'danger',
                                    default => 'secondary',
                                };
                            @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('mobilizers.show', $mobilizer) }}" class="fw-semibold text-decoration-none">{{ $mobilizer->full_name }}</a>
                                    @if($mobilizer->supervisor)
                                        <div class="small text-muted">Supervisor: {{ $mobilizer->supervisor }}</div>
                                    @endif
                                </td>
                                <td>{{ $mobilizer->contact_number ?? '-' }}</td>
                                <td>{{ $mobilizer->email ?? '-' }}</td>
                                <td>{{ $mobilizer->department ?? '-' }}</td>
                                <td>{{ $mobilizer->position ?? '-' }}</td>
                                <td>{{ $mobilizer->branch_region ?? '-' }}</td>
                                <td><span class="badge bg-{{ $badge }}">{{ $mobilizer->status ?? '-' }}</span></td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('mobilizers.show', $mobilizer) }}" class="btn btn-sm btn-outline-primary" title="View" aria-label="View mobilizer {{ $mobilizer->full_name }}"><i class="fas fa-eye" aria-hidden="true"></i></a>
                                    @if($canEdit)
                                        <button type="button" class="btn btn-sm btn-outline-secondary" title="Edit" aria-label="Edit mobilizer {{ $mobilizer->full_name }}"
                                            data-url="{{ route('mobilizers.update', $mobilizer) }}"
                                            data-first-name="{{ $mobilizer->first_name }}"
                                            data-last-name="{{ $mobilizer->last_name }}"
                                            data-contact="{{ $mobilizer->contact_number ?? '' }}"
                                            data-email="{{ $mobilizer->email ?? '' }}"
                                            data-department="{{ $mobilizer->department ?? '' }}"
                                            data-position="{{ $mobilizer->position ?? '' }}"
                                            data-branch="{{ $mobilizer->branch_region ?? '' }}"
                                            data-supervisor="{{ $mobilizer->supervisor ?? '' }}"
                                            data-status="{{ $mobilizer->status ?? 'Active' }}"
                                            data-remarks="{{ $mobilizer->remarks ?? '' }}"
                                            onclick="openMobilizerEdit(this)">
                                            <i class="fas fa-pen" aria-hidden="true"></i>
                                        </button>
                                    @endif
                                    @if($canDelete)
                                        <form action="{{ route('mobilizers.destroy', $mobilizer) }}" method="POST" class="d-inline ypa-confirm-delete" data-confirm-title="Delete mobilizer?" data-confirm-message="Delete {{ $mobilizer->full_name }}? This cannot be undone.">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete" aria-label="Delete mobilizer {{ $mobilizer->full_name }}"><i class="fas fa-trash" aria-hidden="true"></i></button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr class="empty-row">
                                <td colspan="8"><i class="fas fa-inbox"></i>No mobilizers found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="dash-panel-foot">{{ $mobilizers->links() }}</div>
    </div>

</div>

@if($canCreate || $canEdit)
<div class="modal fade" id="mobilizerModal" tabindex="-1" aria-labelledby="mobilizerModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="mobilizerForm" method="POST" action="{{ route('mobilizers.store') }}">
                @csrf
                <input type="hidden" name="_method" id="mobilizerMethod" value="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="mobilizerModalTitle">Add Mobilizer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="modal-section-title">Personal Details</div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="mobilizerFirstName">First Name <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" id="mobilizerFirstName" class="form-control" maxlength="255" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="mobilizerLastName">Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" id="mobilizerLastName" class="form-control" maxlength="255" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="mobilizerContact">Contact Number <span class="text-danger">*</span></label>
                            <input type="text" name="contact_number" id="mobilizerContact" class="form-control" placeholder="+256..." required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="mobilizerEmail">Email</label>
                            <input type="email" name="email" id="mobilizerEmail" class="form-control" maxlength="255">
                        </div>
                    </div>
                    <div class="modal-section-title mt-4">Assignment</div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="mobilizerDepartment">Department <span class="text-danger">*</span></label>
                            <input type="text" name="department" id="mobilizerDepartment" class="form-control" list="mobilizerDepartmentList" maxlength="255" required>
                            <datalist id="mobilizerDepartmentList">
                                @foreach($departmentArr as $dept)
                                    <option value="{{ $dept }}"></option>
                                @endforeach
                            </datalist>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="mobilizerPosition">Position <span class="text-danger">*</span></label>
                            <input type="text" name="position" id="mobilizerPosition" class="form-control" list="mobilizerPositionList" maxlength="255" required>
                            <datalist id="mobilizerPositionList">
                                @foreach($positionArr as $pos)
                                    <option value="{{ $pos }}"></option>
                                @endforeach
                            </datalist>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="mobilizerBranch">Branch / Region</label>
                            <select name="branch_region" id="mobilizerBranch" class="form-select">
                                <option value="">Select Branch</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->name }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="mobilizerSupervisor">Supervisor</label>
                            <input type="text" name="supervisor" id="mobilizerSupervisor" class="form-control" maxlength="255">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="mobilizerStatus">Status <span class="text-danger">*</span></label>
                            <select name="status" id="mobilizerStatus" class="form-select" required>
                                @foreach($mobilizerStatuses as $st)
                                    <option value="{{ $st }}" @selected($st === 'Active')>{{ $st }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="mobilizerRemarks">Remarks</label>
                            <textarea name="remarks" id="mobilizerRemarks" rows="2" class="form-control"></textarea>
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
    function showMobilizerModal() {
        if (window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('mobilizerModal')).show();
        }
    }

    function openMobilizerModal() {
        var form = document.getElementById('mobilizerForm');
        form.reset();
        form.action = "{{ route('mobilizers.store') }}";
        document.getElementById('mobilizerMethod').value = 'POST';
        document.getElementById('mobilizerModalTitle').textContent = 'Add Mobilizer';
        showMobilizerModal();
    }

    function openMobilizerEdit(btn) {
        var form = document.getElementById('mobilizerForm');
        var d = btn.dataset;
        form.reset();
        form.action = d.url;
        document.getElementById('mobilizerMethod').value = 'PUT';
        document.getElementById('mobilizerModalTitle').textContent = 'Edit Mobilizer';
        document.getElementById('mobilizerFirstName').value = d.firstName || '';
        document.getElementById('mobilizerLastName').value = d.lastName || '';
        document.getElementById('mobilizerContact').value = d.contact || '';
        document.getElementById('mobilizerEmail').value = d.email || '';
        document.getElementById('mobilizerDepartment').value = d.department || '';
        document.getElementById('mobilizerPosition').value = d.position || '';
        document.getElementById('mobilizerSupervisor').value = d.supervisor || '';
        document.getElementById('mobilizerStatus').value = d.status || 'Active';
        document.getElementById('mobilizerRemarks').value = d.remarks || '';
        var branch = document.getElementById('mobilizerBranch');
        if (d.branch && !Array.prototype.some.call(branch.options, function (o) { return o.value === d.branch; })) {
            var opt = document.createElement('option');
            opt.value = d.branch;
            opt.textContent = d.branch;
            branch.appendChild(opt);
        }
        branch.value = d.branch || '';
        showMobilizerModal();
    }
</script>
@endpush
@endif
@endsection
