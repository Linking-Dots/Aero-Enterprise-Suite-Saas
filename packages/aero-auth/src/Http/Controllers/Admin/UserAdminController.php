<?php

declare(strict_types=1);

namespace Aero\Auth\Http\Controllers\Admin;

use Aero\Auth\Http\Concerns\ResolvesContextUserModel;
use Aero\Auth\Http\Controllers\Controller;
use Aero\Auth\Http\Requests\Admin\StoreUserAdminRequest;
use Aero\Auth\Http\Requests\Admin\UpdateUserAdminRequest;
use Aero\Auth\Services\UserInvitationService;
use Aero\Auth\Services\UserService;
use Aero\Contracts\AuditServiceInterface;
use Aero\HRMAC\Models\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Shared, context-free user-administration surface — the single controller
 * for BOTH the tenant (`web` guard, `users` table) and platform (`landlord`
 * guard) contexts. Mirrors the "one controller, both contexts, zero
 * core/platform symbols" pattern aero-hrmac's RoleController established:
 *
 *  - The user model is NEVER hardcoded — resolved via
 *    ResolvesContextUserModel (guard -> provider -> model config lookup),
 *    the same context-free approach RoleController/RoleModuleAccessService
 *    already use for `hrmac.models.user` / `auth.providers.users.model`.
 *  - The Inertia view is NEVER hardcoded — chosen from route defaults
 *    (`hrmac_user_view` etc.), falling back to the existing tenant page so
 *    nothing breaks before the shared page ships.
 *  - Super-admin / capability checks are duck-typed (`method_exists`), never
 *    `instanceof` a specific class.
 *
 * Ported from Aero\Core\Http\Controllers\Admin\CoreUserController (tenant)
 * and Aero\Platform\Http\Controllers\Admin\LandlordUserController
 * (platform). This is ADDITIVE — neither source controller is wired away
 * from yet. See the Backend Output Report for this task for the exact
 * method-parity notes and the (documented) behavior deviations.
 */
class UserAdminController extends Controller
{
    use ResolvesContextUserModel;

    public function __construct(
        private readonly UserService $users,
        private readonly UserInvitationService $invitations,
        private readonly AuditServiceInterface $audit,
    ) {}

    public function index(Request $request): Response
    {
        $invitationsAllowed = $this->invitationsAllowed($request);

        $users = $this->users->list($request->only('search', 'role', 'status'));

        $invitations = $invitationsAllowed
            ? $this->invitations->list($request->only('search'))->whereNull('accepted_at')->values()
            : collect();

        $modelClass = $this->resolveUserModel();

        $view = $request->route()?->defaults['hrmac_user_view'] ?? 'Core/Users/Index';

        return Inertia::render($view, [
            'users' => $users,
            'roles' => Role::orderBy('name')->get(['id', 'name']),
            'invitations' => $invitations,
            'filters' => $request->only('search', 'role', 'status'),
            'stats' => [
                'total' => $modelClass::withTrashed()->count(),
                'active' => $modelClass::count(),
                'inactive' => $modelClass::onlyTrashed()->count(),
                'pending' => $invitations->count(),
            ],
            // Command-centre payload: every member normalised (roles + status +
            // 2FA + login state) plus role distribution + sparklines. Guard-free,
            // context-agnostic (resolved user model) — mirrors the platform Users
            // and Roles command centres.
            ...$this->commandCenterData($invitations->count()),
            ...$this->contextProps($request),
        ]);
    }

