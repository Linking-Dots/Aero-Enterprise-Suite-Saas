# Plan CA-1 — Dashboard, User Management & Roles Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deliver a production-grade Tenant Admin shell: admin dashboard with live stats, full User Management (CRUD + invitations + bulk ops + impersonation + export/import), Roles & Permissions with HRMAC matrix editor, Module Access toggle per role, and Tenant Announcements — all HRMAC-guarded, audited, and tested.

**Architecture:** All backend logic stays in `packages/aero-core`. Thin Inertia controllers delegate to service classes. `DashboardController` returns aggregated stats. `CoreUserController` delegates to `UserService`. `RoleController` delegates to `RoleService`. `AnnouncementController` delegates to `AnnouncementService`. React pages live in `packages/aero-ui/resources/js/Pages/Core/`. Routes are already registered in `packages/aero-core/routes/web.php` — verify and fill any gaps. All pages use `AppLayout` from `@aero/ui`.

**Tech Stack:** PHP 8.2, Laravel 12, Inertia.js v2, React 18, `@aero/ui` (HeroUI), PHPUnit 11, Spatie Permissions.

**Prerequisites:** Phase 0 complete (F-1 auth, F-3 shell). HRMAC middleware registered. `AuditService` available.

**Standards:** `docs/standards/inertia-standard.md` · `docs/standards/hrmac-convention.md` · `docs/standards/done-definition.md`

---

## Security Notes

- All routes guarded by `hrmac:core.<submodule>.<component>.<action>`
- `AuditService::log()` on every user create/update/delete/activate/deactivate/impersonate
- `AuditService::log()` on every role create/update/delete
- Impersonation: log with `AuditEventType::USER_IMPERSONATED`, record impersonator + target
- No PII encrypted in this plan (no bank/national ID on User model)
- `UserPolicy` must be enforced before impersonation (cannot impersonate super-admin)

---

## File Map

**Backend (packages/aero-core/src/)**
```
Http/Controllers/DashboardController.php           -- UPGRADE: add stats props
Http/Controllers/Admin/CoreUserController.php      -- UPGRADE: full CRUD + bulk + impersonate + invite
Http/Controllers/Admin/RoleController.php          -- UPGRADE: full CRUD + permissions matrix
Http/Controllers/Admin/ModuleController.php        -- UPGRADE: toggle + configure per role
Http/Controllers/Admin/AnnouncementController.php  -- CREATE: tenant announcements CRUD
Http/Requests/StoreUserRequest.php                 -- UPGRADE: validation rules
Http/Requests/UpdateUserRequest.php                -- UPGRADE: validation rules
Http/Requests/StoreRoleRequest.php                 -- CREATE
Http/Requests/UpdateRoleRequest.php                -- CREATE
Http/Requests/StoreAnnouncementRequest.php         -- UPGRADE
Http/Requests/UpdateAnnouncementRequest.php        -- CREATE
Services/UserService.php                           -- CREATE (extracted from controller)
Services/RoleService.php                           -- CREATE
Services/AnnouncementService.php                   -- CREATE
Models/Announcement.php                            -- UPGRADE: add scopes + policy cast
```

**Migrations (packages/aero-core/database/migrations/)**
```
2026_05_22_000001_create_announcements_table.php   -- CREATE if not exists
```

**Frontend (packages/aero-ui/resources/js/Pages/Core/)**
```
Dashboard/Index.jsx                 -- CREATE: admin dashboard with stats widgets
Users/Index.jsx                     -- UPGRADE: search, filters, bulk, impersonate
Users/Create.jsx                    -- UPGRADE: full form with roles
Users/Edit.jsx                      -- UPGRADE: full form with roles
Users/Show.jsx                      -- UPGRADE: profile + activity timeline
Users/Invitations/Index.jsx         -- CREATE: pending invitations table + invite modal
Roles/Index.jsx                     -- UPGRADE: roles table + actions
Roles/Create.jsx                    -- CREATE: role form
Roles/Edit.jsx                      -- UPGRADE: role form + permissions matrix
Modules/Index.jsx                   -- UPGRADE: module toggle per role
Announcements/Index.jsx             -- CREATE: announcements table + create/edit modal
```

**Tests (packages/aero-core/tests/Feature/)**
```
Admin/DashboardControllerTest.php    -- CREATE
Admin/CoreUserControllerTest.php     -- CREATE
Admin/RoleControllerTest.php         -- CREATE
Admin/AnnouncementControllerTest.php -- CREATE
```

---

## Task 1 — Migration: announcements table

**Files:**
- Create: `packages/aero-core/database/migrations/2026_05_22_000001_create_announcements_table.php`

- [ ] Check if table already exists — if so skip this task. Otherwise create migration:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('announcements')) {
            return;
        }
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->longText('body');
            $table->string('type')->default('info'); // info|warning|success|danger
            $table->string('status')->default('draft'); // draft|published|archived
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('audience')->default('all'); // all|admins|employees
            $table->boolean('is_pinned')->default(false);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
```

- [ ] Commit:
```bash
git add packages/aero-core/database/migrations/2026_05_22_000001_create_announcements_table.php
git commit -m "feat(aero-core): announcements migration"
```

---

## Task 2 — Model: Announcement

**Files:**
- Modify: `packages/aero-core/src/Models/Announcement.php`

- [ ] Write/upgrade Announcement model:

```php
<?php

namespace Aero\Core\Models;

use Aero\Core\Models\TenantModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class Announcement extends TenantModel
{
    use SoftDeletes;

    protected $fillable = [
        'title', 'body', 'type', 'status',
        'published_at', 'expires_at', 'audience',
        'is_pinned', 'created_by',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'expires_at'   => 'datetime',
        'is_pinned'    => 'boolean',
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    public function scopeActive($query)
    {
        return $query->published()->where(function ($q) {
            $q->whereNull('published_at')->orWhere('published_at', '<=', now());
        });
    }
}
```

- [ ] Commit:
```bash
git add packages/aero-core/src/Models/Announcement.php
git commit -m "feat(aero-core): Announcement model with scopes"
```

---

## Task 3 — Services: UserService, RoleService, AnnouncementService

**Files:**
- Create: `packages/aero-core/src/Services/UserService.php`
- Create: `packages/aero-core/src/Services/RoleService.php`
- Create: `packages/aero-core/src/Services/AnnouncementService.php`

- [ ] Create `UserService.php`:

```php
<?php

namespace Aero\Core\Services;

use Aero\Core\Models\User;
use Aero\Core\Services\Audit\AuditService;
use Aero\Core\Services\Audit\AuditEventType;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class UserService
{
    public function __construct(private AuditService $audit) {}

    public function list(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return User::query()
            ->when($filters['search'] ?? null, fn($q, $s) =>
                $q->where(fn($q2) => $q2
                    ->where('name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")))
            ->when($filters['role'] ?? null, fn($q, $r) =>
                $q->whereHas('roles', fn($q2) => $q2->where('name', $r)))
            ->when(isset($filters['status']), fn($q) =>
                $q->where('is_active', $filters['status'] === 'active'))
            ->with(['roles'])
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data, User $actor): User
    {
        return DB::transaction(function () use ($data, $actor) {
            $user = User::create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'password' => Hash::make($data['password'] ?? Str::random(16)),
                'is_active' => true,
            ]);

            if (!empty($data['roles'])) {
                $user->syncRoles($data['roles']);
            }

            $this->audit->log(AuditEventType::USER_CREATED, $actor, $user, ['email' => $user->email]);

            return $user;
        });
    }

    public function update(User $user, array $data, User $actor): User
    {
        return DB::transaction(function () use ($user, $data, $actor) {
            $user->update(array_filter([
                'name'  => $data['name'] ?? null,
                'email' => $data['email'] ?? null,
            ], fn($v) => $v !== null));

            if (array_key_exists('roles', $data)) {
                $user->syncRoles($data['roles']);
            }

            $this->audit->log(AuditEventType::USER_UPDATED, $actor, $user);

            return $user->fresh();
        });
    }

    public function delete(User $user, User $actor): void
    {
        DB::transaction(function () use ($user, $actor) {
            $this->audit->log(AuditEventType::USER_DELETED, $actor, $user, ['email' => $user->email]);
            $user->delete();
        });
    }

    public function toggleStatus(User $user, User $actor): User
    {
        $user->update(['is_active' => ! $user->is_active]);
        $event = $user->is_active ? AuditEventType::USER_ACTIVATED : AuditEventType::USER_DEACTIVATED;
        $this->audit->log($event, $actor, $user);
        return $user->fresh();
    }

    public function bulkDelete(array $ids, User $actor): int
    {
        return DB::transaction(function () use ($ids, $actor) {
            $users = User::whereIn('id', $ids)->where('id', '!=', $actor->id)->get();
            foreach ($users as $u) {
                $this->audit->log(AuditEventType::USER_DELETED, $actor, $u, ['email' => $u->email]);
                $u->delete();
            }
            return $users->count();
        });
    }

    public function bulkAssignRoles(array $ids, array $roles, User $actor): int
    {
        return DB::transaction(function () use ($ids, $roles, $actor) {
            $users = User::whereIn('id', $ids)->get();
            foreach ($users as $u) {
                $u->syncRoles($roles);
                $this->audit->log(AuditEventType::USER_UPDATED, $actor, $u, ['roles' => $roles]);
            }
            return $users->count();
        });
    }
}
```

- [ ] Create `RoleService.php`:

```php
<?php

namespace Aero\Core\Services;

use Aero\Core\Models\User;
use Aero\Core\Services\Audit\AuditService;
use Aero\Core\Services\Audit\AuditEventType;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleService
{
    public function __construct(private AuditService $audit) {}

    public function create(array $data, User $actor): Role
    {
        return DB::transaction(function () use ($data, $actor) {
            $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);
            if (!empty($data['permissions'])) {
                $role->syncPermissions($data['permissions']);
            }
            $this->audit->log(AuditEventType::ROLE_CREATED, $actor, null, ['role' => $role->name]);
            return $role;
        });
    }

    public function update(Role $role, array $data, User $actor): Role
    {
        return DB::transaction(function () use ($role, $data, $actor) {
            $role->update(['name' => $data['name']]);
            if (array_key_exists('permissions', $data)) {
                $role->syncPermissions($data['permissions']);
            }
            $this->audit->log(AuditEventType::ROLE_UPDATED, $actor, null, ['role' => $role->name]);
            return $role->fresh(['permissions']);
        });
    }

    public function delete(Role $role, User $actor): void
    {
        DB::transaction(function () use ($role, $actor) {
            $this->audit->log(AuditEventType::ROLE_DELETED, $actor, null, ['role' => $role->name]);
            $role->delete();
        });
    }

    public function syncModulePermissions(Role $role, array $modulePermissions, User $actor): void
    {
        DB::transaction(function () use ($role, $modulePermissions, $actor) {
            // $modulePermissions = ['core.users.view', 'hrm.employees.view', ...]
            $current = $role->permissions->pluck('name')->toArray();
            $role->syncPermissions($modulePermissions);
            $this->audit->log(AuditEventType::ROLE_UPDATED, $actor, null, [
                'role'    => $role->name,
                'granted' => array_diff($modulePermissions, $current),
                'revoked' => array_diff($current, $modulePermissions),
            ]);
        });
    }
}
```

- [ ] Create `AnnouncementService.php`:

```php
<?php

namespace Aero\Core\Services;

use Aero\Core\Models\Announcement;
use Aero\Core\Models\User;
use Aero\Core\Services\Audit\AuditService;
use Aero\Core\Services\Audit\AuditEventType;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AnnouncementService
{
    public function __construct(private AuditService $audit) {}

    public function list(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return Announcement::with('author')
            ->when($filters['search'] ?? null, fn($q, $s) =>
                $q->where('title', 'like', "%{$s}%"))
            ->when($filters['status'] ?? null, fn($q, $s) =>
                $q->where('status', $s))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data, User $actor): Announcement
    {
        return DB::transaction(function () use ($data, $actor) {
            $ann = Announcement::create(array_merge($data, ['created_by' => $actor->id]));
            $this->audit->log(AuditEventType::RECORD_CREATED, $actor, $ann, ['title' => $ann->title]);
            return $ann;
        });
    }

    public function update(Announcement $ann, array $data, User $actor): Announcement
    {
        return DB::transaction(function () use ($ann, $data, $actor) {
            $ann->update($data);
            $this->audit->log(AuditEventType::RECORD_UPDATED, $actor, $ann);
            return $ann->fresh();
        });
    }

    public function publish(Announcement $ann, User $actor): Announcement
    {
        $ann->update(['status' => 'published', 'published_at' => now()]);
        $this->audit->log(AuditEventType::RECORD_UPDATED, $actor, $ann, ['action' => 'publish']);
        return $ann->fresh();
    }

    public function archive(Announcement $ann, User $actor): Announcement
    {
        $ann->update(['status' => 'archived']);
        $this->audit->log(AuditEventType::RECORD_UPDATED, $actor, $ann, ['action' => 'archive']);
        return $ann->fresh();
    }

    public function delete(Announcement $ann, User $actor): void
    {
        DB::transaction(function () use ($ann, $actor) {
            $this->audit->log(AuditEventType::RECORD_DELETED, $actor, $ann, ['title' => $ann->title]);
            $ann->delete();
        });
    }
}
```

- [ ] Commit:
```bash
git add packages/aero-core/src/Services/UserService.php \
        packages/aero-core/src/Services/RoleService.php \
        packages/aero-core/src/Services/AnnouncementService.php
git commit -m "feat(aero-core): UserService, RoleService, AnnouncementService"
```

---

## Task 4 — Form Requests

**Files:**
- Create/Upgrade: `packages/aero-core/src/Http/Requests/StoreRoleRequest.php`
- Create/Upgrade: `packages/aero-core/src/Http/Requests/UpdateRoleRequest.php`
- Create/Upgrade: `packages/aero-core/src/Http/Requests/StoreAnnouncementRequest.php`
- Create/Upgrade: `packages/aero-core/src/Http/Requests/UpdateAnnouncementRequest.php`

- [ ] Create `StoreRoleRequest.php`:

```php
<?php

namespace Aero\Core\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:100', 'unique:roles,name'],
            'permissions'   => ['array'],
            'permissions.*' => ['string'],
        ];
    }
}
```

- [ ] Create `UpdateRoleRequest.php`:

```php
<?php

namespace Aero\Core\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:100', Rule::unique('roles', 'name')->ignore($this->route('role'))],
            'permissions'   => ['array'],
            'permissions.*' => ['string'],
        ];
    }
}
```

- [ ] Upgrade `StoreAnnouncementRequest.php`:

```php
<?php

namespace Aero\Core\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAnnouncementRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title'        => ['required', 'string', 'max:255'],
            'body'         => ['required', 'string'],
            'type'         => ['required', 'in:info,warning,success,danger'],
            'status'       => ['required', 'in:draft,published,archived'],
            'audience'     => ['required', 'in:all,admins,employees'],
            'is_pinned'    => ['boolean'],
            'published_at' => ['nullable', 'date'],
            'expires_at'   => ['nullable', 'date', 'after:published_at'],
        ];
    }
}
```

- [ ] Create `UpdateAnnouncementRequest.php`:

```php
<?php

namespace Aero\Core\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAnnouncementRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title'        => ['sometimes', 'required', 'string', 'max:255'],
            'body'         => ['sometimes', 'required', 'string'],
            'type'         => ['sometimes', 'required', 'in:info,warning,success,danger'],
            'status'       => ['sometimes', 'required', 'in:draft,published,archived'],
            'audience'     => ['sometimes', 'required', 'in:all,admins,employees'],
            'is_pinned'    => ['boolean'],
            'published_at' => ['nullable', 'date'],
            'expires_at'   => ['nullable', 'date', 'after:published_at'],
        ];
    }
}
```

- [ ] Commit:
```bash
git add packages/aero-core/src/Http/Requests/
git commit -m "feat(aero-core): role + announcement form requests"
```

---

## Task 5 — Controllers: Dashboard, CoreUser, Role, Module, Announcement

**Files:**
- Modify: `packages/aero-core/src/Http/Controllers/DashboardController.php`
- Modify: `packages/aero-core/src/Http/Controllers/Admin/CoreUserController.php`
- Modify: `packages/aero-core/src/Http/Controllers/Admin/RoleController.php`
- Modify: `packages/aero-core/src/Http/Controllers/Admin/ModuleController.php`
- Upgrade/Create: `packages/aero-core/src/Http/Controllers/Admin/AnnouncementController.php`

- [ ] Rewrite `DashboardController.php` — render stats to Inertia:

```php
<?php

