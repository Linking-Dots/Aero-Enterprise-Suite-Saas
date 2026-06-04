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
use Illuminate\Support\Facades\DB;
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
            ->withCount('moduleAccess')
            ->orderBy('priority')
            ->orderBy('name')
            ->get();

        // users_count from the model_has_roles pivot directly — context-free (counts
        // assignments on the default connection regardless of the user model, so it
        // works for tenant User and central LandlordUser alike, and never triggers a
        // tenant-scoped User query on the platform).
        $assignmentCounts = DB::table('model_has_roles')
            ->selectRaw('role_id, COUNT(*) as c')
            ->groupBy('role_id')
            ->pluck('c', 'role_id');
        $roles->each(fn (Role $r) => $r->users_count = (int) ($assignmentCounts[$r->id] ?? 0));

        // The consuming route decides which page renders this role list (tenant vs
        // platform shell) via a route default — HRMAC names no specific view.
        $view = $request->route()?->defaults['hrmac_role_view'] ?? 'Core/Roles/Index';

        return Inertia::render($view, [
            'roles' => $roles,
            // Assignable users for the (tenant) inline-assign modal. Best-effort: the
            // configured user model may be unqueryable in some host contexts (e.g. the
            // tenant-scoped User model on the platform), where this surface isn't used.
            'users' => $this->assignableUsers(),
            'can_manage_super_admin' => $this->isSuperAdmin($request->user()),
        ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, mixed>
     */
    private function assignableUsers(): \Illuminate\Support\Collection
    {
        $userModel = config('hrmac.models.user', User::class);

        try {
            return $userModel::query()->orderBy('name')->get(['id', 'name', 'email']);
        } catch (\Throwable) {
            return collect();
        }
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

    private function isSuperAdmin($user): bool
    {
        // Any authenticated user model with a roles() relation (tenant User or central
        // LandlordUser) — HRMAC does not assume a specific user class.
        return $user !== null
            && method_exists($user, 'roles')
            && $user->roles()->whereIn('name', self::SUPER_ADMIN_ROLES)->exists();
    }
}
