@extends('layouts.app')

@section('title', 'Payment ' . $payment->transaction_number)

@section('content')
@php
    $status = strtoupper((string) $payment->status);
    $statusClass = match ($status) {
        'APPROVED' => 'success',
        'PENDING' => 'warning',
        'REJECTED', 'CANCELLED' => 'danger',
        default => 'secondary',
    };
@endphp
<div class="dash-wrap">
    <div class="dash-head">
        <div>
            <h1 class="dash-name">{{ $payment->transaction_number }}</h1>
            <div class="dash-date">{{ $payment->transactionType->type_name ?? 'Payment Transaction' }}</div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @if($payment->receipt_number)
                <a href="{{ route('payments.receipt', $payment) }}" class="btn btn-outline-primary"><i class="fas fa-print"></i> Receipt</a>
            @endif
            <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> Back</a>
        </div>
    </div>

    <div class="dash-grid mb-4">
        <div class="dash-card accent-success">
            <div class="dash-card-top">
                <span class="dash-card-title">Amount</span>
                <span class="dash-card-icon"><i class="fas fa-money-bill-wave"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format((float) $payment->amount, 2) }}</div>
        </div>
        <div class="dash-card accent-{{ $statusClass === 'secondary' ? 'muted' : $statusClass }}">
            <div class="dash-card-top">
                <span class="dash-card-title">Status</span>
                <span class="dash-card-icon"><i class="fas fa-clipboard-check"></i></span>
            </div>
            <div class="dash-card-value">{{ $payment->status }}</div>
        </div>
        <div class="dash-card accent-info">
            <div class="dash-card-top">
                <span class="dash-card-title">Reconciliation</span>
                <span class="dash-card-icon"><i class="fas fa-balance-scale"></i></span>
            </div>
            <div class="dash-card-value">{{ $payment->reconciliation_status ?? '-' }}</div>
        </div>
    </div>

    @if(app(\App\Services\PermissionService::class)->can('payments_maintain'))
        <div class="dash-panel mb-4">
            <div class="dash-panel-head">
                <span><i class="fas fa-tasks"></i> Finance Actions</span>
            </div>
            <div class="dash-panel-body d-flex gap-2 flex-wrap">
                @if($status === 'PENDING')
                    <form method="POST" action="{{ route('payments.approve', $payment) }}" class="d-flex gap-2">
                        @csrf
                        <input type="text" name="notes" class="form-control form-control-sm" placeholder="Approval note">
                        <button class="btn btn-sm btn-success" type="submit"><i class="fas fa-check"></i> Approve</button>
                    </form>
                    <form method="POST" action="{{ route('payments.reject', $payment) }}" class="d-flex gap-2">
                        @csrf
                        <input type="text" name="notes" class="form-control form-control-sm" placeholder="Rejection reason" required>
                        <button class="btn btn-sm btn-outline-danger" type="submit"><i class="fas fa-times"></i> Reject</button>
                    </form>
                @endif
                @if($status === 'APPROVED' && strtoupper((string) $payment->reconciliation_status) !== 'RECONCILED')
                    <form method="POST" action="{{ route('payments.reconcile', $payment) }}" class="ypa-confirm-delete" data-confirm-title="Mark as reconciled?" data-confirm-message="Mark this payment as reconciled?" data-confirm-label="Reconcile" data-confirm-busy-label="Reconciling..." data-confirm-variant="btn-primary">
                        @csrf
                        <button class="btn btn-sm btn-outline-dark" type="submit"><i class="fas fa-balance-scale"></i> Reconcile</button>
                    </form>
                @endif
                @if($status !== 'PENDING' && !($status === 'APPROVED' && strtoupper((string) $payment->reconciliation_status) !== 'RECONCILED'))
                    <span class="text-muted small">No actions available for this payment.</span>
                @endif
            </div>
        </div>
    @endif

    <div class="dash-panel">
        <div class="dash-panel-head">
            <span><i class="fas fa-info-circle"></i> Payment Details</span>
            <span class="badge bg-{{ $statusClass }}">{{ $payment->status }}</span>
        </div>
        <div class="dash-panel-body">
            <div class="row g-3">
                <div class="col-md-6"><div class="text-muted small">Contract</div><div class="fw-semibold">{{ $payment->contract->contract_number ?? '-' }}</div></div>
                <div class="col-md-6"><div class="text-muted small">Party</div><div class="fw-semibold">{{ strtolower((string) optional($payment->contract)->contract_for) === 'group' ? optional($payment->contract->group)->group_name : optional(optional($payment->contract)->member)->full_name }}</div></div>
                <div class="col-md-4"><div class="text-muted small">Method</div><div class="fw-semibold">{{ $payment->paymentMethod->method_name ?? '-' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">Receipt</div><div class="fw-semibold">{{ $payment->receipt_number ?? '-' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">Type</div><div class="fw-semibold">{{ $payment->transactionType->type_name ?? ucfirst(str_replace('_', ' ', (string) $payment->payment_type)) }}</div></div>
                <div class="col-md-4"><div class="text-muted small">Reference</div><div class="fw-semibold">{{ $payment->reference ?? '-' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">Date</div><div class="fw-semibold">{{ optional($payment->transaction_date)->format('Y-m-d H:i') ?? '-' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">Created By</div><div class="fw-semibold">{{ $payment->creator->name ?? $payment->creator->full_name ?? '-' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">Approved By</div><div class="fw-semibold">{{ $payment->approver->name ?? $payment->approver->full_name ?? '-' }}</div></div>
                <div class="col-12"><div class="text-muted small">Notes</div><div>{{ $payment->notes ?: '-' }}</div></div>
            </div>
        </div>
    </div>
</div>
@endsection
