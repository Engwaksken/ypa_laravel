{{--
    Shared group form fields (used by the index/show modal and the fallback create/edit pages).
    Params:
      $branches, $mobilizers  - dropdown data
      $record                 - array of values (see GroupController::formRecord) or null
      $useOld                 - true on full pages; the modal relies on modal-validation to restore old input
--}}
@php
    $record = $record ?? [];
    $useOld = $useOld ?? false;
    $v = fn (string $key, $default = '') => (string) ($useOld ? old($key, $record[$key] ?? $default) : ($record[$key] ?? $default));
    $isUganda = strtolower(trim($v('country', 'Uganda'))) === 'uganda';
@endphp

<div class="row g-3 group-form-fields">
    <div class="col-12"><div class="modal-section-title">Group details</div></div>
    <div class="col-md-6">
        <label class="form-label">Group Name <span class="text-danger">*</span></label>
        <input type="text" name="group_name" value="{{ $v('group_name') }}" class="form-control @error('group_name') is-invalid @enderror" required maxlength="255">
        @error('group_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Group Category <span class="text-danger">*</span></label>
        <select name="group_category" class="form-select @error('group_category') is-invalid @enderror" required data-group-category>
            <option value="">Select Category</option>
            @foreach(\App\Services\GroupService::CATEGORIES as $cat)
                <option value="{{ $cat }}" @selected($v('group_category') === $cat)>{{ $cat }}</option>
            @endforeach
        </select>
        @error('group_category')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6" data-group-category-other @if($v('group_category') !== 'Other') style="display:none;" @endif>
        <label class="form-label">Specify Category <span class="text-danger">*</span></label>
        <input type="text" name="category_other" value="{{ $v('category_other') }}" class="form-control @error('category_other') is-invalid @enderror" placeholder="e.g. Savings Cooperative" maxlength="255">
        @error('category_other')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Formation Date <span class="text-danger">*</span></label>
        <input type="date" name="formation_date" value="{{ $v('formation_date') }}" class="form-control @error('formation_date') is-invalid @enderror" required>
        @error('formation_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Status</label>
        <select name="status" class="form-select @error('status') is-invalid @enderror">
            @foreach(\App\Services\GroupService::STATUSES as $st)
                <option value="{{ $st }}" @selected($v('status', 'Active') === $st)>{{ $st }}</option>
            @endforeach
        </select>
        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12"><div class="modal-section-title">Location</div></div>
    <div class="col-md-4">
        <label class="form-label">Country</label>
        <input type="text" name="country" value="{{ $v('country', 'Uganda') }}" class="form-control @error('country') is-invalid @enderror" maxlength="255" data-group-country>
        @error('country')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4" data-group-uganda @if(!$isUganda) style="display:none;" @endif>
        <label class="form-label">Sub-region <span class="text-danger">*</span></label>
        <input type="text" name="uganda_subregion" value="{{ $v('uganda_subregion') }}" class="form-control @error('uganda_subregion') is-invalid @enderror" placeholder="e.g. Central" maxlength="255">
        @error('uganda_subregion')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4" data-group-uganda @if(!$isUganda) style="display:none;" @endif>
        <label class="form-label">District <span class="text-danger">*</span></label>
        <input type="text" name="uganda_district" value="{{ $v('uganda_district') }}" class="form-control @error('uganda_district') is-invalid @enderror" placeholder="e.g. Masaka" maxlength="255">
        @error('uganda_district')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12"><div class="modal-section-title">Assignment</div></div>
    <div class="col-md-6">
        <label class="form-label">Branch <span class="text-danger">*</span></label>
        <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror" required>
            <option value="">Select Branch</option>
            @foreach($branches as $branch)
                <option value="{{ $branch->id }}" @selected($v('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
            @endforeach
        </select>
        @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Mobilizer <span class="text-danger">*</span></label>
        <select name="mobilizer_id" class="form-select @error('mobilizer_id') is-invalid @enderror" required>
            <option value="">Select Mobilizer</option>
            @foreach($mobilizers as $mobilizer)
                <option value="{{ $mobilizer->id }}" @selected($v('mobilizer_id') === (string) $mobilizer->id)>
                    {{ $mobilizer->full_name }} ({{ $mobilizer->contact_number ?? 'No phone' }})
                </option>
            @endforeach
        </select>
        @error('mobilizer_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        @if($mobilizers->isEmpty())
            <div class="form-text text-warning">No mobilizers found. Check mobilizers table or users with mobilizer/officer roles.</div>
        @endif
    </div>

    <div class="col-12"><div class="modal-section-title">Bank details</div></div>
    <div class="col-md-4">
        <label class="form-label">Bank Name</label>
        <input type="text" name="bank_name" value="{{ $v('bank_name') }}" class="form-control" maxlength="255">
    </div>
    <div class="col-md-4">
        <label class="form-label">Account Name</label>
        <input type="text" name="bank_account_name" value="{{ $v('bank_account_name') }}" class="form-control" maxlength="255">
    </div>
    <div class="col-md-4">
        <label class="form-label">Account Number</label>
        <input type="text" name="bank_account_number" value="{{ $v('bank_account_number') }}" class="form-control" maxlength="255">
    </div>
</div>

@once
@push('scripts')
<script>
window.YpaGroupForm = (function () {
    function sync(form) {
        const category = form.querySelector('[data-group-category]');
        const categoryOther = form.querySelector('[data-group-category-other]');
        const country = form.querySelector('[data-group-country]');
        const isUganda = country && country.value.trim().toLowerCase() === 'uganda';
        if (categoryOther) categoryOther.style.display = category && category.value === 'Other' ? '' : 'none';
        form.querySelectorAll('[data-group-uganda]').forEach(function (wrap) {
            wrap.style.display = isUganda ? '' : 'none';
        });
    }

    function bind(form) {
        if (!form || form.dataset.groupBound) return;
        form.dataset.groupBound = '1';
        form.addEventListener('change', function () { sync(form); });
        form.addEventListener('input', function (e) {
            if (e.target.matches('[data-group-country]')) sync(form);
        });
        sync(form);
    }

    function fill(form, record) {
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
