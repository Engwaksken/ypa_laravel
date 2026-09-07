@extends('layouts.app')

@section('title', 'My Membership')

@section('content')
@php
    $permission = app(\App\Services\PermissionService::class);
    $money = fn ($amount) => 'UGX ' . number_format((float) $amount, 0);
@endphp

<div class="dash-wrap">

    <div class="dash-head">
        <div>
            <h1 class="dash-name">My Membership</h1>
            <div class="dash-date">{{ $member->membership_id }} &middot; {{ $member->full_name }}</div>
        </div>
        <a href="{{ route('member.dashboard') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="dash-grid mb-4">
        <div class="dash-card">
            <div class="dash-card-top">
                <span class="dash-card-title">Membership ID</span>
                <span class="dash-card-icon"><i class="fas fa-id-card"></i></span>
            </div>
            <div class="dash-card-value">{{ $member->membership_id }}</div>
        </div>
        <div class="dash-card">
            <div class="dash-card-top">
                <span class="dash-card-title">Status</span>
                <span class="dash-card-icon"><i class="fas fa-circle-check"></i></span>
            </div>
            <div class="dash-card-value">
                @php
                    $badge = match($member->membership_status) {
                        'Active' => 'success',
                        'Pending' => 'warning',
                        'Inactive' => 'secondary',
                        'Suspended' => 'danger',
                        'Terminated' => 'dark',
                        default => 'secondary',
                    };
                @endphp
                <span class="badge bg-{{ $badge }}">{{ $member->membership_status ?? '-' }}</span>
            </div>
        </div>
        <div class="dash-card">
            <div class="dash-card-top">
                <span class="dash-card-title">Branch</span>
                <span class="dash-card-icon"><i class="fas fa-building"></i></span>
            </div>
            <div class="dash-card-value">{{ $member->branch->name ?? '-' }}</div>
        </div>
        <div class="dash-card">
            <div class="dash-card-top">
                <span class="dash-card-title">Account Type</span>
                <span class="dash-card-icon"><i class="fas fa-wallet"></i></span>
            </div>
            <div class="dash-card-value">{{ $member->account_type ?? '-' }}</div>
        </div>
    </div>

    <div class="dash-panel">
        <div class="dash-panel-head">
            <span><i class="fas fa-money-bill-transfer"></i> Payment History</span>
        </div>
        <div class="dash-panel-body p-0">
            @if($hasPaymentTable)
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Reference</th>
                                <th>Amount Paid</th>
                                <th>Outstanding</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payments as $payment)
                                <tr>
                                    <td>{{ $payment->created_at ? \Illuminate\Support\Carbon::parse($payment->created_at)->format('d M Y H:i') : '-' }}</td>
                                    <td>{{ $payment->payment_reference ?? $payment->reference ?? '-' }}</td>
                                    <td>{{ $money($payment->total_amount_paid ?? 0) }}</td>
                                    <td>{{ $money($payment->total_amount_outstanding ?? 0) }}</td>
                                    <td>
                                        @php
                                            $paid = (float) ($payment->total_amount_paid ?? 0);
                                            $out = (float) ($payment->total_amount_outstanding ?? 0);
                                            $ps = $paid <= 0 ? 'UNPAID' : ($out <= 0.009 ? 'PAID' : 'PARTIAL');
                                            $pBadge = match($ps) { 'PAID' => 'success', 'PARTIAL' => 'warning', default => 'secondary' };
                                        @endphp
                                        <span class="badge bg-{{ $pBadge }}">{{ $ps }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No payment records found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-4 text-center text-muted">Payment history is not available yet.</div>
            @endif
        </div>
    </div>

    @if($nok)
        <div class="dash-panel mt-4">
            <div class="dash-panel-head">
                <span><i class="fas fa-user-group"></i> Next of Kin</span>
            </div>
            <div class="dash-panel-body">
                <div class="row">
                    <div class="col-md-3"><strong>Name:</strong> {{ $nok->full_name }}</div>
                    <div class="col-md-3"><strong>Relationship:</strong> {{ $nok->relationship ?? '-' }}</div>
                    <div class="col-md-3"><strong>Phone:</strong> {{ $nok->phone ?? '-' }}</div>
                    <div class="col-md-3"><strong>Email:</strong> {{ $nok->email ?? '-' }}</div>
                </div>
            </div>
        </div>
    @endif

    @if($bank)
        <div class="dash-panel mt-4">
            <div class="dash-panel-head">
                <span><i class="fas fa-building-columns"></i> Bank Details</span>
            </div>
            <div class="dash-panel-body">
                <div class="row">
                    <div class="col-md-3"><strong>Account Number:</strong> {{ $bank->bank_account ?? '-' }}</div>
                    <div class="col-md-3"><strong>Account Name:</strong> {{ $bank->account_name ?? '-' }}</div>
                    <div class="col-md-3"><strong>Bank Name:</strong> {{ $bank->bank_name ?? '-' }}</div>
                    <div class="col-md-3"><strong>Bank Branch:</strong> {{ $bank->bank_branch ?? '-' }}</div>
                </div>
            </div>
        </div>
    @endif

</div>
@endsection