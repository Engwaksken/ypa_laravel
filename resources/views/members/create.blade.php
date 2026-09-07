@extends('layouts.app')

@section('title', 'Register Member')

@section('content')
@php
    $permission = app(\App\Services\PermissionService::class);
    $service = app(\App\Services\MemberService::class);
    $old = old();
@endphp

<div class="dash-wrap">

    <div class="dash-head">
        <div>
            <h1 class="dash-name">Register Member</h1>
            <div class="dash-date">Create a new member record</div>
        </div>
        <a href="{{ route('members.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to Members
        </a>
    </div>

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

    <form method="POST" action="{{ route('members.store') }}">
        @csrf

        <div class="dash-panel">
            <div class="dash-panel-head">
                <span><i class="fas fa-user"></i> Personal Information</span>
            </div>
            <div class="dash-panel-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">First Name <span class="text-danger">*</span></label>
                        <input type="text" name="first_name" value="{{ $old['first_name'] ?? '' }}" class="form-control @error('first_name') is-invalid @enderror" required>
                        @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Last Name <span class="text-danger">*</span></label>
                        <input type="text" name="last_name" value="{{ $old['last_name'] ?? '' }}" class="form-control @error('last_name') is-invalid @enderror" required>
                        @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Other Name</label>
                        <input type="text" name="other_name" value="{{ $old['other_name'] ?? '' }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                        <input type="date" name="date_of_birth" value="{{ $old['date_of_birth'] ?? '' }}" class="form-control @error('date_of_birth') is-invalid @enderror" required>
                        @error('date_of_birth')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Sex <span class="text-danger">*</span></label>
                        <select name="sex" class="form-select @error('sex') is-invalid @enderror" required>
                            <option value="">Select Sex</option>
                            @foreach($service::SEXES as $sex)
                                <option value="{{ $sex }}" @selected(($old['sex'] ?? '') === $sex)>{{ $sex }}</option>
                            @endforeach
                        </select>
                        @error('sex')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Nationality</label>
                        <input type="text" name="nationality" value="{{ $old['nationality'] ?? 'Uganda' }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">NIN</label>
                        <input type="text" name="nin" value="{{ $old['nin'] ?? '' }}" class="form-control @error('nin') is-invalid @enderror">
                        @error('nin')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">TIN Number</label>
                        <input type="text" name="tin_number" value="{{ $old['tin_number'] ?? '' }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Marital Status <span class="text-danger">*</span></label>
                        <select name="marital_status" class="form-select @error('marital_status') is-invalid @enderror" required>
                            <option value="">Select Marital Status</option>
                            @foreach($service::MARITAL_STATUSES as $ms)
                                <option value="{{ $ms }}" @selected(($old['marital_status'] ?? '') === $ms)>{{ $ms }}</option>
                            @endforeach
                        </select>
                        @error('marital_status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Number of Children</label>
                        <input type="number" name="children_count" min="0" value="{{ $old['children_count'] ?? 0 }}" class="form-control">
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
                        <input type="text" name="address" value="{{ $old['address'] ?? '' }}" class="form-control @error('address') is-invalid @enderror" required>
                        @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Region</label>
                        <select name="region" class="form-select @error('region') is-invalid @enderror">
                            <option value="">Select Region</option>
                            @foreach($service::REGIONS as $region)
                                <option value="{{ $region }}" @selected(($old['region'] ?? '') === $region)>{{ $region }}</option>
                            @endforeach
                        </select>
                        @error('region')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">District of Residence</label>
                        <input type="text" name="district_residence" value="{{ $old['district_residence'] ?? '' }}" class="form-control @error('district_residence') is-invalid @enderror">
                        @error('district_residence')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Home District</label>
                        <input type="text" name="district" value="{{ $old['district'] ?? '' }}" class="form-control @error('district') is-invalid @enderror">
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
                                <option value="{{ $es }}" @selected(($old['employment_status'] ?? '') === $es)>{{ $es }}</option>
                            @endforeach
                        </select>
                        @error('employment_status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6" id="employment_other_wrap" style="{{ ($old['employment_status'] ?? '') === 'Other' ? '' : 'display:none;' }}">
                        <label class="form-label">Specify Employment (Other) <span class="text-danger">*</span></label>
                        <input type="text" name="employment_other" value="{{ $old['employment_other'] ?? '' }}" class="form-control @error('employment_other') is-invalid @enderror">
                        @error('employment_other')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">How did you know about us? <span class="text-danger">*</span></label>
                        <select name="source" id="source" class="form-select @error('source') is-invalid @enderror" required>
                            <option value="">Select Source</option>
                            @foreach($service::SOURCES as $src)
                                <option value="{{ $src }}" @selected(($old['source'] ?? '') === $src)>{{ $src }}</option>
                            @endforeach
                        </select>
                        @error('source')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6" id="source_station_wrap" style="{{ in_array($old['source'] ?? '', ['Radio', 'TV']) ? '' : 'display:none;' }}">
                        <label class="form-label" id="source_station_label">Station</label>
                        <select name="source_station" id="source_station" class="form-select @error('source_station') is-invalid @enderror">
                            <option value="">Select Station</option>
                        </select>
                        @error('source_station')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6" id="source_other_wrap" style="{{ ($old['source'] ?? '') === 'Other' ? '' : 'display:none;' }}">
                        <label class="form-label">Specify Source (Other) <span class="text-danger">*</span></label>
                        <input type="text" name="source_other" value="{{ $old['source_other'] ?? '' }}" class="form-control @error('source_other') is-invalid @enderror">
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
                        <label class="form-label">Email</label>
                        <input type="email" name="email" value="{{ $old['email'] ?? '' }}" class="form-control @error('email') is-invalid @enderror">
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Telephone 1 <span class="text-danger">*</span></label>
                        <input type="text" name="telephone1" value="{{ $old['telephone1'] ?? '' }}" class="form-control @error('telephone1') is-invalid @enderror" placeholder="+256..." required>
                        @error('telephone1')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Telephone 2</label>
                        <input type="text" name="telephone2" value="{{ $old['telephone2'] ?? '' }}" class="form-control @error('telephone2') is-invalid @enderror" placeholder="+256...">
                        @error('telephone2')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Account Type</label>
                        <select name="account_type" class="form-select @error('account_type') is-invalid @enderror">
                            <option value="">Select Account Type</option>
                            @foreach($service::ACCOUNT_TYPES as $at)
                                <option value="{{ $at }}" @selected(($old['account_type'] ?? '') === $at)>{{ $at }}</option>
                            @endforeach
                        </select>
                        @error('account_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="dash-panel mt-4">
            <div class="dash-panel-head">
                <span><i class="fas fa-user-group"></i> Parents Information</span>
            </div>
            <div class="dash-panel-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Mother Name</label>
                        <input type="text" name="mother_name" value="{{ $old['mother_name'] ?? '' }}" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Mother Phone</label>
                        <input type="text" name="mother_phone" value="{{ $old['mother_phone'] ?? '' }}" class="form-control @error('mother_phone') is-invalid @enderror" placeholder="+256...">
                        @error('mother_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Father Name</label>
                        <input type="text" name="father_name" value="{{ $old['father_name'] ?? '' }}" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Father Phone</label>
                        <input type="text" name="father_phone" value="{{ $old['father_phone'] ?? '' }}" class="form-control @error('father_phone') is-invalid @enderror" placeholder="+256...">
                        @error('father_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="dash-panel mt-4">
            <div class="dash-panel-head">
                <span><i class="fas fa-building-columns"></i> Bank Details</span>
            </div>
            <div class="dash-panel-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Bank Account Number</label>
                        <input type="text" name="bank_account" value="{{ $old['bank_account'] ?? '' }}" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Account Name</label>
                        <input type="text" name="bank_account_name" value="{{ $old['bank_account_name'] ?? '' }}" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Bank Name</label>
                        <input type="text" name="bank_name" value="{{ $old['bank_name'] ?? '' }}" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Bank Branch</label>
                        <input type="text" name="bank_branch" value="{{ $old['bank_branch'] ?? '' }}" class="form-control">
                    </div>
                </div>
            </div>
        </div>

        <div class="dash-panel mt-4">
            <div class="dash-panel-head">
                <span><i class="fas fa-diagram-project"></i> Membership Assignment</span>
            </div>
            <div class="dash-panel-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Branch <span class="text-danger">*</span></label>
                        <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror" required>
                            <option value="">Select Branch</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" @selected((int) ($old['branch_id'] ?? 0) === (int) $branch->id)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Mobilizer <span class="text-danger">*</span></label>
                        <select name="mobilizer_id" class="form-select @error('mobilizer_id') is-invalid @enderror" required>
                            <option value="">Select Mobilizer</option>
                            @foreach($mobilizers as $mobilizer)
                                <option value="{{ $mobilizer->id }}" @selected((int) ($old['mobilizer_id'] ?? 0) === (int) $mobilizer->id)>{{ $mobilizer->full_name }}</option>
                            @endforeach
                        </select>
                        @error('mobilizer_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Membership Status</label>
                        <select name="membership_status" class="form-select @error('membership_status') is-invalid @enderror">
                            <option value="">Pending</option>
                            @foreach($service::MEMBERSHIP_STATUSES as $ms)
                                <option value="{{ $ms }}" @selected(($old['membership_status'] ?? 'Pending') === $ms)>{{ $ms }}</option>
                            @endforeach
                        </select>
                        @error('membership_status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 mt-4 mb-4">
            <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save"></i> Register Member</button>
            <a href="{{ route('members.index') }}" class="btn btn-outline-secondary btn-lg">Cancel</a>
        </div>
    </form>

</div>
@endsection

@push('scripts')
<script>
(function () {
    const sourceSelect = document.getElementById('source');
    const stationWrap = document.getElementById('source_station_wrap');
    const stationLabel = document.getElementById('source_station_label');
    const stationSelect = document.getElementById('source_station');
    const otherWrap = document.getElementById('source_other_wrap');
    const employmentSelect = document.getElementById('employment_status');
    const employmentOtherWrap = document.getElementById('employment_other_wrap');

    const radioStations = @json($service::RADIO_STATIONS);
    const tvStations = @json($service::TV_STATIONS);

    function renderStations() {
        const source = sourceSelect.value;
        if (source === 'Radio' || source === 'TV') {
            stationWrap.style.display = '';
            stationLabel.textContent = source === 'Radio' ? 'Radio Station' : 'TV Station';
            const stations = source === 'Radio' ? radioStations : tvStations;
            stationSelect.innerHTML = '<option value="">Select Station</option>' +
                stations.map(s => '<option value="' + s + '">' + s + '</option>').join('');
        } else {
            stationWrap.style.display = 'none';
            stationSelect.innerHTML = '<option value="">Select Station</option>';
        }
        otherWrap.style.display = source === 'Other' ? '' : 'none';
    }

    function renderEmployment() {
        employmentOtherWrap.style.display = employmentSelect.value === 'Other' ? '' : 'none';
    }

    sourceSelect.addEventListener('change', renderStations);
    employmentSelect.addEventListener('change', renderEmployment);
    renderStations();
    renderEmployment();
})();
</script>
@endpush