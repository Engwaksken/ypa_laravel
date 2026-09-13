<?php

namespace App\Http\Controllers;

use App\Models\RolePermission;
use App\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PermissionsController extends Controller
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('user.status'),
            new Middleware('permission:manage_permissions'),
            (new Middleware('throttle:30,1'))->only(['update']),
        ];
    }

    public function index(Request $request, PermissionService $permission): View
    {
        $roles = $permission->allRoles();
        $groups = $permission->permissionGroups();

        $activeRole = $permission->normalizeRole($request->query('role', 'admin'));
        if (!in_array($activeRole, $roles, true)) {
            $activeRole = 'admin';
        }

        $checked = $permission->effectivePermissionsForRole($activeRole);
        $customized = $permission->rolePermissionsCustomized($activeRole);

        return view('permissions.index', compact(
            'roles',
            'groups',
            'activeRole',
            'checked',
            'customized'
        ));
    }

    public function update(Request $request, PermissionService $permission): RedirectResponse
    {
        $allowedPermissions = collect($permission->permissionGroups())
            ->flatten()
            ->map(fn ($item) => $permission->normalizePermission($item))
            ->unique()
            ->values()
            ->all();

        $data = $request->validate([
            'role' => ['required', 'string'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'max:191', Rule::in($allowedPermissions)],
        ]);

        $role = $permission->normalizeRole($data['role']);

        if (!in_array($role, $permission->allRoles(), true)) {
            return back()->with('error', 'Invalid role selected.');
        }

        if ($role === 'supper_admin') {
            return back()->with('error', 'The Super Admin role always holds all permissions.');
        }

        $selected = collect($data['permissions'] ?? [])
            ->map(fn ($item) => $permission->normalizePermission($item))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $rows = collect($selected)
            ->map(fn ($item) => [
                'role' => $role,
                'permission' => $item,
            ])
            ->push([
                'role' => $role,
                'permission' => PermissionService::CONFIG_MARKER,
            ])
            ->all();

        DB::transaction(function () use ($role, $rows) {
            RolePermission::query()->where('role', $role)->delete();

            foreach (array_chunk($rows, 200) as $chunk) {
                RolePermission::query()->insert($chunk);
            }
        });

        $permission->clearEffectiveCache($role);

        return redirect()
            ->route('permissions.index', ['role' => $role])
            ->with('success', 'Permissions updated for ' . $permission->roleLabel($role) . '.');
    }
}
