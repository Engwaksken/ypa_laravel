@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
@php
    $permission = app(\App\Services\PermissionService::class);
    $service = app(\App\Services\MemberService::class);
    $old = old();
    $m = $member;
@endphp

<div class="dash-wrap">

    <div class="dash-head">
        <div>
            <h1 class="dash-name">My Profile</h1>
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

    <form method="POST" action="{{ route('member.profile.update') }}">
        @csrf

        <div class="dash-panel">
            <div class="dash-panel-head">
                <span><i class="fas fa-user"></i> Personal Information</span>
            </div>
            <div class="dash-panel-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">First Name</label>
                        <input type="text" value="{{ $m->first_name }}" class="form-control" disabled>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Last Name</label>
                        <input type="text" value="{{ $m->last_name }}" class="form-control" disabled>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Other Name</label>
                        <input type="text" value="{{ $m->other_name ?? '' }}" class="form-control" disabled>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Date of Birth</label>
                        <input type="text" value="{{ $m->date_of_birth ? $m->date_of_birth->format('d M Y') : '' }}" class="form-control" disabled>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Sex</label>
                        <input type="text" value="{{ $m->sex ?? '' }}" class="form-control" disabled>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Nationality <span class="text-danger">*</span></label>
                        <input type="text" name="nationality" value="{{ $old['nationality'] ?? $m->nationality }}" class="form-control @error('nationality') is-invalid @enderror" required>
                        @error('nationality')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Marital Status <span class="text-danger">*</span></label>
                        <select name="marital_status" class="form-select @error('marital_status') is-invalid @enderror" required>
                            <option value="">Select Marital Status</option>
                            @foreach($service::MARITAL_STATUSES as $ms)
                                <option value="{{ $ms }}" @selected(($old['marital_status'] ?? $m->marital_status) === $ms)>{{ $ms }}</option>
                            @endforeach
                        </select>
                        @error('marital_status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Number of Children</label>
                        <input type="number" name="children_count" min="0" value="{{ $old['children_count'] ?? $m->children_count }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Account Type <span class="text-danger">*</span></label>
                        <select name="account_type" class="form-select @error('account_type') is-invalid @enderror" required>
                            <option value="">Select Account Type</option>
                            @foreach($service::ACCOUNT_TYPES as $at)
                                <option value="{{ $at }}" @selected(($old['account_type'] ?? $m->account_type) === $at)>{{ $at }}</option>
                            @endforeach
                        </select>
                        @error('account_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="dash-panel mt-4">
            <div class="dash-panel-head">
                <span><i class="fas fa-location-dot"></i> Address & Location</span>
            </div>
            <div class="dash-panel-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Address / Residence <span class="text-danger">*</span></label>
                        <input type="text" name="address" value="{{ $old['address'] ?? $m->address }}" class="form-control @error('address') is-invalid @enderror" required>
                        @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Region</label>
                        <select name="region" class="form-select @error('region') is-invalid @enderror">
                            <option value="">Select Region</option>
                            @foreach($service::REGIONS as $region)
                                <option value="{{ $region }}" @selected(($old['region'] ?? $m->region) === $region)>{{ $region }}</option>
                            @endforeach
                        </select>
                        @error('region')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">District of Residence</label>
                        <input type="text" name="district_residence" value="{{ $old['district_residence'] ?? $m->district_residence }}" class="form-control @error('district_residence') is-invalid @enderror">
                        @error('district_residence')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Home District</label>
                        <input type="text" name="district" value="{{ $old['district'] ?? $m->district }}" class="form-control @error('district') is-invalid @enderror">
                        @error('district')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="dash-panel mt-4">
            <div class="dash-panel-head">
                <span><i class="fas fa-briefcase"></i> Employment & Source</span>
            </div>
            <div class="dash-panel-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Employment Status <span class="text-danger">*</span></label>
                        <select name="employment_status" id="employment_status" class="form-select @error('employment_status') is-invalid @enderror" required>
                            <option value="">Select Employment Status</option>
                            @foreach($service::EMPLOYMENT_STATUSES as $es)
                                <option value="{{ $es }}" @selected(($old['employment_status'] ?? $m->employment_status) === $es)>{{ $es }}</option>
                            @endforeach
                        </select>
                        @error('employment_status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6" id="employment_other_wrap" style="{{ ($old['employment_status'] ?? $m->employment_status) === 'Other' ? '' : 'display:none;' }}">
                        <label class="form-label">Specify Employment (Other) <span class="text-danger">*</span></label>
                        <input type="text" name="employment_other" value="{{ $old['employment_other'] ?? $m->employment_other }}" class="form-control @error('employment_other') is-invalid @enderror">
                        @error('employment_other')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">How did you know about us? <span class="text-danger">*</span></label>
                        <select name="source" id="source" class="form-select @error('source') is-invalid @enderror" required>
                            <option value="">Select Source</option>
                            @foreach($service::SOURCES as $src)
                                <option value="{{ $src }}" @selected(($old['source'] ?? $m->source) === $src)>{{ $src }}</option>
                            @endforeach
                        </select>
                        @error('source')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6" id="source_other_wrap" style="{{ ($old['source'] ?? $m->source) === 'Other' ? '' : 'display:none;' }}">
                        <label class="form-label">Specify Source (Other) <span class="text-danger">*</span></label>
                        <input type="text" name="source_other" value="{{ $old['source_other'] ?? $m->source_other }}" class="form-control @error('source_other') is-invalid @enderror">
                        @error('source_other')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="dash-panel mt-4">
            <div class="dash-panel-head">
                <span><i class="fas fa-phone"></i> Contact Information</span>
            </div>
            <div class="dash-panel-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" value="{{ $old['email'] ?? $m->email }}" class="form-control @error('email') is-invalid @enderror" required>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Telephone 1 <span class="text-danger">*</span></label>
                        <input type="text" name="telephone1" value="{{ $old['telephone1'] ?? $m->telephone1 }}" class="form-control @error('telephone1') is-invalid @enderror" placeholder="+256..." required>
                        @error('telephone1')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Telephone 2</label>
                        <input type="text" name="telephone2" value="{{ $old['telephone2'] ?? $m->telephone2 }}" class="form-control @error('telephone2') is-invalid @enderror" placeholder="+256...">
                        @error('telephone2')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Mother Name</label>
                        <input type="text" name="mother_name" value="{{ $old['mother_name'] ?? $m->mother_name }}" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Mother Phone</label>
                        <input type="text" name="mother_phone" value="{{ $old['mother_phone'] ?? $m->mother_phone }}" class="form-control @error('mother_phone') is-invalid @enderror" placeholder="+256...">
                        @error('mother_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Father Name</label>
                        <input type="text" name="father_name" value="{{ $old['father_name'] ?? $m->father_name }}" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Father Phone</label>
                        <input type="text" name="father_phone" value="{{ $old['father_phone'] ?? $m->father_phone }}" class="form-control @error('father_phone') is-invalid @enderror" placeholder="+256...">
                        @error('father_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 mt-4 mb-4">
            <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save"></i> Update Profile</button>
            <a href="{{ route('member.dashboard') }}" class="btn btn-outline-secondary btn-lg">Cancel</a>
        </div>
    </form>

</div>
@endsection

@push('scripts')
<script>
(function () {
    const sourceSelect = document.getElementById('source');
    const otherWrap = document.getElementById('source_other_wrap');
    const employmentSelect = document.getElementById('employment_status');
    const employmentOtherWrap = document.getElementById('employment_other_wrap');

    function render() {
        otherWrap.style.display = sourceSelect.value === 'Other' ? '' : 'none';
        employmentOtherWrap.style.display = employmentSelect.value === 'Other' ? '' : 'none';
    }

    sourceSelect.addEventListener('change', render);
    employmentSelect.addEventListener('change', render);
    render();
})();
</script>
@endpush