@extends('layouts.app')

@section('title', 'New Meeting')

@section('content')
<div class="dash-wrap">

    <div class="dash-head">
        <div>
            <h1 class="dash-name">New Meeting</h1>
            <div class="dash-date">Create a new meeting record</div>
        </div>
        <div>
            <a href="{{ route('meetings.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Meetings
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

    <div class="dash-panel form-panel">
        <div class="dash-panel-head">
            <span><i class="fas fa-handshake"></i> Meeting Details</span>
        </div>
        <div class="dash-panel-body">
            <form method="POST" action="{{ route('meetings.store') }}">
                @csrf
                @php
                    $tabs = [
                        ['id' => 'meeting-tab-details', 'label' => 'Details', 'fields' => ['meeting_type', 'meeting_title', 'meeting_date', 'meeting_time', 'location', 'chaired_by', 'status']],
                        ['id' => 'meeting-tab-agenda', 'label' => 'Agenda & Notes', 'fields' => ['agenda', 'notes']],
                    ];
                    $activeTabId = $tabs[0]['id'];
                    foreach ($tabs as $tab) {
                        foreach ($tab['fields'] as $field) {
                            if ($errors->has($field)) { $activeTabId = $tab['id']; break 2; }
                        }
                    }
                @endphp

                <ul class="nav nav-tabs ypa-tabs mb-3" role="tablist">
                    @foreach($tabs as $tab)
                        <li class="nav-item" role="presentation">
                            <button type="button" class="nav-link @if($tab['id'] === $activeTabId) active @endif" id="{{ $tab['id'] }}-tab" data-bs-toggle="tab" data-bs-target="#{{ $tab['id'] }}" role="tab" aria-controls="{{ $tab['id'] }}" aria-selected="{{ $tab['id'] === $activeTabId ? 'true' : 'false' }}">{{ $tab['label'] }}</button>
                        </li>
                    @endforeach
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade @if($activeTabId === 'meeting-tab-details') show active @endif" id="meeting-tab-details" role="tabpanel" aria-labelledby="meeting-tab-details-tab">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Meeting Type <span class="text-danger">*</span></label>
                                <select name="meeting_type" class="form-select @error('meeting_type') is-invalid @enderror" required>
                                    @foreach(\App\Services\MeetingService::MEETING_TYPES as $mt)
                                        <option value="{{ $mt }}" @selected(old('meeting_type', 'Monthly') === $mt)>{{ $mt }}</option>
                                    @endforeach
                                </select>
                                @error('meeting_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Meeting Title <span class="text-danger">*</span></label>
                                <input type="text" name="meeting_title" value="{{ old('meeting_title') }}" class="form-control @error('meeting_title') is-invalid @enderror" required>
                                @error('meeting_title')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Meeting Date <span class="text-danger">*</span></label>
                                <input type="date" name="meeting_date" value="{{ old('meeting_date') }}" class="form-control @error('meeting_date') is-invalid @enderror" required>
                                @error('meeting_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Time</label>
                                <input type="time" name="meeting_time" value="{{ old('meeting_time') }}" class="form-control @error('meeting_time') is-invalid @enderror">
                                @error('meeting_time')
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
                                <label class="form-label">Chaired By</label>
                                <input type="text" name="chaired_by" value="{{ old('chaired_by') }}" class="form-control @error('chaired_by') is-invalid @enderror" placeholder="Chairperson name">
                                @error('chaired_by')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                                    @foreach(\App\Services\MeetingService::STATUSES as $st)
                                        <option value="{{ $st }}" @selected(old('status', 'Scheduled') === $st)>{{ $st }}</option>
                                    @endforeach
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade @if($activeTabId === 'meeting-tab-agenda') show active @endif" id="meeting-tab-agenda" role="tabpanel" aria-labelledby="meeting-tab-agenda-tab">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Agenda</label>
                                <textarea name="agenda" rows="3" class="form-control @error('agenda') is-invalid @enderror">{{ old('agenda') }}</textarea>
                                @error('agenda')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" rows="2" class="form-control @error('notes') is-invalid @enderror">{{ old('notes') }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Meeting</button>
                    <a href="{{ route('meetings.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection