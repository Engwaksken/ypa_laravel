@extends('layouts.app')

@section('title', 'New Activity')

@section('content')
<div class="dash-wrap">

    <div class="dash-head">
        <div>
            <h1 class="dash-name">New Activity</h1>
            <div class="dash-date">Create a new activity record</div>
        </div>
        <div>
            <a href="{{ route('activities.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Activities
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
            <span><i class="fas fa-calendar-plus"></i> Activity Details</span>
        </div>
        <div class="dash-panel-body">
            <form method="POST" action="{{ route('activities.store') }}" class="row g-3">
                @csrf

                <div class="col-md-6">
                    <label class="form-label">Activity Name <span class="text-danger">*</span></label>
                    <input type="text" name="activity_name" value="{{ old('activity_name') }}" class="form-control @error('activity_name') is-invalid @enderror" required>
                    @error('activity_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Activity Type <span class="text-danger">*</span></label>
                    <select name="activity_type_id" class="form-select @error('activity_type_id') is-invalid @enderror" required>
                        <option value="">Select Type</option>
                        @foreach($types as $type)
                            <option value="{{ $type->id }}" @selected(old('activity_type_id') == $type->id)>{{ $type->type_name }}</option>
                        @endforeach
                    </select>
                    @error('activity_type_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" rows="3" class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Start Date <span class="text-danger">*</span></label>
                    <input type="date" name="start_date" value="{{ old('start_date') }}" class="form-control @error('start_date') is-invalid @enderror" required>
                    @error('start_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" value="{{ old('end_date') }}" class="form-control @error('end_date') is-invalid @enderror">
                    @error('end_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Location</label>
                    <input type="text" name="location" value="{{ old('location') }}" class="form-control @error('location') is-invalid @enderror" placeholder="Venue / town">
                    @error('location')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Budget (UGX)</label>
                    <input type="text" name="budget" value="{{ old('budget') }}" class="form-control @error('budget') is-invalid @enderror" placeholder="e.g. 1,500,000">
                    @error('budget')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Promotion Activity</label>
                    <select name="is_promotion" class="form-select">
                        @foreach(\App\Services\ActivityService::IS_PROMOTION as $opt)
                            <option value="{{ $opt }}" @selected(old('is_promotion', 'No') === $opt)>{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        @foreach(\App\Services\ActivityService::STATUSES as $st)
                            <option value="{{ $st }}" @selected(old('status', 'Planned') === $st)>{{ $st }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Activity</button>
                    <a href="{{ route('activities.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection