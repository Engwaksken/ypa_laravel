<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Full Name</label>
        <input type="text" name="name" value="{{ old('name', $user->name ?? '') }}" class="form-control" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Email</label>
        <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}" class="form-control" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Password {{ ($requirePassword ?? false) ? '' : '(leave blank to keep current)' }}</label>
        <input type="password" name="password" class="form-control" autocomplete="new-password" @if($requirePassword ?? false) required @endif>
    </div>
    <div class="col-md-6">
        <label class="form-label">Confirm Password</label>
        <input type="password" name="password_confirmation" class="form-control" autocomplete="new-password" @if($requirePassword ?? false) required @endif>
    </div>
    <div class="col-md-4">
        <label class="form-label">Role</label>
        <select name="role" class="form-select" required>
            @foreach($roles as $role)
                <option value="{{ $role }}" @selected(old('role', $user->role ?? '') === $role)>{{ app(\App\Services\PermissionService::class)->roleLabel($role) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Status</label>
        <select name="status" class="form-select" required>
            <option value="active" @selected(old('status', $user->status ?? 'active') === 'active')>Active</option>
            <option value="inactive" @selected(old('status', $user->status ?? '') === 'inactive')>Inactive</option>
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Branch</label>
        <select name="branch_id" class="form-select">
            <option value="">-- None --</option>
            @foreach($branches as $branch)
                <option value="{{ $branch->id }}" @selected((string) old('branch_id', $user->branch_id ?? '') === (string) $branch->id)>{{ $branch->name }}</option>
            @endforeach
        </select>
    </div>
</div>
