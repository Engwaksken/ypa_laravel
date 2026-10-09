{{-- Add/Edit user modal. Requires $assignableRoles and $branches. --}}
@php($permissionService = app(\App\Services\PermissionService::class))
<div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="userForm" method="POST" action="{{ route('users.store') }}">
                @csrf
                <input type="hidden" name="_method" id="userMethod" value="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="userModalTitle">Add User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="modal-section-title">Account</div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" id="userName" class="form-control" maxlength="150" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="userEmail" class="form-control" maxlength="150" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password <span class="text-muted small" id="userPasswordHint"></span></label>
                            <input type="password" name="password" id="userPassword" class="form-control" minlength="8" autocomplete="new-password" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" name="password_confirmation" id="userPasswordConfirm" class="form-control" minlength="8" autocomplete="new-password" required>
                        </div>
                    </div>

                    <div class="modal-section-title mt-4">Access</div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Role</label>
                            <select name="role" id="userRole" class="form-select" required>
                                @if(old('role') && !in_array(old('role'), $assignableRoles, true))
                                    <option value="{{ old('role') }}" data-extra="1">{{ $permissionService->roleLabel(old('role')) }}</option>
                                @endif
                                @foreach($assignableRoles as $role)
                                    <option value="{{ $role }}">{{ $permissionService->roleLabel($role) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status" id="userStatus" class="form-select" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Branch</label>
                            <select name="branch_id" id="userBranch" class="form-select">
                                <option value="">-- None --</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function userModalShow() {
        if (window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('userModal')).show();
        }
    }

    function userSetPasswordRequired(required) {
        ['userPassword', 'userPasswordConfirm'].forEach(function (id) {
            var input = document.getElementById(id);
            input.required = required;
            input.value = '';
        });
        document.getElementById('userPasswordHint').textContent = required ? '' : '(leave blank to keep current)';
    }

    function openUserModal() {
        var form = document.getElementById('userForm');
        form.reset();
        form.action = "{{ route('users.store') }}";
        document.getElementById('userMethod').value = 'POST';
        document.getElementById('userModalTitle').textContent = 'Add User';
        document.querySelectorAll('#userRole option[data-extra]').forEach(function (o) { o.remove(); });
        userSetPasswordRequired(true);
        userModalShow();
    }

    function openUserEdit(btn) {
        var form = document.getElementById('userForm');
        form.reset();
        form.action = btn.dataset.url;
        document.getElementById('userMethod').value = 'PUT';
        document.getElementById('userModalTitle').textContent = 'Edit User';
        document.getElementById('userName').value = btn.dataset.name || '';
        document.getElementById('userEmail').value = btn.dataset.email || '';

        // The user's current role may sit outside the acting user's assignable
        // roles (e.g. editing yourself); keep it selectable so it is preserved.
        var role = document.getElementById('userRole');
        document.querySelectorAll('#userRole option[data-extra]').forEach(function (o) { o.remove(); });
        if (btn.dataset.role && !role.querySelector('option[value="' + btn.dataset.role + '"]')) {
            var opt = document.createElement('option');
            opt.value = btn.dataset.role;
            opt.textContent = btn.dataset.roleLabel || btn.dataset.role;
            opt.dataset.extra = '1';
            role.prepend(opt);
        }
        role.value = btn.dataset.role || '';
        document.getElementById('userStatus').value = btn.dataset.status || 'active';
        document.getElementById('userBranch').value = btn.dataset.branch || '';
        userSetPasswordRequired(false);
        userModalShow();
    }

    // After a failed PUT the validation helper reopens the modal; make sure
    // the password fields are optional again in that case.
    document.addEventListener('DOMContentLoaded', function () {
        if (document.getElementById('userMethod').value === 'PUT') {
            ['userPassword', 'userPasswordConfirm'].forEach(function (id) { document.getElementById(id).required = false; });
            document.getElementById('userPasswordHint').textContent = '(leave blank to keep current)';
        }
    });
</script>
@endpush
