@extends('layouts.app')

@section('title', 'Contract ' . $contract->contract_number)

@section('content')
@php
    $perm = app(\App\Services\PermissionService::class);
    $party = strtolower((string) $contract->contract_for) === 'group' ? optional($contract->group)->group_name : optional($contract->member)->full_name;
    $outstanding = (float) ($contract->contract_outstanding ?? 0);
    $integrity = app(\App\Services\ContractIntegrityService::class);
    $amendable = $integrity->requiresAmendment($contract);
    try { $amendments = $contract->amendments()->with('user')->get(); } catch (\Throwable) { $amendments = collect(); }
@endphp
<div class="dash-wrap">
    <div class="dash-head">
        <div>
            <h1 class="dash-name">{{ $contract->contract_number }}</h1>
            <div class="dash-date">{{ ucfirst((string) $contract->contract_for) }} contract &middot; {{ $party ?: '-' }}</div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('contracts.print', $contract) }}" target="_blank" rel="noopener" class="btn btn-outline-primary"><i class="fas fa-print"></i> Print Contract</a>
            <a href="{{ route('contracts.pdf', ['contract' => $contract, 'download' => 1]) }}" download class="btn btn-outline-secondary">Download PDF</a>
            @if($perm->can('new_payments'))
                <a href="{{ route('payments.create', ['contract_id' => $contract->id]) }}" class="btn btn-outline-success"><i class="fas fa-money-bill-wave"></i> Record Payment</a>
            @endif
            @if($perm->can('termination'))
                <a href="{{ route('termination.create', ['contract_id' => $contract->id]) }}" class="btn btn-outline-danger"><i class="fas fa-ban"></i> Terminate</a>
            @endif
            @if($perm->can('contracts_edit') && ! $integrity->isClosed($contract))
                <a href="{{ route('contracts.edit', $contract) }}" class="btn btn-outline-secondary"><i class="fas fa-edit"></i> {{ $amendable ? 'Amend' : 'Edit' }}</a>
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

    <div class="dash-panel mb-4 contract-document" data-table-tools="off">
        <div class="dash-panel-head">
            <span><i class="fas fa-file-alt"></i> Contract Document{{ $template ? ' · ' . $template->template_name . ' (v' . $template->version . ')' : '' }}</span>
            <a href="{{ route('contracts.print', $contract) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary"><i class="fas fa-print me-1"></i>Print</a>
        </div>
        <div class="dash-panel-body contract-preview-panel">
            @if($documentWarnings ?? [])
                <div class="alert alert-warning">Missing recorded contract details: {{ implode(', ', $documentWarnings) }}. Update the member record before issuing the final agreement.</div>
            @endif
            @if($unresolvedPlaceholders ?? [])
                @php
                    $braces = [str_repeat(chr(123), 2), str_repeat(chr(125), 2)];
                    $unresolvedList = implode(', ', array_map(fn ($key) => $braces[0] . $key . $braces[1], $unresolvedPlaceholders));
                @endphp
                <div class="alert alert-warning">The template uses placeholders that have no matching contract detail, so they were left blank: <code>{{ $unresolvedList }}</code>. Correct the names in the template or fill in the missing details.</div>
            @endif
            @foreach($integrityWarnings ?? [] as $warning)
                <div class="alert alert-warning">{{ $warning }}</div>
            @endforeach
            @unless($template)
                <div class="alert alert-warning">No matching agreement template is configured. The document below shows the contract summary, not legal agreement terms.</div>
            @endunless
            <iframe class="platform-pdf-frame contract-document-frame" src="{{ route('contracts.pdf', $contract) }}#view=FitH" title="Contract document {{ $contract->contract_number }}" loading="lazy"></iframe>
            <p class="small text-muted mt-2 mb-0">If your browser cannot display the PDF, use Download PDF above.</p>
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

    @include('contracts.documents.project-calculation')
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

    @if($amendments->isNotEmpty())
        <div class="dash-panel mt-4">
            <div class="dash-panel-head">
                <span><i class="fas fa-history"></i> Amendment History</span>
                <span class="text-muted small">{{ $amendments->count() }} {{ \Illuminate\Support\Str::plural('amendment', $amendments->count()) }}</span>
            </div>
            <ul class="list-unstyled mb-0 contract-amendments">
                @foreach($amendments as $amendment)
                    <li>
                        <div class="d-flex flex-wrap justify-content-between gap-2">
                            <strong>{{ $amendment->user?->name ?? 'Unknown user' }}</strong>
                            <span class="text-muted small">{{ $amendment->created_at?->format('j M Y, H:i') }} · while {{ str_replace('_', ' ', (string) ($amendment->workflow_status ?: $amendment->contract_status)) }}</span>
                        </div>
                        <p class="mb-1">{{ $amendment->reason }}</p>
                        @php
                            $changed = collect($amendment->changes ?? [])->except('items')->keys()->map(fn ($field) => str_replace('_', ' ', $field));
                        @endphp
                        <div class="small text-muted">
                            Changed: {{ $changed->merge(isset(($amendment->changes ?? [])['items']) ? ['contract items'] : [])->implode(', ') ?: 'no field values (saved unchanged)' }}
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

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

</div>
@endsection

@push('styles')
<style>
.contract-amendments li{padding:14px 18px;border-bottom:1px solid var(--ds-border);text-align:left}.contract-amendments li:last-child{border-bottom:0}.contract-preview-panel{background:#eef2f7}.contract-document-frame{display:block;width:100%;height:min(82vh,1200px);min-height:680px;border:1px solid #dbe3ec;border-radius:8px;background:#fff;box-shadow:0 8px 28px #0f172a1a}.contract-document .dash-panel-head{background:#fff}
@media(max-width:640px){.contract-document-frame{height:70vh;min-height:480px}}
@media print{.dash-head,.contract-document .dash-panel-head,.dash-panel:not(.contract-document){display:none!important}.contract-document{border:0!important;box-shadow:none!important}.contract-document-frame{height:100vh;border:0;box-shadow:none}}
</style>
@endpush
