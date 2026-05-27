<?php

namespace Aero\Core\Http\Controllers\Admin;

use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Http\Requests\StoreRoleRequest;
use Aero\Core\Http\Requests\UpdateRoleRequest;
use Aero\Core\Services\RoleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function __construct(private RoleService $roleService) {}

    public function index(): Response
    {
        return Inertia::render('Core/Roles/Index', [
            'roles' => Role::withCount('users', 'permissions')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Core/Roles/Create', [
            'permissions' => Permission::orderBy('name')->get(['id', 'name']),
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
            'role' => $role->load('permissions'),
            'permissions' => Permission::orderBy('name')->get(['id', 'name']),
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
