@extends('layouts.app')

@section('title', 'Bank Details')

@section('content')
@php
    $permission = app(\App\Services\PermissionService::class);
    $old = old();
    $m = $member;
    $b = $bank;
@endphp

<div class="dash-wrap">

    <div class="dash-head">
        <div>
            <h1 class="dash-name">Bank Details</h1>
            <div class="dash-date">{{ $m->membership_id }} &middot; {{ $m->full_name }}</div>
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

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Please fix the following errors:</strong>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('member.bank_details.save') }}">
        @csrf

        <div class="dash-panel">
            <div class="dash-panel-head">
                <span><i class="fas fa-building-columns"></i> Bank Account Details</span>
            </div>
            <div class="dash-panel-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Bank Account Number</label>
                        <input type="text" name="bank_account" value="{{ $old['bank_account'] ?? $b->bank_account ?? '' }}" class="form-control @error('bank_account') is-invalid @enderror">
                        @error('bank_account')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Account Name</label>
                        <input type="text" name="account_name" value="{{ $old['account_name'] ?? $b->account_name ?? '' }}" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Bank Name</label>
                        <input type="text" name="bank_name" value="{{ $old['bank_name'] ?? $b->bank_name ?? '' }}" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Bank Branch</label>
                        <input type="text" name="bank_branch" value="{{ $old['bank_branch'] ?? $b->bank_branch ?? '' }}" class="form-control">
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 mt-4 mb-4">
            <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save"></i> Save Bank Details</button>
            <a href="{{ route('member.dashboard') }}" class="btn btn-outline-secondary btn-lg">Cancel</a>
        </div>
    </form>

</div>
@endsection