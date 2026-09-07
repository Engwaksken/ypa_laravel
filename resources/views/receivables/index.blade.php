@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h4 class="mb-0">Receivables</h4>
            <small class="text-muted">Income receivables and outstanding balances</small>
        </div>
        <div class="d-flex gap-2">
            @if(app(\App\Services\PermissionService::class)->can('receivables_export'))
                <a href="{{ route('receivables.export', request()->query()) }}" class="btn btn-outline-secondary">Export CSV</a>
            @endif
            @if(app(\App\Services\PermissionService::class)->can('receivables_create'))
                <a href="{{ route('receivables.create') }}" class="btn btn-success">New Receivable</a>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-md-4"><div class="card"><div class="card-body"><strong>Net Payable:</strong> {{ number_format((float) $totalPayable, 2) }}</div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><strong>Paid:</strong> {{ number_format((float) $totalPaid, 2) }}</div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><strong>Outstanding:</strong> {{ number_format((float) $totalOutstanding, 2) }}</div></div></div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Reference, payer, type...">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        @foreach(['Received','Pending','Cancelled'] as $status)
                            <option value="{{ $status }}" @selected($statusFilter === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select">
                        <option value="">All</option>
                        @foreach(['Farm and Livestock Related','Services','Administrative / Other'] as $category)
                            <option value="{{ $category }}" @selected($categoryFilter === $category)>{{ $category }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label">Per Page</label>
                    <select name="per_page" class="form-select">
                        @foreach([10,25,50,100] as $size)
                            <option value="{{ $size }}" @selected((int) $perPage === $size)>{{ $size }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1 d-grid">
                    <button class="btn btn-primary" type="submit">Go</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Reference</th>
                        <th>Payer</th>
                        <th>Category</th>
                        <th>Type</th>
                        <th class="text-end">Payable</th>
                        <th class="text-end">Paid</th>
                        <th class="text-end">Outstanding</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($receivables as $receivable)
                        <tr>
                            <td>{{ $receivable->reference_no }}</td>
                            <td>{{ $receivable->payer_name }}</td>
                            <td>{{ $receivable->category }}</td>
                            <td>{{ $receivable->receivable_type }}</td>
                            <td class="text-end">{{ number_format((float) $receivable->net_amount_payable, 2) }}</td>
                            <td class="text-end">{{ number_format((float) $receivable->amount_paid, 2) }}</td>
                            <td class="text-end">{{ number_format((float) $receivable->outstanding_balance, 2) }}</td>
                            <td>{{ $receivable->status }}</td>
                            <td>{{ optional($receivable->received_date)->format('Y-m-d') ?? '-' }}</td>
                            <td><a href="{{ route('receivables.show', $receivable) }}" class="btn btn-sm btn-outline-secondary">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center text-muted py-4">No receivables found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">{{ $receivables->links() }}</div>
    </div>
</div>
@endsection
