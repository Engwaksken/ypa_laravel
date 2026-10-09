@extends('layouts.app')

@section('title', 'Register Group')

@section('content')
{{-- Fallback full-page form; the groups index uses the same fields in a modal. --}}
<div class="dash-wrap">

    <div class="dash-head">
        <div>
            <h1 class="dash-name">Register Group</h1>
            <div class="dash-date">Create a new group record</div>
        </div>
        <a href="{{ route('groups.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to Groups
        </a>
    </div>

    <form method="POST" action="{{ route('groups.store') }}" id="groupPageForm">
        @csrf
        <div class="dash-panel">
            <div class="dash-panel-head">
                <span><i class="fas fa-people-group"></i> Group Details</span>
            </div>
            <div class="dash-panel-body">
                @include('groups._form_fields', ['record' => null, 'useOld' => true])
            </div>
        </div>

        <div class="d-flex gap-2 mt-4 mb-4">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Group</button>
            <a href="{{ route('groups.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>

</div>

@push('scripts')
<script>YpaGroupForm.bind(document.getElementById('groupPageForm'));</script>
@endpush
@endsection
