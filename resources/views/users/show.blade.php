@extends('layouts.app')

@section('title', 'User Details')

@section('content')
@php($permissionService = app(\App\Services\PermissionService::class))
<div class="dash-wrap">

    <div class="dash-head">
        <div>
            <h1 class="dash-name">{{ $user->name }}</h1>
            <div class="dash-date"><a href="{{ route('users.index') }}">Users</a> / Details</div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Back</a>
            <button type="button" class="btn btn-primary"
                data-url="{{ route('users.update', $user) }}"
                data-name="{{ $user->name }}"
                data-email="{{ $user->email }}"
                data-role="{{ $user->role }}"
                data-role-label="{{ $permissionService->roleLabel($user->role ?? '') }}"
                data-status="{{ $user->status ?? 'active' }}"
                data-branch="{{ $user->branch_id ?? '' }}"
                onclick="openUserEdit(this)">
                <i class="fas fa-pen me-1" aria-hidden="true"></i> Edit
            </button>
        </div>
    </div>

    <div class="dash-panel">
        <div class="dash-panel-head">
            <span><i class="fas fa-user"></i> Account Details</span>
        </div>
        <div class="dash-panel-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <tbody>
                        <tr><th class="w-25">Email</th><td>{{ $user->email ?? '-' }}</td></tr>
                        <tr><th>Role</th><td>{{ $permissionService->roleLabel($user->role ?? '') }}</td></tr>
                        <tr><th>Status</th><td><span class="badge bg-{{ ($user->status ?? '') === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($user->status ?? '-') }}</span></td></tr>
                        <tr><th>Branch</th><td>{{ optional($user->branch)->name ?? '-' }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

@include('users._modal')
@endsection
