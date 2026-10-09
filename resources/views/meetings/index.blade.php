@extends('layouts.app')

@section('title', 'Meetings')

@section('content')
@php
    $permission = app(\App\Services\PermissionService::class);
    $canCreate = $permission->can('meetings_create');
    $canEdit = $permission->can('meetings_edit');
    $canDelete = $permission->can('meetings_delete');
@endphp

<div class="dash-wrap">

    <div class="dash-head">
        <div>
            <h1 class="dash-name">Meetings</h1>
            <div class="dash-date">{{ number_format($kpi['total']) }} meeting(s)</div>
        </div>
        @if($canCreate)
            <button type="button" class="btn btn-primary" onclick="openMeetingModal()">
                <i class="fas fa-calendar-plus"></i> Add Meeting
            </button>
        @endif
    </div>

    <div class="dash-grid d-grid-5 mb-4">
        <div class="dash-card accent-primary">
            <div class="dash-card-top">
                <span class="dash-card-title">Total</span>
                <span class="dash-card-icon"><i class="fas fa-handshake"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format($kpi['total']) }}</div>
        </div>
        <div class="dash-card accent-info">
            <div class="dash-card-top">
                <span class="dash-card-title">Scheduled</span>
                <span class="dash-card-icon"><i class="fas fa-clock"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format($kpi['scheduled']) }}</div>
        </div>
        <div class="dash-card accent-warning">
            <div class="dash-card-top">
                <span class="dash-card-title">Ongoing</span>
                <span class="dash-card-icon"><i class="fas fa-spinner"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format($kpi['ongoing']) }}</div>
        </div>
        <div class="dash-card accent-success">
            <div class="dash-card-top">
                <span class="dash-card-title">Completed</span>
                <span class="dash-card-icon"><i class="fas fa-circle-check"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format($kpi['completed']) }}</div>
        </div>
        <div class="dash-card accent-danger">
            <div class="dash-card-top">
                <span class="dash-card-title">Cancelled</span>
                <span class="dash-card-icon"><i class="fas fa-circle-xmark"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format($kpi['cancelled']) }}</div>
        </div>
    </div>

    <div class="dash-panel">
        <div class="dash-panel-head">
            <span><i class="fas fa-filter"></i> Filter Meetings</span>
        </div>
        <div class="dash-panel-body">
            <form method="GET" action="{{ route('meetings.index') }}" class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Title, location, agenda...">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        @foreach(\App\Services\MeetingService::STATUSES as $st)
                            <option value="{{ $st }}" @selected($statusFilter === $st)>{{ $st }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
                    <a href="{{ route('meetings.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="dash-panel mt-4">
        <div class="dash-panel-head">
            <span><i class="fas fa-list"></i> Meeting List</span>
            <span class="text-muted small">{{ $meetings->total() }} result(s)</span>
        </div>
        <div class="dash-panel-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($meetings as $meeting)
                            @php $meetingTime = $meeting->meeting_time ? substr((string) $meeting->meeting_time, 0, 5) : ''; @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('meetings.show', $meeting) }}" class="fw-semibold text-decoration-none">{{ $meeting->meeting_title }}</a>
                                    @if($meeting->chaired_by)
                                        <div class="small text-muted">Chair: {{ $meeting->chaired_by }}</div>
                                    @endif
                                </td>
                                <td>{{ $meeting->meeting_type }}</td>
                                <td>{{ $meeting->meeting_date ? $meeting->meeting_date->format('M d, Y') : '-' }}</td>
                                <td>{{ $meetingTime ?: '-' }}</td>
                                <td>{{ $meeting->location ?? '-' }}</td>
                                <td><span class="badge bg-{{ $meeting->status_badge }}">{{ $meeting->status ?? '-' }}</span></td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('meetings.show', $meeting) }}" class="btn btn-sm btn-outline-primary" title="View" aria-label="View meeting {{ $meeting->meeting_title }}"><i class="fas fa-eye" aria-hidden="true"></i></a>
                                    @if($canEdit)
                                        <button type="button" class="btn btn-sm btn-outline-secondary" title="Edit" aria-label="Edit meeting {{ $meeting->meeting_title }}"
                                            data-url="{{ route('meetings.update', $meeting) }}"
                                            data-type="{{ $meeting->meeting_type }}"
                                            data-title="{{ $meeting->meeting_title }}"
                                            data-date="{{ $meeting->meeting_date ? $meeting->meeting_date->format('Y-m-d') : '' }}"
                                            data-time="{{ $meetingTime }}"
                                            data-location="{{ $meeting->location ?? '' }}"
                                            data-chair="{{ $meeting->chaired_by ?? '' }}"
                                            data-agenda="{{ $meeting->agenda ?? '' }}"
                                            data-notes="{{ $meeting->notes ?? '' }}"
                                            data-status="{{ $meeting->status }}"
                                            onclick="openMeetingEdit(this)">
                                            <i class="fas fa-pen" aria-hidden="true"></i>
                                        </button>
                                    @endif
                                    @if($canDelete)
                                        <form action="{{ route('meetings.destroy', $meeting) }}" method="POST" class="d-inline ypa-confirm-delete" data-confirm-title="Delete meeting?" data-confirm-message="Delete {{ $meeting->meeting_title }}? This cannot be undone.">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete" aria-label="Delete meeting {{ $meeting->meeting_title }}"><i class="fas fa-trash" aria-hidden="true"></i></button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr class="empty-row">
                                <td colspan="7"><i class="fas fa-inbox"></i>No meetings found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="dash-panel-foot">{{ $meetings->links() }}</div>
    </div>