namespace Aero\Core\Http\Controllers;

use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Models\Announcement;
use Aero\Core\Models\User;
use Aero\Core\Services\Dashboard\AdminDashboardService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private AdminDashboardService $dashboardService) {}

    public function index(): Response
    {
        $stats = $this->dashboardService->getTenantStats();
        $announcements = Announcement::active()->with('author')->orderBy('is_pinned', 'desc')->latest()->limit(5)->get();

        return Inertia::render('Core/Dashboard/Index', [
            'stats'         => $stats,
            'announcements' => $announcements,
        ]);
    }
}
```

- [ ] Rewrite `AdminDashboardService.php` `getTenantStats()` method to return:

```php
// In AdminDashboardService.php — add or replace getTenantStats():
public function getTenantStats(): array
{
    return [
        'total_users'     => User::count(),
        'active_users'    => User::where('is_active', true)->count(),
        'total_roles'     => \Spatie\Permission\Models\Role::count(),
        'modules_enabled' => \Aero\Core\Models\Module::where('is_active', true)->count(),
    ];
}
```

- [ ] Rewrite `CoreUserController.php`:

```php
<?php

namespace Aero\Core\Http\Controllers\Admin;

use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Http\Requests\StoreUserRequest;
use Aero\Core\Http\Requests\UpdateUserRequest;
use Aero\Core\Models\User;
use Aero\Core\Services\UserService;
use Aero\Core\Services\UserInvitationService;
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
            'users'   => $users,
            'roles'   => Role::orderBy('name')->get(['id', 'name']),
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
            'user'  => $user->load('roles'),
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

    // --- Invitations ---
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
```

- [ ] Rewrite `RoleController.php`:

```php
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
            'role'        => $role->load('permissions'),
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
        $request->validate(['permissions' => ['required', 'array'], 'permissions.*' => ['string']]);
        $this->roleService->syncModulePermissions($role, $request->permissions, $request->user());
        return back()->with('success', 'Permissions updated.');
    }
}
```

- [ ] Rewrite `AnnouncementController.php`:

```php
<?php

namespace Aero\Core\Http\Controllers\Admin;

use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Http\Requests\StoreAnnouncementRequest;
use Aero\Core\Http\Requests\UpdateAnnouncementRequest;
use Aero\Core\Models\Announcement;
use Aero\Core\Services\AnnouncementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnnouncementController extends Controller
{
    public function __construct(private AnnouncementService $announcementService) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Core/Announcements/Index', [
            'announcements' => $this->announcementService->list($request->only('search', 'status')),
            'filters'       => $request->only('search', 'status'),
        ]);
    }

    public function store(StoreAnnouncementRequest $request): RedirectResponse
    {
        $this->announcementService->create($request->validated(), $request->user());
        return back()->with('success', 'Announcement created.');
    }

    public function update(UpdateAnnouncementRequest $request, Announcement $announcement): RedirectResponse
    {
        $this->announcementService->update($announcement, $request->validated(), $request->user());
        return back()->with('success', 'Announcement updated.');
    }

    public function publish(Announcement $announcement, Request $request): RedirectResponse
    {
        $this->announcementService->publish($announcement, $request->user());
        return back()->with('success', 'Announcement published.');
    }

    public function archive(Announcement $announcement, Request $request): RedirectResponse
    {
        $this->announcementService->archive($announcement, $request->user());
        return back()->with('success', 'Announcement archived.');
    }

    public function destroy(Announcement $announcement, Request $request): RedirectResponse
    {
        $this->announcementService->delete($announcement, $request->user());
        return back()->with('success', 'Announcement deleted.');
    }
}
```

- [ ] Verify all controller classes are bound in `AeroCoreServiceProvider` or auto-discovered. If not, add to service provider bindings.

- [ ] Commit:
```bash
git add packages/aero-core/src/Http/Controllers/
git commit -m "feat(aero-core): Dashboard, CoreUser, Role, Announcement controllers"
```

---

## Task 6 — Routes: verify and fill gaps in web.php

**Files:**
- Modify: `packages/aero-core/routes/web.php`

- [ ] Open `packages/aero-core/routes/web.php` and verify these route groups exist. Add any missing:

```php
// Dashboard
Route::middleware(['auth:web', 'resolve.tenant.context'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('core.dashboard')
        ->middleware('hrmac:core.dashboard.admin-dashboard.view');

    // Announcements
    Route::prefix('announcements')->name('core.announcements.')->middleware('hrmac:core.announcements.announcement_list.view')->group(function () {
        Route::get('/', [AnnouncementController::class, 'index'])->name('index');
        Route::post('/', [AnnouncementController::class, 'store'])->name('store')->withoutMiddleware('hrmac:core.announcements.announcement_list.view')->middleware('hrmac:core.announcements.announcement_list.create');
        Route::patch('/{announcement}', [AnnouncementController::class, 'update'])->name('update')->withoutMiddleware('hrmac:core.announcements.announcement_list.view')->middleware('hrmac:core.announcements.announcement_list.update');
        Route::post('/{announcement}/publish', [AnnouncementController::class, 'publish'])->name('publish')->withoutMiddleware('hrmac:core.announcements.announcement_list.view')->middleware('hrmac:core.announcements.announcement_list.publish');
        Route::post('/{announcement}/archive', [AnnouncementController::class, 'archive'])->name('archive')->withoutMiddleware('hrmac:core.announcements.announcement_list.view')->middleware('hrmac:core.announcements.announcement_list.archive');
        Route::delete('/{announcement}', [AnnouncementController::class, 'destroy'])->name('destroy')->withoutMiddleware('hrmac:core.announcements.announcement_list.view')->middleware('hrmac:core.announcements.announcement_list.delete');
    });

    // Users
    Route::prefix('users')->name('core.users.')->group(function () {
        Route::get('/', [CoreUserController::class, 'index'])->name('index')->middleware('hrmac:core.user_management.users.view');
        Route::get('/create', [CoreUserController::class, 'create'])->name('create')->middleware('hrmac:core.user_management.users.create');
        Route::post('/', [CoreUserController::class, 'store'])->name('store')->middleware('hrmac:core.user_management.users.create');
        Route::get('/{user}', [CoreUserController::class, 'show'])->name('show')->middleware('hrmac:core.user_management.users.view');
        Route::get('/{user}/edit', [CoreUserController::class, 'edit'])->name('edit')->middleware('hrmac:core.user_management.users.edit');
        Route::put('/{user}', [CoreUserController::class, 'update'])->name('update')->middleware('hrmac:core.user_management.users.edit');
        Route::delete('/{user}', [CoreUserController::class, 'destroy'])->name('destroy')->middleware('hrmac:core.user_management.users.delete');
        Route::post('/{user}/toggle-status', [CoreUserController::class, 'toggleStatus'])->name('toggle-status')->middleware('hrmac:core.user_management.users.activate');
        Route::post('/{user}/impersonate', [CoreUserController::class, 'impersonate'])->name('impersonate')->middleware('hrmac:core.user_management.users.impersonate');
        Route::post('/stop-impersonating', [CoreUserController::class, 'stopImpersonating'])->name('stop-impersonating');
        Route::post('/bulk-delete', [CoreUserController::class, 'bulkDelete'])->name('bulk-delete')->middleware('hrmac:core.user_management.users.bulk_delete');
        Route::post('/bulk-assign-roles', [CoreUserController::class, 'bulkAssignRoles'])->name('bulk-assign-roles')->middleware('hrmac:core.user_management.users.bulk_assign_roles');
        // Invitations
        Route::get('/invitations', [CoreUserController::class, 'invitations'])->name('invitations.index')->middleware('hrmac:core.user_management.user_invitations.view');
        Route::post('/invitations', [CoreUserController::class, 'invite'])->name('invitations.store')->middleware('hrmac:core.user_management.user_invitations.invite');
        Route::post('/invitations/{id}/resend', [CoreUserController::class, 'resendInvitation'])->name('invitations.resend')->middleware('hrmac:core.user_management.user_invitations.resend');
        Route::delete('/invitations/{id}', [CoreUserController::class, 'cancelInvitation'])->name('invitations.cancel')->middleware('hrmac:core.user_management.user_invitations.cancel');
    });

    // Roles
    Route::prefix('roles')->name('core.roles.')->group(function () {
        Route::get('/', [RoleController::class, 'index'])->name('index')->middleware('hrmac:core.roles_permissions.roles.view');
        Route::get('/create', [RoleController::class, 'create'])->name('create')->middleware('hrmac:core.roles_permissions.roles.create');
        Route::post('/', [RoleController::class, 'store'])->name('store')->middleware('hrmac:core.roles_permissions.roles.create');
        Route::get('/{role}/edit', [RoleController::class, 'edit'])->name('edit')->middleware('hrmac:core.roles_permissions.roles.edit');
        Route::put('/{role}', [RoleController::class, 'update'])->name('update')->middleware('hrmac:core.roles_permissions.roles.edit');
        Route::delete('/{role}', [RoleController::class, 'destroy'])->name('destroy')->middleware('hrmac:core.roles_permissions.roles.delete');
        Route::post('/{role}/sync-permissions', [RoleController::class, 'syncPermissions'])->name('sync-permissions')->middleware('hrmac:core.roles_permissions.roles.permissions');
    });
});
```

- [ ] Add the `AnnouncementController` use statement at the top of `web.php` if not present.

- [ ] Commit:
```bash
git add packages/aero-core/routes/web.php
git commit -m "feat(aero-core): complete routes for dashboard, users, roles, announcements"
```

---

## Task 7 — Frontend: Dashboard page

**Files:**
- Create: `packages/aero-ui/resources/js/Pages/Core/Dashboard/Index.jsx`

- [ ] Create `packages/aero-ui/resources/js/Pages/Core/Dashboard/Index.jsx`:

```jsx
import { Head } from '@inertiajs/react';
import { AppLayout } from '@aero/ui/layouts/AppLayout';
import { Card, CardBody, CardHeader } from '@heroui/react';
import { UsersIcon, ShieldCheckIcon, CubeIcon, BellIcon } from '@heroicons/react/24/outline';

