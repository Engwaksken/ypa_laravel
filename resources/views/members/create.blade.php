@extends('layouts.app')

@section('title', 'Register Member')

@section('content')
{{-- Fallback full-page form; the members index uses the same fields in a modal. --}}
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

    <form method="POST" action="{{ route('members.store') }}" id="memberPageForm">
        @csrf
        <div class="dash-panel">
            <div class="dash-panel-head">
                <span><i class="fas fa-user-plus"></i> Member Details</span>
            </div>
            <div class="dash-panel-body">
                @include('members._form_fields', ['record' => null, 'useOld' => true])
            </div>
        </div>

        <div class="d-flex gap-2 mt-4 mb-4">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Register Member</button>
            <a href="{{ route('members.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>

</div>

@push('scripts')
<script>YpaMemberForm.bind(document.getElementById('memberPageForm'));</script>
@endpush
@endsection
