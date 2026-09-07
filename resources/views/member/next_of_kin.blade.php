@extends('layouts.app')

@section('title', 'Next of Kin')

@section('content')
@php
    $permission = app(\App\Services\PermissionService::class);
    $old = old();
    $m = $member;
    $n = $nok;
@endphp

<div class="dash-wrap">

    <div class="dash-head">
        <div>
            <h1 class="dash-name">Next of Kin</h1>
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

    <form method="POST" action="{{ route('member.next_of_kin.save') }}">
        @csrf

        <div class="dash-panel">
            <div class="dash-panel-head">
                <span><i class="fas fa-user-group"></i> Next of Kin Details</span>
            </div>
            <div class="dash-panel-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">First Name <span class="text-danger">*</span></label>
                        <input type="text" name="first_name" value="{{ $old['first_name'] ?? $n->first_name ?? '' }}" class="form-control @error('first_name') is-invalid @enderror" required>
                        @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Last Name <span class="text-danger">*</span></label>
                        <input type="text" name="last_name" value="{{ $old['last_name'] ?? $n->last_name ?? '' }}" class="form-control @error('last_name') is-invalid @enderror" required>
                        @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Relationship <span class="text-danger">*</span></label>
                        <input type="text" name="relationship" value="{{ $old['relationship'] ?? $n->relationship ?? '' }}" class="form-control @error('relationship') is-invalid @enderror" placeholder="e.g. Spouse, Parent, Sibling" required>
                        @error('relationship')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone <span class="text-danger">*</span></label>
                        <input type="text" name="phone" value="{{ $old['phone'] ?? $n->phone ?? '' }}" class="form-control @error('phone') is-invalid @enderror" placeholder="+256..." required>
                        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" value="{{ $old['email'] ?? $n->email ?? '' }}" class="form-control @error('email') is-invalid @enderror">
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">NIN</label>
                        <input type="text" name="nin" value="{{ $old['nin'] ?? $n->nin ?? '' }}" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Date of Birth</label>
                        <input type="date" name="date_of_birth" value="{{ $old['date_of_birth'] ?? ($n->date_of_birth ? $n->date_of_birth->format('Y-m-d') : '') }}" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Address</label>
                        <input type="text" name="address" value="{{ $old['address'] ?? $n->address ?? '' }}" class="form-control">
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 mt-4 mb-4">
            <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save"></i> Save Next of Kin</button>
            <a href="{{ route('member.dashboard') }}" class="btn btn-outline-secondary btn-lg">Cancel</a>
        </div>
    </form>

</div>
@endsection