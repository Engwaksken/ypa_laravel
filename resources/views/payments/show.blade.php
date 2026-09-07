@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h4 class="mb-0">{{ $payment->transaction_number }}</h4>
            <small class="text-muted">{{ $payment->transactionType->type_name ?? 'Payment Transaction' }}</small>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @if($payment->receipt_number)
                <a href="{{ route('payments.receipt', $payment) }}" class="btn btn-outline-primary">Receipt</a>
            @endif
            <a href="{{ route('payments.index') }}" class="btn btn-outline-dark">Back</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    @if(app(\App\Services\PermissionService::class)->can('payments_maintain'))
        <div class="card mb-3">
            <div class="card-header"><strong>Finance Actions</strong></div>
            <div class="card-body d-flex gap-2 flex-wrap">
                @if(strtoupper((string) $payment->status) === 'PENDING')
                    <form method="POST" action="{{ route('payments.approve', $payment) }}" class="d-flex gap-2">
                        @csrf
                        <input type="text" name="notes" class="form-control form-control-sm" placeholder="Approval note">
                        <button class="btn btn-sm btn-success" type="submit">Approve</button>
                    </form>
                    <form method="POST" action="{{ route('payments.reject', $payment) }}" class="d-flex gap-2">
                        @csrf
                        <input type="text" name="notes" class="form-control form-control-sm" placeholder="Rejection reason" required>
                        <button class="btn btn-sm btn-outline-danger" type="submit">Reject</button>
                    </form>
                @endif
                @if(strtoupper((string) $payment->status) === 'APPROVED' && strtoupper((string) $payment->reconciliation_status) !== 'RECONCILED')
                    <form method="POST" action="{{ route('payments.reconcile', $payment) }}" onsubmit="return confirm('Mark this payment as reconciled?')">
                        @csrf
                        <button class="btn btn-sm btn-outline-dark" type="submit">Reconcile</button>
                    </form>
                @endif
            </div>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-md-6"><div class="card"><div class="card-body"><strong>Contract:</strong> {{ $payment->contract->contract_number ?? '-' }}</div></div></div>
        <div class="col-md-6"><div class="card"><div class="card-body"><strong>Party:</strong> {{ strtolower((string) optional($payment->contract)->contract_for) === 'group' ? optional($payment->contract->group)->group_name : optional(optional($payment->contract)->member)->full_name }}</div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><strong>Method:</strong> {{ $payment->paymentMethod->method_name ?? '-' }}</div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><strong>Amount:</strong> {{ number_format((float) $payment->amount, 2) }}</div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><strong>Receipt:</strong> {{ $payment->receipt_number ?? '-' }}</div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><strong>Type:</strong> {{ $payment->transactionType->type_name ?? ucfirst(str_replace('_', ' ', (string) $payment->payment_type)) }}</div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><strong>Status:</strong> {{ $payment->status }}</div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><strong>Reconciliation:</strong> {{ $payment->reconciliation_status ?? '-' }}</div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><strong>Reference:</strong> {{ $payment->reference ?? '-' }}</div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><strong>Date:</strong> {{ optional($payment->transaction_date)->format('Y-m-d H:i') ?? '-' }}</div></div></div>
        <div class="col-md-6"><div class="card"><div class="card-body"><strong>Created By:</strong> {{ $payment->creator->name ?? $payment->creator->full_name ?? '-' }}</div></div></div>
        <div class="col-md-6"><div class="card"><div class="card-body"><strong>Approved By:</strong> {{ $payment->approver->name ?? $payment->approver->full_name ?? '-' }}</div></div></div>
        <div class="col-12"><div class="card"><div class="card-body"><strong>Notes:</strong><div class="mt-2">{{ $payment->notes ?: '-' }}</div></div></div></div>
    </div>
</div>
@endsection