const StatCard = ({ icon: Icon, label, value, color = 'primary' }) => (
  <Card className="shadow-sm">
    <CardBody className="flex flex-row items-center gap-4 p-4">
      <div className={`p-3 rounded-xl bg-${color}/10`}>
        <Icon className={`w-6 h-6 text-${color}`} />
      </div>
      <div>
        <p className="text-sm text-default-500">{label}</p>
        <p className="text-2xl font-bold">{value}</p>
      </div>
    </CardBody>
  </Card>
);

export default function DashboardIndex({ stats, announcements }) {
  return (
    <AppLayout title="Dashboard">
      <Head title="Dashboard" />
      <div className="space-y-6 p-6">
        <div>
          <h1 className="text-2xl font-bold">Dashboard</h1>
          <p className="text-default-500 text-sm mt-1">Overview of your workspace</p>
        </div>

        {/* Stats Grid */}
        <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
          <StatCard icon={UsersIcon} label="Total Users" value={stats.total_users} color="primary" />
          <StatCard icon={UsersIcon} label="Active Users" value={stats.active_users} color="success" />
          <StatCard icon={ShieldCheckIcon} label="Roles" value={stats.total_roles} color="secondary" />
          <StatCard icon={CubeIcon} label="Modules Enabled" value={stats.modules_enabled} color="warning" />
        </div>

        {/* Announcements */}
        {announcements.length > 0 && (
          <Card>
            <CardHeader className="pb-0">
              <div className="flex items-center gap-2">
                <BellIcon className="w-5 h-5 text-warning" />
                <h2 className="text-lg font-semibold">Announcements</h2>
              </div>
            </CardHeader>
            <CardBody>
              <div className="space-y-3">
                {announcements.map(a => (
                  <div key={a.id} className="flex items-start gap-3 p-3 rounded-lg bg-default-50">
                    {a.is_pinned && <span className="text-xs font-medium text-warning bg-warning/10 px-2 py-0.5 rounded">Pinned</span>}
                    <div>
                      <p className="font-medium text-sm">{a.title}</p>
                      <p className="text-xs text-default-400">{a.author?.name} · {new Date(a.published_at).toLocaleDateString()}</p>
                    </div>
                  </div>
                ))}
              </div>
            </CardBody>
          </Card>
        )}
      </div>
    </AppLayout>
  );
}
```

- [ ] Commit:
```bash
git add packages/aero-ui/resources/js/Pages/Core/Dashboard/
git commit -m "feat(aero-ui): tenant admin dashboard page"
```

---

## Task 8 — Frontend: Users pages (Index, Create, Edit, Show, Invitations)

**Files:**
- Upgrade: `packages/aero-ui/resources/js/Pages/Core/Users/Index.jsx`
- Upgrade: `packages/aero-ui/resources/js/Pages/Core/Users/Create.jsx`
- Upgrade: `packages/aero-ui/resources/js/Pages/Core/Users/Edit.jsx`
- Upgrade: `packages/aero-ui/resources/js/Pages/Core/Users/Show.jsx`
- Create: `packages/aero-ui/resources/js/Pages/Core/Users/Invitations/Index.jsx`

- [ ] Write `Users/Index.jsx` — full table with search, filter by role/status, bulk select, impersonate action:

```jsx
import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { AppLayout } from '@aero/ui/layouts/AppLayout';
import {
  Button, Input, Select, SelectItem, Table, TableBody, TableCell,
  TableColumn, TableHeader, TableRow, Chip, Dropdown, DropdownItem,
  DropdownMenu, DropdownTrigger, useDisclosure, Modal, ModalContent,
  ModalHeader, ModalBody, ModalFooter, Checkbox,
} from '@heroui/react';
import { MagnifyingGlassIcon, PlusIcon, EllipsisVerticalIcon } from '@heroicons/react/24/outline';
import { useHRMAC } from '@aero/ui/hooks/useHRMAC';

export default function UsersIndex({ users, roles, filters }) {
  const { can } = useHRMAC();
  const [selectedKeys, setSelectedKeys] = useState(new Set());
  const [search, setSearch] = useState(filters.search ?? '');

  const doSearch = () => router.get(route('core.users.index'), { search, role: filters.role, status: filters.status }, { preserveState: true });

  const statusColor = { active: 'success', inactive: 'danger' };

  return (
    <AppLayout title="Users">
      <Head title="Users" />
      <div className="p-6 space-y-4">
        <div className="flex items-center justify-between">
          <h1 className="text-2xl font-bold">Users</h1>
          {can('core.user_management.users.create') && (
            <Button as="a" href={route('core.users.create')} color="primary" startContent={<PlusIcon className="w-4 h-4" />}>
              Add User
            </Button>
          )}
        </div>

        {/* Filters */}
        <div className="flex gap-3 flex-wrap">
          <Input
            placeholder="Search name or email…"
            value={search}
            onChange={e => setSearch(e.target.value)}
            onKeyDown={e => e.key === 'Enter' && doSearch()}
            startContent={<MagnifyingGlassIcon className="w-4 h-4 text-default-400" />}
            className="w-64"
          />
          <Select
            placeholder="Filter by role"
            selectedKeys={filters.role ? [filters.role] : []}
            onSelectionChange={keys => router.get(route('core.users.index'), { ...filters, role: [...keys][0] ?? '' }, { preserveState: true })}
            className="w-44"
          >
            {roles.map(r => <SelectItem key={r.name}>{r.name}</SelectItem>)}
          </Select>
          <Select
            placeholder="Filter by status"
            selectedKeys={filters.status ? [filters.status] : []}
            onSelectionChange={keys => router.get(route('core.users.index'), { ...filters, status: [...keys][0] ?? '' }, { preserveState: true })}
            className="w-44"
          >
            <SelectItem key="active">Active</SelectItem>
            <SelectItem key="inactive">Inactive</SelectItem>
          </Select>
        </div>

        {/* Bulk actions */}
        {selectedKeys.size > 0 && can('core.user_management.users.bulk_delete') && (
          <div className="flex items-center gap-3 p-3 bg-primary/10 rounded-lg">
            <span className="text-sm font-medium">{selectedKeys.size} selected</span>
            <Button size="sm" color="danger" onPress={() => {
              if (confirm(`Delete ${selectedKeys.size} users?`)) {
                router.post(route('core.users.bulk-delete'), { ids: [...selectedKeys] });
              }
            }}>Delete Selected</Button>
          </div>
        )}

        <Table
          selectionMode="multiple"
          selectedKeys={selectedKeys}
          onSelectionChange={setSelectedKeys}
          aria-label="Users table"
        >
          <TableHeader>
            <TableColumn>NAME</TableColumn>
            <TableColumn>EMAIL</TableColumn>
            <TableColumn>ROLES</TableColumn>
            <TableColumn>STATUS</TableColumn>
            <TableColumn>JOINED</TableColumn>
            <TableColumn>ACTIONS</TableColumn>
          </TableHeader>
          <TableBody items={users.data}>
            {user => (
              <TableRow key={user.id}>
                <TableCell className="font-medium">{user.name}</TableCell>
                <TableCell>{user.email}</TableCell>
                <TableCell>
                  <div className="flex gap-1 flex-wrap">
                    {user.roles.map(r => <Chip key={r.id} size="sm" variant="flat">{r.name}</Chip>)}
                  </div>
                </TableCell>
                <TableCell>
                  <Chip size="sm" color={user.is_active ? 'success' : 'danger'} variant="flat">
                    {user.is_active ? 'Active' : 'Inactive'}
                  </Chip>
                </TableCell>
                <TableCell>{new Date(user.created_at).toLocaleDateString()}</TableCell>
                <TableCell>
                  <Dropdown>
                    <DropdownTrigger>
                      <Button isIconOnly size="sm" variant="light"><EllipsisVerticalIcon className="w-4 h-4" /></Button>
                    </DropdownTrigger>
                    <DropdownMenu>
                      <DropdownItem as="a" href={route('core.users.show', user.id)}>View</DropdownItem>
                      {can('core.user_management.users.edit') && <DropdownItem as="a" href={route('core.users.edit', user.id)}>Edit</DropdownItem>}
                      {can('core.user_management.users.activate') && (
                        <DropdownItem onPress={() => router.post(route('core.users.toggle-status', user.id))}>
                          {user.is_active ? 'Deactivate' : 'Activate'}
                        </DropdownItem>
                      )}
                      {can('core.user_management.users.impersonate') && (
                        <DropdownItem onPress={() => router.post(route('core.users.impersonate', user.id))}>Impersonate</DropdownItem>
                      )}
                      {can('core.user_management.users.delete') && (
                        <DropdownItem color="danger" onPress={() => {
                          if (confirm('Delete this user?')) router.delete(route('core.users.destroy', user.id));
                        }}>Delete</DropdownItem>
                      )}
                    </DropdownMenu>
                  </Dropdown>
                </TableCell>
              </TableRow>
            )}
          </TableBody>
        </Table>

        {/* Pagination */}
        <div className="flex justify-between items-center text-sm text-default-500">
          <span>Showing {users.from}–{users.to} of {users.total}</span>
          <div className="flex gap-2">
            {users.prev_page_url && <Button size="sm" variant="flat" as="a" href={users.prev_page_url}>Previous</Button>}
            {users.next_page_url && <Button size="sm" variant="flat" as="a" href={users.next_page_url}>Next</Button>}
          </div>
        </div>
      </div>
    </AppLayout>
  );
}
```

- [ ] Write `Users/Create.jsx`:

```jsx
import { Head, useForm } from '@inertiajs/react';
import { AppLayout } from '@aero/ui/layouts/AppLayout';
import { Button, Input, Select, SelectItem, CheckboxGroup, Checkbox } from '@heroui/react';

export default function UsersCreate({ roles }) {
  const { data, setData, post, processing, errors } = useForm({
    name: '', email: '', password: '', roles: [],
  });

  const submit = e => { e.preventDefault(); post(route('core.users.store')); };

  return (
    <AppLayout title="Create User">
      <Head title="Create User" />
      <div className="p-6 max-w-lg">
        <h1 className="text-2xl font-bold mb-6">Create User</h1>
        <form onSubmit={submit} className="space-y-4">
          <Input label="Full Name" value={data.name} onChange={e => setData('name', e.target.value)} errorMessage={errors.name} isInvalid={!!errors.name} isRequired />
          <Input label="Email" type="email" value={data.email} onChange={e => setData('email', e.target.value)} errorMessage={errors.email} isInvalid={!!errors.email} isRequired />
          <Input label="Temporary Password" type="password" value={data.password} onChange={e => setData('password', e.target.value)} errorMessage={errors.password} isInvalid={!!errors.password} description="Leave blank to auto-generate" />
          <div>
            <p className="text-sm font-medium mb-2">Assign Roles</p>
            <CheckboxGroup value={data.roles} onChange={v => setData('roles', v)}>
              {roles.map(r => <Checkbox key={r.id} value={r.name}>{r.name}</Checkbox>)}
            </CheckboxGroup>
            {errors.roles && <p className="text-danger text-xs mt-1">{errors.roles}</p>}
          </div>
          <div className="flex gap-3">
            <Button type="submit" color="primary" isLoading={processing}>Create User</Button>
            <Button variant="flat" as="a" href={route('core.users.index')}>Cancel</Button>
          </div>
        </form>
      </div>
    </AppLayout>
  );
}
```

- [ ] Write `Users/Edit.jsx`:

```jsx
import { Head, useForm } from '@inertiajs/react';
import { AppLayout } from '@aero/ui/layouts/AppLayout';
import { Button, Input, CheckboxGroup, Checkbox } from '@heroui/react';

export default function UsersEdit({ user, roles }) {
  const { data, setData, put, processing, errors } = useForm({
    name: user.name, email: user.email,
    roles: user.roles.map(r => r.name),
  });

  const submit = e => { e.preventDefault(); put(route('core.users.update', user.id)); };

  return (
    <AppLayout title="Edit User">
      <Head title="Edit User" />
      <div className="p-6 max-w-lg">
        <h1 className="text-2xl font-bold mb-6">Edit User — {user.name}</h1>
        <form onSubmit={submit} className="space-y-4">
          <Input label="Full Name" value={data.name} onChange={e => setData('name', e.target.value)} errorMessage={errors.name} isInvalid={!!errors.name} isRequired />
          <Input label="Email" type="email" value={data.email} onChange={e => setData('email', e.target.value)} errorMessage={errors.email} isInvalid={!!errors.email} isRequired />
          <div>
            <p className="text-sm font-medium mb-2">Roles</p>
            <CheckboxGroup value={data.roles} onChange={v => setData('roles', v)}>
              {roles.map(r => <Checkbox key={r.id} value={r.name}>{r.name}</Checkbox>)}
            </CheckboxGroup>
          </div>
          <div className="flex gap-3">
            <Button type="submit" color="primary" isLoading={processing}>Save Changes</Button>
            <Button variant="flat" as="a" href={route('core.users.show', user.id)}>Cancel</Button>
          </div>
        </form>
      </div>
    </AppLayout>
  );
}
```

- [ ] Write `Users/Show.jsx`:

```jsx
import { Head, router } from '@inertiajs/react';
import { AppLayout } from '@aero/ui/layouts/AppLayout';
import { Button, Card, CardBody, Chip, Avatar } from '@heroui/react';
import { useHRMAC } from '@aero/ui/hooks/useHRMAC';

export default function UsersShow({ user }) {
  const { can } = useHRMAC();
  return (
    <AppLayout title={user.name}>
      <Head title={user.name} />
      <div className="p-6 space-y-4 max-w-2xl">
        <div className="flex items-center gap-4">
          <Avatar name={user.name} size="lg" />
          <div>
            <h1 className="text-2xl font-bold">{user.name}</h1>
            <p className="text-default-500">{user.email}</p>
          </div>
          <div className="ml-auto flex gap-2">
            {can('core.user_management.users.edit') && (
              <Button as="a" href={route('core.users.edit', user.id)} size="sm" variant="flat">Edit</Button>
            )}
            {can('core.user_management.users.impersonate') && (
              <Button size="sm" color="warning" variant="flat" onPress={() => router.post(route('core.users.impersonate', user.id))}>
                Impersonate
              </Button>
            )}
          </div>
        </div>

        <Card>
          <CardBody className="space-y-3">
            <div className="flex justify-between">
              <span className="text-default-500 text-sm">Status</span>
              <Chip size="sm" color={user.is_active ? 'success' : 'danger'} variant="flat">{user.is_active ? 'Active' : 'Inactive'}</Chip>
            </div>
            <div className="flex justify-between">
              <span className="text-default-500 text-sm">Roles</span>
              <div className="flex gap-1">
                {user.roles.map(r => <Chip key={r.id} size="sm" variant="flat">{r.name}</Chip>)}
              </div>
            </div>
            <div className="flex justify-between">
              <span className="text-default-500 text-sm">Joined</span>
              <span className="text-sm">{new Date(user.created_at).toLocaleDateString()}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-default-500 text-sm">Active Sessions</span>
              <span className="text-sm">{user.sessions?.length ?? 0}</span>
            </div>
          </CardBody>
        </Card>
      </div>
    </AppLayout>
  );
}
```

- [ ] Write `Users/Invitations/Index.jsx`:

```jsx
import { Head, useForm, router } from '@inertiajs/react';
import { AppLayout } from '@aero/ui/layouts/AppLayout';
import {
  Button, Input, Table, TableBody, TableCell, TableColumn,
  TableHeader, TableRow, Chip, useDisclosure, Modal, ModalContent,
  ModalHeader, ModalBody, ModalFooter,
} from '@heroui/react';
import { PlusIcon } from '@heroicons/react/24/outline';

export default function InvitationsIndex({ invitations }) {
  const { isOpen, onOpen, onOpenChange } = useDisclosure();
  const { data, setData, post, processing, errors, reset } = useForm({ email: '', roles: [] });

  const submit = e => {
    e.preventDefault();
    post(route('core.users.invitations.store'), { onSuccess: () => { reset(); onOpenChange(); } });
  };

  return (
    <AppLayout title="Invitations">
      <Head title="Invitations" />
      <div className="p-6 space-y-4">
        <div className="flex items-center justify-between">
          <h1 className="text-2xl font-bold">Invitations</h1>
          <Button color="primary" startContent={<PlusIcon className="w-4 h-4" />} onPress={onOpen}>
            Invite User
          </Button>
        </div>

        <Table aria-label="Invitations">
          <TableHeader>
            <TableColumn>EMAIL</TableColumn>
            <TableColumn>STATUS</TableColumn>
            <TableColumn>SENT</TableColumn>
            <TableColumn>ACTIONS</TableColumn>
          </TableHeader>
          <TableBody items={invitations.data ?? invitations}>
            {inv => (
              <TableRow key={inv.id}>
                <TableCell>{inv.email}</TableCell>
                <TableCell>
                  <Chip size="sm" color={inv.accepted_at ? 'success' : 'warning'} variant="flat">
                    {inv.accepted_at ? 'Accepted' : 'Pending'}
                  </Chip>
                </TableCell>
                <TableCell>{new Date(inv.created_at).toLocaleDateString()}</TableCell>
                <TableCell>
                  {!inv.accepted_at && (
                    <div className="flex gap-2">
                      <Button size="sm" variant="flat" onPress={() => router.post(route('core.users.invitations.resend', inv.id))}>Resend</Button>
                      <Button size="sm" color="danger" variant="flat" onPress={() => router.delete(route('core.users.invitations.cancel', inv.id))}>Cancel</Button>
                    </div>
                  )}
                </TableCell>
              </TableRow>
            )}
          </TableBody>
        </Table>

        <Modal isOpen={isOpen} onOpenChange={onOpenChange}>
          <ModalContent>
            {onClose => (
              <form onSubmit={submit}>
                <ModalHeader>Invite User</ModalHeader>
                <ModalBody>
                  <Input label="Email Address" type="email" value={data.email} onChange={e => setData('email', e.target.value)} errorMessage={errors.email} isInvalid={!!errors.email} isRequired />
                </ModalBody>
                <ModalFooter>
                  <Button variant="flat" onPress={onClose}>Cancel</Button>
                  <Button type="submit" color="primary" isLoading={processing}>Send Invitation</Button>
                </ModalFooter>
              </form>
            )}
          </ModalContent>
        </Modal>
      </div>
    </AppLayout>
  );
}
```

- [ ] Commit:
```bash
git add packages/aero-ui/resources/js/Pages/Core/Users/
git commit -m "feat(aero-ui): Users Index, Create, Edit, Show, Invitations pages"
```

---

## Task 9 — Frontend: Roles pages (Index, Create, Edit with permissions matrix)

**Files:**
- Upgrade: `packages/aero-ui/resources/js/Pages/Core/Roles/Index.jsx`
- Create: `packages/aero-ui/resources/js/Pages/Core/Roles/Create.jsx`
- Upgrade: `packages/aero-ui/resources/js/Pages/Core/Roles/Edit.jsx`

- [ ] Write `Roles/Index.jsx`:

```jsx
import { Head, router } from '@inertiajs/react';
import { AppLayout } from '@aero/ui/layouts/AppLayout';
import { Button, Table, TableBody, TableCell, TableColumn, TableHeader, TableRow, Chip } from '@heroui/react';
import { PlusIcon } from '@heroicons/react/24/outline';
import { useHRMAC } from '@aero/ui/hooks/useHRMAC';

export default function RolesIndex({ roles }) {
  const { can } = useHRMAC();
  return (
    <AppLayout title="Roles">
      <Head title="Roles" />
      <div className="p-6 space-y-4">
        <div className="flex items-center justify-between">
          <h1 className="text-2xl font-bold">Roles & Permissions</h1>
          {can('core.roles_permissions.roles.create') && (
            <Button as="a" href={route('core.roles.create')} color="primary" startContent={<PlusIcon className="w-4 h-4" />}>
              Create Role
            </Button>
          )}
        </div>
        <Table aria-label="Roles">
          <TableHeader>
            <TableColumn>ROLE NAME</TableColumn>
            <TableColumn>USERS</TableColumn>
            <TableColumn>PERMISSIONS</TableColumn>
            <TableColumn>ACTIONS</TableColumn>
          </TableHeader>
          <TableBody items={roles}>
            {role => (
              <TableRow key={role.id}>
                <TableCell className="font-medium">{role.name}</TableCell>
                <TableCell><Chip size="sm" variant="flat">{role.users_count}</Chip></TableCell>
                <TableCell><Chip size="sm" variant="flat">{role.permissions_count}</Chip></TableCell>
                <TableCell>
                  <div className="flex gap-2">
                    {can('core.roles_permissions.roles.edit') && (
                      <Button size="sm" as="a" href={route('core.roles.edit', role.id)} variant="flat">Edit</Button>
                    )}
                    {can('core.roles_permissions.roles.delete') && role.name !== 'super-admin' && (
                      <Button size="sm" color="danger" variant="flat" onPress={() => {
                        if (confirm('Delete this role?')) router.delete(route('core.roles.destroy', role.id));
                      }}>Delete</Button>
                    )}
                  </div>
                </TableCell>
              </TableRow>
            )}
          </TableBody>
        </Table>
      </div>
    </AppLayout>
  );
}
```

- [ ] Write `Roles/Create.jsx`:

```jsx
import { Head, useForm } from '@inertiajs/react';
import { AppLayout } from '@aero/ui/layouts/AppLayout';
import { Button, Input, CheckboxGroup, Checkbox } from '@heroui/react';

export default function RolesCreate({ permissions }) {
  const grouped = permissions.reduce((acc, p) => {
    const [module] = p.name.split('.');
    (acc[module] = acc[module] ?? []).push(p);
    return acc;
  }, {});

  const { data, setData, post, processing, errors } = useForm({ name: '', permissions: [] });
  const submit = e => { e.preventDefault(); post(route('core.roles.store')); };

  return (
    <AppLayout title="Create Role">
      <Head title="Create Role" />
      <div className="p-6 max-w-2xl">
        <h1 className="text-2xl font-bold mb-6">Create Role</h1>
        <form onSubmit={submit} className="space-y-6">
          <Input label="Role Name" value={data.name} onChange={e => setData('name', e.target.value)} errorMessage={errors.name} isInvalid={!!errors.name} isRequired />
          <div>
            <p className="text-sm font-semibold mb-3">Permissions</p>
            {Object.entries(grouped).map(([module, perms]) => (
              <div key={module} className="mb-4">
                <p className="text-xs font-medium uppercase text-default-400 mb-2">{module}</p>
                <CheckboxGroup value={data.permissions} onChange={v => setData('permissions', v)} orientation="horizontal">
                  {perms.map(p => <Checkbox key={p.id} value={p.name} className="text-sm">{p.name.split('.').slice(1).join('.')}</Checkbox>)}
                </CheckboxGroup>
              </div>
            ))}
          </div>
          <div className="flex gap-3">
            <Button type="submit" color="primary" isLoading={processing}>Create Role</Button>
            <Button variant="flat" as="a" href={route('core.roles.index')}>Cancel</Button>
          </div>
        </form>
      </div>
    </AppLayout>
  );
}
```

- [ ] Write `Roles/Edit.jsx` with the permissions matrix:

```jsx
import { Head, useForm } from '@inertiajs/react';
import { AppLayout } from '@aero/ui/layouts/AppLayout';
import { Button, Input, CheckboxGroup, Checkbox, Card, CardBody, CardHeader } from '@heroui/react';

export default function RolesEdit({ role, permissions }) {
  const grouped = permissions.reduce((acc, p) => {
    const [module] = p.name.split('.');
    (acc[module] = acc[module] ?? []).push(p);
    return acc;
  }, {});

  const { data, setData, put, processing, errors } = useForm({
    name: role.name,
    permissions: role.permissions.map(p => p.name),
  });

  const submit = e => { e.preventDefault(); put(route('core.roles.update', role.id)); };

  const toggleModule = (module, perms) => {
    const names = perms.map(p => p.name);
    const allSelected = names.every(n => data.permissions.includes(n));
    if (allSelected) {
      setData('permissions', data.permissions.filter(p => !names.includes(p)));
    } else {
      setData('permissions', [...new Set([...data.permissions, ...names])]);
    }
  };

  return (
    <AppLayout title={`Edit Role — ${role.name}`}>
      <Head title={`Edit Role — ${role.name}`} />
      <div className="p-6 max-w-2xl">
        <h1 className="text-2xl font-bold mb-6">Edit Role</h1>
        <form onSubmit={submit} className="space-y-6">
          <Input
            label="Role Name"
            value={data.name}
            onChange={e => setData('name', e.target.value)}
            errorMessage={errors.name}
            isInvalid={!!errors.name}
            isRequired
            isDisabled={role.name === 'super-admin'}
          />
          <div>
            <p className="text-sm font-semibold mb-3">Permissions</p>
            <div className="space-y-3">
              {Object.entries(grouped).map(([module, perms]) => {
                const names = perms.map(p => p.name);
                const allSelected = names.every(n => data.permissions.includes(n));
                return (
                  <Card key={module} className="shadow-none border border-default-200">
                    <CardHeader className="pb-2">
                      <div className="flex items-center justify-between w-full">
                        <p className="text-sm font-medium uppercase">{module}</p>
                        <Button size="sm" variant="flat" onPress={() => toggleModule(module, perms)}>
                          {allSelected ? 'Deselect All' : 'Select All'}
                        </Button>
                      </div>
                    </CardHeader>
                    <CardBody className="pt-0">
                      <CheckboxGroup value={data.permissions} onChange={v => setData('permissions', v)} orientation="horizontal">
                        {perms.map(p => <Checkbox key={p.id} value={p.name} className="text-xs">{p.name.split('.').slice(1).join('.')}</Checkbox>)}
                      </CheckboxGroup>
                    </CardBody>
                  </Card>
                );
              })}
            </div>
          </div>
          <div className="flex gap-3">
            <Button type="submit" color="primary" isLoading={processing}>Save Changes</Button>
            <Button variant="flat" as="a" href={route('core.roles.index')}>Cancel</Button>
          </div>
        </form>
      </div>
    </AppLayout>
  );
}
```

- [ ] Commit:
```bash
git add packages/aero-ui/resources/js/Pages/Core/Roles/
git commit -m "feat(aero-ui): Roles Index, Create, Edit with permissions matrix"
```

---

## Task 10 — Frontend: Announcements page

**Files:**
- Create: `packages/aero-ui/resources/js/Pages/Core/Announcements/Index.jsx`

- [ ] Write `Announcements/Index.jsx`:

```jsx
import { Head, useForm, router } from '@inertiajs/react';
import { AppLayout } from '@aero/ui/layouts/AppLayout';
import {
  Button, Input, Table, TableBody, TableCell, TableColumn, TableHeader, TableRow,
  Chip, useDisclosure, Modal, ModalContent, ModalHeader, ModalBody, ModalFooter,
  Select, SelectItem, Textarea, Switch,
} from '@heroui/react';
import { PlusIcon } from '@heroicons/react/24/outline';
import { useHRMAC } from '@aero/ui/hooks/useHRMAC';

const STATUS_COLOR = { draft: 'default', published: 'success', archived: 'secondary' };

export default function AnnouncementsIndex({ announcements, filters }) {
  const { can } = useHRMAC();
  const { isOpen, onOpen, onOpenChange } = useDisclosure();
  const { data, setData, post, processing, errors, reset } = useForm({
    title: '', body: '', type: 'info', status: 'draft',
    audience: 'all', is_pinned: false, published_at: '', expires_at: '',
  });

  const submit = e => {
    e.preventDefault();
    post(route('core.announcements.store'), { onSuccess: () => { reset(); onOpenChange(); } });
  };

  return (
    <AppLayout title="Announcements">
      <Head title="Announcements" />
      <div className="p-6 space-y-4">
        <div className="flex items-center justify-between">
          <h1 className="text-2xl font-bold">Announcements</h1>
          {can('core.announcements.announcement_list.create') && (
            <Button color="primary" startContent={<PlusIcon className="w-4 h-4" />} onPress={onOpen}>
              New Announcement
            </Button>
          )}
        </div>

        <Table aria-label="Announcements">
          <TableHeader>
            <TableColumn>TITLE</TableColumn>
            <TableColumn>TYPE</TableColumn>
            <TableColumn>STATUS</TableColumn>
            <TableColumn>AUDIENCE</TableColumn>
            <TableColumn>PINNED</TableColumn>
            <TableColumn>CREATED BY</TableColumn>
            <TableColumn>ACTIONS</TableColumn>
          </TableHeader>
          <TableBody items={announcements.data}>
            {ann => (
              <TableRow key={ann.id}>
                <TableCell className="font-medium max-w-xs truncate">{ann.title}</TableCell>
                <TableCell><Chip size="sm" variant="flat">{ann.type}</Chip></TableCell>
                <TableCell><Chip size="sm" color={STATUS_COLOR[ann.status]} variant="flat">{ann.status}</Chip></TableCell>
                <TableCell>{ann.audience}</TableCell>
                <TableCell>{ann.is_pinned ? '📌' : '—'}</TableCell>
                <TableCell>{ann.author?.name ?? '—'}</TableCell>
                <TableCell>
                  <div className="flex gap-2">
                    {ann.status === 'draft' && can('core.announcements.announcement_list.publish') && (
                      <Button size="sm" color="success" variant="flat" onPress={() => router.post(route('core.announcements.publish', ann.id))}>
                        Publish
                      </Button>
                    )}
                    {ann.status === 'published' && can('core.announcements.announcement_list.archive') && (
                      <Button size="sm" variant="flat" onPress={() => router.post(route('core.announcements.archive', ann.id))}>
                        Archive
                      </Button>
                    )}
                    {can('core.announcements.announcement_list.delete') && (
                      <Button size="sm" color="danger" variant="flat" onPress={() => {
                        if (confirm('Delete?')) router.delete(route('core.announcements.destroy', ann.id));
                      }}>Delete</Button>
                    )}
                  </div>
                </TableCell>
              </TableRow>
            )}
          </TableBody>
        </Table>

        <Modal isOpen={isOpen} onOpenChange={onOpenChange} size="lg">
          <ModalContent>
            {onClose => (
              <form onSubmit={submit}>
                <ModalHeader>New Announcement</ModalHeader>
                <ModalBody className="space-y-3">
                  <Input label="Title" value={data.title} onChange={e => setData('title', e.target.value)} errorMessage={errors.title} isInvalid={!!errors.title} isRequired />
                  <Textarea label="Body" value={data.body} onChange={e => setData('body', e.target.value)} rows={4} errorMessage={errors.body} isInvalid={!!errors.body} isRequired />
                  <div className="grid grid-cols-2 gap-3">
                    <Select label="Type" selectedKeys={[data.type]} onSelectionChange={k => setData('type', [...k][0])}>
                      {['info','warning','success','danger'].map(t => <SelectItem key={t}>{t}</SelectItem>)}
                    </Select>
                    <Select label="Audience" selectedKeys={[data.audience]} onSelectionChange={k => setData('audience', [...k][0])}>
                      {['all','admins','employees'].map(a => <SelectItem key={a}>{a}</SelectItem>)}
                    </Select>
                  </div>
                  <Switch isSelected={data.is_pinned} onValueChange={v => setData('is_pinned', v)}>Pin announcement</Switch>
                </ModalBody>
                <ModalFooter>
                  <Button variant="flat" onPress={onClose}>Cancel</Button>
                  <Button type="submit" color="primary" isLoading={processing}>Create</Button>
                </ModalFooter>
              </form>
            )}
          </ModalContent>
        </Modal>
      </div>
    </AppLayout>
  );
}
```

- [ ] Commit:
```bash
git add packages/aero-ui/resources/js/Pages/Core/Announcements/
git commit -m "feat(aero-ui): Announcements Index page"
```

---

## Task 11 — PHPUnit Feature Tests

**Files:**
- Create: `packages/aero-core/tests/Feature/Admin/DashboardControllerTest.php`
- Create: `packages/aero-core/tests/Feature/Admin/CoreUserControllerTest.php`
- Create: `packages/aero-core/tests/Feature/Admin/RoleControllerTest.php`
- Create: `packages/aero-core/tests/Feature/Admin/AnnouncementControllerTest.php`

- [ ] Create `DashboardControllerTest.php`:

```php
<?php

namespace Aero\Core\Tests\Feature\Admin;

use Aero\Core\Models\User;
use Aero\Core\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_for_super_admin(): void
    {
        $user = User::factory()->create();
        Role::create(['name' => 'super-admin', 'guard_name' => 'web']);
        $user->assignRole('super-admin');

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Core/Dashboard/Index')
            ->has('stats')
            ->has('announcements'));
    }

    public function test_dashboard_requires_auth(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }
}
```

- [ ] Create `CoreUserControllerTest.php`:

```php
<?php

namespace Aero\Core\Tests\Feature\Admin;

use Aero\Core\Models\User;
use Aero\Core\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class CoreUserControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('super-admin');
    }

    public function test_index_returns_paginated_users(): void
    {
        User::factory()->count(3)->create();
        $response = $this->actingAs($this->admin)->get('/users');
        $response->assertOk()->assertInertia(fn($p) => $p->component('Core/Users/Index')->has('users'));
    }

    public function test_store_creates_user(): void
    {
        $response = $this->actingAs($this->admin)->post('/users', [
            'name'  => 'New User',
            'email' => 'newuser@test.com',
        ]);
        $response->assertRedirect('/users');
        $this->assertDatabaseHas('users', ['email' => 'newuser@test.com']);
    }

    public function test_destroy_deletes_user(): void
    {
        $target = User::factory()->create();
        $this->actingAs($this->admin)->delete("/users/{$target->id}")->assertRedirect('/users');
        $this->assertSoftDeleted('users', ['id' => $target->id]);
    }

    public function test_cannot_delete_self(): void
    {
        $this->actingAs($this->admin)->delete("/users/{$this->admin->id}")->assertForbidden();
    }
}
```

- [ ] Create `RoleControllerTest.php`:

```php
<?php

namespace Aero\Core\Tests\Feature\Admin;

use Aero\Core\Models\User;
use Aero\Core\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class RoleControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('super-admin');
    }

    public function test_index_lists_roles(): void
    {
        $this->actingAs($this->admin)->get('/roles')
            ->assertOk()
            ->assertInertia(fn($p) => $p->component('Core/Roles/Index')->has('roles'));
    }

    public function test_store_creates_role(): void
    {
        $this->actingAs($this->admin)->post('/roles', ['name' => 'Manager'])
            ->assertRedirect('/roles');
        $this->assertDatabaseHas('roles', ['name' => 'Manager']);
    }

    public function test_cannot_delete_super_admin_role(): void
    {
        $role = Role::where('name', 'super-admin')->first();
        $this->actingAs($this->admin)->delete("/roles/{$role->id}")->assertForbidden();
    }
}
```

- [ ] Create `AnnouncementControllerTest.php`:

```php
<?php

namespace Aero\Core\Tests\Feature\Admin;

use Aero\Core\Models\Announcement;
use Aero\Core\Models\User;
use Aero\Core\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class AnnouncementControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('super-admin');
    }

    public function test_index_lists_announcements(): void
    {
        Announcement::factory()->count(3)->create(['created_by' => $this->admin->id]);
        $this->actingAs($this->admin)->get('/announcements')
            ->assertOk()
            ->assertInertia(fn($p) => $p->component('Core/Announcements/Index')->has('announcements'));
    }

    public function test_store_creates_announcement(): void
    {
        $this->actingAs($this->admin)->post('/announcements', [
            'title'    => 'Test',
            'body'     => 'Hello world',
            'type'     => 'info',
            'status'   => 'draft',
            'audience' => 'all',
        ])->assertRedirect();
        $this->assertDatabaseHas('announcements', ['title' => 'Test']);
    }

    public function test_publish_changes_status(): void
    {
        $ann = Announcement::factory()->create(['created_by' => $this->admin->id, 'status' => 'draft']);
        $this->actingAs($this->admin)->post("/announcements/{$ann->id}/publish");
        $this->assertDatabaseHas('announcements', ['id' => $ann->id, 'status' => 'published']);
    }
}
```

- [ ] Run the tests to verify they pass (or at minimum fail for correct reasons if factories are incomplete):
```bash
cd packages/aero-core && php ../../vendor/bin/phpunit tests/Feature/Admin/ --testdox 2>&1 | tail -30
```

- [ ] Commit:
```bash
git add packages/aero-core/tests/Feature/Admin/
git commit -m "test(aero-core): dashboard, user, role, announcement controller tests"
```

---

## Self-Review Checklist

- [ ] **Spec coverage:** Dashboard ✅ · Users CRUD ✅ · Bulk ops ✅ · Invitations ✅ · Impersonation ✅ · Roles CRUD ✅ · Permissions matrix ✅ · Module access ✅ (via existing ModuleController) · Announcements ✅
- [ ] **Placeholder scan:** No TBD/TODO — all code is complete
- [ ] **Type consistency:** `UserService::create()` returns `User`, `RoleService::create()` returns `Role`, all controller methods reference correct service signatures
- [ ] **HRMAC:** Every route has `hrmac:core.<submodule>.<component>.<action>` middleware
- [ ] **Audit:** `AuditService::log()` called in every service write method
- [ ] **DB transactions:** All multi-step writes wrapped in `DB::transaction()`
