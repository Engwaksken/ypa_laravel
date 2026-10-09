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
            @if($canEdit)
                <button type="button" class="btn btn-primary" onclick="openParticipantModal()">
                    <i class="fas fa-user-plus"></i> Add Participant
                </button>
            @endif
        </div>
    </div>

    <div class="dash-grid mb-4">
        <div class="dash-card accent-primary">
            <div class="dash-card-top">
                <span class="dash-card-title">Registered</span>
                <span class="dash-card-icon"><i class="fas fa-user-group"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format($stats['total']) }}</div>
        </div>
        <div class="dash-card accent-success">
            <div class="dash-card-top">
                <span class="dash-card-title">Attended</span>
                <span class="dash-card-icon"><i class="fas fa-user-check"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format($stats['attended']) }}</div>
        </div>
        <div class="dash-card accent-muted">
            <div class="dash-card-top">
                <span class="dash-card-title">Not Attended</span>
                <span class="dash-card-icon"><i class="fas fa-user-slash"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format($stats['not_attended']) }}</div>
        </div>
        <div class="dash-card accent-info">
            <div class="dash-card-top">
                <span class="dash-card-title">Attendance Rate</span>
                <span class="dash-card-icon"><i class="fas fa-percent"></i></span>
            </div>
            <div class="dash-card-value">{{ $stats['total'] > 0 ? round($stats['attended'] / $stats['total'] * 100) : 0 }}%</div>
        </div>
    </div>

    <div class="dash-panel">
        <div class="dash-panel-head">
            <span><i class="fas fa-filter"></i> Filter Participants</span>
        </div>
        <div class="dash-panel-body">
            <form method="GET" action="{{ route('activities.participants', $activity) }}" class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Name, membership ID or phone...">
                </div>
                <div class="col-md-4 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
                    <a href="{{ route('activities.participants', $activity) }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="dash-panel mt-4">
        <div class="dash-panel-head">
            <span><i class="fas fa-list"></i> Participant List</span>
            <span class="text-muted small">{{ $participants->total() }} result(s)</span>
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
                                <td><strong>{{ $participant->member->membership_id ?? '-' }}</strong></td>
                                <td>{{ $participant->member->full_name ?? 'External participant' }}</td>
                                <td>{{ $participant->member->telephone1 ?? '-' }}</td>
                                <td><span class="badge bg-light text-dark border">{{ $participant->participant_type ?? 'Member' }}</span></td>
                                <td>
                                    @if($participant->attended)
                                        <span class="badge bg-success">Yes</span>
                                    @else
                                        <span class="badge bg-secondary">No</span>
                                    @endif
                                </td>
                                <td class="text-end text-nowrap">
                                    @if($canEdit)
                                        <form action="{{ route('activities.participants.attendance', [$activity, $participant]) }}" method="POST" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="attended" value="{{ $participant->attended ? 0 : 1 }}">
                                            <button type="submit" class="btn btn-sm btn-outline-{{ $participant->attended ? 'warning' : 'success' }}" title="{{ $participant->attended ? 'Mark not attended' : 'Mark attended' }}">
                                                <i class="fas fa-{{ $participant->attended ? 'user-slash' : 'user-check' }}"></i>
                                            </button>
                                        </form>
                                        <form action="{{ route('activities.participants.destroy', [$activity, $participant]) }}" method="POST" class="d-inline ypa-confirm-delete" data-confirm-title="Remove participant?" data-confirm-message="Remove {{ $participant->member->full_name ?? 'this participant' }} from this activity?">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Remove"><i class="fas fa-trash"></i></button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr class="empty-row">
                                <td colspan="6"><i class="fas fa-inbox"></i>No participants found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="dash-panel-foot">{{ $participants->links() }}</div>
    </div>

</div>

@if($canEdit)
<div class="modal fade" id="participantModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="participantForm" method="POST" action="{{ route('activities.participants.store', $activity) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="participantModalTitle">Add Participant</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Member <span class="text-danger">*</span></label>
                            <select name="member_id" id="participantMember" class="form-select" required>
                                <option value="">Select Member</option>
                                @foreach(\App\Models\Member::query()->orderByDesc('id')->limit(500)->get() as $member)
                                    <option value="{{ $member->id }}">{{ $member->membership_id }} - {{ $member->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Participant Type</label>
                            <select name="participant_type" id="participantType" class="form-select">
                                @foreach(\App\Services\ActivityService::PARTICIPANT_TYPES as $pt)
                                    <option value="{{ $pt }}">{{ $pt }}</option>
                                @endforeach
                            </select>
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
    function openParticipantModal() {
        document.getElementById('participantForm').reset();
        if (window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('participantModal')).show();
        }
    }
</script>
@endpush
@endif
@endsection