    /**
     * Normalised roster + KPI stats + role distribution + sparklines for the
     * Users command centre. Best-effort: if the resolved user model can't be
     * queried in this context everything degrades to empty so the page still
     * renders on its paginated `users` prop.
     *
     * @return array{members: array, memberStats: array, roleDist: array, sparks: array}
     */
    private function commandCenterData(int $pending): array
    {
        try {
            $rows = $this->newUserQuery()->with('roles:id,name')->orderByDesc('id')->get();
        } catch (\Throwable) {
            return ['members' => [], 'memberStats' => [], 'roleDist' => [], 'sparks' => []];
        }

        $members = $rows->map(function ($u) {
            $locked = ($u->account_locked_at ?? null) !== null;
            $inactive = method_exists($u, 'trashed') && $u->trashed();

            return [
                'id'            => $u->id,
                'name'          => $u->name ?: ($u->user_name ?? '—'),
                'email'         => $u->email,
                'avatar_url'    => $u->avatar_url ?? null,
                'roles'         => $u->relationLoaded('roles') ? $u->roles->pluck('name')->values()->all() : [],
                'status'        => $locked ? 'locked' : ($inactive ? 'inactive' : 'active'),
                'tfa'           => ($u->two_factor_confirmed_at ?? null) !== null,
                'last_login_at' => ($u->last_login_at ?? null) ? \Carbon\Carbon::parse($u->last_login_at)->toIso8601String() : null,
                'login_count'   => (int) ($u->login_count ?? 0),
                'created_at'    => ($u->created_at ?? null) ? \Carbon\Carbon::parse($u->created_at)->toIso8601String() : null,
            ];
        })->all();

        $count = fn (callable $p) => count(array_filter($members, $p));
        $total = max(1, count($members));
        $tfaOn = $count(fn ($m) => $m['tfa']);
        $roleDist = [];
        foreach ($members as $m) {
            foreach ($m['roles'] as $r) {
                $roleDist[$r] = ($roleDist[$r] ?? 0) + 1;
            }
        }
        arsort($roleDist);

        $stats = [
            'total'        => count($members),
            'active'       => $count(fn ($m) => $m['status'] === 'active'),
            'inactive'     => $count(fn ($m) => $m['status'] === 'inactive'),
            'locked'       => $count(fn ($m) => $m['status'] === 'locked'),
            'tfa_on'       => $tfaOn,
            'tfa_pct'      => (int) round($tfaOn / $total * 100),
            'admins'       => $count(fn ($m) => (bool) array_filter($m['roles'], fn ($r) => str_contains($r, 'Admin'))),
            'no_role'      => $count(fn ($m) => empty($m['roles'])),
            'never_logged' => $count(fn ($m) => $m['last_login_at'] === null),
            'pending'      => $pending,
        ];

        return [
            'members'     => $members,
            'memberStats' => $stats,
            'roleDist'    => array_map(fn ($name, $c) => ['role' => $name, 'count' => $c], array_keys($roleDist), array_values($roleDist)),
            'sparks'      => $this->memberSparks($rows),
        ];
    }

    /**
     * 6-month cumulative member-count sparkline from created_at.
     *
     * @return array{members: array<int,int>}
     */
    private function memberSparks($rows): array
    {
        $end = now()->startOfMonth();
        $series = [];
        foreach (range(5, 0) as $i) {
            $cut = $end->copy()->subMonths($i)->endOfMonth();
            $series[] = $rows->filter(fn ($u) => ($u->created_at ?? null) && \Carbon\Carbon::parse($u->created_at)->lte($cut))->count();
        }

        return ['members' => $series];
    }

    public function create(Request $request): Response
    {
        $view = $request->route()?->defaults['hrmac_user_create_view'] ?? 'Core/Users/Create';

        return Inertia::render($view, [
            'roles' => Role::orderBy('name')->get(['id', 'name']),
            ...$this->contextProps($request),
        ]);
    }

    public function store(StoreUserAdminRequest $request): RedirectResponse
    {
        // Aero\Auth\Services\UserService::create() already wraps the write in
        // DB::transaction() and calls AuditServiceInterface::log() internally
        // — logging again here would double-write the audit trail (a defect
        // present in the legacy CoreUserController::store(), not repeated).
        $this->users->create($request->validated(), $request->user());

        return back()->with('success', 'User created.');
    }

    public function show(Request $request, int $id): Response
    {
        $user = $this->resolveUserOrFail($id);

        $relations = ['roles'];
        if (method_exists($user, 'sessions')) {
            $relations[] = 'sessions';
        }
        if (method_exists($user, 'devices')) {
            $relations[] = 'devices';
        }
        $user->load($relations);

        $view = $request->route()?->defaults['hrmac_user_show_view'] ?? 'Core/Users/Show';

        return Inertia::render($view, ['user' => $user, ...$this->contextProps($request)]);
    }

    public function edit(Request $request, int $id): Response
    {
        $user = $this->resolveUserOrFail($id);

        $view = $request->route()?->defaults['hrmac_user_edit_view'] ?? 'Core/Users/Edit';

        return Inertia::render($view, [
            'user' => $user->load('roles'),
            'roles' => Role::orderBy('name')->get(['id', 'name']),
            ...$this->contextProps($request),
        ]);
    }

    public function update(UpdateUserAdminRequest $request, int $id): RedirectResponse
    {
        $user = $this->resolveUserOrFail($id);

        $this->users->update($user, $request->validated(), $request->user());

        return back()->with('success', 'User updated.');
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $user = $this->resolveUserOrFail($id);

        abort_if($request->user()?->id === $user->id, 403, 'Cannot delete yourself.');

        $this->users->delete($user, $request->user());

        return back()->with('success', 'User deleted.');
    }

    public function toggleStatus(Request $request, int $id): RedirectResponse
    {
        $user = $this->resolveUserOrFail($id);

        $this->users->toggleStatus($user, $request->user());

        return back()->with('success', 'User status updated.');
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $count = $this->users->bulkDelete($data['ids'], $request->user());

        return back()->with('success', "{$count} users deleted.");
    }

    public function bulkToggleStatus(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_ids' => ['required', 'array'],
            'user_ids.*' => ['integer'],
            'active' => ['required', 'boolean'],
        ]);

        // Set each selected user to the requested active state (SoftDeletes-based),
        // only toggling those that differ — so the bulk action honors the intent
        // rather than blindly flipping.
        foreach ($this->newUserQuery()->whereIn('id', $data['user_ids'])->get() as $user) {
            $isActive = ! (method_exists($user, 'trashed') && $user->trashed());
            if ($isActive !== $data['active']) {
                $this->users->toggleStatus($user, $request->user());
            }
        }

        return back()->with('success', 'User statuses updated.');
    }

    public function bulkAssignRoles(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
            'role_ids' => ['required', 'array'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
        ]);

        $count = $this->users->bulkAssignRoles($data['ids'], $data['role_ids'], $request->user());

        return back()->with('success', "Roles assigned to {$count} users.");
    }

    /**
     * Scope-gated (Boss-approved): only acts when the consuming route opts
     * in via the `hrmac_user_impersonation` default (tenant only, today).
     */
    public function impersonate(Request $request, int $id): RedirectResponse
    {
        abort_unless($this->impersonationAllowed($request), 403);

        $target = $this->resolveUserOrFail($id);

        abort_if(
            method_exists($target, 'hasRole') && $target->hasRole('super-admin'),
            403,
            'Cannot impersonate super-admin.'
        );

        $impersonator = $request->user();
        $guard = $this->resolveGuardName();

        $this->audit->log(
            event: 'auth.user.impersonate_started',
            action: 'impersonate_started',
            subject: $target,
            description: "Admin '{$impersonator?->email}' started impersonating '{$target->email}'",
            metadata: [
                'actor_id' => $impersonator?->id,
                'target_user_id' => $target->id,
                'guard' => $guard,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ],
        );

        session([
            'impersonating' => $target->id,
            'impersonator' => $impersonator?->id,
            'impersonation_guard' => $guard,
        ]);
        Auth::guard($guard)->login($target);

        // No fixed dashboard route name exists across both contexts
        // (core.dashboard vs the platform equivalent) — redirect to the
        // app root rather than assume a route name aero-auth cannot know.
        return redirect()->to('/')->with('info', "Impersonating {$target->name}.");
    }

    public function stopImpersonating(Request $request): RedirectResponse
    {
        $guard = session('impersonation_guard', $this->resolveGuardName());
        $impersonatorId = session('impersonator');
        $impersonatedId = session('impersonating');
        session()->forget(['impersonating', 'impersonator', 'impersonation_guard']);

        if ($impersonatorId) {
            Auth::guard($guard)->loginUsingId($impersonatorId);
        }

        $this->audit->log(
            event: 'auth.user.impersonate_stopped',
            action: 'impersonate_stopped',
            subject: null,
            description: 'Impersonation ended',
            metadata: [
                'actor_id' => $impersonatorId,
                'target_user_id' => $impersonatedId,
                'guard' => $guard,
            ],
        );

        return redirect()->to('/');
    }

    /**
     * Scope-gated (Boss-approved): tenant-only invitation surfaces.
     */
    public function invitations(Request $request): Response
    {
        abort_unless($this->invitationsAllowed($request), 403);

        $invitations = $this->invitations->list($request->only('search'));

        $view = $request->route()?->defaults['hrmac_user_invitations_view'] ?? 'Core/Users/Invitations/Index';

        return Inertia::render($view, ['invitations' => $invitations, ...$this->contextProps($request)]);
    }

    public function invite(Request $request): RedirectResponse
    {
        abort_unless($this->invitationsAllowed($request), 403);

        $table = (new ($this->resolveUserModel()))->getTable();

        $data = $request->validate([
            'email' => ['required', 'email', Rule::unique($table, 'email')],
            'roles' => ['array'],
        ]);

        $this->invitations->invite($data['email'], $data['roles'] ?? [], $request->user());

        return back()->with('success', 'Invitation sent.');
    }

    public function resendInvitation(Request $request, int $invitationId): RedirectResponse
    {
        abort_unless($this->invitationsAllowed($request), 403);

        $this->invitations->resend($invitationId, $request->user());

        return back()->with('success', 'Invitation resent.');
    }

    public function cancelInvitation(Request $request, int $invitationId): RedirectResponse
    {
        abort_unless($this->invitationsAllowed($request), 403);

        $this->invitations->cancel($invitationId, $request->user());

        return back()->with('success', 'Invitation cancelled.');
    }

    /**
     * Shared context wiring for the prop-driven Shared/UserManagement pages: the
     * consuming route supplies its prefix/namespace/scope/capabilities via route
     * defaults so ONE page component serves both tenant and platform.
     *
     * @return array<string, mixed>
     */
    private function contextProps(Request $request): array
    {
        $defaults = $request->route()?->defaults ?? [];
        $scope = $defaults['hrmac_scope'] ?? 'tenant';

        return [
            'routePrefix' => $defaults['hrmac_route_prefix'] ?? 'core.users',
            'hrmacNamespace' => $defaults['hrmac_namespace'] ?? 'auth.user_management',
            'scope' => $scope,
            'dashboardRoute' => $defaults['hrmac_dashboard_route']
                ?? ($scope === 'platform' ? 'platform.admin.dashboard' : 'core.dashboard'),
            'capabilities' => [
                'impersonation' => $this->impersonationAllowed($request),
                'invitations' => $this->invitationsAllowed($request),
            ],
        ];
    }

    private function impersonationAllowed(Request $request): bool
    {
        return (bool) ($request->route()?->defaults['hrmac_user_impersonation'] ?? false);
    }

    private function invitationsAllowed(Request $request): bool
    {
        return (bool) ($request->route()?->defaults['hrmac_user_invitations'] ?? false);
    }

    private function newUserQuery(): Builder
    {
        $modelClass = $this->resolveUserModel();
        $query = $modelClass::query();

        // Duck-typed: only widen to withTrashed() when the resolved model
        // actually uses SoftDeletes (active/inactive is a soft-delete state
        // on Aero\Auth\Models\User, but this controller assumes nothing
        // about the resolved class beyond what it can detect).
        if (method_exists($modelClass, 'bootSoftDeletes')) {
            $query->withTrashed();
        }

        return $query;
    }

    private function resolveUserOrFail(int $id): Model
    {
        return $this->newUserQuery()->findOrFail($id);
    }
}
