<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Branch;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UsersController extends Controller
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('user.status'),
            new Middleware('permission:manage_users'),
            (new Middleware('throttle:30,1'))->only(['store', 'update', 'destroy']),
        ];
    }

    protected function authorizeRoleAssignment(string $targetRole, ?User $actingUser = null): void
    {
        $permission = app(PermissionService::class);
        $actingUser = $actingUser ?? auth()->user();

        if (!$permission->canManageRole($targetRole, $actingUser)) {
            abort(403, 'You cannot assign a role equal to or higher than your own.');
        }

        if ($permission->isHighestPrivileged($targetRole) && !$permission->isHighestPrivileged($actingUser->role)) {
            abort(403, 'Only a Super Admin can create or promote to Super Admin.');
        }
    }

    protected function getAssignableRoles(?User $actingUser = null): array
    {
        $permission = app(PermissionService::class);
        $actingUser = $actingUser ?? auth()->user();
        $allRoles = $permission->allRoles();
        $currentLevel = $permission->roleLevel($actingUser->role);

        return array_filter($allRoles, function ($role) use ($permission, $currentLevel) {
            return $permission->roleLevel($role) < $currentLevel;
        });
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $roleFilter = trim((string) $request->query('role', ''));
        $statusFilter = trim((string) $request->query('status', ''));

        $query = User::query()->orderByDesc('id');

        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like);
            });
        }

        if ($roleFilter !== '') {
            $query->where('role', $roleFilter);
        }

        if ($statusFilter !== '') {
            $query->where('status', $statusFilter);
        }

        $users = $query->paginate(20)->withQueryString();
        $roles = app(PermissionService::class)->allRoles();

        $stats = [
            'total' => User::count(),
            'active' => User::where('status', 'active')->count(),
            'inactive' => User::where('status', 'inactive')->count(),
        ];

        return view('users.index', compact(
            'users',
            'search',
            'roleFilter',
            'statusFilter',
            'roles',
            'stats'
        ));
    }

    public function create(): View
    {
        return view('users.create', [
            'roles' => $this->getAssignableRoles(),
            'branches' => Branch::query()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $this->authorizeRoleAssignment($data['role']);
        $data['password'] = Hash::make($data['password']);

        User::create($data);

        return redirect()
            ->route('users.index')
            ->with('success', 'User created successfully!');
    }

    public function show(User $user): View
    {
        return view('users.show', compact('user'));
    }

    public function edit(User $user): View
    {
        return view('users.edit', [
            'user' => $user,
            'roles' => $this->getAssignableRoles(),
            'branches' => Branch::query()->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();
        $actingUser = auth()->user();

        if ($user->id === $actingUser->id) {
            if (isset($data['role']) && $data['role'] !== $user->role) {
                return back()->with('error', 'You cannot change your own role.');
            }
            if (($data['status'] ?? '') === 'inactive') {
                return back()->with('error', 'You cannot deactivate your own account.');
            }
        } else {
            // A user may only manage accounts below their own privilege level.
            $this->authorizeRoleAssignment($user->role, $actingUser);

            if (isset($data['role']) && $data['role'] !== $user->role) {
                $this->authorizeRoleAssignment($data['role'], $actingUser);
                if ($user->normalizedRole() === 'supper_admin' && $this->isLastSuperAdmin($user)) {
                    return back()->with('error', 'Cannot demote the only Super Admin.');
                }
            }
        }

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()
            ->route('users.index')
            ->with('success', 'User updated successfully!');
    }

    public function destroy(User $user): RedirectResponse
    {
        $actingUser = auth()->user();

        if ($user->id === $actingUser->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        if ($user->normalizedRole() === 'supper_admin' && $this->isLastSuperAdmin($user)) {
            return back()->with('error', 'Cannot delete the only Super Admin.');
        }

        $this->authorizeRoleAssignment($user->role, $actingUser);

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', 'User deleted successfully!');
    }

    protected function isLastSuperAdmin(User $user): bool
    {
        return User::where('role', 'supper_admin')->count() <= 1;
    }
}
