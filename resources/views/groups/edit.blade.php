@extends('layouts.app')

@section('title', 'Edit Group')

@section('content')
{{-- Fallback full-page form; the groups index/show pages use the same fields in a modal. --}}
<div class="dash-wrap">

    <div class="dash-head">
        <div>
            <h1 class="dash-name">Edit Group</h1>
            <div class="dash-date">{{ $group->group_code }} &middot; {{ $group->group_name }}</div>
        </div>
        <a href="{{ route('groups.show', $group) }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to Group
        </a>
    </div>

    <form method="POST" action="{{ route('groups.update', $group) }}" id="groupPageForm">
        @csrf
        @method('PUT')
        <div class="dash-panel">
            <div class="dash-panel-head">
                <span><i class="fas fa-pen"></i> Group Details</span>
            </div>
            <div class="dash-panel-body">
                @include('groups._form_fields', ['record' => $record, 'useOld' => true])
            </div>
        </div>

        <div class="d-flex gap-2 mt-4 mb-4">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Group</button>
            <a href="{{ route('groups.show', $group) }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>

</div>

@push('scripts')
<script>YpaGroupForm.bind(document.getElementById('groupPageForm'));</script>
@endpush
@endsection
