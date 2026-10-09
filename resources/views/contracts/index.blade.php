@extends('layouts.app')

@section('title', 'Contracts')

@section('content')
@php($perm = app(\App\Services\PermissionService::class))
<div class="dash-wrap">

    <div class="dash-head">
        <div>
            <h1 class="dash-name">Contracts</h1>
            <div class="dash-date">Contract lifecycle and workflow records &middot; {{ number_format($contracts->total()) }} contract(s)</div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('contracts.export', request()->query()) }}" class="btn btn-outline-success"><i class="fas fa-file-csv"></i> Export</a>
            @if($perm->can('contracts_edit'))
                <a href="{{ route('contract-templates.index') }}" class="btn btn-outline-secondary"><i class="fas fa-file-alt"></i> Templates</a>
            @endif
            @if($perm->can('contracts_create'))
                <a href="{{ route('contracts.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> New Contract</a>
            @endif
        </div>
    </div>

    <div class="dash-panel">
        <div class="dash-panel-head">
            <span><i class="fas fa-filter"></i> Filter Contracts</span>
        </div>
        <div class="dash-panel-body">
            <form method="GET" action="{{ route('contracts.index') }}" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" value="{{ $search }}" placeholder="Contract, party, project, branch...">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Party</label>
                    <select name="contract_for" class="form-select">
                        <option value="">All Parties</option>
                        <option value="member" @selected($contractFor === 'member')>Member</option>
                        <option value="group" @selected($contractFor === 'group')>Group</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        @foreach(\App\Models\Contract::STATUSES as $status)
                            <option value="{{ $status }}" @selected($statusFilter === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Per page</label>
                    <select name="per_page" class="form-select">
                        @foreach([10, 25, 50, 100] as $option)
                            <option value="{{ $option }}" @selected((int) $perPage === $option)>{{ $option }} / page</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i> Filter</button>
                    <a href="{{ route('contracts.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="dash-panel mt-4">
        <div class="dash-panel-head">
            <span><i class="fas fa-file-contract"></i> Contract List</span>
            <span class="text-muted small">{{ $contracts->total() }} result(s)</span>
        </div>
        <div class="dash-panel-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Contract</th>
                            <th>Party</th>
                            <th>Project</th>
                            <th>Branch</th>
                            <th>Status</th>
                            <th>Workflow</th>
                            <th class="num">Amount</th>
                            <th class="num">Outstanding</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($contracts as $contract)
                            @php
                                $party = strtolower((string) $contract->contract_for) === 'group'
                                    ? optional($contract->group)->group_name
                                    : optional($contract->member)->full_name;
                                $statusClass = match (strtolower((string) $contract->status)) {
                                    'active' => 'success',
                                    'terminated', 'cancelled', 'rejected' => 'danger',
                                    'completed', 'closed' => 'primary',
                                    'pending', 'draft' => 'warning',
                                    default => 'secondary',
                                };
                            @endphp
                            <tr>
                                <td><strong>{{ $contract->contract_number }}</strong></td>
                                <td>{{ $party ?: '-' }}</td>
                                <td>{{ $contract->project->project_name ?? '-' }}</td>
                                <td>{{ $contract->branch->name ?? '-' }}</td>
                                <td><span class="badge bg-{{ $statusClass }}">{{ $contract->status }}</span></td>
                                <td><span class="badge bg-info text-dark">{{ $contract->workflow_status }}</span></td>
                                <td class="num">{{ number_format((float) ($contract->contract_amount ?? 0), 2) }}</td>
                                <td class="num">{{ number_format((float) ($contract->contract_outstanding ?? 0), 2) }}</td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('contracts.show', $contract) }}" class="btn btn-sm btn-outline-primary" title="View"><i class="fas fa-eye"></i></a>
                                    <a href="{{ route('contracts.pdf', $contract) }}" class="btn btn-sm btn-outline-secondary" title="PDF"><i class="fas fa-file-pdf"></i></a>
                                    @if($perm->can('contracts_edit'))
                                        <a href="{{ route('contracts.edit', $contract) }}" class="btn btn-sm btn-outline-secondary" title="Edit"><i class="fas fa-edit"></i></a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr class="empty-row"><td colspan="9"><i class="fas fa-inbox"></i>No contracts found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="dash-panel-foot">{{ $contracts->links() }}</div>
    </div>

</div>
@endsection
