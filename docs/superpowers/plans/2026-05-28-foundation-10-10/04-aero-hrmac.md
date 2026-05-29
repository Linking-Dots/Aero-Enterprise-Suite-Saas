# aero-hrmac — Plan to 10/10

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task.

**Current score:** 9/10 (Phase 1 found HRMAC architecture is mature and consistent; this plan closes the remaining 5 known gaps)
**Target score:** 10/10
**Estimated effort:** 2–3 engineer-days

**Goal:** Close the 5 known HRMAC gaps from Phase 1 + this plan's secondary review:

1. **HRMAC denial events not persisted** — `HrmacAuditLog` table and model exist (migration `2026_05_14_000001_create_hrmac_audit_log_table.php`) but `CheckRoleModuleAccess::handle()` only calls `Log::warning()` (line 86) instead of writing to the structured table.
2. **Super-admin role list is unscoped strings** — `config/hrmac.php:31-35` matches role NAMES across both guards (`landlord` + `web`). A tenant role literally named `Super Administrator` would pass the check.
3. **`is_active` module flag is decorative** — `ServiceProvider::boot()` registers routes regardless; `RoleModuleAccessService` doesn't filter by `is_active`.
4. **Discovery `required_fields` mismatch** — `config/hrmac.php:170` lists `['module_key', 'label', 'scope']` but actual `config/module.php` files use `code`, `name`, `scope`. Validation warns on every package.
5. **Single test file** — `tests/Unit/Services/RoleModuleAccessAuditTest.php` is the only test. The access engine that gates the entire platform needs much deeper coverage.

**Architecture:** No structural change. Add `HrmacAuditLog` writes to denial path. Add guard-scoping to super-admin check. Add `is_active` filter to access service. Fix discovery validator. Expand test suite.

**Tech Stack:** Laravel 12 middleware + service binding, Eloquent on tenant DB (`HrmacAuditLog` lives where the role lives — verify).

**Prerequisite:** None — but the changes here unblock the **HRMAC permission-key-mismatch tests** in aero-core ([02-aero-core.md](02-aero-core.md) Task 18) and aero-platform ([03-aero-platform.md](03-aero-platform.md) Task 13).

---

## Reference

- 24 PHP files, 6 migrations, 1 test
- `config/hrmac.php` is the engine config (not a `config/module.php` — hrmac doesn't declare itself as a module)
- Models: `Module`, `SubModule`, `Component`, `Action`, `Role`, `RoleModuleAccess`, `LandlordRoleModuleAccess`, `HrmacAuditLog`
- Middleware: `CheckRoleModuleAccess` (the central engine), `SmartLandingRedirect`
- Service: `RoleModuleAccessService` (the cascading access checker)

## File Structure

| File | Responsibility |
|---|---|
| `packages/aero-hrmac/src/Http/Middleware/CheckRoleModuleAccess.php` | Persist denials to HrmacAuditLog; pass denial through `HrmacAuditService` |
| `packages/aero-hrmac/src/Services/HrmacAuditService.php` (new) | Single entry-point for HRMAC audit writes |
| `packages/aero-hrmac/src/Services/RoleModuleAccessService.php` | Add `is_active` filter on Module/SubModule lookups |
| `packages/aero-hrmac/config/hrmac.php` | Scope super-admin check by guard; fix `required_fields` mismatch; add `enforce_is_active` flag |
| `packages/aero-hrmac/src/Concerns/ChecksHRMAC.php` | Verify it cascades correctly (Phase 1 confirmed cascade works; add a test) |
| `packages/aero-hrmac/src/Console/Commands/SyncModuleHierarchy.php` | Add advisory lock (shared with aero-platform Task 10) |
| `packages/aero-hrmac/tests/Unit/Middleware/CheckRoleModuleAccessTest.php` (new) | Full middleware coverage |
| `packages/aero-hrmac/tests/Unit/Services/HrmacAuditServiceTest.php` (new) | Audit write contract |
| `packages/aero-hrmac/tests/Unit/Services/RoleModuleAccessServiceTest.php` (new) | Cascading checker coverage |
| `packages/aero-hrmac/tests/Feature/SuperAdminGuardScopingTest.php` (new) | Tenant role named "Super Administrator" must NOT pass landlord guard |
| `packages/aero-hrmac/tests/Feature/IsActiveFlagEnforcementTest.php` (new) | Disabled module → 403 |
| `packages/aero-hrmac/tests/Feature/ModuleDiscoveryValidationTest.php` (new) | Required-field validator matches actual config shape |

---

## Task 1: Persist HRMAC denials to `HrmacAuditLog`

**Severity:** High — without this, security observability requires `grep` over log files.

**Files:**
- Create: `packages/aero-hrmac/src/Services/HrmacAuditService.php`
- Modify: `packages/aero-hrmac/src/Http/Middleware/CheckRoleModuleAccess.php:86-94`
- Create: `packages/aero-hrmac/tests/Unit/Services/HrmacAuditServiceTest.php`

- [ ] **Step 1: Write failing test**

```php
<?php

namespace Aero\HRMAC\Tests\Feature;

use Aero\Core\Models\User;
use Aero\HRMAC\Models\HrmacAuditLog;
use Tests\TestCase;

class HrmacDenialAuditTest extends TestCase
{
    public function test_denial_creates_audit_log_row(): void
    {
        $user = User::factory()->create(); // no permissions
        $this->actingAs($user)
            ->get('/hrm/employees') // requires hrmac:hrm.employees
            ->assertForbidden();

        $this->assertDatabaseHas('hrmac_audit_log', [
            'user_id' => $user->id,
            'event' => 'access_denied',
            'module_code' => 'hrm',
            'sub_module_code' => 'employees',
        ]);
    }
}
```

- [ ] **Step 2: Run (FAIL — only Log::warning is called)**

- [ ] **Step 3: Create `HrmacAuditService`**

```php
<?php

namespace Aero\HRMAC\Services;

use Aero\HRMAC\Models\HrmacAuditLog;
use Illuminate\Http\Request;

class HrmacAuditService
{
    public function logDenial(
        Request $request,
        mixed $user,
        string $moduleCode,
        ?string $subModuleCode,
        ?string $componentCode,
        ?string $actionCode,
    ): void {
        HrmacAuditLog::create([
            'user_id' => $user?->id,
            'user_type' => $user ? $user::class : null,
            'event' => 'access_denied',
            'module_code' => $moduleCode,
            'sub_module_code' => $subModuleCode,
            'component_code' => $componentCode,
            'action_code' => $actionCode,
            'ip_address' => $request->ip(),
            'user_agent' => substr($request->userAgent() ?? '', 0, 500),
            'path' => $request->path(),
            'method' => $request->method(),
            'roles' => $user?->roles?->pluck('name')->toArray(),
            'created_at' => now(),
        ]);
    }

    public function logGrant(
        Request $request,
        mixed $user,
        string $moduleCode,
        ?string $subModuleCode = null,
    ): void {
        // Only log grants when explicitly enabled (high volume)
        if (! config('hrmac.logging.log_grants', false)) return;

        HrmacAuditLog::create([
            'user_id' => $user?->id,
            'event' => 'access_granted',
            'module_code' => $moduleCode,
            'sub_module_code' => $subModuleCode,
            'ip_address' => $request->ip(),
            'path' => $request->path(),
            'created_at' => now(),
        ]);
    }
}
```

- [ ] **Step 4: Inject + call from middleware**

```php
// CheckRoleModuleAccess.php
public function __construct(
    protected RoleModuleAccessInterface $roleModuleAccessService,
    protected HrmacAuditService $audit,
) {}

// In handle() after access denied block:
if (! $hasAccess) {
    $this->audit->logDenial($request, $user, $moduleCode, $subModuleCode, $componentCode, $actionCode);
    Log::warning('Role module access denied', [...]);
    return $this->denyAccess($request, $moduleCode, $subModuleCode);
}
```

- [ ] **Step 5: Run test (PASS)**

- [ ] **Step 6: Commit**

```bash
git commit -am "feat(hrmac): persist access denials to HrmacAuditLog (closes Phase 1 gap)"
```

---

## Task 2: Scope super-admin check by guard

**Severity:** High — string match across guards is the brittle escape hatch identified in Phase 1.

**Files:**
- Modify: `packages/aero-hrmac/config/hrmac.php:31-35`
- Modify: `packages/aero-hrmac/src/Http/Middleware/CheckRoleModuleAccess.php:176-199`
- Create: `packages/aero-hrmac/tests/Feature/SuperAdminGuardScopingTest.php`

- [ ] **Step 1: Restructure config to scope by guard**

```php
// config/hrmac.php
'super_admin_roles' => [
    'landlord' => ['Platform Super Administrator', 'platform-super-admin'],
    'web'      => ['Tenant Super Administrator', 'tenant_super_administrator', 'super-admin'],
],
```

- [ ] **Step 2: Write failing test**

```php
public function test_tenant_user_with_role_named_super_administrator_does_not_bypass_landlord_guard(): void
{
    // Tenant DB user (guard=web) with a role literally named 'Super Administrator'
    $user = User::factory()->withRole('Super Administrator', guard: 'web')->create();
    $this->actingAs($user, 'web');

    // Hit a route gated for landlord-scope only
    $response = $this->get('/admin/platform-dashboard');
    $response->assertForbidden();
}

public function test_landlord_super_admin_bypasses_landlord_routes(): void
{
    $admin = LandlordUser::factory()->withRole('Platform Super Administrator', guard: 'landlord')->create();
    $this->actingAs($admin, 'landlord');
    $response = $this->get('/admin/platform-dashboard');
    $response->assertOk();
}
```

- [ ] **Step 3: Run (FAIL — string match crosses guards)**

- [ ] **Step 4: Update `isSuperAdmin` to consider guard**

```php
protected function isSuperAdmin($user): bool
{
    $guard = $this->resolveCurrentGuard();
    $roles = config("hrmac.super_admin_roles.{$guard}", []);

    if (! method_exists($user, 'hasAnyRole')) return false;

    return $user->hasAnyRole($roles);
}

protected function resolveCurrentGuard(): string
{
    foreach (['landlord', 'web', 'api'] as $g) {
        if (Auth::guard($g)->check()) return $g;
    }
    return 'web';
}
```

- [ ] **Step 5: Run test (PASS)**

- [ ] **Step 6: Update default seeders that may reference old role names**

- [ ] **Step 7: Commit**

```bash
git commit -am "fix(hrmac): scope super-admin role check by guard (closes Phase 1 unscoped-string risk)"
```

---

## Task 3: Enforce `is_active` flag in access service

**Severity:** Medium — disabling a module via DB does not actually deny access today.

**Files:**
- Modify: `packages/aero-hrmac/src/Services/RoleModuleAccessService.php`
- Modify: `packages/aero-hrmac/config/hrmac.php` (add flag)
- Create: `packages/aero-hrmac/tests/Feature/IsActiveFlagEnforcementTest.php`

- [ ] **Step 1: Add config flag**

```php
// config/hrmac.php
'enforce_is_active' => env('HRMAC_ENFORCE_IS_ACTIVE', true),
```

- [ ] **Step 2: Write failing test**

```php
public function test_disabled_module_denies_access_even_for_user_with_permission(): void
{
    Module::where('code', 'hrm')->update(['is_active' => false]);
    $user = User::factory()->withPermission('hrm.employees.view')->create();
    $this->actingAs($user);
    $this->get('/hrm/employees')->assertForbidden();
}
```

- [ ] **Step 3: Run (FAIL — module disabled but access granted)**

- [ ] **Step 4: Add is_active filter in service**

```php
public function userCanAccessModule($user, string $moduleCode): bool
{
    if (config('hrmac.enforce_is_active', true)) {
        $module = Module::where('code', $moduleCode)->first();
        if (! $module || ! $module->is_active) return false;
    }
    // ... existing role check
}
```

Apply same pattern to `userCanAccessSubModule` (also check SubModule.is_active if column exists).

- [ ] **Step 5: Run test (PASS)**

- [ ] **Step 6: Commit**

```bash
git commit -am "fix(hrmac): enforce Module.is_active flag in RoleModuleAccessService"
```

---

## Task 4: Fix discovery `required_fields` mismatch

**Severity:** Low — validator warns on every package today; signal is noise.

**Files:**
- Modify: `packages/aero-hrmac/config/hrmac.php:170`
- Modify: `packages/aero-hrmac/src/Services/ModuleDiscoveryService.php` (verify validator)
- Create: `packages/aero-hrmac/tests/Feature/ModuleDiscoveryValidationTest.php`

- [ ] **Step 1: Update `required_fields` to match actual config shape**

```php
'discovery' => [
    'paths' => [
        'vendor/aero/*/config/module.php',
        'modules/*/config/module.php',
        'packages/aero-*/config/module.php', // ← monorepo dev path (composer-path autoload)
    ],
    'validate' => true,
    'required_fields' => ['code', 'name', 'scope'], // ← was 'module_key', 'label', 'scope'
],
```

- [ ] **Step 2: Write test**

```php
public function test_real_package_configs_pass_discovery_validation(): void
{
    $discoverer = app(ModuleDiscoveryService::class);
    $modules = $discoverer->all();
    foreach (['core', 'platform', 'hrm', 'finance', 'crm'] as $expected) {
        $this->assertArrayHasKey($expected, $modules, "Expected module {$expected} not discovered or failed validation");
    }
}
```

- [ ] **Step 3: Run (FAIL or noisy warnings)**

- [ ] **Step 4: PASS + commit**

```bash
git commit -am "fix(hrmac): align discovery required_fields with actual config shape (code/name/scope)"
```

---

## Task 5: Add advisory lock to `SyncModuleHierarchy`

(Shared task with aero-platform Task 10 — done once in this canonical location.)

**Files:**
- Modify: `packages/aero-hrmac/src/Console/Commands/SyncModuleHierarchy.php`

- [ ] **Step 1: Write concurrent execution test**

```php
public function test_concurrent_sync_is_serialized_via_advisory_lock(): void
{
    // First sync acquires lock, second should refuse immediately
}
```

- [ ] **Step 2: Add lock wrapper**

```php
public function handle(): int
{
    $lockKey = 'aero:sync-modules';
    $acquired = DB::selectOne('SELECT GET_LOCK(?, 30) as ok', [$lockKey])->ok;
    if (! $acquired) {
        $this->error('Another modules:sync run is in progress. Aborting.');
        return self::FAILURE;
    }
    try {
        return $this->doSync();
    } finally {
        DB::statement('SELECT RELEASE_LOCK(?)', [$lockKey]);
    }
}
```

- [ ] **Step 3: Commit**

```bash
git commit -am "fix(hrmac): advisory lock on modules:sync (race condition)"
```

---

## Task 6: Expand middleware test coverage

**Files:**
- Create: `packages/aero-hrmac/tests/Unit/Middleware/CheckRoleModuleAccessTest.php`

Branches to cover (per middleware code review):
- Unauthenticated → 401 JSON OR redirect to login
- Super admin (correct guard) → passes
- Dot-notation path: `hrm.employees` → sub-module check
- Dot-notation with one extra: `hrm.employees,view` → action check
- Dot-notation with two extras: `hrm.employees.departments,department-list,create` → component+action
- Legacy comma format: `hrm,employees` → still supported
- Sub-module aliases: `time-off` → `leaves`
- Denial → 403 JSON / Inertia / redirect
- Inertia denial preserves X-Inertia header

- [ ] **Step 1: Write tests** (10+ test methods)

- [ ] **Step 2: Run, fix any latent bugs**

- [ ] **Step 3: Commit**

```bash
git commit -am "test(hrmac): full CheckRoleModuleAccess middleware coverage"
```

---

## Task 7: Expand service test coverage

**Files:**
- Create: `packages/aero-hrmac/tests/Unit/Services/RoleModuleAccessServiceTest.php`

Branches:
- Cascading access resolution (module → sub-module → component → action)
- Inheritance flags (`module_grants_sub_modules`, etc.)
- Cache hit/miss behavior
- `is_active` filtering (covered by Task 3 — link)
- `getFirstAccessibleRoute` returns first matching landing route

- [ ] **Step 1: Write tests**

- [ ] **Step 2: Run, fix gaps**

- [ ] **Step 3: Commit**

```bash
git commit -am "test(hrmac): RoleModuleAccessService unit coverage"
```

---

## Task 8: Expose `HrmacAuditLog` viewer page

**Files:**
- Add: `packages/aero-hrmac/src/Http/Controllers/HrmacAuditController.php`
- Add: route + Inertia page (under `packages/aero-ui/resources/js/Pages/Hrmac/`)
- Add: HRMAC gate `hrmac:core.audit_logs.hrmac_denials.view`

- [ ] **Step 1: Controller index with pagination + filter by user/module/date**

- [ ] **Step 2: Inertia page (HeroUI table)**

- [ ] **Step 3: Test**

- [ ] **Step 4: Commit**

```bash
git commit -am "feat(hrmac): denial audit log viewer page"
```

---

## Task 9: Final verification

- [ ] **Step 1: Run hrmac tests**

```bash
cd packages/aero-hrmac && vendor/bin/phpunit
```

Expected: all green, ≥85% line coverage on middleware + service.

- [ ] **Step 2: Run permission-mismatch test from aero-core**

Expected: PASS (presumes aero-core Task 18 + aero-platform Task 13 also landed).

- [ ] **Step 3: Score recheck**

| Dimension | Target |
|---|---|
| Denial observability (audit log persisted) | 10/10 |
| Super-admin guard scoping | 10/10 |
| is_active enforcement | 10/10 |
| Discovery validator accuracy | 10/10 |
| Race-safety of modules:sync | 10/10 |
| Test coverage of middleware + service | 10/10 |

- [ ] **Step 4: Tag**

```bash
git tag aero-hrmac-10-10
```

---

## Self-Review

- ✅ All 5 Phase 1 known gaps addressed (Tasks 1, 2, 3, 4, + viewer Task 8)
- ✅ Race condition on modules:sync closed (Task 5)
- ✅ Test coverage expanded from 1 file to ~6 files (Tasks 6, 7)
- ✅ TDD shape across the board
- ✅ Shared task with aero-platform marked explicitly (Task 5)

## Execution Handoff

Order: Task 1 (audit persistence — biggest visible improvement) → Task 2 (security hardening) → Task 3 (correctness) → Task 4 (cleanup) → Task 5 (race) → Tasks 6-8 (depth) → Task 9 (verify).

These are mostly small (S-M) tasks. ~2-3 engineer-days total.
