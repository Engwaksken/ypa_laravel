@extends('layouts.app')

@section('title', e($activity->activity_name))

@section('content')
@php
    $permission = app(\App\Services\PermissionService::class);
    $canEdit = $permission->can('activities_edit');
    $canDelete = $permission->can('activities_delete');
@endphp

<div class="dash-wrap">

    <div class="dash-head">
        <div>
            <h1 class="dash-name">{{ $activity->activity_name }}</h1>
            <div class="dash-date">
                {{ $activity->activity_code }} &middot; {{ $activity->type->type_name ?? 'No type' }}
                <span class="badge bg-{{ $activity->status_badge }} ms-2">{{ $activity->status }}</span>
                @if($activity->is_promotion === 'Yes')
                    <span class="badge bg-info ms-1">Promotion</span>
                @endif
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('activities.participants', $activity) }}" class="btn btn-outline-primary">
                <i class="fas fa-user-group"></i> Participants
            </a>
            @if($canEdit)
                <a href="{{ route('activities.edit', $activity) }}" class="btn btn-outline-secondary">
                    <i class="fas fa-edit"></i> Edit
                </a>
            @endif
            <a href="{{ route('activities.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="dash-panel">
                <div class="dash-panel-head">
                    <span><i class="fas fa-circle-info"></i> Activity Information</span>
                </div>
                <div class="dash-panel-body">
                    <table class="table table-sm mb-0">
                        <tbody>
                            <tr>
                                <th class="text-muted">Activity Code</th>
                                <td>{{ $activity->activity_code }}</td>
                            </tr>
                            <tr>
                                <th class="text-muted">Type</th>
                                <td>{{ $activity->type->type_name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th class="text-muted">Start Date</th>
                                <td>{{ $activity->start_date ? $activity->start_date->format('M d, Y') : '-' }}</td>
                            </tr>
                            <tr>
                                <th class="text-muted">End Date</th>
                                <td>{{ $activity->end_date ? $activity->end_date->format('M d, Y') : '-' }}</td>
                            </tr>
                            <tr>
                                <th class="text-muted">Location</th>
                                <td>{{ $activity->location ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th class="text-muted">Budget</th>
                                <td>{{ $activity->budget !== null ? 'UGX ' . number_format((float) $activity->budget, 0) : '-' }}</td>
                            </tr>
                            <tr>
                                <th class="text-muted">Promotion</th>
                                <td>{{ $activity->is_promotion ?? 'No' }}</td>
                            </tr>
                            <tr>
                                <th class="text-muted">Status</th>
                                <td><span class="badge bg-{{ $activity->status_badge }}">{{ $activity->status }}</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            @if($activity->description)
                <div class="dash-panel">
                    <div class="dash-panel-head">
                        <span><i class="fas fa-align-left"></i> Description</span>
                    </div>
                    <div class="dash-panel-body">
                        <p class="mb-0">{{ $activity->description }}</p>
                    </div>
                </div>
            @endif

            <div class="dash-panel">
                <div class="dash-panel-head">
                    <span><i class="fas fa-user-group"></i> Participants ({{ $activity->participants->count() }})</span>
                    <a href="{{ route('activities.participants', $activity) }}" class="btn btn-sm btn-outline-primary">Manage</a>
                </div>
                <div class="dash-panel-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Membership ID</th>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Attended</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($activity->participants->take(10) as $participant)
                                    <tr>
                                        <td>{{ $participant->member->membership_id ?? '-' }}</td>
                                        <td>{{ $participant->member->full_name ?? 'External participant' }}</td>
                                        <td>{{ $participant->participant_type ?? 'Member' }}</td>
                                        <td>
                                            @if($participant->attended)
                                                <span class="badge bg-success">Yes</span>
                                            @else
                                                <span class="badge bg-secondary">No</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">No participants registered yet.</td>
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
@endsection