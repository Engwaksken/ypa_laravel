@extends('layouts.app')

@section('title', 'Edit Member')

@section('content')
{{-- Fallback full-page form; the members index/show pages use the same fields in a modal. --}}
<div class="dash-wrap">

    <div class="dash-head">
        <div>
            <h1 class="dash-name">Edit Member</h1>
            <div class="dash-date">{{ $member->membership_id }} &middot; {{ $member->full_name }}</div>
        </div>
        <a href="{{ route('members.show', $member) }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to Member
        </a>
    </div>

    <form method="POST" action="{{ route('members.update', $member) }}" id="memberPageForm">
        @csrf
        @method('PUT')
        <div class="dash-panel">
            <div class="dash-panel-head">
                <span><i class="fas fa-user-pen"></i> Member Details</span>
            </div>
            <div class="dash-panel-body">
                @include('members._form_fields', ['record' => $record, 'useOld' => true])
            </div>
        </div>

        <div class="d-flex gap-2 mt-4 mb-4">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Member</button>
            <a href="{{ route('members.show', $member) }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>

</div>

@push('scripts')
<script>YpaMemberForm.bind(document.getElementById('memberPageForm'));</script>
@endpush
@endsection
