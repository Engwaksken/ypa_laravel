@extends('layouts.app')

@section('title', 'Activities')

@section('content')
@php
    $permission = app(\App\Services\PermissionService::class);
    $canCreate = $permission->can('activities_create');
    $canEdit = $permission->can('activities_edit');
    $canDelete = $permission->can('activities_delete');
    $canExport = $permission->can('activities_export');
@endphp

<div class="dash-wrap">

    <div class="dash-head">
        <div>
            <h1 class="dash-name">Activities</h1>
            <div class="dash-date">{{ number_format($kpi['total']) }} activity/activities</div>
        </div>
        <div class="d-flex gap-2">
            @if($canExport)
                <a href="{{ route('activities.export', request()->query()) }}" class="btn btn-outline-secondary">
                    <i class="fas fa-file-csv"></i> Export CSV
                </a>
            @endif
            @if($canCreate)
                <a href="{{ route('activities.create') }}" class="btn btn-primary">
                    <i class="fas fa-calendar-plus"></i> New Activity
                </a>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="dash-grid mb-4">
        <div class="dash-card">
            <div class="dash-card-top">
                <span class="dash-card-title">Total Activities</span>
                <span class="dash-card-icon"><i class="fas fa-calendar"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format($kpi['total']) }}</div>
        </div>
        <div class="dash-card">
            <div class="dash-card-top">
                <span class="dash-card-title">Planned</span>
                <span class="dash-card-icon"><i class="fas fa-clock"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format($kpi['planned']) }}</div>
        </div>
        <div class="dash-card">
            <div class="dash-card-top">
                <span class="dash-card-title">Ongoing</span>
                <span class="dash-card-icon"><i class="fas fa-spinner"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format($kpi['ongoing']) }}</div>
        </div>
        <div class="dash-card">
            <div class="dash-card-top">
                <span class="dash-card-title">Completed</span>
                <span class="dash-card-icon"><i class="fas fa-circle-check"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format($kpi['completed']) }}</div>
        </div>
        <div class="dash-card">
            <div class="dash-card-top">
                <span class="dash-card-title">Cancelled</span>
                <span class="dash-card-icon"><i class="fas fa-circle-xmark"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format($kpi['cancelled']) }}</div>
        </div>
    </div>

    <div class="dash-panel">
        <div class="dash-panel-head">
            <span><i class="fas fa-filter"></i> Filter Activities</span>
        </div>
        <div class="dash-panel-body">
            <form method="GET" action="{{ route('activities.index') }}" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Code, name, location, type...">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        @foreach(\App\Services\ActivityService::STATUSES as $st)
                            <option value="{{ $st }}" @selected($statusFilter === $st)>{{ $st }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select">
                        <option value="">All Types</option>
                        @foreach($types as $type)
                            <option value="{{ $type->id }}" @selected($typeFilter == $type->id)>{{ $type->type_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
                    <a href="{{ route('activities.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="dash-panel mt-4">
        <div class="dash-panel-head">
            <span><i class="fas fa-list"></i> Activity List</span>
            <span class="text-muted small">{{ $activities->total() }} result(s)</span>
        </div>
        <div class="dash-panel-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Activity Name</th>
                            <th>Type</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($activities as $activity)
                            <tr>
                                <td><strong>{{ $activity->activity_code }}</strong></td>
                                <td>{{ $activity->activity_name }}</td>
                                <td>{{ $activity->type->type_name ?? '-' }}</td>
                                <td>{{ $activity->start_date ? $activity->start_date->format('M d, Y') : '-' }}</td>
                                <td>{{ $activity->end_date ? $activity->end_date->format('M d, Y') : '-' }}</td>
                                <td>{{ $activity->location ?? '-' }}</td>
                                <td>
                                    <span class="badge bg-{{ $activity->status_badge }}">{{ $activity->status ?? '-' }}</span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('activities.show', $activity) }}" class="btn btn-sm btn-outline-primary" title="View"><i class="fas fa-eye"></i></a>
                                    @if($canEdit)
                                        <a href="{{ route('activities.edit', $activity) }}" class="btn btn-sm btn-outline-secondary" title="Edit"><i class="fas fa-edit"></i></a>
                                    @endif
                                    @if($canDelete)
                                        <form action="{{ route('activities.destroy', $activity) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this activity? This cannot be undone.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No activities found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="dash-panel-body">
            {{ $activities->links() }}
        </div>
    </div>

</div>
@endsection