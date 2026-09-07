@extends('layouts.app')

@section('title', 'Edit Mobilizer')

@section('content')
@php
    $permission = app(\App\Services\PermissionService::class);
    $old = old();
    $m = $mobilizer;
@endphp

<div class="dash-wrap">

    <div class="dash-head">
        <div>
            <h1 class="dash-name">Edit Mobilizer</h1>
            <div class="dash-date">{{ $m->full_name }}</div>
        </div>
        <a href="{{ route('mobilizers.show', $m) }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to Mobilizer
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

    <form method="POST" action="{{ route('mobilizers.update', $m) }}">
        @csrf
        @method('PUT')

        <div class="dash-panel">
            <div class="dash-panel-head">
                <span><i class="fas fa-user"></i> Mobilizer Information</span>
            </div>
            <div class="dash-panel-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">First Name <span class="text-danger">*</span></label>
                        <input type="text" name="first_name" value="{{ $old['first_name'] ?? $m->first_name }}" class="form-control @error('first_name') is-invalid @enderror" required>
                        @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Last Name <span class="text-danger">*</span></label>
                        <input type="text" name="last_name" value="{{ $old['last_name'] ?? $m->last_name }}" class="form-control @error('last_name') is-invalid @enderror" required>
                        @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Contact Number <span class="text-danger">*</span></label>
                        <input type="text" name="contact_number" value="{{ $old['contact_number'] ?? $m->contact_number }}" class="form-control @error('contact_number') is-invalid @enderror" placeholder="+256..." required>
                        @error('contact_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" value="{{ $old['email'] ?? $m->email }}" class="form-control @error('email') is-invalid @enderror">
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Department <span class="text-danger">*</span></label>
                        <input type="text" name="department" value="{{ $old['department'] ?? $m->department }}" class="form-control @error('department') is-invalid @enderror" required>
                        @error('department')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Position <span class="text-danger">*</span></label>
                        <input type="text" name="position" value="{{ $old['position'] ?? $m->position }}" class="form-control @error('position') is-invalid @enderror" required>
                        @error('position')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Branch / Region <span class="text-danger">*</span></label>
                        <select name="branch_region" class="form-select @error('branch_region') is-invalid @enderror">
                            <option value="">Select Branch</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->name }}" @selected(($old['branch_region'] ?? $m->branch_region) === $branch->name)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        @error('branch_region')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Supervisor</label>
                        <input type="text" name="supervisor" value="{{ $old['supervisor'] ?? $m->supervisor }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                            <option value="">Select Status</option>
                            @foreach(['Active', 'Inactive', 'Suspended', 'Terminated'] as $st)
                                <option value="{{ $st }}" @selected(($old['status'] ?? $m->status) === $st)>{{ $st }}</option>
                            @endforeach
                        </select>
                        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" rows="3" class="form-control @error('remarks') is-invalid @enderror">{{ $old['remarks'] ?? $m->remarks }}</textarea>
                        @error('remarks')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 mt-4 mb-4">
            <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save"></i> Update Mobilizer</button>
            <a href="{{ route('mobilizers.show', $m) }}" class="btn btn-outline-secondary btn-lg">Cancel</a>
        </div>
    </form>

</div>
@endsection