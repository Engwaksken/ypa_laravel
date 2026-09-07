@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h4 class="mb-0">Termination #{{ $termination->id }}</h4>
            <small class="text-muted">{{ $termination->contract->contract_number ?? '-' }}</small>
        </div>
        <a href="{{ route('termination.index') }}" class="btn btn-outline-dark">Back</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row g-3">
        <div class="col-md-6"><div class="card"><div class="card-body"><strong>Party:</strong> {{ strtolower((string) optional($termination->contract)->contract_for) === 'group' ? optional($termination->contract->group)->group_name : optional(optional($termination->contract)->member)->full_name }}</div></div></div>
        <div class="col-md-6"><div class="card"><div class="card-body"><strong>Date:</strong> {{ optional($termination->termination_date)->format('Y-m-d') ?? '-' }}</div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><strong>Amount Paid:</strong> {{ number_format((float) $termination->amount_paid, 2) }}</div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><strong>Deduction:</strong> {{ number_format((float) $termination->deduction_amount, 2) }}</div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><strong>Refund:</strong> {{ number_format((float) $termination->refund_amount, 2) }}</div></div></div>
        <div class="col-md-6"><div class="card"><div class="card-body"><strong>Reason:</strong> {{ $termination->reason }}</div></div></div>
        <div class="col-md-6"><div class="card"><div class="card-body"><strong>Created By:</strong> {{ $termination->creator->name ?? $termination->creator->full_name ?? '-' }}</div></div></div>
        <div class="col-12"><div class="card"><div class="card-body"><strong>Notes:</strong><div class="mt-2">{{ $termination->notes ?: '-' }}</div></div></div></div>
    </div>
</div>
@endsection
