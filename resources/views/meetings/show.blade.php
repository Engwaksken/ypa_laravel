@extends('layouts.app')

@section('title', e($meeting->meeting_title))

@section('content')
@php
    $permission = app(\App\Services\PermissionService::class);
    $canEdit = $permission->can('meetings_edit');
    $canDelete = $permission->can('meetings_delete');
    $canManage = $permission->can('meetings_manage');
@endphp

<div class="dash-wrap">

    <div class="dash-head">
        <div>
            <h1 class="dash-name">{{ $meeting->meeting_title }}</h1>
            <div class="dash-date">
                {{ $meeting->meeting_type }} &middot; {{ $meeting->meeting_date ? $meeting->meeting_date->format('M d, Y') : '-' }}
                <span class="badge bg-{{ $meeting->status_badge }} ms-2">{{ $meeting->status }}</span>
            </div>
        </div>
        <div class="d-flex gap-2">
            @if($canManage)
                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#inviteModal" onclick="openInviteModal({{ $meeting->id }})">
                    <i class="fas fa-envelope"></i> Invites
                </button>
                <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#attendanceModal" onclick="openAttendanceModal({{ $meeting->id }})">
                    <i class="fas fa-clipboard-check"></i> Attendance
                </button>
                @if($meeting->status !== 'Completed')
                    <button type="button" class="btn btn-outline-secondary" onclick="markComplete({{ $meeting->id }}, this)">
                        <i class="fas fa-check-double" aria-hidden="true"></i> Mark Complete
                    </button>
                @endif
            @endif
            @if($canEdit)
                <a href="{{ route('meetings.edit', $meeting) }}" class="btn btn-outline-secondary">
                    <i class="fas fa-pen" aria-hidden="true"></i> Edit
                </a>
            @endif
            <a href="{{ route('meetings.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="dash-panel">
                <div class="dash-panel-head">
                    <span><i class="fas fa-circle-info"></i> Meeting Information</span>
                </div>
                <div class="dash-panel-body">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <tbody>
                                <tr>
                                    <th class="text-muted">Meeting Type</th>
                                    <td>{{ $meeting->meeting_type }}</td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Date</th>
                                    <td>{{ $meeting->meeting_date ? $meeting->meeting_date->format('M d, Y') : '-' }}</td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Time</th>
                                    <td>{{ $meeting->meeting_time ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Location</th>
                                    <td>{{ $meeting->location ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Status</th>
                                    <td><span class="badge bg-{{ $meeting->status_badge }}">{{ $meeting->status }}</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    @if($meeting->agenda)
                        <hr>
                        <p class="mb-0">{{ $meeting->agenda }}</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="dash-panel">
                <div class="dash-panel-head">
                    <span><i class="fas fa-envelope"></i> Invites ({{ $meeting->invites->count() }})</span>
                </div>
                <div class="dash-panel-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Membership ID</th>
                                    <th>Name</th>
                                    <th>Invited</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($meeting->invites as $invite)
                                    <tr>
                                        <td>{{ $invite->member->membership_id ?? '-' }}</td>
                                        <td>{{ $invite->member->full_name ?? '-' }}</td>
                                        <td>
                                            @if($invite->invited)
                                                <span class="badge bg-success">Yes</span>
                                            @else
                                                <span class="badge bg-secondary">No</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="empty-row">
                                        <td colspan="3"><i class="fas fa-inbox"></i>No invites sent yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="dash-panel mt-4">
                <div class="dash-panel-head">
                    <span><i class="fas fa-clipboard-check"></i> Attendance ({{ $meeting->attendance->where('attended', true)->count() }})</span>
                </div>
                <div class="dash-panel-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Membership ID</th>
                                    <th>Name</th>
                                    <th>Attended</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($meeting->attendance as $row)
                                    <tr>
                                        <td>{{ $row->member->membership_id ?? '-' }}</td>
                                        <td>{{ $row->member->full_name ?? '-' }}</td>
                                        <td>
                                            @if($row->attended)
                                                <span class="badge bg-success">Yes</span>
                                            @else
                                                <span class="badge bg-secondary">No</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="empty-row">
                                        <td colspan="3"><i class="fas fa-inbox"></i>No attendance recorded yet.</td>
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

@if($canManage)
    <!-- Invite Modal -->
    <div class="modal fade" id="inviteModal" tabindex="-1" aria-labelledby="meetingInviteTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="meetingInviteTitle"><i class="fas fa-envelope" aria-hidden="true"></i> Manage Invites</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="inviteModalContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="saveInvitesButton" disabled onclick="saveInviteSelection()">
                        <i class="fas fa-save"></i> Save Invites
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Attendance Modal -->
    <div class="modal fade" id="attendanceModal" tabindex="-1" aria-labelledby="meetingAttendanceTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="meetingAttendanceTitle"><i class="fas fa-clipboard-check" aria-hidden="true"></i> Record Attendance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="attendanceModalContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-success" id="saveAttendanceButton" disabled onclick="saveAttendance()">
                        <i class="fas fa-save"></i> Save Attendance
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif
@endsection

@push('scripts')
<script>
(function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    const token = csrfToken ? csrfToken.getAttribute('content') : '';

    // HTML-escape user-controlled values before they are interpolated into
    // innerHTML strings (member names / membership ids are user-controlled
    // and were previously injected raw -> stored XSS in an admin session).
    function esc(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    let inviteRequest = 0;
    let attendanceRequest = 0;
    window.openInviteModal = function (meetingId) {
        const request = ++inviteRequest;
        document.getElementById('saveInvitesButton').disabled = true;
        document.body.dataset.meetingId = meetingId;
        const content = document.getElementById('inviteModalContent');
        content.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>';

        fetch('{{ route("meetings.ajax.get_invites") }}?id=' + meetingId, {
            headers: { 'Accept': 'application/json' }
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (request !== inviteRequest) return;
            if (!data.success) {
                content.innerHTML = '<div class="alert alert-danger mb-0">' + esc(data.message || 'Failed to load invites.') + '</div>';
                return;
            }
            renderInviteList(content, data.invites || []);
            document.getElementById('saveInvitesButton').disabled = false;
        })
        .catch(function () {
            if (request !== inviteRequest) return;
            content.innerHTML = '<div class="alert alert-danger mb-0">Failed to load invites.</div>';
        });
    };

    function renderInviteList(container, invites) {
        const invitedIds = invites.filter(function (i) { return i.invited; }).map(function (i) { return i.member_id; });
        let html = '<div class="mb-3">';
        html += '<label class="form-label" for="inviteMemberSearch">Search members</label>';
        html += '<input type="text" id="inviteMemberSearch" class="form-control" placeholder="Type to search members...">';
        html += '<div id="inviteSearchResults" class="list-group member-search-results mt-2"></div>';
        html += '</div>';
        html += '<div class="table-responsive"><table class="table table-sm table-hover align-middle mb-0">';
        html += '<thead><tr><th>Membership ID</th><th>Name</th><th>Invited</th></tr></thead><tbody id="inviteTableBody">';
        invites.forEach(function (invite) {
            html += '<tr data-member-id="' + esc(invite.member_id) + '">';
            html += '<td>' + esc(invite.membership_id || '-') + '</td>';
            html += '<td>' + esc(invite.name || '-') + '</td>';
            html += '<td><input type="checkbox" class="form-check-input invite-check" aria-label="Invite ' + esc(invite.name || invite.membership_id || 'member') + '" value="' + esc(invite.member_id) + '"' + (invite.invited ? ' checked' : '') + '></td>';
            html += '</tr>';
        });
        html += '</tbody></table></div>';
        container.innerHTML = html;

        const searchInput = document.getElementById('inviteMemberSearch');
        const resultsBox = document.getElementById('inviteSearchResults');
        const tableBody = document.getElementById('inviteTableBody');

        if (searchInput) {
            searchInput.addEventListener('input', function () {
                const term = searchInput.value.trim();
                if (term.length < 2) {
                    resultsBox.innerHTML = '';
                    return;
                }
                fetch('{{ route("meetings.ajax.search_members") }}?term=' + encodeURIComponent(term), {
                    headers: { 'Accept': 'application/json' }
                })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (searchInput.value.trim() !== term) return;
                    resultsBox.innerHTML = '';
                    (data.members || []).forEach(function (member) {
                        const already = tableBody.querySelector('tr[data-member-id="' + member.id + '"]');
                        if (already) return;
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'list-group-item list-group-item-action';
                        btn.textContent = member.membership_id + ' - ' + member.name;
                        btn.addEventListener('click', function () {
                            const tr = document.createElement('tr');
                            tr.dataset.memberId = member.id;
                            tr.innerHTML = '<td>' + esc(member.membership_id) + '</td><td>' + esc(member.name) + '</td><td><input type="checkbox" class="form-check-input invite-check" aria-label="Invite ' + esc(member.name || member.membership_id || 'member') + '" value="' + esc(member.id) + '" checked></td>';
                            tableBody.appendChild(tr);
                            resultsBox.innerHTML = '';
                            searchInput.value = '';
                        });
                        resultsBox.appendChild(btn);
                    });
                }).catch(function () {
                    resultsBox.textContent = 'Unable to search members. Please try again.';
                });
            });
        }
    }

    window.saveInviteSelection = function () {
        const saveButton = document.getElementById('saveInvitesButton');
        if (saveButton.disabled) return;
        saveButton.disabled = true;
        const meetingId = document.body.dataset.meetingId;
        const memberIds = Array.from(document.querySelectorAll('.invite-check:checked')).map(function (cb) { return cb.value; });

        const formData = new FormData();
        formData.append('meeting_id', meetingId);
        memberIds.forEach(function (id) { formData.append('member_ids[]', id); });

        fetch('{{ route("meetings.ajax.save_invites") }}', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token
            },
            body: formData
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Failed to save invites.');
                saveButton.disabled = false;
            }
        }).catch(function () {
            alert('Unable to save invites. Please check your connection and try again.');
            saveButton.disabled = false;
        });
    };

    window.openAttendanceModal = function (meetingId) {
        const request = ++attendanceRequest;
        document.getElementById('saveAttendanceButton').disabled = true;
        document.body.dataset.meetingId = meetingId;
        const content = document.getElementById('attendanceModalContent');
        content.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-success" role="status"></div></div>';

        fetch('{{ route("meetings.ajax.get_attendance") }}?id=' + meetingId, {
            headers: { 'Accept': 'application/json' }
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (!data.success) {
                if (request !== attendanceRequest) return;
                content.innerHTML = '<div class="alert alert-danger mb-0">' + esc(data.message || 'Failed to load attendance.') + '</div>';
                return;
            }
            if (request !== attendanceRequest) return;
            renderAttendanceList(content, data.attendance || []);
            document.getElementById('saveAttendanceButton').disabled = false;
        })
        .catch(function () {
            if (request !== attendanceRequest) return;
            content.innerHTML = '<div class="alert alert-danger mb-0">Failed to load attendance.</div>';
        });
    };

    function renderAttendanceList(container, attendance) {
        let html = '<div class="mb-3">';
        html += '<label class="form-label" for="attendanceMemberSearch">Search members</label>';
        html += '<input type="text" id="attendanceMemberSearch" class="form-control" placeholder="Type to search members...">';
        html += '<div id="attendanceSearchResults" class="list-group member-search-results mt-2"></div>';
        html += '</div>';
        html += '<div class="table-responsive"><table class="table table-sm table-hover align-middle mb-0">';
        html += '<thead><tr><th>Membership ID</th><th>Name</th><th>Attended</th></tr></thead><tbody id="attendanceTableBody">';
        attendance.forEach(function (row) {
            html += '<tr data-member-id="' + esc(row.member_id) + '">';
            html += '<td>' + esc(row.membership_id || '-') + '</td>';
            html += '<td>' + esc(row.name || '-') + '</td>';
            html += '<td><input type="checkbox" class="form-check-input attend-check" aria-label="Attendance for ' + esc(row.name || row.membership_id || 'member') + '" value="' + esc(row.member_id) + '"' + (row.attended ? ' checked' : '') + '></td>';
            html += '</tr>';
        });
        html += '</tbody></table></div>';
        container.innerHTML = html;

        const searchInput = document.getElementById('attendanceMemberSearch');
        const resultsBox = document.getElementById('attendanceSearchResults');
        const tableBody = document.getElementById('attendanceTableBody');

        if (searchInput) {
            searchInput.addEventListener('input', function () {
                const term = searchInput.value.trim();
                if (term.length < 2) {
                    resultsBox.innerHTML = '';
                    return;
                }
                fetch('{{ route("meetings.ajax.search_members") }}?term=' + encodeURIComponent(term), {
                    headers: { 'Accept': 'application/json' }
                })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (searchInput.value.trim() !== term) return;
                    resultsBox.innerHTML = '';
                    (data.members || []).forEach(function (member) {
                        const already = tableBody.querySelector('tr[data-member-id="' + member.id + '"]');
                        if (already) return;
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'list-group-item list-group-item-action';
                        btn.textContent = member.membership_id + ' - ' + member.name;
                        btn.addEventListener('click', function () {
                            const tr = document.createElement('tr');
                            tr.dataset.memberId = member.id;
                            tr.innerHTML = '<td>' + esc(member.membership_id) + '</td><td>' + esc(member.name) + '</td><td><input type="checkbox" class="form-check-input attend-check" aria-label="Attendance for ' + esc(member.name || member.membership_id || 'member') + '" value="' + esc(member.id) + '" checked></td>';
                            tableBody.appendChild(tr);
                            resultsBox.innerHTML = '';
                            searchInput.value = '';
                        });
                        resultsBox.appendChild(btn);
                    });
                }).catch(function () {
                    resultsBox.textContent = 'Unable to search members. Please try again.';
                });
            });
        }
    }

    window.saveAttendance = function () {
        const saveButton = document.getElementById('saveAttendanceButton');
        if (saveButton.disabled) return;
        saveButton.disabled = true;
        const meetingId = document.body.dataset.meetingId;
        const attendees = Array.from(document.querySelectorAll('.attend-check:checked')).map(function (cb) { return cb.value; });

        const formData = new FormData();
        formData.append('meeting_id', meetingId);
        attendees.forEach(function (id) { formData.append('attendees[]', id); });

        fetch('{{ route("meetings.ajax.save_attendance") }}', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token
            },
            body: formData
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Failed to save attendance.');
                saveButton.disabled = false;
            }
        }).catch(function () {
            alert('Unable to save attendance. Please check your connection and try again.');
            saveButton.disabled = false;
        });
    };

    // Shared confirmation dialog (layouts/partials/confirm-delete). The page is
    // reloaded only after the user closes the dialog following a success.
    let reloadOnClose = false;
    const confirmModalEl = document.getElementById('ypaConfirmDeleteModal');
    if (confirmModalEl) {
        confirmModalEl.addEventListener('hidden.bs.modal', function () {
            if (reloadOnClose) location.reload();
        });
    }

    window.markComplete = function (meetingId, trigger) {
        if (!window.YpaConfirmDelete) return;
        window.YpaConfirmDelete.open({
            title: 'Mark meeting as completed?',
            message: 'This marks the meeting as completed.',
            trigger: trigger || document.activeElement,
            onConfirm: function () {
                const formData = new FormData();
                formData.append('meeting_id', meetingId);

                return fetch('{{ route("meetings.ajax.mark_complete") }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: formData
                })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (!data.success) {
                        // Server stated a definite refusal, so retry is safe.
                        throw { definite: true, message: data.message || 'Failed to mark meeting as completed.' };
                    }
                    reloadOnClose = true;
                    return 'Meeting marked as completed.';
                });
            }
        });
    };
})();
</script>
@endpush
