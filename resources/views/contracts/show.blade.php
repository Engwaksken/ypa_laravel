@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h4 class="mb-0">{{ $contract->contract_number }}</h4>
            <small class="text-muted">{{ ucfirst((string) $contract->contract_for) }} contract</small>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('contracts.pdf', $contract) }}" class="btn btn-outline-secondary">PDF</a>
            @if(app(\App\Services\PermissionService::class)->can('new_payments'))
                <a href="{{ route('payments.create', ['contract_id' => $contract->id]) }}" class="btn btn-outline-success">Record Payment</a>
            @endif
            @if(app(\App\Services\PermissionService::class)->can('termination'))
                <a href="{{ route('termination.create', ['contract_id' => $contract->id]) }}" class="btn btn-outline-danger">Terminate</a>
            @endif
            @if(app(\App\Services\PermissionService::class)->can('contracts_edit'))
                <a href="{{ route('contracts.edit', $contract) }}" class="btn btn-outline-warning">Edit</a>
            @endif
            @if(app(\App\Services\PermissionService::class)->can('contracts_delete'))
                <form method="POST" action="{{ route('contracts.destroy', $contract) }}" onsubmit="return confirm('Delete this contract?')">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-outline-danger" type="submit">Delete</button>
                </form>
            @endif
            <a href="{{ route('contracts.index') }}" class="btn btn-outline-dark">Back</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-md-4"><div class="card"><div class="card-body"><strong>Party:</strong> {{ strtolower((string) $contract->contract_for) === 'group' ? optional($contract->group)->group_name : optional($contract->member)->full_name }}</div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><strong>Project:</strong> {{ $contract->project->project_name ?? '-' }}</div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><strong>Branch:</strong> {{ $contract->branch->name ?? '-' }}</div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><strong>Status:</strong> {{ $contract->status }}</div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><strong>Workflow:</strong> {{ $contract->workflow_status }}</div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><strong>Payment Method:</strong> {{ $contract->paymentMethod->method_name ?? '-' }}</div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><strong>Total:</strong> {{ number_format((float) ($contract->contract_amount ?? 0), 2) }}</div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><strong>Paid:</strong> {{ number_format((float) ($contract->total_paid ?? 0), 2) }}</div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><strong>Outstanding:</strong> {{ number_format((float) ($contract->contract_outstanding ?? 0), 2) }}</div></div></div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><strong>Contract Items</strong></div>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th><th>Type</th><th>Qty</th><th>Unit</th><th class="text-end">Unit Price</th><th class="text-end">Monthly Return</th><th class="text-end">Total Hives</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($contract->items as $item)
                        <tr>
                            <td>{{ $item->item_name }}</td>
                            <td>{{ $item->item_type }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ $item->unit_name }}</td>
                            <td class="text-end">{{ number_format((float) $item->unit_price, 2) }}</td>
                            <td class="text-end">{{ number_format((float) $item->monthly_return, 2) }}</td>
                            <td class="text-end">{{ number_format((float) $item->total_hives, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-3">No contract items found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if(app(\App\Services\PermissionService::class)->can('contracts_edit'))
        <div class="card mb-3">
            <div class="card-header"><strong>Workflow</strong></div>
            <div class="card-body d-flex flex-wrap gap-2">
                <form method="POST" action="{{ route('contracts.workflow.save_draft', $contract) }}">@csrf<button class="btn btn-outline-secondary" type="submit">Save Draft</button></form>
                <form method="POST" action="{{ route('contracts.workflow.submit', $contract) }}">@csrf<button class="btn btn-outline-primary" type="submit">Submit</button></form>
                <form method="POST" action="{{ route('contracts.workflow.sign', $contract) }}">@csrf<button class="btn btn-outline-success" type="submit">Sign</button></form>
                <form method="POST" action="{{ route('contracts.workflow.finalize', $contract) }}">@csrf<button class="btn btn-outline-dark" type="submit">Finalize</button></form>
                <form method="POST" action="{{ route('contracts.workflow.reject', $contract) }}" class="d-flex gap-2">@csrf<input type="text" name="notes" class="form-control form-control-sm" placeholder="Reject notes"><button class="btn btn-outline-danger" type="submit">Reject</button></form>
            </div>
        </div>
    @endif

    @if($template)
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Rendered Template Preview</strong>
                <a href="{{ route('contracts.pdf', $contract) }}" class="btn btn-sm btn-outline-secondary">Download PDF</a>
            </div>
            <div class="card-body">
                {!! $renderedHtml !!}
            </div>
        </div>
    @endif
</div>
@endsection
