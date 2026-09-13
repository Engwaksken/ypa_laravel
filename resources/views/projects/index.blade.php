@extends('layouts.app')
@section('title', 'Projects')
@section('content')
@php
    $permission = app(\App\Services\PermissionService::class);
    $canCreate = $permission->can('projects_create') || $permission->can('create_projects');
    $canEdit = $permission->can('projects_edit') || $permission->can('edit_projects');
    $canDelete = $permission->can('projects_delete') || $permission->can('delete_projects');
    $canExport = $permission->can('projects_export') || $permission->can('export_projects');
    $canCategories = $permission->can('project_categories') || $permission->can('manage_project_categories');
@endphp
<div class="dash-wrap">

    <div class="dash-head">
        <div>
            <div class="dash-greeting">Operations</div>
            <h1 class="dash-name">Projects</h1>
            <div class="dash-date">{{ number_format($stats['total']) }} registered project(s)</div>
        </div>
        <div class="d-flex gap-2">
            @if($canCategories)
                <a href="{{ route('project-categories.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-layer-group"></i> Categories
                </a>
            @endif
            @if($canExport)
                <a href="{{ route('projects.export', request()->query()) }}" class="btn btn-outline-secondary">
                    <i class="fas fa-file-csv"></i> Export CSV
                </a>
            @endif
            @if($canCreate)
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#projectModal" onclick="openCreateModal()">
                    <i class="fas fa-diagram-project"></i> New Project
                </button>
            @endif
        </div>
    </div>

    <div class="dash-grid mb-4">
        <div class="dash-card"><div class="dash-card-top"><span class="dash-card-title">Total Projects</span><span class="dash-card-icon"><i class="fas fa-diagram-project"></i></span></div><div class="dash-card-value">{{ number_format($stats['total']) }}</div></div>
        <div class="dash-card"><div class="dash-card-top"><span class="dash-card-title">Active</span><span class="dash-card-icon"><i class="fas fa-play-circle"></i></span></div><div class="dash-card-value">{{ number_format($stats['active']) }}</div></div>
        <div class="dash-card"><div class="dash-card-top"><span class="dash-card-title">Planning</span><span class="dash-card-icon"><i class="fas fa-clipboard-list"></i></span></div><div class="dash-card-value">{{ number_format($stats['planning']) }}</div></div>
        <div class="dash-card"><div class="dash-card-top"><span class="dash-card-title">Completed</span><span class="dash-card-icon"><i class="fas fa-check-circle"></i></span></div><div class="dash-card-value">{{ number_format($stats['completed']) }}</div></div>
        <div class="dash-card"><div class="dash-card-top"><span class="dash-card-title">Reg. Fees</span><span class="dash-card-icon"><i class="fas fa-money-bill"></i></span></div><div class="dash-card-value">UGX {{ number_format($stats['total_registration_fee']) }}</div></div>
        <div class="dash-card"><div class="dash-card-top"><span class="dash-card-title">Admin Fees</span><span class="dash-card-icon"><i class="fas fa-coins"></i></span></div><div class="dash-card-value">UGX {{ number_format($stats['total_admin_fee']) }}</div></div>
    </div>

    <div class="dash-panel mb-4">
        <div class="dash-panel-head"><span><i class="fas fa-filter"></i> Filter Projects</span></div>
        <div class="dash-panel-body">
            <form method="GET" action="{{ route('projects.index') }}" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Project name, code, category...">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All statuses</option>
                        @foreach($statuses as $st)
                            <option value="{{ $st }}" @selected($statusFilter === $st)>{{ $st }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Rows</label>
                    <select name="per_page" class="form-select">
                        @foreach([5, 10, 20, 50, 100] as $pp)
                            <option value="{{ $pp }}" @selected($perPage === $pp)>{{ $pp }} / page</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
                    <a href="{{ route('projects.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="dash-panel">
        <div class="dash-panel-head">
            <span><i class="fas fa-diagram-project"></i> All Projects</span>
            <span class="text-muted small">{{ $projects->total() }} result(s)</span>
        </div>
        <div class="dash-panel-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Project</th>
                            <th>Category</th>
                            <th>Dates</th>
                            <th class="text-end">Reg. Fee</th>
                            <th class="text-end">Admin Fee</th>
                            <th class="text-center">Members</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($projects as $project)
                            <tr>
                                <td><code class="pj-code">{{ $project->project_code }}</code></td>
                                <td>
                                    <div class="pj-name">{{ $project->project_name }}</div>
                                    @if($project->description)
                                        <div class="pj-desc small text-muted">{{ \Illuminate\Support\Str::limit($project->description, 60) }}</div>
                                    @endif
                                </td>
                                <td><span class="badge text-bg-light">{{ $project->category->category_name ?? 'N/A' }}</span></td>
                                <td>
                                    <span class="text-muted small">
                                        {{ $project->start_date?->format('M d, Y') ?? '—' }}
                                        <i class="fas fa-arrow-right mx-1 {{ app()->getLocale() === 'en' ? 'small' : '' }}"></i>
                                        {{ $project->end_date?->format('M d, Y') ?? 'Ongoing' }}
                                    </span>
                                </td>
                                <td class="text-end">UGX {{ number_format($project->registration_fee, 0) }}</td>
                                <td class="text-end">UGX {{ number_format($project->administrative_fee, 0) }}</td>
                                <td class="text-center"><span class="badge text-bg-primary">{{ number_format($project->member_count) }}</span></td>
                                <td><span class="badge text-bg-{{ ['Active'=>'success','Planning'=>'warning','Completed'=>'info','On Hold'=>'secondary','Cancelled'=>'danger'][$project->status] ?? 'secondary' }}">{{ $project->status }}</span></td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-primary" title="View" onclick='viewProject({{ $project->id }})'><i class="fas fa-eye"></i></button>
                                    @if($canEdit)
                                        <button class="btn btn-sm btn-outline-secondary" title="Edit" onclick='editProject({{ $project->id }})'><i class="fas fa-edit"></i></button>
                                    @endif
                                    @if($canDelete)
                                        <button class="btn btn-sm btn-outline-danger" title="Delete" onclick="deleteProject({{ $project->id }}, '{{ addslashes($project->project_name) }}')"><i class="fas fa-trash"></i></button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-muted py-5">No projects found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="dash-panel-body">{{ $projects->links() }}</div>
    </div>

</div>

@if($canCreate || $canEdit)
<div class="modal fade" id="projectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="projectForm" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title" id="projectModalTitle"><i class="fas fa-diagram-project"></i> New Project</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="formAlert"></div>
                    <input type="hidden" id="project_id" name="project_id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Project Name <span class="text-danger">*</span></label>
                            <input type="text" name="project_name" id="project_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Project Code <span class="text-danger">*</span></label>
                            <input type="text" name="project_code" id="project_code" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Category <span class="text-danger">*</span></label>
                            <select name="project_category_id" id="project_category_id" class="form-select" required>
                                <option value="">Select category...</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->category_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select name="status" id="status" class="form-select" required>
                                @foreach($statuses as $st)
                                    <option value="{{ $st }}" @selected($st === 'Planning')>{{ $st }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea name="description" id="description" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" id="start_date" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">End Date <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="date" name="end_date" id="end_date" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Registration Fee UGX <span class="text-danger">*</span></label>
                            <input type="number" name="registration_fee" id="registration_fee" class="form-control" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Administrative Fee UGX</label>
                            <input type="number" name="administrative_fee" id="administrative_fee" class="form-control" step="0.01" min="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveBtn" onclick="submitProjectForm()"><i class="fas fa-save"></i> Create Project</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<div class="modal fade" id="viewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-eye"></i> Project Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewModalBody">Loading...</div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button></div>
        </div>
    </div>
</div>

@if($canDelete)
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger"><i class="fas fa-trash"></i> Delete Project</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="deleteAlert"></div>
                <p>You are about to delete <strong id="delete_project_name"></strong>. This action cannot be undone.</p>
                <input type="hidden" id="delete_project_id">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn" onclick="confirmDelete()"><i class="fas fa-trash"></i> Delete</button>
            </div>
        </div>
    </div>
</div>
@endif

@push('styles')
<style>
.pj-code {
    font-family: 'Courier New', monospace;
    font-size: .78rem;
    background: var(--bs-tertiary-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: .375rem;
    padding: .15rem .5rem;
    color: var(--bs-primary);
    font-weight: 700;
    white-space: nowrap;
}
.pj-name { font-weight: 700; color: var(--bs-emphasis-color); }
.pj-desc { color: var(--bs-secondary-color); line-height: 1.4; }
.pj-view-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; }
.pj-view-section h6 { font-weight: 700; color: var(--bs-primary); border-bottom: 1px solid var(--bs-border-color); padding-bottom: .4rem; margin-bottom: .6rem; }
.pj-view-row { display: flex; justify-content: space-between; align-items: flex-start; gap: .75rem; padding: .4rem 0; font-size: .9rem; }
.pj-view-row + .pj-view-row { border-top: 1px solid var(--bs-border-color-translucent); }
.pj-view-row__label { color: var(--bs-secondary-color); font-weight: 500; flex-shrink: 0; }
.pj-view-row__val { font-weight: 600; color: var(--bs-emphasis-color); text-align: right; }
.pj-alert { padding: .65rem 1rem; border-radius: .5rem; margin-bottom: 1rem; font-size: .875rem; display: flex; align-items: center; gap: .6rem; }
.pj-alert--danger { background: var(--bs-danger-bg-subtle); color: var(--bs-danger); border: 1px solid var(--bs-danger-border-subtle); }
.pj-alert--success { background: var(--bs-success-bg-subtle); color: var(--bs-success); border: 1px solid var(--bs-success-border-subtle); }
@media (max-width: 900px) { .pj-view-grid { grid-template-columns: 1fr; } }
</style>
@endpush

@push('scripts')
<script>
function esc(v) {
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(v ?? ''));
    return d.innerHTML;
}

function moneyUGX(v) {
    return 'UGX ' + Number(v || 0).toLocaleString(undefined, { maximumFractionDigits: 0 });
}

function safeDate(v, fallback) {
    if (!v) return fallback || '—';
    const d = new Date(v);
    return isNaN(d.getTime()) ? (fallback || '—') : d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
}

function statusBadge(s) {
    const map = { 'Active': 'success', 'Planning': 'warning', 'Completed': 'info', 'On Hold': 'secondary', 'Cancelled': 'danger' };
    return '<span class="badge text-bg-' + (map[s] || 'secondary') + '">' + esc(s) + '</span>';
}

function showFormAlert(html, type) {
    const el = document.getElementById('formAlert');
    if (el) el.innerHTML = '<div class="pj-alert pj-alert--' + (type || 'danger') + '">' + html + '</div>';
}

function submitProjectForm() {
    const form = document.getElementById('projectForm');
    if (!form.checkValidity()) { form.reportValidity(); return; }

    const btn = document.getElementById('saveBtn');
    const isEdit = !!document.getElementById('project_id').value;
    const url = isEdit
        ? '{{ route('projects.update', ':id') }}'.replace(':id', document.getElementById('project_id').value)
        : '{{ route('projects.store') }}';
    const method = isEdit ? 'PUT' : 'POST';

    btn.disabled = true;
    const orig = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

    const fd = new FormData(form);
    fd.set('_token', document.querySelector('meta[name="csrf-token"]').content);

    fetch(url, { method: method, body: fd, headers: { 'Accept': 'application/json' } })
        .then(r => r.json().then(data => ({ ok: r.ok, status: r.status, data })))
        .then(({ ok, data }) => {
            if (!ok || !data.success) throw new Error(data.message || 'Failed to save project.');
            showFormAlert('&#10003; ' + esc(data.message), 'success');
            setTimeout(() => window.location.reload(), 700);
        })
        .catch(err => {
            showFormAlert(esc(err.message), 'danger');
            btn.disabled = false;
            btn.innerHTML = orig;
        });
}

function viewProject(id) {
    const body = document.getElementById('viewModalBody');
    body.innerHTML = '<div class="text-center py-4 text-muted"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
    const modal = new bootstrap.Modal(document.getElementById('viewModal'));
    modal.show();

    fetch('{{ route('projects.ajax.details') }}' + '?id=' + id, { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(res => {
            if (!res.success) throw new Error(res.message || 'Failed to load project.');
            const p = res.project;
            body.innerHTML = '<div class="pj-view-grid">'
                + '<div class="pj-view-section"><h6>Project Info</h6>'
                + '<div class="pj-view-row"><span class="pj-view-row__label">Code</span><span class="pj-view-row__val"><code class="pj-code">' + esc(p.project_code) + '</code></span></div>'
                + '<div class="pj-view-row"><span class="pj-view-row__label">Name</span><span class="pj-view-row__val">' + esc(p.project_name) + '</span></div>'
                + '<div class="pj-view-row"><span class="pj-view-row__label">Category</span><span class="pj-view-row__val"><span class="badge text-bg-light">' + esc(p.category_name || 'N/A') + '</span></span></div>'
                + '<div class="pj-view-row"><span class="pj-view-row__label">Status</span><span class="pj-view-row__val">' + statusBadge(p.status) + '</span></div>'
                + '<div class="pj-view-row"><span class="pj-view-row__label">Reg. Fee</span><span class="pj-view-row__val">' + moneyUGX(p.registration_fee) + '</span></div>'
                + '<div class="pj-view-row"><span class="pj-view-row__label">Admin Fee</span><span class="pj-view-row__val">' + moneyUGX(p.administrative_fee) + '</span></div>'
                + '</div>'
                + '<div class="pj-view-section"><h6>Timeline & Team</h6>'
                + '<div class="pj-view-row"><span class="pj-view-row__label">Start</span><span class="pj-view-row__val">' + safeDate(p.start_date) + '</span></div>'
                + '<div class="pj-view-row"><span class="pj-view-row__label">End</span><span class="pj-view-row__val">' + (p.end_date ? safeDate(p.end_date) : '<em class="text-muted">Ongoing</em>') + '</span></div>'
                + '<div class="pj-view-row"><span class="pj-view-row__label">Members</span><span class="pj-view-row__val"><span class="badge text-bg-primary">' + Number(p.member_count || 0).toLocaleString() + '</span></span></div>'
                + '<div class="pj-view-row"><span class="pj-view-row__label">Branch</span><span class="pj-view-row__val">' + esc(p.branch_name || '—') + '</span></div>'
                + '<div class="pj-view-row"><span class="pj-view-row__label">Created</span><span class="pj-view-row__val">' + safeDate(p.created_at) + '</span></div>'
                + '<div class="pj-view-row"><span class="pj-view-row__label">Updated</span><span class="pj-view-row__val">' + safeDate(p.updated_at) + '</span></div>'
                + '</div>'
                + '<div class="pj-view-section" style="grid-column:1/-1;"><h6>Description</h6>'
                + '<p style="font-size:.9rem;line-height:1.65;">' + esc(p.description || '—') + '</p>'
                + '</div></div>';
        })
        .catch(err => {
            body.innerHTML = '<div class="pj-alert pj-alert--danger">' + esc(err.message) + '</div>';
        });
}

function openCreateModal() {
    document.getElementById('projectModalTitle').innerHTML = '<i class="fas fa-diagram-project"></i> New Project';
    document.getElementById('saveBtn').innerHTML = '<i class="fas fa-save"></i> Create Project';
    document.getElementById('projectForm').reset();
    document.getElementById('project_id').value = '';
    document.getElementById('project_code').disabled = false;
    document.getElementById('formAlert').innerHTML = '';
}

function editProject(id) {
    document.getElementById('projectModalTitle').innerHTML = '<i class="fas fa-edit"></i> Edit Project';
    document.getElementById('saveBtn').innerHTML = '<i class="fas fa-save"></i> Update Project';
    document.getElementById('projectForm').reset();
    document.getElementById('formAlert').innerHTML = '';

    fetch('{{ route('projects.ajax.details') }}' + '?id=' + id, { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(res => {
            if (!res.success) throw new Error(res.message || 'Failed to load project.');
            const p = res.project;
            document.getElementById('project_id').value = p.id;
            document.getElementById('project_name').value = p.project_name || '';
            document.getElementById('project_code').value = p.project_code || '';
            document.getElementById('project_code').disabled = true;
            document.getElementById('project_category_id').value = p.project_category_id || '';
            document.getElementById('description').value = p.description || '';
            document.getElementById('start_date').value = p.start_date || '';
            document.getElementById('end_date').value = p.end_date || '';
            document.getElementById('registration_fee').value = p.registration_fee || 0;
            document.getElementById('administrative_fee').value = p.administrative_fee || 0;
            document.getElementById('status').value = p.status || 'Planning';
        })
        .catch(err => showFormAlert(esc(err.message), 'danger'));
}

function deleteProject(id, name) {
    document.getElementById('delete_project_id').value = id;
    document.getElementById('delete_project_name').textContent = name;
    document.getElementById('deleteAlert').innerHTML = '';
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

function confirmDelete() {
    const id = document.getElementById('delete_project_id').value;
    const btn = document.getElementById('confirmDeleteBtn');
    btn.disabled = true;
    const orig = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';

    const fd = new FormData();
    fd.set('_token', document.querySelector('meta[name="csrf-token"]').content);
    fd.set('_method', 'DELETE');

    fetch('{{ route('projects.destroy', ':id') }}'.replace(':id', id), { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } })
        .then(r => r.json().then(data => ({ ok: r.ok, data })))
        .then(({ ok, data }) => {
            if (!ok || !data.success) throw new Error(data.message || 'Delete failed.');
            document.getElementById('deleteAlert').innerHTML = '<div class="pj-alert pj-alert--success">&#10003; ' + esc(data.message) + '</div>';
            setTimeout(() => window.location.reload(), 700);
        })
        .catch(err => {
            document.getElementById('deleteAlert').innerHTML = '<div class="pj-alert pj-alert--danger">' + esc(err.message) + '</div>';
            btn.disabled = false;
            btn.innerHTML = orig;
        });
}
</script>
@endpush
@endsection