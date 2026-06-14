<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\HRMAC\Models\Role;
use Aero\Platform\Http\Controllers\Controller;
use Aero\Auth\Models\User;
use Aero\Auth\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * P-4 Landlord User Controller
 *
 * CRUD + toggle-status for platform (landlord) users.
 * Distinct from the existing UserController which serves a different API contract.
 *
 * Route prefix: /admin/p4/users
 * Route names:  platform.admin.p4users.*
 */
class LandlordUserController extends Controller
{
    public function __construct(private UserService $svc) {}

    public function index(Request $request): Response
    {
        // Active/inactive is managed via SoftDeletes (active = not trashed). Map the
        // legacy q/active filter onto the unified search/status contract.
        $status = $request->filled('active')
            ? ($request->boolean('active') ? 'active' : 'inactive')
            : 'all';

        return Inertia::render('Platform/Admin/Users/Index', [
            'users' => $this->svc->list(['search' => $request->input('q'), 'status' => $status]),
            'roles' => Role::orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['q', 'active']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', Rule::unique('central.users', 'email')],
            'password' => ['required', 'string', 'min:8'],
            'active' => ['boolean'],
            'role_ids' => ['array'],
            'role_ids.*' => ['integer', 'exists:central.roles,id'],
        ]);

        $this->svc->create($data);

        return back()->with('success', 'User created.');
    }

    public function show(User $user): Response
    {
        return Inertia::render('Platform/Admin/Users/Show', [
            'user' => $user->load('roles:id,name'),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', Rule::unique('central.users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'active' => ['boolean'],
            'role_ids' => ['array'],
            'role_ids.*' => ['integer', 'exists:central.roles,id'],
        ]);

        $this->svc->update($user, $data);

        return back()->with('success', 'User updated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->svc->delete($user);

        return back()->with('success', 'User deleted.');
    }

    public function toggleStatus(User $user): RedirectResponse
    {
        $this->svc->toggleStatus($user);

        return back()->with('success', 'Status updated.');
    }
}
