<?php

namespace Aero\Core\Http\Controllers\Admin;

use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Http\Requests\StoreUserRequest;
use Aero\Core\Http\Requests\UpdateUserRequest;
use Aero\Core\Models\User;
use Aero\Core\Services\UserInvitationService;
use Aero\Core\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class CoreUserController extends Controller
{
    public function __construct(
        private UserService $userService,
        private UserInvitationService $invitationService,
    ) {}

    public function index(Request $request): Response
    {
        $users = $this->userService->list($request->only('search', 'role', 'status'));

        return Inertia::render('Core/Users/Index', [
            'users' => $users,
            'roles' => Role::orderBy('name')->get(['id', 'name']),
            'filters' => $request->only('search', 'role', 'status'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Core/Users/Create', [
            'roles' => Role::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->userService->create($request->validated(), $request->user());

        return redirect()->route('core.users.index')->with('success', 'User created.');
    }

    public function show(User $user): Response
    {
        $user->load(['roles', 'sessions', 'devices']);

        return Inertia::render('Core/Users/Show', ['user' => $user]);
    }

    public function edit(User $user): Response
    {
        return Inertia::render('Core/Users/Edit', [
            'user' => $user->load('roles'),
            'roles' => Role::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->userService->update($user, $request->validated(), $request->user());

        return redirect()->route('core.users.show', $user)->with('success', 'User updated.');
    }

    public function destroy(User $user, Request $request): RedirectResponse
    {
        abort_if($user->id === $request->user()->id, 403, 'Cannot delete yourself.');
        $this->userService->delete($user, $request->user());

        return redirect()->route('core.users.index')->with('success', 'User deleted.');
    }

    public function toggleStatus(User $user, Request $request): RedirectResponse
    {
        $this->userService->toggleStatus($user, $request->user());

        return back()->with('success', 'User status updated.');
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $request->validate(['ids' => ['required', 'array']]);
        $count = $this->userService->bulkDelete($request->input('ids', []), $request->user());

        return back()->with('success', "{$count} users deleted.");
    }

    public function bulkAssignRoles(Request $request): RedirectResponse
    {
        $request->validate(['ids' => ['required', 'array'], 'roles' => ['required', 'array']]);
        $count = $this->userService->bulkAssignRoles($request->ids, $request->roles, $request->user());

        return back()->with('success', "Roles assigned to {$count} users.");
    }

    public function impersonate(User $user, Request $request): RedirectResponse
    {
        abort_if($user->hasRole('super-admin'), 403, 'Cannot impersonate super-admin.');
        session(['impersonating' => $user->id, 'impersonator' => $request->user()->id]);
        auth()->login($user);

        return redirect()->route('core.dashboard')->with('info', "Impersonating {$user->name}.");
    }

    public function stopImpersonating(Request $request): RedirectResponse
    {
        $impersonatorId = session('impersonator');
        session()->forget(['impersonating', 'impersonator']);
        if ($impersonatorId) {
            auth()->loginUsingId($impersonatorId);
        }

        return redirect()->route('core.dashboard');
    }

    public function invitations(Request $request): Response
    {
        $invitations = $this->invitationService->list($request->only('search'));

        return Inertia::render('Core/Users/Invitations/Index', ['invitations' => $invitations]);
    }

    public function invite(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'unique:users,email'],
            'roles' => ['array'],
        ]);
        $this->invitationService->invite($request->email, $request->roles ?? [], $request->user());

        return back()->with('success', 'Invitation sent.');
    }

    public function resendInvitation(int $invitationId, Request $request): RedirectResponse
    {
        $this->invitationService->resend($invitationId, $request->user());

        return back()->with('success', 'Invitation resent.');
    }

    public function cancelInvitation(int $invitationId, Request $request): RedirectResponse
    {
        $this->invitationService->cancel($invitationId, $request->user());

        return back()->with('success', 'Invitation cancelled.');
    }
}
