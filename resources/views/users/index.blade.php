@extends('layouts.app')

@section('title', 'Users')

@section('content')
<div class="dash-wrap">

    <div class="dash-head">
        <div>
            <h1 class="dash-name">Users</h1>
            <div class="dash-date">{{ number_format($stats['total']) }} user(s)</div>
        </div>
        <button type="button" class="btn btn-primary" onclick="openUserModal()">
            <i class="fas fa-user-plus"></i> Add User
        </button>
    </div>

    <div class="dash-grid mb-4">
        <div class="dash-card accent-primary">
            <div class="dash-card-top">
                <span class="dash-card-title">Total</span>
                <span class="dash-card-icon"><i class="fas fa-users"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format($stats['total']) }}</div>
        </div>
        <div class="dash-card accent-success">
            <div class="dash-card-top">
                <span class="dash-card-title">Active</span>
                <span class="dash-card-icon"><i class="fas fa-user-check"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format($stats['active']) }}</div>
        </div>
        <div class="dash-card accent-muted">
            <div class="dash-card-top">
                <span class="dash-card-title">Inactive</span>
                <span class="dash-card-icon"><i class="fas fa-user-slash"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format($stats['inactive']) }}</div>
        </div>
        <div class="dash-card accent-warning">
            <div class="dash-card-top">
                <span class="dash-card-title">No Branch</span>
                <span class="dash-card-icon"><i class="fas fa-code-branch"></i></span>
            </div>
            <div class="dash-card-value">{{ number_format($stats['no_branch']) }}</div>
        </div>
    </div>

    <div class="dash-panel">
        <div class="dash-panel-head">
            <span><i class="fas fa-filter"></i> Filter Users</span>
        </div>
        <div class="dash-panel-body">
            <form method="GET" action="{{ route('users.index') }}" class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Name or email...">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Role</label>
                    <select name="role" class="form-select">
                        <option value="">All Roles</option>
                        @foreach($roles as $role)
                            <option value="{{ $role }}" @selected($roleFilter === $role)>{{ app(\App\Services\PermissionService::class)->roleLabel($role) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="active" @selected($statusFilter === 'active')>Active</option>
                        <option value="inactive" @selected($statusFilter === 'inactive')>Inactive</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
                    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="dash-panel mt-4">
        <div class="dash-panel-head">
            <span><i class="fas fa-list"></i> User List</span>
            <span class="text-muted small">{{ $users->total() }} result(s)</span>
        </div>
        <div class="dash-panel-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Branch</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td><strong>{{ $user->name ?? '-' }}</strong></td>
                                <td>{{ $user->email ?? '-' }}</td>
                                <td>{{ app(\App\Services\PermissionService::class)->roleLabel($user->role ?? '') }}</td>
                                <td>{{ optional($user->branch)->name ?? '-' }}</td>
                                <td><span class="badge bg-{{ ($user->status ?? '') === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($user->status ?? '-') }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('users.show', $user) }}" class="btn btn-sm btn-outline-primary" title="View" aria-label="View user {{ $user->name }}"><i class="fas fa-eye" aria-hidden="true"></i></a>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" title="Edit" aria-label="Edit user {{ $user->name }}"
                                        data-url="{{ route('users.update', $user) }}"
                                        data-name="{{ $user->name }}"
                                        data-email="{{ $user->email }}"
                                        data-role="{{ $user->role }}"
                                        data-role-label="{{ app(\App\Services\PermissionService::class)->roleLabel($user->role ?? '') }}"
                                        data-status="{{ $user->status ?? 'active' }}"
                                        data-branch="{{ $user->branch_id ?? '' }}"
                                        onclick="openUserEdit(this)">
                                        <i class="fas fa-pen" aria-hidden="true"></i>
                                    </button>
                                    <form action="{{ route('users.destroy', $user) }}" method="POST" class="d-inline ypa-confirm-delete" data-confirm-title="Delete user?" data-confirm-message="Delete {{ $user->name }}? This cannot be undone.">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete" aria-label="Delete user {{ $user->name }}"><i class="fas fa-trash" aria-hidden="true"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr class="empty-row">
                                <td colspan="6"><i class="fas fa-inbox"></i>No users found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="dash-panel-foot">{{ $users->links() }}</div>
    </div>

</div>

@include('users._modal')
@endsection
