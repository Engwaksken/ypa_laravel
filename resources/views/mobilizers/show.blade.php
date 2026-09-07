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
                <form action="{{ route('mobilizers.destroy', $m) }}" method="POST" onsubmit="return confirm('Delete this mobilizer? This cannot be undone.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> Delete</button>
                </form>
            @endif
        </div>
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
        <div class="dash-card">
            <div class="dash-card-top">
                <span class="dash-card-title">Department</span>
                <span class="dash-card-icon"><i class="fas fa-building"></i></span>
            </div>
            <div class="dash-card-value">{{ $m->department ?? '-' }}</div>
        </div>
        <div class="dash-card">
            <div class="dash-card-top">
                <span class="dash-card-title">Position</span>
                <span class="dash-card-icon"><i class="fas fa-briefcase"></i></span>
            </div>
            <div class="dash-card-value">{{ $m->position ?? '-' }}</div>
        </div>
        <div class="dash-card">
            <div class="dash-card-top">
                <span class="dash-card-title">Branch</span>
                <span class="dash-card-icon"><i class="fas fa-location-dot"></i></span>
            </div>
            <div class="dash-card-value">{{ $m->branch_region ?? '-' }}</div>
        </div>
    </div>

    <div class="dash-panel">
        <div class="dash-panel-head">
            <span><i class="fas fa-user"></i> Mobilizer Information</span>
        </div>
        <div class="dash-panel-body">
            <div class="row">
                <div class="col-md-3"><strong>First Name:</strong> {{ $m->first_name }}</div>
                <div class="col-md-3"><strong>Last Name:</strong> {{ $m->last_name }}</div>
                <div class="col-md-3"><strong>Contact Number:</strong> {{ $m->contact_number ?? '-' }}</div>
                <div class="col-md-3"><strong>Email:</strong> {{ $m->email ?? '-' }}</div>
                <div class="col-md-3 mt-3"><strong>Department:</strong> {{ $m->department ?? '-' }}</div>
                <div class="col-md-3 mt-3"><strong>Position:</strong> {{ $m->position ?? '-' }}</div>
                <div class="col-md-3 mt-3"><strong>Supervisor:</strong> {{ $m->supervisor ?? '-' }}</div>
                <div class="col-md-3 mt-3"><strong>Branch:</strong> {{ $m->branch_region ?? '-' }}</div>
                <div class="col-md-3 mt-3"><strong>Status:</strong> {{ $m->status ?? '-' }}</div>
                <div class="col-md-9 mt-3"><strong>Remarks:</strong> {{ $m->remarks ?? '-' }}</div>
            </div>
        </div>
    </div>

    <div class="dash-panel mt-4">
        <div class="dash-panel-head">
            <span><i class="fas fa-clock"></i> Record Info</span>
        </div>
        <div class="dash-panel-body">
            <div class="row">
                <div class="col-md-3"><strong>Created:</strong> {{ $m->created_at ? $m->created_at->format('d M Y H:i') : '-' }}</div>
                <div class="col-md-3"><strong>Updated:</strong> {{ $m->updated_at ? $m->updated_at->format('d M Y H:i') : '-' }}</div>
            </div>
        </div>
    </div>

</div>
@endsection