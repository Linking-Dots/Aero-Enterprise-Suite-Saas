<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Models\LandlordRole;
use Aero\Platform\Services\LandlordRoleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * P-4 Landlord Role Controller
 *
 * CRUD + clone + permission assignment for platform (landlord) roles.
 * Distinct from the existing RoleController which handles HRMAC Spatie roles.
 *
 * Route prefix: /admin/p4/roles
 * Route names:  platform.admin.p4roles.*
 */
class LandlordRoleController extends Controller
{
    public function __construct(private LandlordRoleService $svc) {}

    public function index(): Response
    {
        return Inertia::render('Platform/Admin/Roles/Index', [
            'roles' => $this->svc->list(),
            'availablePermissions' => $this->availablePermissions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('central.landlord_roles', 'name')],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['array'],
            'permissions.*' => ['string'],
        ]);

        $this->svc->create($data);

        return back()->with('success', 'Role created.');
    }

    public function update(Request $request, LandlordRole $role): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('central.landlord_roles', 'name')->ignore($role->id)],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['array'],
            'permissions.*' => ['string'],
        ]);

        $this->svc->update($role, $data);

        return back()->with('success', 'Role updated.');
    }

    public function destroy(LandlordRole $role): RedirectResponse
    {
        $this->svc->delete($role);

        return back()->with('success', 'Role deleted.');
    }

    public function clone(Request $request, LandlordRole $role): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('central.landlord_roles', 'name')],
        ]);

        $this->svc->clone($role, $data['name']);

        return back()->with('success', 'Role cloned.');
    }

    public function updatePermissions(Request $request, LandlordRole $role): RedirectResponse
    {
        $data = $request->validate([
            'permissions' => ['required', 'array'],
            'permissions.*' => ['string'],
        ]);

        $this->svc->assignPermissions($role, $data['permissions']);

        return back()->with('success', 'Permissions updated.');
    }

    private function availablePermissions(): array
    {
        $moduleConfig = config('aero-platform') ?? [];
        $out = [];

        foreach ($moduleConfig['submodules'] ?? [] as $sub) {
            foreach ($sub['components'] ?? [] as $component) {
                foreach ($component['actions'] ?? [] as $action) {
                    $out[] = [
                        'path' => "{$sub['code']}.{$component['code']}.{$action['code']}",
                        'label' => "{$sub['name']} → {$component['name']} → {$action['name']}",
                    ];
                }
            }
        }

        return $out;
    }
}
