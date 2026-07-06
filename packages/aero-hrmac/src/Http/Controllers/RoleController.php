<?php

declare(strict_types=1);

namespace Aero\HRMAC\Http\Controllers;

use Aero\HRMAC\Http\Requests\StoreRoleRequest;
use Aero\HRMAC\Http\Requests\UpdateRoleRequest;
use Aero\HRMAC\Models\Module;
use Aero\HRMAC\Models\ModuleComponent;
use Aero\HRMAC\Models\ModuleComponentAction;
use Aero\HRMAC\Models\Role;
use Aero\HRMAC\Models\RoleModuleAccess;
use Aero\HRMAC\Models\SubModule;
use Aero\HRMAC\Services\RoleService;
use Aero\Kernel\Http\Controllers\Controller;
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
        // works for tenant User and central User alike, and never triggers a
        // tenant-scoped User query on the platform).
        $assignmentCounts = DB::table('model_has_roles')
            ->selectRaw('role_id, COUNT(*) as c')
            ->groupBy('role_id')
            ->pluck('c', 'role_id');
        $roles->each(fn (Role $r) => $r->users_count = (int) ($assignmentCounts[$r->id] ?? 0));

        // The consuming route supplies its context via route defaults — HRMAC names no
        // specific view, prefix, or namespace. Both tenant and platform now render the
        // shared Pages/Shared/AccessControl/Roles/Index, parameterized by these props.
        $defaults = $request->route()?->defaults ?? [];
        $view = $defaults['hrmac_role_view'] ?? 'Shared/AccessControl/Roles/Index';
        $scope = $defaults['hrmac_scope'] ?? 'tenant';

        $props = [
            'roles' => $roles,
            // Assignable users for the inline assign-user drawer. Best-effort: the
            // configured user model may be unqueryable in some host contexts (e.g. the
            // tenant-scoped User model on the platform), where this surface isn't used.
            'users' => $this->assignableUsers(),
            'can_manage_super_admin' => $this->isSuperAdmin($request->user()),
            // Context wiring for the shared page (prop-driven route/HRMAC strings).
            'routePrefix' => $defaults['hrmac_route_prefix'] ?? 'core.roles',
            'moduleAccessRoutePrefix' => $defaults['hrmac_module_access_prefix'] ?? 'core.modules',
            'hrmacNamespace' => $defaults['hrmac_namespace'] ?? 'hrmac.roles_permissions',
            'scope' => $scope,
            'dashboardRoute' => $defaults['hrmac_dashboard_route']
                ?? ($scope === 'platform' ? 'platform.admin.dashboard' : 'core.dashboard'),
        ];

        // The shared RBAC page edits per-role module access inline (the access Drawer),
        // so it needs the scoped module tree, the available action scopes, and the
        // registry counts. Attached for the shared page (both contexts) and the legacy
        // core page. The scope selects the tenant vs platform module tree.
        if (in_array($view, ['Shared/AccessControl/Roles/Index', 'Core/Roles/Index'], true)) {
            $props['modules'] = $this->moduleTree($scope);
            $props['accessScopes'] = RoleModuleAccess::ACCESS_SCOPES;
            $props['statistics'] = $this->moduleStatistics($scope);
        }

        return Inertia::render($view, $props);
    }

    /**
     * Build the hierarchical module tree (module → sub-module → component → action)
     * the access Drawer renders. Mirrors the shape ModuleController exposes, using
     * numeric DB ids so it round-trips with core.modules.role-access.show|sync.
     *
     * @return array<int, array<string, mixed>>
     */
    private function moduleTree(string $scope): array
    {
        return Module::where('scope', $scope)
            ->where('is_active', true)
            ->with([
                'subModules' => fn ($q) => $q->orderBy('priority'),
                'subModules.components' => fn ($q) => $q->orderBy('id'),
                'subModules.components.actions' => fn ($q) => $q->orderBy('id'),
            ])
            ->orderBy('priority')
            ->get()
            ->map(fn (Module $module) => [
                'id' => $module->id,
                'code' => $module->code,
                'name' => $module->name,
                'is_core' => $module->is_core,
                'sub_modules' => $module->subModules->map(fn (SubModule $sub) => [
                    'id' => $sub->id,
                    'code' => $sub->code,
                    'name' => $sub->name,
                    'components' => $sub->components->map(fn (ModuleComponent $comp) => [
                        'id' => $comp->id,
                        'code' => $comp->code,
                        'name' => $comp->name,
                        'actions' => $comp->actions->map(fn (ModuleComponentAction $action) => [
                            'id' => $action->id,
                            'code' => $action->code,
                            'name' => $action->name,
                        ])->toArray(),
                    ])->toArray(),
                ])->toArray(),
            ])->toArray();
    }

    /**
     * Registry counts for the access-related KPIs on the RBAC page.
     *
     * @return array<string, int>
     */
    private function moduleStatistics(string $scope): array
    {
        $moduleIds = Module::where('scope', $scope)->where('is_active', true)->pluck('id');
        $subModuleIds = SubModule::whereIn('module_id', $moduleIds)->pluck('id');

        return [
            'total_modules' => $moduleIds->count(),
            'total_sub_modules' => $subModuleIds->count(),
            'total_components' => ModuleComponent::whereIn('sub_module_id', $subModuleIds)->count(),
            'total_actions' => ModuleComponentAction::whereHas(
                'component',
                fn ($q) => $q->whereIn('sub_module_id', $subModuleIds)
            )->count(),
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, mixed>
     */
    private function assignableUsers(): \Illuminate\Support\Collection
    {
        $userModel = config('hrmac.models.user') ?: config('auth.providers.users.model');

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

        $userModel = config('hrmac.models.user') ?: config('auth.providers.users.model');
        $user = $userModel::findOrFail($data['user_id']);
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
        // User) — HRMAC does not assume a specific user class.
        return $user !== null
            && method_exists($user, 'roles')
            && $user->roles()->whereIn('name', self::SUPER_ADMIN_ROLES)->exists();
    }
}
