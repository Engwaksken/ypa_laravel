@extends('layouts.app')

@section('title', 'Member Details')

@section('content')
@php
    $permission = app(\App\Services\PermissionService::class);
    $canEdit = $permission->can('members_edit');
    $canDelete = $permission->can('members_delete');
    $m = $member;
@endphp

<div class="dash-wrap">

    <div class="dash-head">
        <div>
            <h1 class="dash-name">{{ $m->full_name }}</h1>
            <div class="dash-date">{{ $m->membership_id }}</div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('members.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Members
            </a>
            @if($canEdit)
                <a href="{{ route('members.edit', $m) }}" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Edit Member
                </a>
            @endif
            @if($canDelete)
                <form action="{{ route('members.destroy', $m) }}" method="POST" onsubmit="return confirm('Delete this member? This cannot be undone.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> Delete</button>
                </form>
            @endif
        </div>
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
                <span class="dash-card-title">Membership Status</span>
                <span class="dash-card-icon"><i class="fas fa-id-card"></i></span>
            </div>
            <div class="dash-card-value">
                @php
                    $badge = match($m->membership_status) {
                        'Active' => 'success',
                        'Pending' => 'warning',
                        'Inactive' => 'secondary',
                        'Suspended' => 'danger',
                        'Terminated' => 'dark',
                        default => 'secondary',
                    };
                @endphp
                <span class="badge bg-{{ $badge }}">{{ $m->membership_status ?? '-' }}</span>
            </div>
        </div>
        <div class="dash-card">
            <div class="dash-card-top">
                <span class="dash-card-title">Branch</span>
                <span class="dash-card-icon"><i class="fas fa-building"></i></span>
            </div>
            <div class="dash-card-value">{{ $m->branch->name ?? '-' }}</div>
        </div>
        <div class="dash-card">
            <div class="dash-card-top">
                <span class="dash-card-title">Mobilizer</span>
                <span class="dash-card-icon"><i class="fas fa-users-cog"></i></span>
            </div>
            <div class="dash-card-value">{{ $m->mobilizer->full_name ?? '-' }}</div>
        </div>
        <div class="dash-card">
            <div class="dash-card-top">
                <span class="dash-card-title">Account Type</span>
                <span class="dash-card-icon"><i class="fas fa-wallet"></i></span>
            </div>
            <div class="dash-card-value">{{ $m->account_type ?? '-' }}</div>
        </div>
    </div>

    <div class="dash-panel">
        <div class="dash-panel-head">
            <span><i class="fas fa-user"></i> Personal Information</span>
        </div>
        <div class="dash-panel-body">
            <div class="row">
                <div class="col-md-3"><strong>First Name:</strong> {{ $m->first_name }}</div>
                <div class="col-md-3"><strong>Last Name:</strong> {{ $m->last_name }}</div>
                <div class="col-md-3"><strong>Other Name:</strong> {{ $m->other_name ?? '-' }}</div>
                <div class="col-md-3"><strong>Date of Birth:</strong> {{ $m->date_of_birth ? $m->date_of_birth->format('d M Y') : '-' }}</div>
                <div class="col-md-3 mt-3"><strong>Sex:</strong> {{ $m->sex ?? '-' }}</div>
                <div class="col-md-3 mt-3"><strong>Nationality:</strong> {{ $m->nationality ?? '-' }}</div>
                <div class="col-md-3 mt-3"><strong>NIN:</strong> {{ $m->nin ?? '-' }}</div>
                <div class="col-md-3 mt-3"><strong>TIN Number:</strong> {{ $m->tin_number ?? '-' }}</div>
                <div class="col-md-3 mt-3"><strong>Marital Status:</strong> {{ $m->marital_status ?? '-' }}</div>
                <div class="col-md-3 mt-3"><strong>Children:</strong> {{ $m->children_count ?? 0 }}</div>
            </div>
        </div>
    </div>

    <div class="dash-panel mt-4">
        <div class="dash-panel-head">
            <span><i class="fas fa-location-dot"></i> Address & Location</span>
        </div>
        <div class="dash-panel-body">
            <div class="row">
                <div class="col-md-3"><strong>Address:</strong> {{ $m->address ?? '-' }}</div>
                <div class="col-md-3"><strong>Region:</strong> {{ $m->region ?? '-' }}</div>
                <div class="col-md-3"><strong>District of Residence:</strong> {{ $m->district_residence ?? '-' }}</div>
                <div class="col-md-3"><strong>Home District:</strong> {{ $m->district ?? '-' }}</div>
            </div>
        </div>
    </div>

    <div class="dash-panel mt-4">
        <div class="dash-panel-head">
            <span><i class="fas fa-briefcase"></i> Employment & Source</span>
        </div>
        <div class="dash-panel-body">
            <div class="row">
                <div class="col-md-3"><strong>Employment:</strong> {{ $m->employment_status ?? '-' }}</div>
                <div class="col-md-3"><strong>Source:</strong> {{ $m->source ?? '-' }}</div>
                <div class="col-md-3"><strong>Source Station:</strong> {{ $m->source_station ?? '-' }}</div>
                <div class="col-md-3"><strong>Source Other:</strong> {{ $m->source_other ?? '-' }}</div>
            </div>
        </div>
    </div>

    <div class="dash-panel mt-4">
        <div class="dash-panel-head">
            <span><i class="fas fa-phone"></i> Contact Information</span>
        </div>
        <div class="dash-panel-body">
            <div class="row">
                <div class="col-md-3"><strong>Email:</strong> {{ $m->email ?? '-' }}</div>
                <div class="col-md-3"><strong>Telephone 1:</strong> {{ $m->telephone1 ?? '-' }}</div>
                <div class="col-md-3"><strong>Telephone 2:</strong> {{ $m->telephone2 ?? '-' }}</div>
                <div class="col-md-3"><strong>Account Type:</strong> {{ $m->account_type ?? '-' }}</div>
                <div class="col-md-3 mt-3"><strong>Mother:</strong> {{ $m->mother_name ?? '-' }} {{ $m->mother_phone ? '(' . $m->mother_phone . ')' : '' }}</div>
                <div class="col-md-3 mt-3"><strong>Father:</strong> {{ $m->father_name ?? '-' }} {{ $m->father_phone ? '(' . $m->father_phone . ')' : '' }}</div>
            </div>
        </div>
    </div>

    <div class="dash-panel mt-4">
        <div class="dash-panel-head">
            <span><i class="fas fa-building-columns"></i> Bank Details</span>
        </div>
        <div class="dash-panel-body">
            <div class="row">
                <div class="col-md-3"><strong>Account Number:</strong> {{ $m->bank_account ?? '-' }}</div>
                <div class="col-md-3"><strong>Account Name:</strong> {{ $m->bank_account_name ?? '-' }}</div>
                <div class="col-md-3"><strong>Bank Name:</strong> {{ $m->bank_name ?? '-' }}</div>
                <div class="col-md-3"><strong>Bank Branch:</strong> {{ $m->bank_branch ?? '-' }}</div>
            </div>
        </div>
    </div>

    @if($m->nextOfKin->isNotEmpty())
        <div class="dash-panel mt-4">
            <div class="dash-panel-head">
                <span><i class="fas fa-user-group"></i> Next of Kin</span>
            </div>
            <div class="dash-panel-body">
                <div class="row">
                    <div class="col-md-3"><strong>Name:</strong> {{ $m->nextOfKin->first()->full_name }}</div>
                    <div class="col-md-3"><strong>Relationship:</strong> {{ $m->nextOfKin->first()->relationship ?? '-' }}</div>
                    <div class="col-md-3"><strong>Phone:</strong> {{ $m->nextOfKin->first()->phone ?? '-' }}</div>
                    <div class="col-md-3"><strong>Email:</strong> {{ $m->nextOfKin->first()->email ?? '-' }}</div>
                </div>
            </div>
        </div>
    @endif

    @if($m->bankDetail)
        <div class="dash-panel mt-4">
            <div class="dash-panel-head">
                <span><i class="fas fa-building-columns"></i> Bank Details (Linked)</span>
            </div>
            <div class="dash-panel-body">
                <div class="row">
                    <div class="col-md-3"><strong>Account Number:</strong> {{ $m->bankDetail->bank_account ?? '-' }}</div>
                    <div class="col-md-3"><strong>Account Name:</strong> {{ $m->bankDetail->account_name ?? '-' }}</div>
                    <div class="col-md-3"><strong>Bank Name:</strong> {{ $m->bankDetail->bank_name ?? '-' }}</div>
                    <div class="col-md-3"><strong>Bank Branch:</strong> {{ $m->bankDetail->bank_branch ?? '-' }}</div>
                </div>
            </div>
        </div>
    @endif

    <div class="dash-panel mt-4">
        <div class="dash-panel-head">
            <span><i class="fas fa-clock"></i> Record Info</span>
        </div>
        <div class="dash-panel-body">
            <div class="row">
                <div class="col-md-3"><strong>Created:</strong> {{ $m->created_at ? $m->created_at->format('d M Y H:i') : '-' }}</div>
                <div class="col-md-3"><strong>Updated:</strong> {{ $m->updated_at ? $m->updated_at->format('d M Y H:i') : '-' }}</div>
            </div>
        </div>
    </div>

</div>
@endsection