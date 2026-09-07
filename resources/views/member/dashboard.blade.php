@extends('layouts.app')

@section('title', 'My Dashboard')

@section('content')
@php
    $permission = app(\App\Services\PermissionService::class);
    $money = fn ($amount) => 'UGX ' . number_format((float) $amount, 0);
@endphp

<div class="dash-wrap">

    <div class="dash-head">
        <div class="dash-user">
            <div class="dash-avatar">{{ strtoupper(mb_substr($member->first_name ?? 'M', 0, 1)) }}</div>
            <div>
                <div class="dash-greeting">{{ $greeting }}</div>
                <h1 class="dash-name">{{ $member->full_name }}</h1>
                <div class="dash-date">{{ $member->membership_id }} &middot; {{ now()->format('l, F j, Y') }}</div>
            </div>
        </div>
        <div class="role-badge">
            <i class="fas fa-id-card"></i>
            {{ $member->membership_status ?? 'Member' }}
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="dash-panel">
        <div class="dash-panel-head">
            <span><i class="fas fa-bolt"></i> Quick Actions</span>
        </div>
        <div class="dash-panel-body">
            <div class="quick-actions">
                <a class="quick-btn" href="{{ route('member.profile') }}"><i class="fas fa-user-edit"></i> My Profile</a>
                <a class="quick-btn" href="{{ route('member.next_of_kin') }}"><i class="fas fa-user-group"></i> Next of Kin</a>
                <a class="quick-btn" href="{{ route('member.bank_details') }}"><i class="fas fa-building-columns"></i> Bank Details</a>
                <a class="quick-btn secondary" href="{{ route('membership') }}"><i class="fas fa-id-card"></i> Membership</a>
            </div>
        </div>
    </div>

    <div class="dash-section">
        <h2 class="dash-section-title"><i class="fas fa-user"></i> My Information</h2>
        <div class="dash-grid">
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
    </div>

    <div class="dash-panel">
        <div class="dash-panel-head">
            <span><i class="fas fa-address-book"></i> Contact Summary</span>
        </div>
        <div class="dash-panel-body">
            <div class="row">
                <div class="col-md-3"><strong>Email:</strong> {{ $member->email ?? '-' }}</div>
                <div class="col-md-3"><strong>Telephone 1:</strong> {{ $member->telephone1 ?? '-' }}</div>
                <div class="col-md-3"><strong>Telephone 2:</strong> {{ $member->telephone2 ?? '-' }}</div>
                <div class="col-md-3"><strong>Address:</strong> {{ $member->address ?? '-' }}</div>
            </div>
        </div>
    </div>

    @if($nok)
        <div class="dash-panel mt-4">
            <div class="dash-panel-head">
                <span><i class="fas fa-user-group"></i> Next of Kin</span>
                <a href="{{ route('member.next_of_kin') }}" class="quick-btn secondary">Edit</a>
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
                <a href="{{ route('member.bank_details') }}" class="quick-btn secondary">Edit</a>
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