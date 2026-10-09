@extends('layouts.app')

@section('title', 'Contract ' . $contract->contract_number)

@section('content')
@php
    $perm = app(\App\Services\PermissionService::class);
    $party = strtolower((string) $contract->contract_for) === 'group' ? optional($contract->group)->group_name : optional($contract->member)->full_name;
    $outstanding = (float) ($contract->contract_outstanding ?? 0);
@endphp
<div class="dash-wrap">
    <div class="dash-head">
        <div>
            <h1 class="dash-name">{{ $contract->contract_number }}</h1>
            <div class="dash-date">{{ ucfirst((string) $contract->contract_for) }} contract &middot; {{ $party ?: '-' }}</div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('contracts.pdf', $contract) }}" class="btn btn-outline-secondary"><i class="fas fa-file-pdf"></i> PDF</a>
            @if($perm->can('new_payments'))
                <a href="{{ route('payments.create', ['contract_id' => $contract->id]) }}" class="btn btn-outline-success"><i class="fas fa-money-bill-wave"></i> Record Payment</a>
            @endif
            @if($perm->can('termination'))
                <a href="{{ route('termination.create', ['contract_id' => $contract->id]) }}" class="btn btn-outline-danger"><i class="fas fa-ban"></i> Terminate</a>
            @endif
            @if($perm->can('contracts_edit'))
                <a href="{{ route('contracts.edit', $contract) }}" class="btn btn-outline-secondary"><i class="fas fa-edit"></i> Edit</a>
            @endif
            @if($perm->can('contracts_delete'))
                <form method="POST" action="{{ route('contracts.destroy', $contract) }}" class="d-inline ypa-confirm-delete" data-confirm-title="Delete contract?" data-confirm-message="Delete contract {{ $contract->contract_number }}? This cannot be undone.">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-outline-danger" type="submit"><i class="fas fa-trash"></i> Delete</button>
                </form>
            @endif
            <a href="{{ route('contracts.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> Back</a>
        </div>
    </div>

    <div class="dash-grid mb-4">
        <div class="dash-card accent-primary">
            <div class="dash-card-top">
                <span class="dash-card-title">Total</span>
                <span class="dash-card-icon"><i class="fas fa-file-invoice-dollar"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format((float) ($contract->contract_amount ?? 0), 2) }}</div>
        </div>
        <div class="dash-card accent-success">
            <div class="dash-card-top">
                <span class="dash-card-title">Paid</span>
                <span class="dash-card-icon"><i class="fas fa-check-circle"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format((float) ($contract->total_paid ?? 0), 2) }}</div>
        </div>
        <div class="dash-card {{ $outstanding > 0 ? 'accent-warning' : 'accent-success' }}">
            <div class="dash-card-top">
                <span class="dash-card-title">Outstanding</span>
                <span class="dash-card-icon"><i class="fas fa-hourglass-half"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format($outstanding, 2) }}</div>
        </div>
    </div>

    <div class="dash-panel">
        <div class="dash-panel-head">
            <span><i class="fas fa-info-circle"></i> Contract Details</span>
        </div>
        <div class="dash-panel-body">
            <div class="row g-3">
                <div class="col-md-4"><div class="text-muted small">Party</div><div class="fw-semibold">{{ $party ?: '-' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">Project</div><div class="fw-semibold">{{ $contract->project->project_name ?? '-' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">Branch</div><div class="fw-semibold">{{ $contract->branch->name ?? '-' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">Status</div><div><span class="badge bg-secondary">{{ $contract->status }}</span></div></div>
                <div class="col-md-4"><div class="text-muted small">Workflow</div><div><span class="badge bg-info text-dark">{{ $contract->workflow_status }}</span></div></div>
                <div class="col-md-4"><div class="text-muted small">Payment Method</div><div class="fw-semibold">{{ $contract->paymentMethod->method_name ?? '-' }}</div></div>
            </div>
        </div>
    </div>

    <div class="dash-panel mt-4">
        <div class="dash-panel-head">
            <span><i class="fas fa-boxes"></i> Contract Items</span>
        </div>
        <div class="dash-panel-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th class="num">Qty</th>
                            <th>Unit</th>
                            <th class="num">Unit Price</th>
                            <th class="num">Monthly Return</th>
                            <th class="num">Total Hives</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($contract->items as $item)
                            <tr>
                                <td><strong>{{ $item->item_name }}</strong></td>
                                <td>{{ $item->item_type }}</td>
                                <td class="num">{{ $item->quantity }}</td>
                                <td>{{ $item->unit_name }}</td>
                                <td class="num">{{ number_format((float) $item->unit_price, 2) }}</td>
                                <td class="num">{{ number_format((float) $item->monthly_return, 2) }}</td>
                                <td class="num">{{ number_format((float) $item->total_hives, 2) }}</td>
                            </tr>
                        @empty
                            <tr class="empty-row"><td colspan="7"><i class="fas fa-inbox"></i>No contract items found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if($perm->can('contracts_edit'))
        <div class="dash-panel mt-4">
            <div class="dash-panel-head">
                <span><i class="fas fa-project-diagram"></i> Workflow</span>
            </div>
            <div class="dash-panel-body d-flex flex-wrap gap-2">
                <form method="POST" action="{{ route('contracts.workflow.save_draft', $contract) }}">@csrf<button class="btn btn-outline-secondary" type="submit">Save Draft</button></form>
                <form method="POST" action="{{ route('contracts.workflow.submit', $contract) }}">@csrf<button class="btn btn-outline-primary" type="submit">Submit</button></form>
                <form method="POST" action="{{ route('contracts.workflow.sign', $contract) }}">@csrf<button class="btn btn-outline-success" type="submit">Sign</button></form>
                <form method="POST" action="{{ route('contracts.workflow.finalize', $contract) }}">@csrf<button class="btn btn-outline-dark" type="submit">Finalize</button></form>
                <form method="POST" action="{{ route('contracts.workflow.reject', $contract) }}" class="d-flex gap-2">@csrf<input type="text" name="notes" class="form-control form-control-sm" placeholder="Reject notes"><button class="btn btn-outline-danger" type="submit">Reject</button></form>
            </div>
        </div>
    @endif

    @if($template)
        <div class="dash-panel mt-4">
            <div class="dash-panel-head">
                <span><i class="fas fa-file-alt"></i> Rendered Template Preview</span>
                <a href="{{ route('contracts.pdf', $contract) }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-download"></i> Download PDF</a>
            </div>
            <div class="dash-panel-body">
                {!! $renderedHtml !!}
            </div>
        </div>
    @endif
</div>
@endsection
