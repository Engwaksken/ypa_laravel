@extends('layouts.app')

@section('title', 'Harvests')

@section('content')
<div class="dash-wrap">

    <div class="dash-head">
        <div>
            <h1 class="dash-name">Harvests</h1>
            <div class="dash-date">Harvest requests, approvals, and payments</div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @if(app(\App\Services\PermissionService::class)->can('harvest_view'))
                <a href="{{ route('harvests.export', request()->query()) }}" class="btn btn-outline-success"><i class="fas fa-file-csv"></i> Export CSV</a>
            @endif
            @if($canManage)
                <button type="button" class="btn btn-primary" onclick="openHarvestModal()"><i class="fas fa-plus"></i> Record Harvest</button>
            @endif
        </div>
    </div>

    <div class="dash-grid mb-4">
        <div class="dash-card accent-primary">
            <div class="dash-card-top">
                <span class="dash-card-title">Harvests</span>
                <span class="dash-card-icon"><i class="fas fa-seedling"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format($harvests->total()) }}</div>
        </div>
        <div class="dash-card accent-info">
            <div class="dash-card-top">
                <span class="dash-card-title">Total Harvested</span>
                <span class="dash-card-icon"><i class="fas fa-coins"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format((float) $totalAmount, 2) }}</div>
        </div>
        <div class="dash-card accent-success">
            <div class="dash-card-top">
                <span class="dash-card-title">Total Net</span>
                <span class="dash-card-icon"><i class="fas fa-wallet"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format((float) $totalNet, 2) }}</div>
        </div>
    </div>

    <div class="dash-panel">
        <div class="dash-panel-head">
            <span><i class="fas fa-filter"></i> Filter Harvests</span>
        </div>
        <div class="dash-panel-body">
            <form method="GET" action="{{ route('harvests.index') }}" class="row g-3">
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
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i> Filter</button>
                    <a href="{{ route('harvests.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="dash-panel mt-4">
        <div class="dash-panel-head">
            <span><i class="fas fa-list"></i> Harvest List</span>
            <span class="text-muted small">{{ $harvests->total() }} result(s)</span>
        </div>
        <div class="dash-panel-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Harvest</th>
                            <th>Contract</th>
                            <th>Owner</th>
                            <th>Item</th>
                            <th>Type</th>
                            <th class="num">Amount</th>
                            <th class="num">Net</th>
                            <th>Status</th>
                            <th>Stage</th>
                            <th>Date</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($harvests as $harvest)
                            @php
                                $contract = $harvest->contract;
                                $owner = strtolower((string) optional($contract)->contract_for) === 'group' ? optional(optional($contract)->group)->group_name : optional(optional($contract)->member)->full_name;
                                $itemName = optional(optional($harvest->itemHarvests->first())->item)->item_name;
                                $statusClass = match (strtolower((string) $harvest->status)) {
                                    'approved' => 'primary',
                                    'paid' => 'success',
                                    'rejected' => 'danger',
                                    'pending' => 'warning',
                                    default => 'secondary',
                                };
                            @endphp
                            <tr>
                                <td><strong>#{{ $harvest->id }}</strong></td>
                                <td>{{ optional($contract)->contract_number ?? '-' }}</td>
                                <td>{{ $owner ?: '-' }}</td>
                                <td>{{ $itemName ?: '-' }}</td>
                                <td>{{ $harvest->harvest_type }}</td>
                                <td class="num">{{ number_format((float) $harvest->amount_harvested, 2) }}</td>
                                <td class="num">{{ number_format((float) $harvest->net_amount, 2) }}</td>
                                <td><span class="badge bg-{{ $statusClass }}">{{ $harvest->status }}</span></td>
                                <td><span class="badge bg-light text-dark border">{{ $harvest->approval_stage }}</span></td>
                                <td>{{ optional($harvest->harvest_date)->format('Y-m-d') ?? '-' }}</td>
                                <td class="text-end"><a href="{{ route('harvests.show', $harvest) }}" class="btn btn-sm btn-outline-primary" title="View"><i class="fas fa-eye"></i></a></td>
                            </tr>
                        @empty
                            <tr class="empty-row"><td colspan="11"><i class="fas fa-inbox"></i>No harvests found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="dash-panel-foot">{{ $harvests->links() }}</div>
    </div>

</div>

@if($canManage)
<div class="modal fade" id="harvestModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="harvestForm" method="POST" action="{{ route('harvests.store') }}">
                @csrf
                <input type="hidden" name="_method" id="harvestFormMethod" value="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="harvestModalTitle">Add Harvest</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @include('harvests._fields', ['contractItem' => null])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i> Save Harvest</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function openHarvestModal() {
        var form = document.getElementById('harvestForm');
        form.reset();
        form.action = "{{ route('harvests.store') }}";
        document.getElementById('harvestFormMethod').value = 'POST';
        document.getElementById('harvestDate').value = "{{ now()->format('Y-m-d') }}";
        if (window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('harvestModal')).show();
        }
    }
</script>
@endpush
@endif
@endsection
