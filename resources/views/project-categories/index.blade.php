@extends('layouts.app')
@section('title', 'Project Categories')
@section('content')
@php
    $permission = app(\App\Services\PermissionService::class);
    $canExport = $permission->can('project_categories_export');
@endphp
<div class="dash-wrap">

    <div class="dash-head">
        <div>
            <div class="dash-greeting">Operations</div>
            <h1 class="dash-name">Project Categories</h1>
            <div class="dash-date">{{ number_format($stats['total']) }} category(ies) used by projects and contracts</div>
        </div>
        <div class="d-flex gap-2">
            @if($canExport)
                <a href="{{ route('project-categories.export', request()->query()) }}" class="btn btn-outline-secondary">
                    <i class="fas fa-file-csv"></i> Export CSV
                </a>
            @endif
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#categoryModal" onclick="openCategoryModal()">
                <i class="fas fa-plus"></i> Add Category
            </button>
        </div>
    </div>

    <div class="dash-grid mb-4">
        <div class="dash-card"><div class="dash-card-top"><span class="dash-card-title">Total Categories</span><span class="dash-card-icon"><i class="fas fa-layer-group"></i></span></div><div class="dash-card-value">{{ number_format($stats['total']) }}</div></div>
        <div class="dash-card"><div class="dash-card-top"><span class="dash-card-title">Active</span><span class="dash-card-icon"><i class="fas fa-circle-check"></i></span></div><div class="dash-card-value">{{ number_format($stats['active']) }}</div></div>
        <div class="dash-card"><div class="dash-card-top"><span class="dash-card-title">Inactive</span><span class="dash-card-icon"><i class="fas fa-circle-pause"></i></span></div><div class="dash-card-value">{{ number_format($stats['inactive']) }}</div></div>
    </div>

    <div class="dash-panel">
        <div class="dash-panel-head">
            <span><i class="fas fa-filter"></i> Filter Categories</span>
        </div>
        <div class="dash-panel-body">
            <form method="GET" action="{{ route('project-categories.index') }}" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Category name or description...">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="active" @selected($statusFilter === 'active')>Active</option>
                        <option value="inactive" @selected($statusFilter === 'inactive')>Inactive</option>
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
                    <a href="{{ route('project-categories.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="dash-panel mt-4">
        <div class="dash-panel-head">
            <span><i class="fas fa-layer-group"></i> All Categories</span>
            <span class="text-muted small">{{ $categories->total() }} result(s)</span>
        </div>
        <div class="dash-panel-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Projects</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Updated</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categories as $category)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td><strong>{{ $category->category_name }}</strong></td>
                                <td class="text-muted small">{{ \Illuminate\Support\Str::limit($category->description ?? '—', 60) }}</td>
                                <td><span class="badge text-bg-primary">{{ number_format($category->projects_count) }}</span></td>
                                <td>
                                    <span class="badge text-bg-{{ $category->status ? 'success' : 'secondary' }}">{{ $category->status ? 'Active' : 'Inactive' }}</span>
                                </td>
                                <td class="text-muted small">{{ $category->created_at?->format('M d, Y') ?? '—' }}</td>
                                <td class="text-muted small">{{ $category->updated_at?->format('M d, Y') ?? '—' }}</td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-secondary" title="Edit" onclick='editCategory({{ $category->id }})'><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-sm btn-outline-danger" title="Delete" onclick="deleteCategory({{ $category->id }}, '{{ addslashes($category->category_name) }}')"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-5">No categories found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="dash-panel-body">{{ $categories->links() }}</div>
    </div>

</div>

<div class="modal fade" id="categoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="categoryForm" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title" id="categoryModalTitle"><i class="fas fa-layer-group"></i> Add Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="categoryAlert"></div>
                    <input type="hidden" id="category_id" name="category_id">
                    <div class="mb-3">
                        <label class="form-label">Category Name <span class="text-danger">*</span></label>
                        <input type="text" name="category_name" id="category_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" id="category_status" class="form-select">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="category_description" class="form-control" rows="4"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="categorySaveBtn" onclick="saveCategory()"><i class="fas fa-save"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="categoryDeleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger"><i class="fas fa-trash"></i> Delete Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="categoryDeleteAlert"></div>
                <p>Delete <strong id="categoryDeleteName"></strong>? You cannot delete a category if projects are linked to it.</p>
                <input type="hidden" id="categoryDeleteId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="categoryDeleteBtn" onclick="confirmDeleteCategory()"><i class="fas fa-trash"></i> Delete</button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.pj-alert { padding: .65rem 1rem; border-radius: .5rem; margin-bottom: 1rem; font-size: .875rem; display: flex; align-items: center; gap: .6rem; }
.pj-alert--danger { background: var(--bs-danger-bg-subtle); color: var(--bs-danger); border: 1px solid var(--bs-danger-border-subtle); }
.pj-alert--success { background: var(--bs-success-bg-subtle); color: var(--bs-success); border: 1px solid var(--bs-success-border-subtle); }
</style>
@endpush

@push('scripts')
<script>
function esc(v) {
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(v ?? ''));
    return d.innerHTML;
}

function showCategoryAlert(html, type) {
    const el = document.getElementById('categoryAlert');
    if (el) el.innerHTML = '<div class="pj-alert pj-alert--' + (type || 'danger') + '">' + html + '</div>';
}

function openCategoryModal() {
    document.getElementById('categoryModalTitle').innerHTML = '<i class="fas fa-layer-group"></i> Add Category';
    document.getElementById('categorySaveBtn').innerHTML = '<i class="fas fa-save"></i> Save';
    document.getElementById('categoryForm').reset();
    document.getElementById('category_id').value = '';
    document.getElementById('category_status').value = '1';
    document.getElementById('categoryAlert').innerHTML = '';
}

function saveCategory() {
    const form = document.getElementById('categoryForm');
    if (!form.checkValidity()) { form.reportValidity(); return; }

    const btn = document.getElementById('categorySaveBtn');
    const isEdit = !!document.getElementById('category_id').value;
    const url = isEdit
        ? '{{ route('project-categories.update', ':id') }}'.replace(':id', document.getElementById('category_id').value)
        : '{{ route('project-categories.store') }}';
    const method = isEdit ? 'PUT' : 'POST';

    btn.disabled = true;
    const orig = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

    const fd = new FormData(form);
    fd.set('_token', document.querySelector('meta[name="csrf-token"]').content);

    fetch(url, { method: method, body: fd, headers: { 'Accept': 'application/json' } })
        .then(r => r.json().then(data => ({ ok: r.ok, data })))
        .then(({ ok, data }) => {
            if (!ok || !data.success) throw new Error(data.message || 'Failed to save category.');
            showCategoryAlert('&#10003; ' + esc(data.message), 'success');
            setTimeout(() => window.location.reload(), 700);
        })
        .catch(err => {
            showCategoryAlert(esc(err.message), 'danger');
            btn.disabled = false;
            btn.innerHTML = orig;
        });
}

function editCategory(id) {
    document.getElementById('categoryModalTitle').innerHTML = '<i class="fas fa-edit"></i> Edit Category';
    document.getElementById('categorySaveBtn').innerHTML = '<i class="fas fa-save"></i> Update';
    document.getElementById('categoryForm').reset();
    document.getElementById('categoryAlert').innerHTML = '';
    document.getElementById('category_id').value = id;
    fetch('{{ route('project-categories.edit-data', ':id') }}'.replace(':id', id), { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(res => {
            if (!res.success) throw new Error(res.message || 'Failed to load category.');
            const c = res.category;
            document.getElementById('category_name').value = c.category_name || '';
            document.getElementById('category_status').value = String(c.status ?? 1);
            document.getElementById('category_description').value = c.description || '';
            new bootstrap.Modal(document.getElementById('categoryModal')).show();
        })
        .catch(err => showCategoryAlert(esc(err.message), 'danger'));
}

function deleteCategory(id, name) {
    document.getElementById('categoryDeleteId').value = id;
    document.getElementById('categoryDeleteName').textContent = name;
    document.getElementById('categoryDeleteAlert').innerHTML = '';
    new bootstrap.Modal(document.getElementById('categoryDeleteModal')).show();
}

function confirmDeleteCategory() {
    const id = document.getElementById('categoryDeleteId').value;
    const btn = document.getElementById('categoryDeleteBtn');
    btn.disabled = true;
    const orig = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';

    const fd = new FormData();
    fd.set('_token', document.querySelector('meta[name="csrf-token"]').content);
    fd.set('_method', 'DELETE');

    const url = '{{ route('project-categories.destroy', ':id') }}'.replace(':id', id);

    fetch(url, { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } })
        .then(r => r.json().then(data => ({ ok: r.ok, data })))
        .then(({ ok, data }) => {
            if (!ok || !data.success) throw new Error(data.message || 'Delete failed.');
            document.getElementById('categoryDeleteAlert').innerHTML = '<div class="pj-alert pj-alert--success">&#10003; ' + esc(data.message) + '</div>';
            setTimeout(() => window.location.reload(), 700);
        })
        .catch(err => {
            document.getElementById('categoryDeleteAlert').innerHTML = '<div class="pj-alert pj-alert--danger">' + esc(err.message) + '</div>';
            btn.disabled = false;
            btn.innerHTML = orig;
        });
}
</script>
@endpush
@endsection