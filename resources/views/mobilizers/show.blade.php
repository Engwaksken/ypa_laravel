@extends('layouts.app')

@section('title', 'Mobilizer Details')

@section('content')
@php
    $permission = app(\App\Services\PermissionService::class);
    $canEdit = $permission->can('mobilizers_edit');
    $canDelete = $permission->can('mobilizers_delete');
    $m = $mobilizer;
@endphp

<div class="dash-wrap">

    <div class="dash-head">
        <div>
            <h1 class="dash-name">{{ $m->full_name }}</h1>
            <div class="dash-date">Mobilizer details</div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('mobilizers.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Mobilizers
            </a>
            @if($canEdit)
                <a href="{{ route('mobilizers.edit', $m) }}" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Edit Mobilizer
                </a>
            @endif
            @if($canDelete)
                <form action="{{ route('mobilizers.destroy', $m) }}" method="POST" class="ypa-confirm-delete" data-confirm-title="Delete mobilizer?" data-confirm-message="Delete {{ $m->full_name }}? This cannot be undone.">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger"><i class="fas fa-trash"></i> Delete</button>
                </form>
            @endif
        </div>
    </div>

    <div class="dash-grid mb-4">
        <div class="dash-card accent-success">
            <div class="dash-card-top">
                <span class="dash-card-title">Status</span>
                <span class="dash-card-icon"><i class="fas fa-user-shield"></i></span>
            </div>
            <div class="dash-card-value">
                @php
                    $badge = match($m->status) {
                        'Active' => 'success',
                        'Inactive' => 'secondary',
                        'Suspended' => 'warning',
                        'Terminated' => 'danger',
                        default => 'secondary',
                    };
                @endphp
                <span class="badge bg-{{ $badge }}">{{ $m->status ?? '-' }}</span>
            </div>
        </div>
        <div class="dash-card accent-primary">
            <div class="dash-card-top">
                <span class="dash-card-title">Department</span>
                <span class="dash-card-icon"><i class="fas fa-building"></i></span>
            </div>
            <div class="dash-card-value">{{ $m->department ?? '-' }}</div>
        </div>
        <div class="dash-card accent-purple">
            <div class="dash-card-top">
                <span class="dash-card-title">Position</span>
                <span class="dash-card-icon"><i class="fas fa-briefcase"></i></span>
            </div>
            <div class="dash-card-value">{{ $m->position ?? '-' }}</div>
        </div>
        <div class="dash-card accent-info">
            <div class="dash-card-top">
                <span class="dash-card-title">Branch</span>
                <span class="dash-card-icon"><i class="fas fa-location-dot"></i></span>
            </div>
            <div class="dash-card-value">{{ $m->branch_region ?? '-' }}</div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="dash-panel">
                <div class="dash-panel-head">
                    <span><i class="fas fa-user"></i> Mobilizer Information</span>
                </div>
                <div class="dash-panel-body">
                    <table class="table table-sm mb-0">
                        <tbody>
                            <tr><th class="text-muted" style="width: 35%">First Name</th><td>{{ $m->first_name }}</td></tr>
                            <tr><th class="text-muted">Last Name</th><td>{{ $m->last_name }}</td></tr>
                            <tr><th class="text-muted">Contact Number</th><td>{{ $m->contact_number ?? '-' }}</td></tr>
                            <tr><th class="text-muted">Email</th><td>{{ $m->email ?? '-' }}</td></tr>
                            <tr><th class="text-muted">Department</th><td>{{ $m->department ?? '-' }}</td></tr>
                            <tr><th class="text-muted">Position</th><td>{{ $m->position ?? '-' }}</td></tr>
                            <tr><th class="text-muted">Supervisor</th><td>{{ $m->supervisor ?? '-' }}</td></tr>
                            <tr><th class="text-muted">Branch / Region</th><td>{{ $m->branch_region ?? '-' }}</td></tr>
                            <tr><th class="text-muted">Remarks</th><td>{{ $m->remarks ?? '-' }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="dash-panel">
                <div class="dash-panel-head">
                    <span><i class="fas fa-clock"></i> Record Info</span>
                </div>
                <div class="dash-panel-body">
                    <table class="table table-sm mb-0">
                        <tbody>
                            <tr><th class="text-muted">Created</th><td>{{ $m->created_at ? $m->created_at->format('d M Y H:i') : '-' }}</td></tr>
                            <tr><th class="text-muted">Updated</th><td>{{ $m->updated_at ? $m->updated_at->format('d M Y H:i') : '-' }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection