<?php

namespace Aero\Core\Http\Controllers\Admin;

use Aero\Contracts\AuditServiceInterface;
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
use Aero\HRMAC\Models\Role;

class CoreUserController extends Controller
{
    /**
     * Plan 02 T3 — Phase 1 audit found this controller had ZERO calls to
     * AuditService despite mutating user lifecycle (create/update/delete/
     * toggle/impersonate). Every mutating action now logs via the injected
     * AuditService so compliance dashboards can reconstruct who did what.
     */
    public function __construct(
        private UserService $userService,
        private UserInvitationService $invitationService,
        private AuditServiceInterface $audit,
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
        $user = $this->userService->create($request->validated(), $request->user());

        $this->audit->log(
            event: 'core.user.created',
            action: 'created',
            subject: $user instanceof User ? $user : null,
            description: 'User account created',
            after: $request->safe()->except(['password', 'password_confirmation']),
            metadata: ['actor_id' => $request->user()?->id],
        );

        return redirect()->route('core.users.index')->with('success', 'User created.');
    }

    public function show(User $user): Response
    {
        $relationships = ['roles'];
        if (method_exists($user, 'sessions')) {
            $relationships[] = 'sessions';
        }
        if (method_exists($user, 'devices')) {
            $relationships[] = 'devices';
        }
        $user->load($relationships);

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
        $before = $user->only(['name', 'email', 'is_active']);

        $this->userService->update($user, $request->validated(), $request->user());

        $this->audit->log(
            event: 'core.user.updated',
            action: 'updated',
            subject: $user->fresh(),
            description: 'User account updated',
            before: $before,
            after: $request->safe()->except(['password', 'password_confirmation']),
            metadata: ['actor_id' => $request->user()?->id],
        );

        return redirect()->route('core.users.show', $user)->with('success', 'User updated.');
    }

    public function destroy(User $user, Request $request): RedirectResponse
    {
        abort_if($user->id === $request->user()->id, 403, 'Cannot delete yourself.');

        $snapshot = $user->only(['id', 'name', 'email']);

        $this->userService->delete($user, $request->user());

        $this->audit->log(
            event: 'core.user.deleted',
            action: 'deleted',
            subject: null,
            description: "User '{$snapshot['email']}' deleted",
            before: $snapshot,
            metadata: ['actor_id' => $request->user()?->id, 'target_user_id' => $snapshot['id']],
        );

        return redirect()->route('core.users.index')->with('success', 'User deleted.');
    }

    public function toggleStatus(User $user, Request $request): RedirectResponse
    {
        $previousStatus = $user->is_active;

        $this->userService->toggleStatus($user, $request->user());

        $this->audit->log(
            event: 'core.user.status_toggled',
            action: $previousStatus ? 'deactivated' : 'activated',
            subject: $user->fresh(),
            description: "User '{$user->email}' " . ($previousStatus ? 'deactivated' : 'activated'),
            before: ['is_active' => $previousStatus],
            after: ['is_active' => ! $previousStatus],
            metadata: ['actor_id' => $request->user()?->id],
        );

        return back()->with('success', 'User status updated.');
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $request->validate(['ids' => ['required', 'array']]);
        $ids = $request->input('ids', []);
        $count = $this->userService->bulkDelete($ids, $request->user());

        $this->audit->log(
            event: 'core.user.bulk_deleted',
            action: 'bulk_deleted',
            subject: null,
            description: "{$count} users deleted in bulk",
            metadata: ['actor_id' => $request->user()?->id, 'target_user_ids' => $ids, 'count' => $count],
        );

        return back()->with('success', "{$count} users deleted.");
    }

    public function bulkAssignRoles(Request $request): RedirectResponse
    {
        $request->validate(['ids' => ['required', 'array'], 'roles' => ['required', 'array']]);
        $count = $this->userService->bulkAssignRoles($request->ids, $request->roles, $request->user());

        $this->audit->log(
            event: 'core.user.bulk_roles_assigned',
            action: 'bulk_roles_assigned',
            subject: null,
            description: "Roles assigned to {$count} users",
            metadata: [
                'actor_id' => $request->user()?->id,
                'target_user_ids' => $request->ids,
                'roles' => $request->roles,
                'count' => $count,
            ],
        );

        return back()->with('success', "Roles assigned to {$count} users.");
    }

    public function impersonate(User $user, Request $request): RedirectResponse
    {
        abort_if($user->hasRole('super-admin'), 403, 'Cannot impersonate super-admin.');

        $impersonator = $request->user();

        $this->audit->log(
            event: 'core.user.impersonate_started',
            action: 'impersonate_started',
            subject: $user,
            description: "Admin '{$impersonator?->email}' started impersonating '{$user->email}'",
            metadata: [
                'actor_id' => $impersonator?->id,
                'target_user_id' => $user->id,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ],
        );

        session(['impersonating' => $user->id, 'impersonator' => $impersonator->id]);
        auth()->login($user);

        return redirect()->route('core.dashboard')->with('info', "Impersonating {$user->name}.");
    }

    public function stopImpersonating(Request $request): RedirectResponse
    {
        $impersonatorId = session('impersonator');
        $impersonatedId = session('impersonating');
        session()->forget(['impersonating', 'impersonator']);
        if ($impersonatorId) {
            auth()->loginUsingId($impersonatorId);
        }

        $this->audit->log(
            event: 'core.user.impersonate_stopped',
            action: 'impersonate_stopped',
            subject: null,
            description: 'Impersonation ended',
            metadata: [
                'actor_id' => $impersonatorId,
                'target_user_id' => $impersonatedId,
            ],
        );

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
