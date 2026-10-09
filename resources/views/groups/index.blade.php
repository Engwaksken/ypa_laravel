@extends('layouts.app')

@section('title', 'Groups')

@section('content')
@php
    $permission = app(\App\Services\PermissionService::class);
    $canCreate = $permission->can('groups_create');
    $canEdit = $permission->can('groups_edit');
    $canDelete = $permission->can('groups_delete');
    $canExport = $permission->can('groups_export');
@endphp

<div class="dash-wrap">

    <div class="dash-head">
        <div>
            <h1 class="dash-name">Groups</h1>
            <div class="dash-date">{{ number_format($kpi['total']) }} registered group(s)</div>
        </div>
        <div class="d-flex gap-2">
            @if($canExport)
                <a href="{{ route('groups.export', request()->query()) }}" class="btn btn-outline-secondary">
                    <i class="fas fa-file-csv"></i> Export CSV
                </a>
            @endif
            @if($canCreate)
                <button type="button" class="btn btn-primary" onclick="openGroupModal()">
                    <i class="fas fa-plus"></i> Register Group
                </button>
            @endif
        </div>
    </div>

    <div class="dash-grid d-grid-5 mb-4">
        <div class="dash-card accent-primary">
            <div class="dash-card-top">
                <span class="dash-card-title">Total Groups</span>
                <span class="dash-card-icon"><i class="fas fa-people-group"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format($kpi['total']) }}</div>
        </div>
        <div class="dash-card accent-success">
            <div class="dash-card-top">
                <span class="dash-card-title">Active</span>
                <span class="dash-card-icon"><i class="fas fa-circle-check"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format($kpi['active']) }}</div>
        </div>
        <div class="dash-card accent-muted">
            <div class="dash-card-top">
                <span class="dash-card-title">Inactive</span>
                <span class="dash-card-icon"><i class="fas fa-circle-pause"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format($kpi['inactive']) }}</div>
        </div>
        <div class="dash-card accent-warning">
            <div class="dash-card-top">
                <span class="dash-card-title">Suspended</span>
                <span class="dash-card-icon"><i class="fas fa-ban"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format($kpi['suspended']) }}</div>
        </div>
        <div class="dash-card accent-danger">
            <div class="dash-card-top">
                <span class="dash-card-title">Dissolved</span>
                <span class="dash-card-icon"><i class="fas fa-circle-xmark"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format($kpi['dissolved']) }}</div>
        </div>
    </div>

    <div class="dash-panel">
        <div class="dash-panel-head">
            <span><i class="fas fa-filter"></i> Filter Groups</span>
        </div>
        <div class="dash-panel-body">
            <form method="GET" action="{{ route('groups.index') }}" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Code, name, district, branch...">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        @foreach(['Active', 'Inactive', 'Suspended', 'Dissolved'] as $st)
                            <option value="{{ $st }}" @selected($statusFilter === $st)>{{ $st }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select">
                        <option value="">All Categories</option>
                        @foreach(\App\Services\GroupService::CATEGORIES as $cat)
                            <option value="{{ $cat }}" @selected($categoryFilter === $cat)>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
                    <a href="{{ route('groups.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="dash-panel mt-4">
        <div class="dash-panel-head">
            <span><i class="fas fa-list"></i> Group List</span>
            <span class="text-muted small">{{ $groups->total() }} result(s)</span>
        </div>
        <div class="dash-panel-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Group Code</th>
                            <th>Group Name</th>
                            <th>Category</th>
                            <th>Branch</th>
                            <th>District</th>
                            <th>Formation Date</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($groups as $group)
                            <tr>
                                <td><strong>{{ $group->group_code }}</strong></td>
                                <td><a href="{{ route('groups.show', $group) }}" class="text-decoration-none">{{ $group->group_name }}</a></td>
                                <td>{{ $group->group_category }}</td>
                                <td>{{ $group->branch->name ?? '-' }}</td>
                                <td>{{ $group->uganda_district ?? $group->country ?? '-' }}</td>
                                <td>{{ $group->formation_date ? $group->formation_date->format('M d, Y') : '-' }}</td>
                                <td>
                                    <span class="badge bg-{{ $group->status_badge }}">{{ $group->status ?? '-' }}</span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('groups.show', $group) }}" class="btn btn-sm btn-outline-primary" title="View"><i class="fas fa-eye"></i></a>
                                    @if($canEdit)
                                        <button type="button" class="btn btn-sm btn-outline-secondary" title="Edit"
                                            data-url="{{ route('groups.update', $group) }}"
                                            data-record='@json($groupRecords[$group->id] ?? [])'
                                            onclick="openGroupEdit(this)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    @endif
                                    @if($canDelete)
                                        <form action="{{ route('groups.destroy', $group) }}" method="POST" class="d-inline ypa-confirm-delete" data-confirm-title="Delete group?" data-confirm-message="Delete {{ $group->group_name }} ({{ $group->group_code }})? This cannot be undone.">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr class="empty-row">
                                <td colspan="8"><i class="fas fa-inbox"></i>No groups found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="dash-panel-foot">
            {{ $groups->links() }}
        </div>
    </div>

</div>

@if($canCreate || $canEdit)
    @include('groups._modal_form')
@endif
@endsection