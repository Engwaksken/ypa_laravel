@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0">Contracts</h4>
            <small class="text-muted">Contract lifecycle and workflow records</small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('contracts.export', request()->query()) }}" class="btn btn-outline-success"><i class="fas fa-file-csv me-1"></i> Export</a>
            @if(app(\App\Services\PermissionService::class)->can('contracts_edit'))
                <a href="{{ route('contract-templates.index') }}" class="btn btn-outline-secondary"><i class="fas fa-file-alt me-1"></i> Templates</a>
            @endif
            @if(app(\App\Services\PermissionService::class)->can('contracts_create'))
                <a href="{{ route('contracts.create') }}" class="btn btn-primary"><i class="fas fa-plus me-1"></i> New Contract</a>
            @endif
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" value="{{ $search }}" placeholder="Search contract, party, project, branch...">
                </div>
                <div class="col-md-2">
                    <select name="contract_for" class="form-select">
                        <option value="">All Parties</option>
                        <option value="member" @selected($contractFor === 'member')>Member</option>
                        <option value="group" @selected($contractFor === 'group')>Group</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        @foreach(\App\Models\Contract::STATUSES as $status)
                            <option value="{{ $status }}" @selected($statusFilter === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="per_page" class="form-select">
                        @foreach([10, 25, 50, 100] as $option)
                            <option value="{{ $option }}" @selected((int) $perPage === $option)>{{ $option }} / page</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-grid">
                    <button class="btn btn-dark" type="submit">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-striped align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Contract</th>
                        <th>Party</th>
                        <th>Project</th>
                        <th>Branch</th>
                        <th>Status</th>
                        <th>Workflow</th>
                        <th class="text-end">Amount</th>
                        <th class="text-end">Outstanding</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($contracts as $contract)
                        @php
                            $party = strtolower((string) $contract->contract_for) === 'group'
                                ? optional($contract->group)->group_name
                                : optional($contract->member)->full_name;
                        @endphp
                        <tr>
                            <td>{{ $contract->contract_number }}</td>
                            <td>{{ $party ?: '-' }}</td>
                            <td>{{ $contract->project->project_name ?? '-' }}</td>
                            <td>{{ $contract->branch->name ?? '-' }}</td>
                            <td><span class="badge bg-secondary">{{ $contract->status }}</span></td>
                            <td><span class="badge bg-info text-dark">{{ $contract->workflow_status }}</span></td>
                            <td class="text-end">{{ number_format((float) ($contract->contract_amount ?? 0), 2) }}</td>
                            <td class="text-end">{{ number_format((float) ($contract->contract_outstanding ?? 0), 2) }}</td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('contracts.show', $contract) }}" class="btn btn-sm btn-outline-primary">View</a>
                                <a href="{{ route('contracts.pdf', $contract) }}" class="btn btn-sm btn-outline-secondary">PDF</a>
                                <a href="{{ route('contracts.edit', $contract) }}" class="btn btn-sm btn-outline-warning">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted py-4">No contracts found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $contracts->links() }}
    </div>
</div>
@endsection
