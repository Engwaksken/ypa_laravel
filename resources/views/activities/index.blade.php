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
            <div class="dash-date">{{ number_format($kpi['total']) }} activity record(s)</div>
        </div>
        <div class="d-flex gap-2">
            @if($canExport)
                <a href="{{ route('activities.export', request()->query()) }}" class="btn btn-outline-secondary">
                    <i class="fas fa-file-csv"></i> Export CSV
                </a>
            @endif
            @if($canCreate)
                <button type="button" class="btn btn-primary" onclick="openActivityModal()">
                    <i class="fas fa-calendar-plus"></i> Add Activity
                </button>
            @endif
        </div>
    </div>

    <div class="dash-grid d-grid-5 mb-4">
        <div class="dash-card accent-primary">
            <div class="dash-card-top">
                <span class="dash-card-title">Total</span>
                <span class="dash-card-icon"><i class="fas fa-calendar"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format($kpi['total']) }}</div>
        </div>
        <div class="dash-card accent-info">
            <div class="dash-card-top">
                <span class="dash-card-title">Planned</span>
                <span class="dash-card-icon"><i class="fas fa-clock"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format($kpi['planned']) }}</div>
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
            <span><i class="fas fa-filter"></i> Filter Activities</span>
        </div>
        <div class="dash-panel-body">
            <form method="GET" action="{{ route('activities.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Code, name, location, type...">
                </div>
                <div class="col-md-2">
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
                <div class="col-md-2">
                    <label class="form-label">Promotion</label>
                    <select name="is_promotion" class="form-select">
                        <option value="">All</option>
                        @foreach(\App\Services\ActivityService::IS_PROMOTION as $opt)
                            <option value="{{ $opt }}" @selected($promotionFilter === $opt)>{{ $opt }}</option>
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
                            <th>Activity</th>
                            <th>Type</th>
                            <th>Dates</th>
                            <th>Location</th>
                            <th class="num">Budget (UGX)</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($activities as $activity)
                            <tr>
                                <td><strong>{{ $activity->activity_code }}</strong></td>
                                <td>
                                    <a href="{{ route('activities.show', $activity) }}" class="fw-semibold text-decoration-none">{{ $activity->activity_name }}</a>
                                    @if($activity->is_promotion === 'Yes')
                                        <span class="badge bg-info ms-1">Promotion</span>
                                    @endif
                                </td>
                                <td>{{ $activity->type->type_name ?? '-' }}</td>
                                <td class="text-nowrap">
                                    {{ $activity->start_date ? $activity->start_date->format('M d, Y') : '-' }}
                                    @if($activity->end_date)
                                        <div class="small text-muted">to {{ $activity->end_date->format('M d, Y') }}</div>
                                    @endif
                                </td>
                                <td>{{ $activity->location ?? '-' }}</td>
                                <td class="num">{{ $activity->budget !== null ? number_format((float) $activity->budget) : '-' }}</td>
                                <td><span class="badge bg-{{ $activity->status_badge }}">{{ $activity->status ?? '-' }}</span></td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('activities.show', $activity) }}" class="btn btn-sm btn-outline-primary" title="View"><i class="fas fa-eye"></i></a>
                                    <a href="{{ route('activities.participants', $activity) }}" class="btn btn-sm btn-outline-info" title="Participants"><i class="fas fa-users"></i></a>
                                    @if($canEdit)
                                        <button type="button" class="btn btn-sm btn-outline-secondary" title="Edit"
                                            data-url="{{ route('activities.update', $activity) }}"
                                            data-name="{{ $activity->activity_name }}"
                                            data-type="{{ $activity->activity_type_id }}"
                                            data-description="{{ $activity->description ?? '' }}"
                                            data-start="{{ $activity->start_date ? $activity->start_date->format('Y-m-d') : '' }}"
                                            data-end="{{ $activity->end_date ? $activity->end_date->format('Y-m-d') : '' }}"
                                            data-location="{{ $activity->location ?? '' }}"
                                            data-budget="{{ $activity->budget !== null ? 0 + $activity->budget : '' }}"
                                            data-promotion="{{ $activity->is_promotion ?? 'No' }}"
                                            data-status="{{ $activity->status ?? '' }}"
                                            onclick="openActivityEdit(this)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    @endif
                                    @if($canDelete)
                                        <form action="{{ route('activities.destroy', $activity) }}" method="POST" class="d-inline ypa-confirm-delete" data-confirm-title="Delete activity?" data-confirm-message="Delete {{ $activity->activity_name }}? This cannot be undone.">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr class="empty-row">
                                <td colspan="8"><i class="fas fa-inbox"></i>No activities found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="dash-panel-foot">{{ $activities->links() }}</div>
    </div>