</div>

@if($canCreate || $canEdit)
<div class="modal fade" id="meetingModal" tabindex="-1" aria-labelledby="meetingModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="meetingForm" method="POST" action="{{ route('meetings.store') }}">
                @csrf
                <input type="hidden" name="_method" id="meetingMethod" value="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="meetingModalTitle">Add Meeting</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="modal-section-title">Meeting Details</div>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label" for="meetingTitle">Meeting Title <span class="text-danger">*</span></label>
                            <input type="text" name="meeting_title" id="meetingTitle" class="form-control" maxlength="255" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="meetingType">Meeting Type <span class="text-danger">*</span></label>
                            <select name="meeting_type" id="meetingType" class="form-select" required>
                                @foreach(\App\Services\MeetingService::MEETING_TYPES as $mt)
                                    <option value="{{ $mt }}">{{ $mt }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="meetingDate">Date <span class="text-danger">*</span></label>
                            <input type="date" name="meeting_date" id="meetingDate" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="meetingTime">Time</label>
                            <input type="time" name="meeting_time" id="meetingTime" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="meetingStatus">Status <span class="text-danger">*</span></label>
                            <select name="status" id="meetingStatus" class="form-select" required>
                                @foreach(\App\Services\MeetingService::STATUSES as $st)
                                    <option value="{{ $st }}" @selected($st === 'Scheduled')>{{ $st }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="meetingLocation">Location</label>
                            <input type="text" name="location" id="meetingLocation" class="form-control" maxlength="255" placeholder="Venue / town">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="meetingChair">Chaired By</label>
                            <input type="text" name="chaired_by" id="meetingChair" class="form-control" maxlength="150" placeholder="Chairperson name">
                        </div>
                    </div>
                    <div class="modal-section-title mt-4">Agenda &amp; Notes</div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" for="meetingAgenda">Agenda</label>
                            <textarea name="agenda" id="meetingAgenda" rows="3" class="form-control"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="meetingNotes">Notes</label>
                            <textarea name="notes" id="meetingNotes" rows="2" class="form-control"></textarea>
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
    function showMeetingModal() {
        if (window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('meetingModal')).show();
        }
    }

    function openMeetingModal() {
        var form = document.getElementById('meetingForm');
        form.reset();
        form.action = "{{ route('meetings.store') }}";
        document.getElementById('meetingMethod').value = 'POST';
        document.getElementById('meetingModalTitle').textContent = 'Add Meeting';
        showMeetingModal();
    }

    function openMeetingEdit(btn) {
        var form = document.getElementById('meetingForm');
        var d = btn.dataset;
        form.reset();
        form.action = d.url;
        document.getElementById('meetingMethod').value = 'PUT';
        document.getElementById('meetingModalTitle').textContent = 'Edit Meeting';
        document.getElementById('meetingTitle').value = d.title || '';
        document.getElementById('meetingType').value = d.type || 'Monthly';
        document.getElementById('meetingDate').value = d.date || '';
        document.getElementById('meetingTime').value = d.time || '';
        document.getElementById('meetingStatus').value = d.status || 'Scheduled';
        document.getElementById('meetingLocation').value = d.location || '';
        document.getElementById('meetingChair').value = d.chair || '';
        document.getElementById('meetingAgenda').value = d.agenda || '';
        document.getElementById('meetingNotes').value = d.notes || '';
        showMeetingModal();
    }
</script>
@endpush
@endif
@endsection
