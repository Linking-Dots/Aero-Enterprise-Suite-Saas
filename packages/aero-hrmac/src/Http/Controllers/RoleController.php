<?php

declare(strict_types=1);

namespace Aero\HRMAC\Http\Controllers;

use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Models\User;
use Aero\HRMAC\Http\Requests\StoreRoleRequest;
use Aero\HRMAC\Http\Requests\UpdateRoleRequest;
use Aero\HRMAC\Models\Role;
use Aero\HRMAC\Services\RoleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Role management — the single, canonical surface for roles, living in aero-hrmac
 * (the access-control package). Roles carry no Spatie permissions; authorization is
 * HRMAC module-access (role_module_access). Create/edit/delete/assign all happen
 * inline from the index page (aero-ui Core/Roles/Index). All mutations are audited
 * via RoleService.
 */
class RoleController extends Controller
{
    private const SUPER_ADMIN_ROLES = ['Super Administrator', 'super-admin', 'tenant_super_administrator'];

    public function __construct(private readonly RoleService $roles) {}

    public function index(Request $request): Response
    {
        $roles = Role::query()
            ->withCount(['users', 'moduleAccess'])
            ->orderBy('priority')
            ->orderBy('name')
            ->get();

        return Inertia::render('Core/Roles/Index', [
            'roles' => $roles,
            'users' => User::query()->orderBy('name')->get(['id', 'name', 'email']),
            'can_manage_super_admin' => $this->isSuperAdmin($request->user()),
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $this->roles->create($request->validated(), $request->user());

        return back()->with('success', 'Role created.');
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        abort_if($this->isProtectedName($role->name) || $role->is_protected, 403, 'Protected roles cannot be modified.');
        $this->roles->update($role, $request->validated(), $request->user());

        return back()->with('success', 'Role updated.');
    }

    public function destroy(Request $request, Role $role): RedirectResponse
    {
        abort_if($this->isProtectedName($role->name) || $role->is_protected, 403, 'Protected roles cannot be deleted.');
        $this->roles->delete($role, $request->user());

        return back()->with('success', 'Role deleted.');
    }

    public function assignUser(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer'],
            'roles' => ['required', 'array'],
            'roles.*' => ['integer'],
        ]);

        $user = User::findOrFail($data['user_id']);
        $this->roles->assignToUser($user, $data['roles'], $request->user());

        return back()->with('success', 'Roles assigned.');
    }

    private function isProtectedName(string $name): bool
    {
        return in_array($name, self::SUPER_ADMIN_ROLES, true);
    }

    private function isSuperAdmin(?User $user): bool
    {
        return $user !== null && $user->roles()->whereIn('name', self::SUPER_ADMIN_ROLES)->exists();
    }
}
