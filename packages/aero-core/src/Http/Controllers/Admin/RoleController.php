<?php

namespace Aero\Core\Http\Controllers\Admin;

use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Http\Requests\StoreRoleRequest;
use Aero\Core\Http\Requests\UpdateRoleRequest;
use Aero\Core\Services\RoleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Aero\HRMAC\Models\Role;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
    public function __construct(private RoleService $roleService) {}

    public function index(): Response
    {
        // HRMAC: role "permissions" are module-access grants (role_module_access),
        // not Spatie permissions. Count those via the moduleAccess relation.
        return Inertia::render('Core/Roles/Index', [
            'roles' => Role::withCount('users', 'moduleAccess')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): Response
    {
        // Module-access grants are edited via the HRMAC module-access tree
        // (RoleModuleAccessService), not a Spatie permission picker.
        return Inertia::render('Core/Roles/Create', [
            'permissions' => [],
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $this->roleService->create($request->validated(), $request->user());

        return redirect()->route('core.roles.index')->with('success', 'Role created.');
    }

    public function edit(Role $role): Response
    {
        return Inertia::render('Core/Roles/Edit', [
            'role' => $role->loadCount('moduleAccess'),
            'permissions' => [],
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        abort_if($role->name === 'super-admin', 403, 'Cannot edit super-admin role.');
        $this->roleService->update($role, $request->validated(), $request->user());

        return redirect()->route('core.roles.index')->with('success', 'Role updated.');
    }

    public function destroy(Role $role, Request $request): RedirectResponse
    {
        abort_if($role->name === 'super-admin', 403, 'Cannot delete super-admin role.');
        $this->roleService->delete($role, $request->user());

        return redirect()->route('core.roles.index')->with('success', 'Role deleted.');
    }

    public function syncPermissions(Request $request, Role $role): RedirectResponse
    {
        $request->validate([
            'permissions' => ['required', 'array'],
            'permissions.*' => ['string'],
        ]);
        $this->roleService->syncModulePermissions($role, $request->permissions, $request->user());

        return back()->with('success', 'Permissions updated.');
    }
}
