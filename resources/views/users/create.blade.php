@extends('layouts.app')

@section('title', 'Add User')

@section('content')
<div class="dash-wrap">

    <div class="dash-head">
        <div>
            <h1 class="dash-name">Add User</h1>
            <div class="dash-date"><a href="{{ route('users.index') }}">Users</a> / New</div>
        </div>
    </div>

    <div class="dash-panel">
        <div class="dash-panel-body">
            <form method="POST" action="{{ route('users.store') }}">
                @csrf
                @include('users._form', ['requirePassword' => true])
                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save User</button>
                    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
