@extends('layouts.app')

@section('title', 'Activity Registration')

@section('content')
<div class="dash-wrap">

    <div class="dash-head">
        <div>
            <h1 class="dash-name">Activity Registration</h1>
            <div class="dash-date">{{ $activity->activity_name }} &middot; {{ $activity->activity_code }}</div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('external_error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('external_error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="dash-panel">
                <div class="dash-panel-head">
                    <span><i class="fas fa-circle-info"></i> Activity Details</span>
                </div>
                <div class="dash-panel-body">
                    <table class="table table-sm mb-0">
                        <tbody>
                            <tr>
                                <th class="text-muted">Activity</th>
                                <td>{{ $activity->activity_name }}</td>
                            </tr>
                            <tr>
                                <th class="text-muted">Type</th>
                                <td>{{ $activity->type->type_name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th class="text-muted">Start Date</th>
                                <td>{{ $activity->start_date ? $activity->start_date->format('M d, Y') : '-' }}</td>
                            </tr>
                            <tr>
                                <th class="text-muted">End Date</th>
                                <td>{{ $activity->end_date ? $activity->end_date->format('M d, Y') : '-' }}</td>
                            </tr>
                            <tr>
                                <th class="text-muted">Location</th>
                                <td>{{ $activity->location ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th class="text-muted">Status</th>
                                <td><span class="badge bg-{{ $activity->status_badge }}">{{ $activity->status }}</span></td>
                            </tr>
                        </tbody>
                    </table>
                    @if($activity->description)
                        <hr>
                        <p class="mb-0">{{ $activity->description }}</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="dash-panel">
                <div class="dash-panel-head">
                    <span><i class="fas fa-user-plus"></i> Register for this Activity</span>
                </div>
                <div class="dash-panel-body">
                    <form method="POST" action="{{ route('activity-registration.register') }}" class="row g-3">
                        @csrf
                        <input type="hidden" name="activity_id" value="{{ $activity->id }}">

                        <div class="col-md-6">
                            <label class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" value="{{ old('first_name') }}" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" value="{{ old('last_name') }}" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Gender <span class="text-danger">*</span></label>
                            <select name="gender" class="form-select" required>
                                <option value="">Select Gender</option>
                                <option value="Male" @selected(old('gender') === 'Male')>Male</option>
                                <option value="Female" @selected(old('gender') === 'Female')>Female</option>
                                <option value="Other" @selected(old('gender') === 'Other')>Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone <span class="text-danger">*</span></label>
                            <input type="text" name="phone" value="{{ old('phone') }}" class="form-control" placeholder="+256..." required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" value="{{ old('email') }}" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Address</label>
                            <input type="text" name="address" value="{{ old('address') }}" class="form-control">
                        </div>

                        <div class="col-12 d-flex gap-2">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Register</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection