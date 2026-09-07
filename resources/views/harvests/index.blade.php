@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h4 class="mb-0">Harvests</h4>
            <small class="text-muted">Harvest requests, approvals, and payments</small>
        </div>
        <div class="d-flex gap-2">
            @if(app(\App\Services\PermissionService::class)->can('harvest_view'))
                <a href="{{ route('harvests.export', request()->query()) }}" class="btn btn-outline-secondary">Export CSV</a>
            @endif
            @if(app(\App\Services\PermissionService::class)->can('harvest_manage'))
                <a href="{{ route('harvests.create') }}" class="btn btn-success">Record Harvest</a>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-md-6"><div class="card"><div class="card-body"><strong>Total Harvested:</strong> {{ number_format((float) $totalAmount, 2) }}</div></div></div>
        <div class="col-md-6"><div class="card"><div class="card-body"><strong>Total Net:</strong> {{ number_format((float) $totalNet, 2) }}</div></div></div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Contract, owner, type...">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        @foreach(['Pending','Approved','Rejected','Paid'] as $status)
                            <option value="{{ $status }}" @selected($statusFilter === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Stage</label>
                    <select name="stage" class="form-select">
                        <option value="">All</option>
                        @foreach(['Generated','review','reviewed','approved','rejected','paid'] as $stage)
                            <option value="{{ $stage }}" @selected($stageFilter === $stage)>{{ $stage }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Per Page</label>
                    <select name="per_page" class="form-select">
                        @foreach([10,25,50,100] as $size)
                            <option value="{{ $size }}" @selected((int) $perPage === $size)>{{ $size }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-grid"><button class="btn btn-primary" type="submit">Go</button></div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Harvest</th>
                        <th>Contract</th>
                        <th>Owner</th>
                        <th>Item</th>
                        <th>Type</th>
                        <th class="text-end">Amount</th>
                        <th class="text-end">Net</th>
                        <th>Status</th>
                        <th>Stage</th>
                        <th>Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($harvests as $harvest)
                        @php
                            $contract = $harvest->contract;
                            $owner = strtolower((string) optional($contract)->contract_for) === 'group' ? optional(optional($contract)->group)->group_name : optional(optional($contract)->member)->full_name;
                            $itemName = optional(optional($harvest->itemHarvests->first())->item)->item_name;
                        @endphp
                        <tr>
                            <td>#{{ $harvest->id }}</td>
                            <td>{{ optional($contract)->contract_number ?? '-' }}</td>
                            <td>{{ $owner ?: '-' }}</td>
                            <td>{{ $itemName ?: '-' }}</td>
                            <td>{{ $harvest->harvest_type }}</td>
                            <td class="text-end">{{ number_format((float) $harvest->amount_harvested, 2) }}</td>
                            <td class="text-end">{{ number_format((float) $harvest->net_amount, 2) }}</td>
                            <td>{{ $harvest->status }}</td>
                            <td>{{ $harvest->approval_stage }}</td>
                            <td>{{ optional($harvest->harvest_date)->format('Y-m-d') ?? '-' }}</td>
                            <td><a href="{{ route('harvests.show', $harvest) }}" class="btn btn-sm btn-outline-secondary">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="text-center text-muted py-4">No harvests found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">{{ $harvests->links() }}</div>
    </div>
</div>
@endsection
