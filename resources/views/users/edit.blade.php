@extends('layouts.app')

@section('title', 'Edit User')

@section('content')
<div class="dash-wrap">

    <div class="dash-head">
        <div>
            <h1 class="dash-name">Edit User</h1>
            <div class="dash-date"><a href="{{ route('users.index') }}">Users</a> / {{ $user->name }}</div>
        </div>
    </div>

    <div class="dash-panel">
        <div class="dash-panel-body">
            <form method="POST" action="{{ route('users.update', $user) }}">
                @csrf
                @method('PUT')
                @include('users._form', ['requirePassword' => false])
                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Update User</button>
                    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
