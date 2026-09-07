@extends('layouts.app')

@section('title', 'Activity Participants')

@section('content')
@php
    $permission = app(\App\Services\PermissionService::class);
    $canEdit = $permission->can('activities_edit');
@endphp

<div class="dash-wrap">

    <div class="dash-head">
        <div>
            <h1 class="dash-name">Activity Participants</h1>
            <div class="dash-date">{{ $activity->activity_name }} &middot; {{ $activity->activity_code }}</div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('activities.show', $activity) }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Activity
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Please fix the following errors:</strong>
            <ul class="mb-0 mt-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($canEdit)
        <div class="dash-panel mb-4">
            <div class="dash-panel-head">
                <span><i class="fas fa-user-plus"></i> Register Participant</span>
            </div>
            <div class="dash-panel-body">
                <form method="POST" action="{{ route('activities.participants.store', $activity) }}" class="row g-3">
                    @csrf
                    <div class="col-md-6">
                        <label class="form-label">Member <span class="text-danger">*</span></label>
                        <select name="member_id" class="form-select" required>
                            <option value="">Select Member</option>
                            @foreach(\App\Models\Member::query()->orderByDesc('id')->limit(500)->get() as $member)
                                <option value="{{ $member->id }}">{{ $member->membership_id }} - {{ $member->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Participant Type</label>
                        <select name="participant_type" class="form-select">
                            @foreach(\App\Services\ActivityService::PARTICIPANT_TYPES as $pt)
                                <option value="{{ $pt }}">{{ $pt }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100"><i class="fas fa-plus"></i> Add</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <div class="dash-panel">
        <div class="dash-panel-head">
            <span><i class="fas fa-user-group"></i> Participant List</span>
            <span class="text-muted small">{{ $participants->total() }} result(s)</span>
        </div>
        <div class="dash-panel-body">
            <form method="GET" action="{{ route('activities.participants', $activity) }}" class="row g-3">
                <div class="col-md-8">
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Search by name, membership ID or phone...">
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
                    <a href="{{ route('activities.participants', $activity) }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
        <div class="dash-panel-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Membership ID</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Type</th>
                            <th>Attended</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($participants as $participant)
                            <tr>
                                <td>{{ $participant->member->membership_id ?? '-' }}</td>
                                <td>{{ $participant->member->full_name ?? 'External participant' }}</td>
                                <td>{{ $participant->member->telephone1 ?? '-' }}</td>
                                <td>{{ $participant->participant_type ?? 'Member' }}</td>
                                <td>
                                    @if($participant->attended)
                                        <span class="badge bg-success">Yes</span>
                                    @else
                                        <span class="badge bg-secondary">No</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if($canEdit)
                                        <form action="{{ route('activities.participants.attendance', [$activity, $participant]) }}" method="POST" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="attended" value="{{ $participant->attended ? 0 : 1 }}">
                                            <button type="submit" class="btn btn-sm btn-outline-{{ $participant->attended ? 'warning' : 'success' }}" title="{{ $participant->attended ? 'Mark not attended' : 'Mark attended' }}">
                                                <i class="fas fa-{{ $participant->attended ? 'user-slash' : 'user-check' }}"></i>
                                            </button>
                                        </form>
                                        <form action="{{ route('activities.participants.destroy', [$activity, $participant]) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this participant?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Remove"><i class="fas fa-trash"></i></button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No participants found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="dash-panel-body">
            {{ $participants->links() }}
        </div>
    </div>

</div>
@endsection