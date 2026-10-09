{{--
    Shared member form fields (used by the index/show modal and the fallback create/edit pages).
    Params:
      $branches, $mobilizers  - dropdown data
      $record                 - array of values (see MemberController::formRecord) or null
      $useOld                 - true on full pages; the modal relies on modal-validation to restore old input
--}}
@php
    $service = \App\Services\MemberService::class;
    $record = $record ?? [];
    $useOld = $useOld ?? false;
    $v = fn (string $key, $default = '') => (string) ($useOld ? old($key, $record[$key] ?? $default) : ($record[$key] ?? $default));
    $stationKind = in_array($v('source'), ['Radio', 'TV'], true) ? $v('source') : '';
@endphp

<div class="row g-3 member-form-fields">
    <div class="col-12"><div class="modal-section-title">Personal details</div></div>
    <div class="col-md-4">
        <label class="form-label">First Name <span class="text-danger">*</span></label>
        <input type="text" name="first_name" value="{{ $v('first_name') }}" class="form-control @error('first_name') is-invalid @enderror" required maxlength="255">
        @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Last Name <span class="text-danger">*</span></label>
        <input type="text" name="last_name" value="{{ $v('last_name') }}" class="form-control @error('last_name') is-invalid @enderror" required maxlength="255">
        @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Other Name</label>
        <input type="text" name="other_name" value="{{ $v('other_name') }}" class="form-control" maxlength="255">
    </div>
    <div class="col-md-4">
        <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
        <input type="date" name="date_of_birth" value="{{ $v('date_of_birth') }}" class="form-control @error('date_of_birth') is-invalid @enderror" required>
        @error('date_of_birth')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Sex <span class="text-danger">*</span></label>
        <select name="sex" class="form-select @error('sex') is-invalid @enderror" required>
            <option value="">Select Sex</option>
            @foreach($service::SEXES as $sex)
                <option value="{{ $sex }}" @selected($v('sex') === $sex)>{{ $sex }}</option>
            @endforeach
        </select>
        @error('sex')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Marital Status <span class="text-danger">*</span></label>
        <select name="marital_status" class="form-select @error('marital_status') is-invalid @enderror" required>
            <option value="">Select Marital Status</option>
            @foreach($service::MARITAL_STATUSES as $ms)
                <option value="{{ $ms }}" @selected($v('marital_status') === $ms)>{{ $ms }}</option>
            @endforeach
        </select>
        @error('marital_status')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Nationality</label>
        <input type="text" name="nationality" value="{{ $v('nationality', 'Uganda') }}" class="form-control" maxlength="255">
    </div>
    <div class="col-md-4">
        <label class="form-label">NIN</label>
        <input type="text" name="nin" value="{{ $v('nin') }}" class="form-control @error('nin') is-invalid @enderror" maxlength="255">
        @error('nin')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-2">
        <label class="form-label">TIN Number</label>
        <input type="text" name="tin_number" value="{{ $v('tin_number') }}" class="form-control" maxlength="255">
    </div>
    <div class="col-md-2">
        <label class="form-label">Children</label>
        <input type="number" name="children_count" min="0" value="{{ $v('children_count', 0) }}" class="form-control">
    </div>

    <div class="col-12"><div class="modal-section-title">Address &amp; location</div></div>
    <div class="col-md-6">
        <label class="form-label">Address / Residence <span class="text-danger">*</span></label>
        <input type="text" name="address" value="{{ $v('address') }}" class="form-control @error('address') is-invalid @enderror" required>
        @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Region <small class="text-muted">(required for Uganda)</small></label>
        <select name="region" class="form-select @error('region') is-invalid @enderror">
            <option value="">Select Region</option>
            @foreach($service::REGIONS as $region)
                <option value="{{ $region }}" @selected($v('region') === $region)>{{ $region }}</option>
            @endforeach
        </select>
        @error('region')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">District of Residence <small class="text-muted">(required for Uganda)</small></label>
        <input type="text" name="district_residence" value="{{ $v('district_residence') }}" class="form-control @error('district_residence') is-invalid @enderror" maxlength="255">
        @error('district_residence')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Home District <small class="text-muted">(required for Uganda)</small></label>
        <input type="text" name="district" value="{{ $v('district') }}" class="form-control @error('district') is-invalid @enderror" maxlength="255">
        @error('district')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12"><div class="modal-section-title">Employment &amp; source</div></div>
    <div class="col-md-6">
        <label class="form-label">Employment Status <span class="text-danger">*</span></label>
        <select name="employment_status" class="form-select @error('employment_status') is-invalid @enderror" required data-member-employment>
            <option value="">Select Employment Status</option>
            @foreach($service::EMPLOYMENT_STATUSES as $es)
                <option value="{{ $es }}" @selected($v('employment_status') === $es)>{{ $es }}</option>
            @endforeach
        </select>
        @error('employment_status')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6" data-member-employment-other @if($v('employment_status') !== 'Other') style="display:none;" @endif>
        <label class="form-label">Specify Employment (Other) <span class="text-danger">*</span></label>
        <input type="text" name="employment_other" value="{{ $v('employment_other') }}" class="form-control @error('employment_other') is-invalid @enderror" maxlength="255">
        @error('employment_other')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">How did you know about us? <span class="text-danger">*</span></label>
        <select name="source" class="form-select @error('source') is-invalid @enderror" required data-member-source>
            <option value="">Select Source</option>
            @foreach($service::SOURCES as $src)
                <option value="{{ $src }}" @selected($v('source') === $src)>{{ $src }}</option>
            @endforeach
        </select>
        @error('source')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6" data-member-station-wrap @if($stationKind === '') style="display:none;" @endif>
        <label class="form-label" data-member-station-label>{{ $stationKind === 'TV' ? 'TV Station' : ($stationKind === 'Radio' ? 'Radio Station' : 'Station') }} <span class="text-danger">*</span></label>
        <select name="source_station" class="form-select @error('source_station') is-invalid @enderror" data-member-station>
            <option value="">Select Station</option>
            @foreach($service::RADIO_STATIONS as $st)
                <option value="{{ $st }}" data-kind="Radio" @selected($stationKind === 'Radio' && $v('source_station') === $st) @if($stationKind !== 'Radio') hidden disabled @endif>{{ $st }}</option>
            @endforeach
            @foreach($service::TV_STATIONS as $st)
                <option value="{{ $st }}" data-kind="TV" @selected($stationKind === 'TV' && $v('source_station') === $st) @if($stationKind !== 'TV') hidden disabled @endif>{{ $st }}</option>
            @endforeach
        </select>
        @error('source_station')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6" data-member-source-other @if($v('source') !== 'Other') style="display:none;" @endif>
        <label class="form-label">Specify Source (Other) <span class="text-danger">*</span></label>
        <input type="text" name="source_other" value="{{ $v('source_other') }}" class="form-control @error('source_other') is-invalid @enderror" maxlength="255">
        @error('source_other')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12"><div class="modal-section-title">Contact details</div></div>
    <div class="col-md-4">
        <label class="form-label">Telephone 1 <span class="text-danger">*</span></label>
        <input type="text" name="telephone1" value="{{ $v('telephone1') }}" class="form-control @error('telephone1') is-invalid @enderror" placeholder="+256..." required>
        @error('telephone1')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Telephone 2</label>
        <input type="text" name="telephone2" value="{{ $v('telephone2') }}" class="form-control @error('telephone2') is-invalid @enderror" placeholder="+256...">
        @error('telephone2')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Email</label>
        <input type="email" name="email" value="{{ $v('email') }}" class="form-control @error('email') is-invalid @enderror" maxlength="255">
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Mother Name</label>
        <input type="text" name="mother_name" value="{{ $v('mother_name') }}" class="form-control" maxlength="255">
    </div>
    <div class="col-md-3">
        <label class="form-label">Mother Phone</label>
        <input type="text" name="mother_phone" value="{{ $v('mother_phone') }}" class="form-control @error('mother_phone') is-invalid @enderror" placeholder="+256...">
        @error('mother_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Father Name</label>
        <input type="text" name="father_name" value="{{ $v('father_name') }}" class="form-control" maxlength="255">
    </div>
    <div class="col-md-3">
        <label class="form-label">Father Phone</label>
        <input type="text" name="father_phone" value="{{ $v('father_phone') }}" class="form-control @error('father_phone') is-invalid @enderror" placeholder="+256...">
        @error('father_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12"><div class="modal-section-title">Bank details</div></div>
    <div class="col-md-4">
        <label class="form-label">Account Type</label>
        <select name="account_type" class="form-select @error('account_type') is-invalid @enderror">
            <option value="">Select Account Type</option>
            @foreach($service::ACCOUNT_TYPES as $at)
                <option value="{{ $at }}" @selected($v('account_type') === $at)>{{ $at }}</option>
            @endforeach
        </select>
        @error('account_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Bank Account Number</label>
        <input type="text" name="bank_account" value="{{ $v('bank_account') }}" class="form-control" maxlength="255">
    </div>
    <div class="col-md-4">
        <label class="form-label">Account Name</label>
        <input type="text" name="bank_account_name" value="{{ $v('bank_account_name') }}" class="form-control" maxlength="255">
    </div>
    <div class="col-md-6">
        <label class="form-label">Bank Name</label>
        <input type="text" name="bank_name" value="{{ $v('bank_name') }}" class="form-control" maxlength="255">
    </div>
    <div class="col-md-6">
        <label class="form-label">Bank Branch</label>
        <input type="text" name="bank_branch" value="{{ $v('bank_branch') }}" class="form-control" maxlength="255">
    </div>

    <div class="col-12"><div class="modal-section-title">Membership assignment</div></div>
    <div class="col-md-4">
        <label class="form-label">Branch <span class="text-danger">*</span></label>
        <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror" required>
            <option value="">Select Branch</option>
            @foreach($branches as $branch)
                <option value="{{ $branch->id }}" @selected($v('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
            @endforeach
        </select>
        @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Mobilizer <span class="text-danger">*</span></label>
        <select name="mobilizer_id" class="form-select @error('mobilizer_id') is-invalid @enderror" required>
            <option value="">Select Mobilizer</option>
            @foreach($mobilizers as $mobilizer)
                <option value="{{ $mobilizer->id }}" @selected($v('mobilizer_id') === (string) $mobilizer->id)>{{ $mobilizer->full_name }}</option>
            @endforeach
        </select>
        @error('mobilizer_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        @if($mobilizers->isEmpty())
            <div class="form-text text-warning">No mobilizers found.</div>
        @endif
    </div>
    <div class="col-md-4">
        <label class="form-label">Membership Status</label>
        <select name="membership_status" class="form-select @error('membership_status') is-invalid @enderror">
            @foreach($service::MEMBERSHIP_STATUSES as $ms)
                <option value="{{ $ms }}" @selected($v('membership_status', 'Pending') === $ms)>{{ $ms }}</option>
            @endforeach
        </select>
        @error('membership_status')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

@once
@push('scripts')
<script>
window.YpaMemberForm = (function () {
    function sync(form) {
        const source = form.querySelector('[data-member-source]');
        const station = form.querySelector('[data-member-station]');
        const stationWrap = form.querySelector('[data-member-station-wrap]');
        const stationLabel = form.querySelector('[data-member-station-label]');
        const sourceOther = form.querySelector('[data-member-source-other]');
        const employment = form.querySelector('[data-member-employment]');
        const employmentOther = form.querySelector('[data-member-employment-other]');
        const kind = source && (source.value === 'Radio' || source.value === 'TV') ? source.value : '';

        if (station) {
            [...station.options].forEach(function (opt) {
                if (!opt.dataset.kind) return;
                const show = opt.dataset.kind === kind;
                opt.hidden = !show;
                opt.disabled = !show;
            });
            const current = station.selectedOptions[0];
            if (current && current.dataset.kind && current.dataset.kind !== kind) station.value = '';
        }
        if (stationWrap) stationWrap.style.display = kind ? '' : 'none';
        if (stationLabel) stationLabel.firstChild.textContent = (kind === 'Radio' ? 'Radio Station' : (kind === 'TV' ? 'TV Station' : 'Station')) + ' ';
        if (sourceOther) sourceOther.style.display = source && source.value === 'Other' ? '' : 'none';
        if (employmentOther) employmentOther.style.display = employment && employment.value === 'Other' ? '' : 'none';
    }

    function bind(form) {
        if (!form || form.dataset.memberBound) return;
        form.dataset.memberBound = '1';
        form.addEventListener('change', function (e) {
            if (e.target.matches('[data-member-source], [data-member-employment]')) sync(form);
        });
        sync(form);
    }

    function fill(form, record) {
        // Enable every station option first so a stored station value can be selected.
        form.querySelectorAll('[data-member-station] option').forEach(function (opt) { opt.disabled = false; });
        Object.keys(record || {}).forEach(function (name) {
            const field = form.elements.namedItem(name);
            if (!field || field.type === 'hidden' || field.type === 'file') return;
            field.value = record[name] === null || record[name] === undefined ? '' : String(record[name]);
        });
        sync(form);
    }

    function clearErrors(form) {
        form.querySelectorAll('.is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });
        form.querySelectorAll('.modal-body > .alert-danger').forEach(function (el) { el.remove(); });
    }

    return { sync: sync, bind: bind, fill: fill, clearErrors: clearErrors };
})();
</script>
@endpush
@endonce