</div>

@if($canCreate || $canEdit)
<div class="modal fade" id="activityModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="activityForm" method="POST" action="{{ route('activities.store') }}">
                @csrf
                <input type="hidden" name="_method" id="activityMethod" value="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="activityModalTitle">Add Activity</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="modal-section-title">Activity Details</div>
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label">Activity Name <span class="text-danger">*</span></label>
                            <input type="text" name="activity_name" id="activityName" class="form-control" maxlength="255" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Activity Type <span class="text-danger">*</span></label>
                            <select name="activity_type_id" id="activityType" class="form-select" required>
                                <option value="">Select Type</option>
                                @foreach($types as $type)
                                    <option value="{{ $type->id }}">{{ $type->type_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" id="activityDescription" rows="3" class="form-control"></textarea>
                        </div>
                    </div>
                    <div class="modal-section-title mt-4">Schedule &amp; Budget</div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" id="activityStart" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">End Date</label>
                            <input type="date" name="end_date" id="activityEnd" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Budget (UGX)</label>
                            <input type="number" name="budget" id="activityBudget" class="form-control" min="0" step="any" placeholder="e.g. 1500000">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Location</label>
                            <input type="text" name="location" id="activityLocation" class="form-control" maxlength="255" placeholder="Venue / town">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Promotion</label>
                            <select name="is_promotion" id="activityPromotion" class="form-select">
                                @foreach(\App\Services\ActivityService::IS_PROMOTION as $opt)
                                    <option value="{{ $opt }}" @selected($opt === 'No')>{{ $opt }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="status" id="activityStatus" class="form-select">
                                @foreach(\App\Services\ActivityService::STATUSES as $st)
                                    <option value="{{ $st }}" @selected($st === 'Planned')>{{ $st }}</option>
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
    function showActivityModal() {
        if (window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('activityModal')).show();
        }
    }

    function openActivityModal() {
        var form = document.getElementById('activityForm');
        form.reset();
        form.action = "{{ route('activities.store') }}";
        document.getElementById('activityMethod').value = 'POST';
        document.getElementById('activityModalTitle').textContent = 'Add Activity';
        showActivityModal();
    }

    function openActivityEdit(btn) {
        var form = document.getElementById('activityForm');
        var d = btn.dataset;
        form.reset();
        form.action = d.url;
        document.getElementById('activityMethod').value = 'PUT';
        document.getElementById('activityModalTitle').textContent = 'Edit Activity';
        document.getElementById('activityName').value = d.name || '';
        document.getElementById('activityType').value = d.type || '';
        document.getElementById('activityDescription').value = d.description || '';
        document.getElementById('activityStart').value = d.start || '';
        document.getElementById('activityEnd').value = d.end || '';
        document.getElementById('activityBudget').value = d.budget || '';
        document.getElementById('activityLocation').value = d.location || '';
        document.getElementById('activityPromotion').value = d.promotion || 'No';
        if (d.status) document.getElementById('activityStatus').value = d.status;
        showActivityModal();
    }
</script>
@endpush
@endif
@endsection
