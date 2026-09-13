<?php

namespace App\Http\Requests;

use App\Services\PermissionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $permission = app(PermissionService::class);
        $actingUser = $this->user();
        $currentLevel = $actingUser ? $permission->roleLevel($actingUser->role) : 0;
        $assignableRoles = array_filter(
            $permission->allRoles(),
            fn (string $role): bool => $permission->roleLevel($role) < $currentLevel
        );

        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::in($assignableRoles)],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $permission = app(PermissionService::class);
            $targetRole = $this->input('role');
            if (!$permission->canManageRole($targetRole)) {
                $validator->errors()->add('role', 'You cannot assign a role equal to or higher than your own.');
            }
        });
    }
}
