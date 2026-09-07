@extends('layouts.app')

@section('title', 'Register Group')

@section('content')
<div class="dash-wrap">

    <div class="dash-head">
        <div>
            <h1 class="dash-name">Register Group</h1>
            <div class="dash-date">Create a new group record</div>
        </div>
        <div>
            <a href="{{ route('groups.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Groups
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Please fix the following errors:</strong>
            <ul class="mb-0 mt-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="dash-panel">
        <div class="dash-panel-head">
            <span><i class="fas fa-users"></i> Group Details</span>
        </div>
        <div class="dash-panel-body">
            <form method="POST" action="{{ route('groups.store') }}" class="row g-3">
                @csrf

                <div class="col-md-6">
                    <label class="form-label">Group Name <span class="text-danger">*</span></label>
                    <input type="text" name="group_name" value="{{ old('group_name') }}" class="form-control @error('group_name') is-invalid @enderror" required>
                    @error('group_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Group Category <span class="text-danger">*</span></label>
                    <select name="group_category" id="group_category" class="form-select @error('group_category') is-invalid @enderror" required>
                        <option value="">Select Category</option>
                        @foreach(\App\Services\GroupService::CATEGORIES as $cat)
                            <option value="{{ $cat }}" @selected(old('group_category') === $cat)>{{ $cat }}</option>
                        @endforeach
                    </select>
                    @error('group_category')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6" id="category_other_wrap" style="display: none;">
                    <label class="form-label">Specify Category</label>
                    <input type="text" name="category_other" value="{{ old('category_other') }}" class="form-control @error('category_other') is-invalid @enderror" placeholder="e.g. Savings Cooperative">
                    @error('category_other')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Country</label>
                    <input type="text" name="country" id="country" value="{{ old('country', 'Uganda') }}" class="form-control @error('country') is-invalid @enderror">
                    @error('country')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6" id="uganda_subregion_wrap">
                    <label class="form-label">Sub-region</label>
                    <input type="text" name="uganda_subregion" value="{{ old('uganda_subregion') }}" class="form-control @error('uganda_subregion') is-invalid @enderror" placeholder="e.g. Central">
                    @error('uganda_subregion')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6" id="uganda_district_wrap">
                    <label class="form-label">District</label>
                    <input type="text" name="uganda_district" value="{{ old('uganda_district') }}" class="form-control @error('uganda_district') is-invalid @enderror" placeholder="e.g. Masaka">
                    @error('uganda_district')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Formation Date <span class="text-danger">*</span></label>
                    <input type="date" name="formation_date" value="{{ old('formation_date') }}" class="form-control @error('formation_date') is-invalid @enderror" required>
                    @error('formation_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Branch <span class="text-danger">*</span></label>
                    <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror" required>
                        <option value="">Select Branch</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" @selected(old('branch_id') == $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                    @error('branch_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Mobilizer <span class="text-danger">*</span></label>
                    <select name="mobilizer_id" id="mobilizer_id" class="form-select @error('mobilizer_id') is-invalid @enderror" required>
                        <option value="">Select Mobilizer</option>
                        @foreach($mobilizers as $mobilizer)
                            <option value="{{ $mobilizer->id }}"
                                data-name="{{ $mobilizer->full_name }}"
                                data-phone="{{ $mobilizer->contact_number }}"
                                data-email="{{ $mobilizer->email }}"
                                data-code="{{ $mobilizer->mobilizer_code ?? '' }}"
                                data-branch-name="{{ $mobilizer->branch_region }}"
                                data-source="{{ $mobilizer->department }}"
                                @selected(old('mobilizer_id') == $mobilizer->id)>
                                {{ $mobilizer->full_name }} ({{ $mobilizer->contact_number ?? 'No phone' }})
                            </option>
                        @endforeach
                    </select>
                    @error('mobilizer_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    @if($mobilizers->isEmpty())
                        <div class="form-text text-warning">No mobilizers found. Check mobilizers table or users with mobilizer/officer roles.</div>
                    @endif
                </div>

                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        @foreach(\App\Services\GroupService::STATUSES as $st)
                            <option value="{{ $st }}" @selected(old('status', 'Active') === $st)>{{ $st }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12">
                    <div class="card border">
                        <div class="card-header bg-light">
                            <strong><i class="fas fa-building-columns"></i> Bank Details</strong>
                        </div>
                        <div class="card-body row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Bank Name</label>
                                <input type="text" name="bank_name" value="{{ old('bank_name') }}" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Account Name</label>
                                <input type="text" name="bank_account_name" value="{{ old('bank_account_name') }}" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Account Number</label>
                                <input type="text" name="bank_account_number" value="{{ old('bank_account_number') }}" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Group</button>
                    <a href="{{ route('groups.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
(function () {
    const categorySelect = document.getElementById('group_category');
    const categoryOtherWrap = document.getElementById('category_other_wrap');
    const countryInput = document.getElementById('country');
    const subregionWrap = document.getElementById('uganda_subregion_wrap');
    const districtWrap = document.getElementById('uganda_district_wrap');

    function toggleCategoryOther() {
        if (categorySelect && categoryOtherWrap) {
            categoryOtherWrap.style.display = categorySelect.value === 'Other' ? '' : 'none';
        }
    }

    function toggleUgandaFields() {
        const isUganda = countryInput && countryInput.value.trim().toLowerCase() === 'uganda';
        if (subregionWrap) subregionWrap.style.display = isUganda ? '' : 'none';
        if (districtWrap) districtWrap.style.display = isUganda ? '' : 'none';
    }

    if (categorySelect) categorySelect.addEventListener('change', toggleCategoryOther);
    if (countryInput) countryInput.addEventListener('input', toggleUgandaFields);

    toggleCategoryOther();
    toggleUgandaFields();
})();
</script>
@endpush