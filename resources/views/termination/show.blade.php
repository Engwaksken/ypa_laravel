@extends('layouts.app')

@section('title', 'Termination #' . $termination->id)

@section('content')
<div class="dash-wrap">
    <div class="dash-head">
        <div>
            <h1 class="dash-name">Termination #{{ $termination->id }}</h1>
            <div class="dash-date">{{ $termination->contract->contract_number ?? '-' }}</div>
        </div>
        <a href="{{ route('termination.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> Back</a>
    </div>

    <div class="dash-grid mb-4">
        <div class="dash-card accent-primary">
            <div class="dash-card-top">
                <span class="dash-card-title">Amount Paid</span>
                <span class="dash-card-icon"><i class="fas fa-money-bill-wave"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format((float) $termination->amount_paid, 2) }}</div>
        </div>
        <div class="dash-card accent-danger">
            <div class="dash-card-top">
                <span class="dash-card-title">Deduction</span>
                <span class="dash-card-icon"><i class="fas fa-minus-circle"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format((float) $termination->deduction_amount, 2) }}</div>
        </div>
        <div class="dash-card accent-success">
            <div class="dash-card-top">
                <span class="dash-card-title">Refund</span>
                <span class="dash-card-icon"><i class="fas fa-undo"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format((float) $termination->refund_amount, 2) }}</div>
        </div>
    </div>

    <div class="dash-panel">
        <div class="dash-panel-head">
            <span><i class="fas fa-info-circle"></i> Termination Details</span>
        </div>
        <div class="dash-panel-body">
            <div class="row g-3">
                <div class="col-md-6"><div class="text-muted small">Party</div><div class="fw-semibold">{{ strtolower((string) optional($termination->contract)->contract_for) === 'group' ? optional($termination->contract->group)->group_name : optional(optional($termination->contract)->member)->full_name }}</div></div>
                <div class="col-md-6"><div class="text-muted small">Date</div><div class="fw-semibold">{{ optional($termination->termination_date)->format('Y-m-d') ?? '-' }}</div></div>
                <div class="col-md-6"><div class="text-muted small">Reason</div><div class="fw-semibold">{{ $termination->reason }}</div></div>
                <div class="col-md-6"><div class="text-muted small">Created By</div><div class="fw-semibold">{{ $termination->creator->name ?? $termination->creator->full_name ?? '-' }}</div></div>
                <div class="col-12"><div class="text-muted small">Notes</div><div>{{ $termination->notes ?: '-' }}</div></div>
            </div>
        </div>
    </div>
</div>
@endsection
