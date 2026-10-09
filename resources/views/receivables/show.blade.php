@extends('layouts.app')

@section('title', 'Receivable ' . $receivable->reference_no)

@section('content')
@php($permissionService = app(\App\Services\PermissionService::class))
@php($statusClass = ['Received' => 'success', 'Pending' => 'warning', 'Cancelled' => 'secondary'][$receivable->status] ?? 'secondary')
<div class="dash-wrap">

    <div class="dash-head">
        <div>
            <h1 class="dash-name">{{ $receivable->reference_no }}</h1>
            <div class="dash-date"><a href="{{ route('receivables.index') }}">Receivables</a> / {{ $receivable->receivable_type }}</div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('receivables.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Back</a>
            @if($permissionService->can('receivables_edit'))
                @include('receivables._edit-button', ['receivable' => $receivable, 'buttonClass' => 'btn btn-outline-primary', 'label' => 'Edit'])
            @endif
            @if($permissionService->can('receivables_delete'))
                <form method="POST" action="{{ route('receivables.destroy', $receivable) }}" class="ypa-confirm-delete" data-confirm-title="Delete receivable?" data-confirm-message="Delete {{ $receivable->reference_no }} and its payment records? This cannot be undone.">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-outline-danger" type="submit"><i class="fas fa-trash me-1"></i> Delete</button>
                </form>
            @endif
        </div>
    </div>

    <div class="dash-grid mb-4">
        <div class="dash-card accent-primary">
            <div class="dash-card-top">
                <span class="dash-card-title">Net Payable</span>
                <span class="dash-card-icon"><i class="fas fa-file-invoice-dollar"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format((float) $receivable->net_amount_payable, 2) }}</div>
        </div>
        <div class="dash-card accent-success">
            <div class="dash-card-top">
                <span class="dash-card-title">Paid</span>
                <span class="dash-card-icon"><i class="fas fa-circle-check"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format((float) $receivable->amount_paid, 2) }}</div>
        </div>
        <div class="dash-card accent-warning">
            <div class="dash-card-top">
                <span class="dash-card-title">Outstanding</span>
                <span class="dash-card-icon"><i class="fas fa-hourglass-half"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format((float) $receivable->outstanding_balance, 2) }}</div>
        </div>
        <div class="dash-card accent-{{ $statusClass === 'secondary' ? 'muted' : $statusClass }}">
            <div class="dash-card-top">
                <span class="dash-card-title">Status</span>
                <span class="dash-card-icon"><i class="fas fa-flag"></i></span>
            </div>
            <div class="dash-card-value">{{ $receivable->status ?? '-' }}</div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="dash-panel h-100">
                <div class="dash-panel-head">
                    <span><i class="fas fa-user"></i> Payer</span>
                </div>
                <div class="dash-panel-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <tbody>
                                <tr><th style="width: 40%">Name</th><td>{{ $receivable->payer_name }}</td></tr>
                                <tr><th>Type</th><td>{{ $receivable->payer_type ?? '-' }}</td></tr>
                                <tr><th>Phone</th><td>{{ $receivable->payer_phone ?: '-' }}</td></tr>
                                <tr><th>Email</th><td>{{ $receivable->receiver_email ?: '-' }}</td></tr>
                                <tr><th>Member</th><td>{{ optional($receivable->member)->full_name ?? '-' }}</td></tr>
                                <tr><th>Group</th><td>{{ optional($receivable->group)->group_name ?? ($receivable->group_name ?: '-') }}</td></tr>
                                <tr><th>Branch</th><td>{{ optional($receivable->branch)->name ?? '-' }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="dash-panel h-100">
                <div class="dash-panel-head">
                    <span><i class="fas fa-file-invoice"></i> Receivable</span>
                </div>
                <div class="dash-panel-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <tbody>
                                <tr><th style="width: 40%">Date</th><td>{{ optional($receivable->received_date)->format('Y-m-d') ?? '-' }}</td></tr>
                                <tr><th>Category</th><td>{{ $receivable->category ?? '-' }}</td></tr>
                                <tr><th>Type</th><td>{{ $receivable->receivable_type ?? '-' }}{{ $receivable->other_type ? ' (' . $receivable->other_type . ')' : '' }}</td></tr>
                                <tr><th>Amount Payable</th><td class="num">{{ number_format((float) $receivable->amount_payable, 2) }}</td></tr>
                                <tr><th>Discount</th><td class="num">{{ number_format((float) $receivable->discount, 2) }}</td></tr>
                                <tr><th>Payment Method</th><td>{{ $receivable->payment_method ?? '-' }}</td></tr>
                                <tr><th>Payment Reference</th><td>{{ $receivable->payment_reference ?: '-' }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($permissionService->can('receivables_create') && (float) $receivable->outstanding_balance > 0 && $receivable->status !== 'Cancelled')
        <div class="dash-panel mt-4">
            <div class="dash-panel-head">
                <span><i class="fas fa-money-bill-wave"></i> Record Payment</span>
            </div>
            <div class="dash-panel-body">
                <form method="POST" action="{{ route('receivables.pay', $receivable) }}" class="row g-3">
                    @csrf
                    <div class="col-md-3">
                        <label class="form-label">Payment Date</label>
                        <input type="date" name="payment_date" value="{{ old('payment_date', now()->format('Y-m-d')) }}" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Amount</label>
                        <input type="number" step="0.01" min="0.01" max="{{ $receivable->outstanding_balance }}" name="amount_paid" value="{{ old('amount_paid', $receivable->outstanding_balance) }}" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Method</label>
                        <select name="payment_method" class="form-select" required>
                            @foreach(['Cash', 'Mobile Money', 'Bank'] as $method)
                                <option value="{{ $method }}" @selected(old('payment_method', $receivable->payment_method) === $method)>{{ $method }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Reference</label>
                        <input type="text" name="payment_reference" value="{{ old('payment_reference') }}" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" rows="2" class="form-control">{{ old('notes') }}</textarea>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-success" type="submit"><i class="fas fa-save me-1"></i> Save Payment</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <div class="dash-panel mt-4">
        <div class="dash-panel-head">
            <span><i class="fas fa-clock-rotate-left"></i> Payment History</span>
            <span class="text-muted small">{{ $receivable->payments->count() }} payment(s)</span>
        </div>
        <div class="dash-panel-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Receipt</th>
                            <th>Method</th>
                            <th>Reference</th>
                            <th class="num">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($receivable->payments as $payment)
                            <tr>
                                <td>{{ optional($payment->payment_date)->format('Y-m-d') ?? '-' }}</td>
                                <td>{{ $payment->receipt_number ?? '-' }}</td>
                                <td>{{ $payment->payment_method ?? '-' }}</td>
                                <td>{{ $payment->payment_reference ?? '-' }}</td>
                                <td class="num">{{ number_format((float) $payment->amount_paid, 2) }}</td>
                            </tr>
                        @empty
                            <tr class="empty-row">
                                <td colspan="5"><i class="fas fa-inbox"></i>No payment records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="dash-panel mt-4">
        <div class="dash-panel-head">
            <span><i class="fas fa-align-left"></i> Description</span>
        </div>
        <div class="dash-panel-body">
            {{ $receivable->description ?: '-' }}
        </div>
    </div>

</div>

@if($permissionService->can('receivables_edit'))
    @include('receivables._modal')
@endif
@endsection
