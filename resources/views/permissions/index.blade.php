@extends('layouts.app')

@section('title', 'Permissions')

@section('content')
<div class="dash-wrap">

    <div class="dash-head">
        <div>
            <h1 class="dash-name">Permissions</h1>
            <div class="dash-date">Role permission matrix{{ $customized ? ' — customized for this role' : ' — built-in defaults' }}</div>
        </div>
    </div>

    <div class="dash-panel">
        <div class="dash-panel-body">
            <ul class="nav nav-tabs ypa-tabs mb-4" role="tablist">
                @foreach($roles as $role)
                    <li class="nav-item" role="presentation">
                        <a class="nav-link {{ $activeRole === $role ? 'active' : '' }}" href="{{ route('permissions.index', ['role' => $role]) }}" role="tab">
                            {{ app(\App\Services\PermissionService::class)->roleLabel($role) }}
                        </a>
                    </li>
                @endforeach
            </ul>

            @if($activeRole === 'supper_admin')
                <div class="alert alert-info">The Super Admin role always holds all permissions (<code>*</code>) and cannot be customized.</div>
            @else
                <form method="POST" action="{{ route('permissions.update') }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="role" value="{{ $activeRole }}">

                    <div class="row g-3">
                        @foreach($groups as $groupName => $permissions)
                            <div class="col-md-6 col-lg-4">
                                <div class="card h-100">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <strong>{{ ucwords(str_replace('_', ' ', $groupName)) }}</strong>
                                        <span class="badge bg-secondary">{{ count($permissions) }}</span>
                                    </div>
                                    <div class="card-body" style="max-height:260px;overflow-y:auto;">
                                        @foreach($permissions as $permission)
                                            <div class="form-check">
                                                <input type="checkbox" name="permissions[]" value="{{ $permission }}" id="perm-{{ $groupName }}-{{ $loop->index }}" class="form-check-input" @checked(in_array($permission, $checked, true))>
                                                <label class="form-check-label small" for="perm-{{ $groupName }}-{{ $loop->index }}">{{ $permission }}</label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save Permissions</button>
                    </div>
                </form>
            @endif
        </div>
    </div>

</div>
@endsection
