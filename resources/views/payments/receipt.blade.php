@extends('layouts.app')

@section('content')
@php
    $contract = $payment->contract;
    $party = strtolower((string) optional($contract)->contract_for) === 'group'
        ? optional(optional($contract)->group)->group_name
        : optional(optional($contract)->member)->full_name;
@endphp

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h4 class="mb-0">Payment Receipt</h4>
            <small class="text-muted">{{ $payment->receipt_number }}</small>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary" onclick="window.print()" type="button">Print</button>
            <a href="{{ route('payments.show', $payment) }}" class="btn btn-outline-dark">Back</a>
        </div>
    </div>

    <div class="card receipt-card">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between border-bottom pb-3 mb-3">
                <div>
                    <h5 class="mb-1">Youth Platform Africa</h5>
                    <div class="text-muted">Official payment receipt</div>
                </div>
                <div class="text-end">
                    <div><strong>Receipt:</strong> {{ $payment->receipt_number }}</div>
                    <div><strong>Date:</strong> {{ optional($payment->transaction_date)->format('Y-m-d') ?? optional($payment->created_at)->format('Y-m-d') }}</div>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6"><strong>Received From:</strong><br>{{ $party ?: '-' }}</div>
                <div class="col-md-6"><strong>Contract:</strong><br>{{ optional($contract)->contract_number ?? '-' }}</div>
                <div class="col-md-6"><strong>Payment Method:</strong><br>{{ $payment->paymentMethod->method_name ?? '-' }}</div>
                <div class="col-md-6"><strong>Reference:</strong><br>{{ $payment->reference ?? '-' }}</div>
            </div>

            <div class="table-responsive mb-3">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ $payment->transactionType->type_name ?? ucfirst(str_replace('_', ' ', (string) $payment->payment_type)) }}</td>
                            <td class="text-end">UGX {{ number_format((float) $payment->amount, 2) }}</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>Total</th>
                            <th class="text-end">UGX {{ number_format((float) $payment->amount, 2) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="row g-3">
                <div class="col-md-6"><strong>Status:</strong> {{ $payment->status }}</div>
                <div class="col-md-6"><strong>Received By:</strong> {{ $payment->creator->name ?? $payment->creator->full_name ?? '-' }}</div>
                <div class="col-12"><strong>Notes:</strong> {{ $payment->notes ?: '-' }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
