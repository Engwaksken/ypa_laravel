@extends('layouts.app')

@section('title', 'Receivables')

@section('content')
@php($permissionService = app(\App\Services\PermissionService::class))
<div class="dash-wrap">

    <div class="dash-head">
        <div>
            <h1 class="dash-name">Receivables</h1>
            <div class="dash-date">Income receivables and outstanding balances</div>
        </div>
        <div class="d-flex gap-2">
            @if($permissionService->can('receivables_export'))
                <a href="{{ route('receivables.export', request()->query()) }}" class="btn btn-outline-secondary">
                    <i class="fas fa-file-export" aria-hidden="true"></i> Export CSV
                </a>
            @endif
            @if($permissionService->can('receivables_create'))
                <button type="button" class="btn btn-primary" onclick="openReceivableModal()">
                    <i class="fas fa-plus"></i> Add Receivable
                </button>
            @endif
        </div>
    </div>

    <div class="dash-grid mb-4">
        <div class="dash-card accent-info">
            <div class="dash-card-top">
                <span class="dash-card-title">Records</span>
                <span class="dash-card-icon"><i class="fas fa-file-invoice"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format($receivables->total()) }}</div>
        </div>
        <div class="dash-card accent-primary">
            <div class="dash-card-top">
                <span class="dash-card-title">Net Payable</span>
                <span class="dash-card-icon"><i class="fas fa-file-invoice-dollar"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format((float) $totalPayable, 2) }}</div>
        </div>
        <div class="dash-card accent-success">
            <div class="dash-card-top">
                <span class="dash-card-title">Paid</span>
                <span class="dash-card-icon"><i class="fas fa-circle-check"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format((float) $totalPaid, 2) }}</div>
        </div>
        <div class="dash-card accent-warning">
            <div class="dash-card-top">
                <span class="dash-card-title">Outstanding</span>
                <span class="dash-card-icon"><i class="fas fa-hourglass-half"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format((float) $totalOutstanding, 2) }}</div>
        </div>
    </div>

    <div class="dash-panel">
        <div class="dash-panel-head">
            <span><i class="fas fa-filter"></i> Filter Receivables</span>
        </div>
        <div class="dash-panel-body">
            <form method="GET" action="{{ route('receivables.index') }}" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Reference, payer, type...">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        @foreach(['Received', 'Pending', 'Cancelled'] as $status)
                            <option value="{{ $status }}" @selected($statusFilter === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select">
                        <option value="">All Categories</option>
                        @foreach(['Farm and Livestock Related', 'Services', 'Administrative / Other'] as $category)
                            <option value="{{ $category }}" @selected($categoryFilter === $category)>{{ $category }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Per Page</label>
                    <select name="per_page" class="form-select">
                        @foreach([10, 25, 50, 100] as $size)
                            <option value="{{ $size }}" @selected((int) $perPage === $size)>{{ $size }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
                    <a href="{{ route('receivables.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="dash-panel mt-4">
        <div class="dash-panel-head">
            <span><i class="fas fa-list"></i> Receivable List</span>
            <span class="text-muted small">{{ $receivables->total() }} result(s)</span>
        </div>
        <div class="dash-panel-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Date</th>
                            <th>Payer</th>
                            <th>Category</th>
                            <th>Type</th>
                            <th class="num">Payable</th>
                            <th class="num">Paid</th>
                            <th class="num">Outstanding</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($receivables as $receivable)
                            @php($statusClass = ['Received' => 'success', 'Pending' => 'warning', 'Cancelled' => 'secondary'][$receivable->status] ?? 'secondary')
                            <tr>
                                <td><strong>{{ $receivable->reference_no }}</strong></td>
                                <td>{{ optional($receivable->received_date)->format('Y-m-d') ?? '-' }}</td>
                                <td>{{ $receivable->payer_name }}</td>
                                <td>{{ $receivable->category }}</td>
                                <td>{{ $receivable->receivable_type }}</td>
                                <td class="num">{{ number_format((float) $receivable->net_amount_payable, 2) }}</td>
                                <td class="num">{{ number_format((float) $receivable->amount_paid, 2) }}</td>
                                <td class="num">{{ number_format((float) $receivable->outstanding_balance, 2) }}</td>
                                <td><span class="badge bg-{{ $statusClass }}">{{ $receivable->status ?? '-' }}</span></td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('receivables.show', $receivable) }}" class="btn btn-sm btn-outline-primary" title="View" aria-label="View receivable {{ $receivable->reference_no }}"><i class="fas fa-eye" aria-hidden="true"></i></a>
                                    @if($permissionService->can('receivables_edit'))
                                        @include('receivables._edit-button', ['receivable' => $receivable, 'buttonClass' => 'btn btn-sm btn-outline-secondary', 'label' => ''])
                                    @endif
                                    @if($permissionService->can('receivables_delete'))
                                        <form action="{{ route('receivables.destroy', $receivable) }}" method="POST" class="d-inline ypa-confirm-delete" data-confirm-title="Delete receivable?" data-confirm-message="Delete {{ $receivable->reference_no }} and its payment records? This cannot be undone.">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete" aria-label="Delete receivable {{ $receivable->reference_no }}"><i class="fas fa-trash" aria-hidden="true"></i></button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr class="empty-row">
                                <td colspan="10"><i class="fas fa-inbox"></i>No receivables found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="dash-panel-foot">{{ $receivables->links() }}</div>
    </div>

</div>

@if($permissionService->can('receivables_create') || $permissionService->can('receivables_edit'))
    @include('receivables._modal')
@endif
@endsection
