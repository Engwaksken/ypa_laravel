@extends('layouts.app')

@section('title', 'User Details')

@section('content')
<div class="dash-wrap">

    <div class="dash-head">
        <div>
            <h1 class="dash-name">{{ $user->name }}</h1>
            <div class="dash-date"><a href="{{ route('users.index') }}">Users</a> / Details</div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('users.edit', $user) }}" class="btn btn-outline-secondary"><i class="fas fa-edit me-1"></i> Edit</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <p class="mb-1"><strong>Email:</strong> {{ $user->email ?? '-' }}</p>
            <p class="mb-1"><strong>Role:</strong> {{ app(\App\Services\PermissionService::class)->roleLabel($user->role ?? '') }}</p>
            <p class="mb-1"><strong>Status:</strong> <span class="badge bg-{{ ($user->status ?? '') === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($user->status ?? '-') }}</span></p>
            <p class="mb-0"><strong>Branch:</strong> {{ optional($user->branch)->name ?? '-' }}</p>
        </div>
    </div>

</div>
@endsection
