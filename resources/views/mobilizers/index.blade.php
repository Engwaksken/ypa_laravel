@extends('layouts.app')

@section('title', 'Mobilizers')

@section('content')
@php
    $permission = app(\App\Services\PermissionService::class);
    $canCreate = $permission->can('mobilizers_create');
    $canEdit = $permission->can('mobilizers_edit');
    $canDelete = $permission->can('mobilizers_delete');
@endphp

<div class="dash-wrap">

    <div class="dash-head">
        <div>
            <h1 class="dash-name">Mobilizers</h1>
            <div class="dash-date">{{ number_format($stats['total']) }} mobilizer(s)</div>
        </div>
        @if($canCreate)
            <a href="{{ route('mobilizers.create') }}" class="btn btn-primary">
                <i class="fas fa-user-plus"></i> Add Mobilizer
            </a>
        @endif
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="dash-grid mb-4">
        <div class="dash-card">
            <div class="dash-card-top">
                <span class="dash-card-title">Total</span>
                <span class="dash-card-icon"><i class="fas fa-users-cog"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format($stats['total']) }}</div>
        </div>
        <div class="dash-card">
            <div class="dash-card-top">
                <span class="dash-card-title">Active</span>
                <span class="dash-card-icon"><i class="fas fa-user-check"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format($stats['active']) }}</div>
        </div>
        <div class="dash-card">
            <div class="dash-card-top">
                <span class="dash-card-title">Inactive</span>
                <span class="dash-card-icon"><i class="fas fa-user-slash"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format($stats['inactive']) }}</div>
        </div>
        <div class="dash-card">
            <div class="dash-card-top">
                <span class="dash-card-title">Suspended</span>
                <span class="dash-card-icon"><i class="fas fa-user-xmark"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format($stats['suspended']) }}</div>
        </div>
        <div class="dash-card">
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
                        @foreach(['Active', 'Inactive', 'Suspended', 'Terminated'] as $st)
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
            <span class="text-muted small">{{ count($mobilizers) }} result(s)</span>
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
                            <tr>
                                <td><strong>{{ $mobilizer->full_name }}</strong></td>
                                <td>{{ $mobilizer->contact_number ?? '-' }}</td>
                                <td>{{ $mobilizer->email ?? '-' }}</td>
                                <td>{{ $mobilizer->department ?? '-' }}</td>
                                <td>{{ $mobilizer->position ?? '-' }}</td>
                                <td>{{ $mobilizer->branch_region ?? '-' }}</td>
                                <td>
                                    @php
                                        $badge = match($mobilizer->status) {
                                            'Active' => 'success',
                                            'Inactive' => 'secondary',
                                            'Suspended' => 'warning',
                                            'Terminated' => 'danger',
                                            default => 'secondary',
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $badge }}">{{ $mobilizer->status ?? '-' }}</span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('mobilizers.show', $mobilizer) }}" class="btn btn-sm btn-outline-primary" title="View"><i class="fas fa-eye"></i></a>
                                    @if($canEdit)
                                        <a href="{{ route('mobilizers.edit', $mobilizer) }}" class="btn btn-sm btn-outline-secondary" title="Edit"><i class="fas fa-edit"></i></a>
                                    @endif
                                    @if($canDelete)
                                        <form action="{{ route('mobilizers.destroy', $mobilizer) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this mobilizer? This cannot be undone.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No mobilizers found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection