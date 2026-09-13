@extends('layouts.app')
@section('title', 'Expenses')
@section('content')
@php
    $money = fn ($amount) => 'UGX ' . number_format((float) ($amount ?? 0), 0);
@endphp
<div class="dash-wrap">

    <div class="dash-head">
        <div>
            <div class="dash-greeting">Finance</div>
            <h1 class="dash-name">Expense Management</h1>
            <div class="dash-date">
                Branch: {{ $branchName }} &middot; {{ number_format($total) }} record(s)
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @if($canExport)
                <a href="{{ route('expenses.export', request()->except(['page'])) }}" class="btn btn-outline-secondary">
                    <i class="fas fa-file-csv"></i> Export CSV
                </a>
            @endif
            @if($canImport)
                <button type="button" class="btn btn-outline-info" onclick="openBulkUploadModal()">
                    <i class="fas fa-upload"></i> Bulk Upload
                </button>
                <a href="{{ route('expenses.template') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-download"></i> Template
                </a>
            @endif
            @if($canCreate)
                <button type="button" class="btn btn-primary" onclick="openExpenseModal()">
                    <i class="fas fa-plus"></i> Add Expense
                </button>
            @endif
        </div>
    </div>

    <div class="dash-grid d-grid-4 mb-4">
        <div class="dash-card">
            <div class="dash-card-top"><span class="dash-card-title">Today's Expenses</span><span class="dash-card-icon"><i class="fas fa-calendar-day"></i></span></div>
            <div class="dash-card-value">{{ $money($stats['today_total']) }}</div>
            <div class="dash-card-sub">{{ number_format($stats['today_count']) }} record(s) today</div>
        </div>
        <div class="dash-card">
            <div class="dash-card-top"><span class="dash-card-title">This Month</span><span class="dash-card-icon"><i class="fas fa-calendar-alt"></i></span></div>
            <div class="dash-card-value">{{ $money($stats['month_total']) }}</div>
            <div class="dash-card-sub">{{ number_format($stats['month_count']) }} expense record(s)</div>
        </div>
        <div class="dash-card">
            <div class="dash-card-top"><span class="dash-card-title">Harvest Payouts</span><span class="dash-card-icon"><i class="fas fa-hand-holding-usd"></i></span></div>
            <div class="dash-card-value">{{ $money($stats['harvest_total']) }}</div>
            <div class="dash-card-sub">Paid harvest payouts only</div>
        </div>
        <div class="dash-card">
            <div class="dash-card-top"><span class="dash-card-title">Pending Harvest Liability</span><span class="dash-card-icon"><i class="fas fa-hourglass-half"></i></span></div>
            <div class="dash-card-value">{{ $money($stats['pending_harvest']) }}</div>
            <div class="dash-card-sub">Not yet paid (pending/reviewed/approved)</div>
        </div>
    </div>

    <div class="dash-panel">
        <div class="dash-panel-head">
            <span><i class="fas fa-filter"></i> Filter Expenses</span>
            <span class="text-muted small">Search by branch, category, method, source or date.</span>
        </div>
        <div class="dash-panel-body">
            <form method="GET" action="{{ route('expenses.index') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Branch</label>
                    <select name="branch" class="form-select" {{ !$canAllBranches ? 'disabled' : '' }}>
                        @if($canAllBranches)
                            <option value="">All Branches</option>
                        @endif
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" @selected($selectedBranch === (int) $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                    @if(!$canAllBranches)
                        <input type="hidden" name="branch" value="{{ $formBranchId }}">
                    @endif
                </div>
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Title, member, contract, reference...">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select">
                        <option value="">All Categories</option>
                        @foreach(\App\Http\Requests\StoreExpenseRequest::CATEGORIES as $cat)
                            <option value="{{ $cat }}" @selected($categoryFilter === $cat)>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Payment Method</label>
                    <select name="payment_method" class="form-select">
                        <option value="">All Methods</option>
                        @foreach(\App\Http\Requests\StoreExpenseRequest::PAYMENT_METHODS as $method)
                            <option value="{{ $method }}" @selected($methodFilter === $method)>{{ $method }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Source</label>
                    <select name="source" class="form-select">
                        <option value="">All Sources</option>
                        <option value="manual" @selected($sourceFilter === 'manual')>Manual Expenses</option>
                        <option value="harvest" @selected($sourceFilter === 'harvest')>Harvest Payouts</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date From</label>
                    <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date To</label>
                    <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Show</label>
                    <select name="per_page" class="form-select">
                        @foreach([10, 20, 50, 100] as $pp)
                            <option value="{{ $pp }}" @selected($perPage === $pp)>{{ $pp }} per page</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
                    <a href="{{ route('expenses.index', $canAllBranches ? [] : ['branch' => $formBranchId]) }}" class="btn btn-outline-secondary">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <div class="dash-panel mt-4">
        <div class="dash-panel-head">
            <span><i class="fas fa-receipt"></i> Expenses &amp; Harvest Payout Liabilities</span>
            <span class="text-muted small">{{ number_format($total) }} record(s)</span>
        </div>
        <div class="dash-panel-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Source</th>
                            <th>Title / Details</th>
                            <th>Category</th>
                            <th>Owner / Contract</th>
                            <th>Method</th>
                            <th class="text-end">Gross</th>
                            <th class="text-end">Fees</th>
                            <th class="text-end">Net</th>
                            <th>Posting</th>
                            @if($canEdit || $canDelete)
                                <th class="text-end">Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            @php
                                $isManual = ($row['source_type'] ?? '') === 'manual';
                                $isPosted = !empty($row['journal_id']);
                                $ownerText = '-';
                                if (($row['source_type'] ?? '') === 'harvest') {
                                    $ownerName = trim((string) ($row['owner_name'] ?? ''));
                                    $ownerType = trim((string) ($row['owner_type'] ?? ''));
                                    $contract = trim((string) ($row['contract_number'] ?? ''));
                                    $ownerText = trim($ownerName . ($ownerType !== '' ? ' (' . ucfirst($ownerType) . ')' : '') . ($contract !== '' ? ' - ' . $contract : ''));
                                    $ownerText = $ownerText === '' ? '-' : $ownerText;
                                }
                                $postedOn = !empty($row['journal_reference']) ? $row['journal_reference'] : null;
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $row['record_date'] ? \Carbon\Carbon::parse($row['record_date'])->format('M d, Y') : '-' }}</strong>
                                    <div class="text-muted small">{{ $row['branch_name'] ?: '—' }}</div>
                                </td>
                                <td>
                                    <span class="badge text-bg-{{ $isManual ? 'primary' : 'info' }}">
                                        {{ $isManual ? 'Manual Expense' : 'Harvest Liability' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold">{{ $row['title'] ?: '—' }}</div>
                                    @if($row['description'])
                                        <div class="text-muted small">{{ $row['description'] }}</div>
                                    @endif
                                    @if($row['payment_reference'])
                                        <div class="text-muted small">Ref: {{ $row['payment_reference'] }}</div>
                                    @endif
                                </td>
                                <td>{{ $row['category'] ?: '—' }}</td>
                                <td>{{ $ownerText }}</td>
                                <td>{{ $row['payment_method'] ?: '—' }}</td>
                                <td class="text-end fw-bold">{{ $money($row['gross_amount']) }}</td>
                                <td class="text-end">
                                    {{ $money($row['total_fees']) }}
                                    @if((float) $row['maintenance_fee'] > 0)
                                        <div class="text-muted small">Maint: {{ $money($row['maintenance_fee']) }}</div>
                                    @endif
                                </td>
                                <td class="text-end fw-bold">{{ $money($row['net_amount']) }}</td>
                                <td>
                                    <span class="badge text-bg-{{ $isPosted ? 'success' : 'warning' }}">{{ $isPosted ? 'Posted' : 'Not Posted' }}</span>
                                    @if($postedOn)
                                        <div class="text-muted small">{{ $postedOn }}</div>
                                    @endif
                                </td>
                                @if($canEdit || $canDelete)
                                    <td class="text-end">
                                        <div class="d-flex gap-1 justify-content-end">
                                            @if($isManual && $canEdit)
                                                <button class="btn btn-sm btn-outline-secondary" title="Edit" onclick='openExpenseModalEdit(@json($row))'><i class="fas fa-edit"></i></button>
                                            @endif
                                            @if($isManual && $canDelete)
                                                <button class="btn btn-sm btn-outline-danger" title="Delete" onclick="deleteExpense({{ $row['source_id'] }}, '{{ addslashes($row['title']) }}')"><i class="fas fa-trash"></i></button>
                                            @endif
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr><td colspan="{{ ($canEdit || $canDelete) ? 11 : 10 }}" class="text-center text-muted py-5">No expense records found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($paginator->hasPages())
            <div class="dash-panel-body">{{ $paginator->links() }}</div>
        @endif
    </div>

</div>

@if($canCreate || $canEdit)
<div class="modal fade" id="expenseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="expenseForm" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title" id="expenseModalTitle"><i class="fas fa-receipt"></i> Add New Expense</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="expenseAlert"></div>
                    <input type="hidden" name="expense_id" id="expenseId">
                    <input type="hidden" name="debit_account_code" value="5000">
                    <input type="hidden" name="credit_account_code" value="1000">
                    <div class="mb-3">
                        <label class="form-label">Expense Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="expenseTitle" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Amount <span class="text-danger">*</span></label>
                            <input type="number" name="amount" id="expenseAmount" class="form-control" min="0" step="0.01" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date <span class="text-danger">*</span></label>
                            <input type="date" name="expense_date" id="expenseDate" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Branch <span class="text-danger">*</span></label>
                        <select name="branch_id" id="expenseBranch" class="form-select" {{ !$canAllBranches ? 'disabled' : '' }}>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" @selected((int) $branch->id === $formBranchId)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        @if(!$canAllBranches)
                            <input type="hidden" name="branch_id" value="{{ $formBranchId }}">
                        @endif
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category <span class="text-danger">*</span></label>
                        <select name="category" id="expenseCategory" class="form-select" onchange="toggleCustomCategory(this.value)" required>
                            <option value="">Select category</option>
                            @foreach(\App\Http\Requests\StoreExpenseRequest::CATEGORIES as $cat)
                                <option value="{{ $cat }}">{{ $cat }}</option>
                            @endforeach
                        </select>
                        <input type="text" name="custom_category" id="customCategory" class="form-control mt-2" placeholder="Enter custom category" style="display:none;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                        <select name="payment_method" id="expensePaymentMethod" class="form-select" required>
                            @foreach(\App\Http\Requests\StoreExpenseRequest::PAYMENT_METHODS as $method)
                                <option value="{{ $method }}">{{ $method }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="expenseDescription" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="expenseSaveBtn" onclick="saveExpense()"><i class="fas fa-check"></i> Save Expense</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@if($canDelete)
<div class="modal fade" id="expenseDeleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger"><i class="fas fa-trash"></i> Delete Expense</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="expenseDeleteAlert"></div>
                <p>Delete expense <strong id="expenseDeleteName"></strong>? This cannot be undone.</p>
                <input type="hidden" id="expenseDeleteId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="expenseDeleteBtn" onclick="confirmDeleteExpense()"><i class="fas fa-trash"></i> Delete</button>
            </div>
        </div>
    </div>
</div>
@endif

@if($canImport)
<div class="modal fade" id="bulkUploadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="bulkUploadForm" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-upload"></i> Bulk Upload Expenses</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="uploadAlert"></div>
                    <div class="mb-3">
                        <label class="form-label">Branch</label>
                        <select name="branch_id" id="bulkBranch" class="form-select" {{ !$canAllBranches ? 'disabled' : '' }}>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" @selected((int) $branch->id === $formBranchId)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        @if(!$canAllBranches)
                            <input type="hidden" name="branch_id" value="{{ $formBranchId }}">
                        @endif
                    </div>
                    <div class="mb-3">
                        <label class="form-label">CSV File <span class="text-danger">*</span></label>
                        <input type="file" name="csv_file" id="csvFile" accept=".csv" class="form-control" required>
                        <div class="form-text">Required columns: title, amount, expense_date. Max 5MB.</div>
                    </div>
                    <div id="uploadResults" style="display:none;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="uploadBtn" onclick="submitBulkUpload()"><i class="fas fa-upload"></i> Upload &amp; Import</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@push('styles')
<style>
.exy-alert { padding: .65rem 1rem; border-radius: .5rem; margin-bottom: 1rem; font-size: .875rem; }
.exy-alert--danger { background: var(--bs-danger-bg-subtle); color: var(--bs-danger); border: 1px solid var(--bs-danger-border-subtle); }
.exy-alert--success { background: var(--bs-success-bg-subtle); color: var(--bs-success); border: 1px solid var(--bs-success-border-subtle); }
</style>
@endpush

@push('scripts')
<script>
function esc(v) {
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(v ?? ''));
    return d.innerHTML;
}

function showExpenseAlert(html, type) {
    const el = document.getElementById('expenseAlert');
    if (el) el.innerHTML = '<div class="exy-alert exy-alert--' + (type || 'danger') + '">' + html + '</div>';
}

function toggleCustomCategory(value) {
    const custom = document.getElementById('customCategory');
    if (!custom) return;
    if (value === 'Other') {
        custom.style.display = 'block';
        custom.required = true;
    } else {
        custom.style.display = 'none';
        custom.required = false;
        custom.value = '';
    }
}

function openExpenseModal() {
    document.getElementById('expenseModalTitle').innerHTML = '<i class="fas fa-receipt"></i> Add New Expense';
    document.getElementById('expenseSaveBtn').innerHTML = '<i class="fas fa-check"></i> Save Expense';
    document.getElementById('expenseForm').reset();
    document.getElementById('expenseId').value = '';
    document.getElementById('expenseAlert').innerHTML = '';
    document.getElementById('expenseDate').value = '{{ date('Y-m-d') }}';
    @if($canAllBranches)
    document.getElementById('expenseBranch').value = '{{ $formBranchId }}';
    @endif
    document.getElementById('expensePaymentMethod').value = 'Cash';
    toggleCustomCategory('');
    new bootstrap.Modal(document.getElementById('expenseModal')).show();
}

function openExpenseModalEdit(row) {
    document.getElementById('expenseModalTitle').innerHTML = '<i class="fas fa-edit"></i> Edit Expense';
    document.getElementById('expenseSaveBtn').innerHTML = '<i class="fas fa-check"></i> Update Expense';
    document.getElementById('expenseForm').reset();
    document.getElementById('expenseAlert').innerHTML = '';
    document.getElementById('expenseId').value = row.source_id;
    document.getElementById('expenseTitle').value = row.title || '';
    document.getElementById('expenseAmount').value = row.gross_amount || row.net_amount || '';
    document.getElementById('expenseDate').value = String(row.record_date || '').substring(0, 10);
    document.getElementById('expenseDescription').value = row.description || '';
    @if($canAllBranches)
    document.getElementById('expenseBranch').value = String(row.branch_id || '{{ $formBranchId }}');
    @endif
    document.getElementById('expensePaymentMethod').value = row.payment_method || 'Cash';

    const fixedCategories = @json(\App\Http\Requests\StoreExpenseRequest::CATEGORIES);
    const category = document.getElementById('expenseCategory');
    const custom = document.getElementById('customCategory');

    if (row.category && fixedCategories.includes(row.category)) {
        category.value = row.category;
        toggleCustomCategory(row.category);
    } else if (row.category) {
        category.value = 'Other';
        toggleCustomCategory('Other');
        custom.value = row.category;
    } else {
        category.value = '';
        toggleCustomCategory('');
    }

    new bootstrap.Modal(document.getElementById('expenseModal')).show();
}

function saveExpense() {
    const form = document.getElementById('expenseForm');
    if (!form.checkValidity()) { form.reportValidity(); return; }

    const isEdit = !!document.getElementById('expenseId').value;
    const url = isEdit
        ? '{{ route('expenses.update', ':id') }}'.replace(':id', document.getElementById('expenseId').value)
        : '{{ route('expenses.store') }}';
    const method = isEdit ? 'PUT' : 'POST';
    const btn = document.getElementById('expenseSaveBtn');
    const orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

    const fd = new FormData(form);
    fd.set('_token', document.querySelector('meta[name="csrf-token"]').content);

    fetch(url, { method: method, body: fd, headers: { 'Accept': 'application/json' } })
        .then(r => r.json().then(data => ({ ok: r.ok, data })))
        .then(({ ok, data }) => {
            if (!ok || !data.success) throw new Error(data.message || 'Failed to save expense.');
            showExpenseAlert('&#10003; ' + esc(data.message), 'success');
            setTimeout(() => window.location.reload(), 700);
        })
        .catch(err => {
            showExpenseAlert(esc(err.message), 'danger');
            btn.disabled = false;
            btn.innerHTML = orig;
        });
}

function deleteExpense(id, name) {
    document.getElementById('expenseDeleteId').value = id;
    document.getElementById('expenseDeleteName').textContent = name;
    document.getElementById('expenseDeleteAlert').innerHTML = '';
    new bootstrap.Modal(document.getElementById('expenseDeleteModal')).show();
}

function confirmDeleteExpense() {
    const id = document.getElementById('expenseDeleteId').value;
    const btn = document.getElementById('expenseDeleteBtn');
    const orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';

    const fd = new FormData();
    fd.set('_token', document.querySelector('meta[name="csrf-token"]').content);
    fd.set('_method', 'DELETE');

    fetch('{{ route('expenses.destroy', ':id') }}'.replace(':id', id), { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } })
        .then(r => r.json().then(data => ({ ok: r.ok, data })))
        .then(({ ok, data }) => {
            if (!ok || !data.success) throw new Error(data.message || 'Delete failed.');
            document.getElementById('expenseDeleteAlert').innerHTML = '<div class="exy-alert exy-alert--success">&#10003; ' + esc(data.message) + '</div>';
            setTimeout(() => window.location.reload(), 700);
        })
        .catch(err => {
            document.getElementById('expenseDeleteAlert').innerHTML = '<div class="exy-alert exy-alert--danger">' + esc(err.message) + '</div>';
            btn.disabled = false;
            btn.innerHTML = orig;
        });
}

function openBulkUploadModal() {
    document.getElementById('bulkUploadForm').reset();
    document.getElementById('uploadAlert').innerHTML = '';
    document.getElementById('uploadResults').style.display = 'none';
    @if($canAllBranches)
    document.getElementById('bulkBranch').value = '{{ $formBranchId }}';
    @endif
    new bootstrap.Modal(document.getElementById('bulkUploadModal')).show();
}

function showUploadAlert(html, type) {
    const el = document.getElementById('uploadAlert');
    if (el) el.innerHTML = '<div class="exy-alert exy-alert--' + (type || 'danger') + '">' + html + '</div>';
}

function submitBulkUpload() {
    const form = document.getElementById('bulkUploadForm');
    const fileInput = document.getElementById('csvFile');
    const file = fileInput.files[0];

    if (!file) return showUploadAlert('Please select a CSV file', 'danger');
    if (!file.name.toLowerCase().endsWith('.csv')) return showUploadAlert('Please upload a valid CSV file', 'danger');
    if (file.size > 5 * 1024 * 1024) return showUploadAlert('File size must be less than 5MB', 'danger');

    const btn = document.getElementById('uploadBtn');
    const orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';

    const fd = new FormData(form);
    fd.set('_token', document.querySelector('meta[name="csrf-token"]').content);

    fetch('{{ route('expenses.import') }}', { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } })
        .then(r => r.json().then(data => ({ ok: r.ok, data })))
        .then(({ ok, data }) => {
            const results = document.getElementById('uploadResults');
            if (ok && data.success) {
                showUploadAlert('&#10003; ' + esc(data.message), 'success');
                results.style.display = 'block';
                results.innerHTML = '<div class="exy-alert exy-alert--success">Imported: <strong>' + (data.imported ?? 0) + '</strong>, Skipped: <strong>' + (data.skipped ?? 0) + '</strong></div>';
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showUploadAlert(esc(data.message || 'Upload failed.'), 'danger');
                const errors = Array.isArray(data.errors) ? data.errors.slice(0, 10) : [];
                if (errors.length) {
                    results.style.display = 'block';
                    results.innerHTML = '<div class="exy-alert exy-alert--danger"><strong>Errors:</strong><ul class="mb-0">' + errors.map(e => '<li>' + esc(e) + '</li>').join('') + '</ul></div>';
                }
                btn.disabled = false;
                btn.innerHTML = orig;
            }
        })
        .catch(() => {
            showUploadAlert('An error occurred during upload. Please try again.', 'danger');
            btn.disabled = false;
            btn.innerHTML = orig;
        });
}
</script>
@endpush
@endsection