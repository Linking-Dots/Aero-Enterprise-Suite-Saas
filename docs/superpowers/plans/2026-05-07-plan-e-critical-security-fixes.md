# Plan E — Critical Security Fixes

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Close the three critical security gaps identified in the architectural audit: platform permission bypass (all landlords are super-admins), guard context bleeding (landlord session leaks into tenant routes), and ModuleRegistry cross-tenant enablement state.

**Architecture:** Three independent fixes applied to `packages/aero-core` and `packages/aero-platform`. E1 syncs platform module hierarchy into HRMAC (the same `module → sub_module → component → action` system used on the tenant side) and seeds platform roles via HRMAC's `syncRoleAccess()`. E2 replaces the TODO bypass in `handlePlatformAccess()` with a HRMAC hierarchy check — `userCanAccessModule()` / `userCanAccessSubModule()` / `userCanAccessAction()` by code, with cascade built in. E3+E4+E5 introduce two context middlewares (`ResolvePlatformContext`, `ResolveTenantContext`) so `detectGuard()` reads intent from the route stack, not the hostname. E6 adds per-tenant enablement caching to `ModuleRegistry`. E7 fixes `TenantCache` to use `TenantScopeInterface`.

**Tech Stack:** Laravel 11, HRMAC (`aero/hrmac` — `RoleModuleAccessInterface`), PHP 8.2 readonly properties. **No spatie/laravel-permission used for platform access** — HRMAC owns all role-module access on both sides.

**Prerequisite:** `main` branch after Plans A–D. Run `php artisan config:clear` after each task.

---

## File Map

### New Files
- `packages/aero-platform/database/seeders/PlatformHrmacSeeder.php`
- `packages/aero-core/src/Http/Middleware/ResolvePlatformContext.php`
- `packages/aero-core/src/Http/Middleware/ResolveTenantContext.php`
- `packages/aero-core/src/ValueObjects/RequestContext.php`

### Modified Files
- `packages/aero-core/src/Http/Middleware/CheckModuleAccess.php` — fix `detectGuard()` (read context) + fix `handlePlatformAccess()` (enforce permissions)
- `packages/aero-core/src/AeroCoreServiceProvider.php` — register new middleware aliases
- `packages/aero-core/src/Services/ModuleRegistry.php` — add `isEnabledForTenant()` + `flushTenant()`
- `packages/aero-platform/src/AeroPlatformServiceProvider.php` — apply context middleware to platform routes

---

## Task E1: Sync Platform Module Hierarchy into HRMAC

**Files:**
- Create: `packages/aero-platform/database/seeders/PlatformHrmacSeeder.php`

**Context — how HRMAC works on both sides:**

HRMAC (`aero/hrmac`) is the single access-control system for both tenant and platform. It uses four DB tables: `modules`, `sub_modules`, `components`, `actions`, and a join table `role_module_access`. The hierarchy cascades downward — granting module access implies sub_module+component+action access. The `modules.scope` column distinguishes `'tenant'` from `'platform'` modules.

The `aero:sync-module` command (from `aero-core`) reads every package's `config/module.php` and inserts/updates the HRMAC hierarchy tables. It auto-detects scope from `module.php`'s `'scope'` key. For `aero-platform` (scope: `'platform'`), this populates the central DB with the platform module tree.

This seeder runs AFTER `aero:sync-module` and creates two platform roles with HRMAC access:
1. `Super Platform Admin` — full access to all platform modules (via HRMAC module-level grant)
2. `Platform Admin` — read-only access (view actions only)

- [ ] **Step E1.1: First verify aero:sync-module includes platform modules**

```bash
cd c:/laragon/www/aeos365
php artisan aero:sync-module 2>&1 | tail -10
```

Then confirm the platform module is synced:
```bash
php artisan tinker --execute="
use Aero\HRMAC\Models\Module;
echo Module::where('scope', 'platform')->count() . ' platform modules synced';
echo PHP_EOL;
Module::where('scope', 'platform')->pluck('code')->each(fn(\$c) => print(\$c . PHP_EOL));
"
```

Expected: the platform module and its submodules are present. If count is 0, run:
```bash
php artisan aero:sync-module --force 2>&1 | tail -5
```

- [ ] **Step E1.2: Write PlatformHrmacSeeder**

```php
<?php
// packages/aero-platform/database/seeders/PlatformHrmacSeeder.php

namespace Aero\Platform\Database\Seeders;

use Aero\HRMAC\Contracts\RoleModuleAccessInterface;
use Aero\HRMAC\Models\Module as HrmacModule;
use Aero\HRMAC\Models\Role as HrmacRole;
use Illuminate\Database\Seeder;

class PlatformHrmacSeeder extends Seeder
{
    public function run(): void
    {
        /** @var RoleModuleAccessInterface $hrmac */
        $hrmac = app(RoleModuleAccessInterface::class);

        // 1. Ensure platform module hierarchy is synced
        \Illuminate\Support\Facades\Artisan::call('aero:sync-module');

        // 2. Create Super Platform Admin role (if not exists)
        $superAdmin = HrmacRole::firstOrCreate(
            ['name' => 'Super Platform Admin'],
            ['guard_name' => 'landlord', 'is_system' => true]
        );

        // 3. Grant Super Platform Admin full access to the platform module
        //    HRMAC cascade: module-level grant covers all submodules, components, actions
        $platformModule = HrmacModule::where('code', 'platform')
            ->where('scope', 'platform')
            ->first();

        if ($platformModule) {
            $hrmac->syncRoleAccess($superAdmin, [
                'modules' => [$platformModule->id],  // full module access = all children granted
                'sub_modules' => [],
                'components' => [],
                'actions' => [],
            ]);
            $this->command->info("Super Platform Admin granted full access to platform module (id: {$platformModule->id})");
        } else {
            $this->command->error('Platform module not found in HRMAC — run: php artisan aero:sync-module first');
        }

        // 4. Create Platform Admin role (read-only — view actions only)
        $platformAdmin = HrmacRole::firstOrCreate(
            ['name' => 'Platform Admin'],
            ['guard_name' => 'landlord', 'is_system' => true]
        );

        // Grant view-only actions across all platform submodules
        if ($platformModule) {
            $viewActions = \Aero\HRMAC\Models\Action::whereHas('component.subModule', function ($q) use ($platformModule) {
                $q->where('module_id', $platformModule->id);
            })->where('code', 'view')->pluck('id')->toArray();

            $hrmac->syncRoleAccess($platformAdmin, [
                'modules'     => [],
                'sub_modules' => [],
                'components'  => [],
                'actions'     => $viewActions,  // only view actions
            ]);
            $this->command->info("Platform Admin granted " . count($viewActions) . " view-only actions");
        }

        $hrmac->clearRoleCache($superAdmin);
        $hrmac->clearRoleCache($platformAdmin);

        $this->command->info('Platform HRMAC roles seeded successfully');
    }
}
```

- [ ] **Step E1.3: Run seeder**

```bash
cd c:/laragon/www/aeos365
php artisan db:seed --class="Aero\Platform\Database\Seeders\PlatformHrmacSeeder" 2>&1 | tail -8
```

Expected:
```
Super Platform Admin granted full access to platform module (id: N)
Platform Admin granted N view-only actions
Platform HRMAC roles seeded successfully
```

- [ ] **Step E1.4: Commit**

```bash
git add packages/aero-platform/database/seeders/PlatformHrmacSeeder.php
git commit -m "feat(aero-platform): add PlatformHrmacSeeder — seeds Super Platform Admin + Platform Admin roles via HRMAC hierarchy"
```

---

## Task E2: Fix handlePlatformAccess — Use HRMAC Hierarchy Check

**Files:**
- Modify: `packages/aero-core/src/Http/Middleware/CheckModuleAccess.php`

**How HRMAC hierarchy check works:**

`RoleModuleAccessInterface` exposes three code-based user checks that respect the cascade:
- `userCanAccessModule($user, 'platform')` — true if user's role has module-level grant (= full access)
- `userCanAccessSubModule($user, 'platform', 'tenant_management')` — true if granted module OR sub_module
- `userCanAccessAction($user, 'platform', 'tenant_management', 'view')` — true if granted anywhere in the chain

HRMAC internally calls `isSuperAdmin($user)` at the start of each check — so super admins bypass everything automatically without special-casing here.

The `$componentCode` parameter maps to the component level in HRMAC. Since HRMAC does not expose a code-based `userCanAccessComponent()` method, we treat component-level requests as sub_module-level (the cascade covers it — if you have sub_module access, all components within it are accessible).

- [ ] **Step E2.1: Read the current handlePlatformAccess method**

Open `packages/aero-core/src/Http/Middleware/CheckModuleAccess.php`. Locate `handlePlatformAccess()` — it currently has `// TODO` and returns `$next($request)` unconditionally.

- [ ] **Step E2.2: Add the HRMAC import to the class**

At the top of `CheckModuleAccess.php`, add this use statement alongside the existing ones:

```php
use Aero\HRMAC\Contracts\RoleModuleAccessInterface;
```

- [ ] **Step E2.3: Replace the entire handlePlatformAccess() method**

```php
/**
 * Handle access control for platform/landlord context using HRMAC.
 *
 * Uses the same hierarchy as the tenant side: module → sub_module → component → action.
 * RoleModuleAccessInterface handles super-admin bypass and cascade internally.
 * No spatie permissions involved — HRMAC owns all access control on both sides.
 */
protected function handlePlatformAccess(
    Request $request,
    Closure $next,
    $user,
    string $moduleCode,
    ?string $subModuleCode = null,
    ?string $componentCode = null,
    ?string $actionCode    = null
): Response {
    try {
        /** @var RoleModuleAccessInterface $hrmac */
        $hrmac = app(RoleModuleAccessInterface::class);

        $allowed = match (true) {
            // Action-level check (most granular) — requires subModuleCode
            $actionCode !== null && $subModuleCode !== null =>
                $hrmac->userCanAccessAction($user, $moduleCode, $subModuleCode, $actionCode),

            // SubModule-level check — also covers component-level (cascade)
            $subModuleCode !== null =>
                $hrmac->userCanAccessSubModule($user, $moduleCode, $subModuleCode),

            // Module-level check (broadest)
            default =>
                $hrmac->userCanAccessModule($user, $moduleCode),
        };

    } catch (\Throwable $e) {
        // HRMAC not available (e.g. tables not migrated yet) — deny access safely
        \Illuminate\Support\Facades\Log::warning('HRMAC unavailable for platform access check', [
            'error'  => $e->getMessage(),
            'module' => $moduleCode,
            'user'   => $user->id ?? null,
        ]);
        $allowed = false;
    }

    if (! $allowed) {
        return $this->denyAccess(
            $request,
            'insufficient_permissions',
            "You do not have access to this platform feature.",
            403,
            [
                'module'    => $moduleCode,
                'submodule' => $subModuleCode,
                'component' => $componentCode,
                'action'    => $actionCode,
            ]
        );
    }

    return $next($request);
}
```

- [ ] **Step E2.4: Verify syntax**

```bash
php -l packages/aero-core/src/Http/Middleware/CheckModuleAccess.php
```

Expected: `No syntax errors detected`

- [ ] **Step E2.5: Verify HRMAC check works for a landlord user in tinker**

```bash
cd c:/laragon/www/aeos365
php artisan tinker --execute="
// Simulate the check that handlePlatformAccess will do
\$hrmac = app(\Aero\HRMAC\Contracts\RoleModuleAccessInterface::class);
\$user  = \Aero\Platform\Models\LandlordUser::first();
if (\$user) {
    echo 'User: ' . \$user->email . PHP_EOL;
    echo 'Can access platform module: ' . (\$hrmac->userCanAccessModule(\$user, 'platform') ? 'YES' : 'NO') . PHP_EOL;
} else {
    echo 'No landlord users found — seed one first';
}
"
```

- [ ] **Step E2.6: Commit**

```bash
git add packages/aero-core/src/Http/Middleware/CheckModuleAccess.php
git commit -m "fix(aero-core): handlePlatformAccess uses HRMAC hierarchy (module→submodule→action) — same system as tenant side, no bypass"
```

---

## Task E3: Request Context Value Object

**Files:**
- Create: `packages/aero-core/src/ValueObjects/RequestContext.php`

A simple readonly value object that carries the resolved scope and guard for the current request. Injected by route-specific middleware, read by `CheckModuleAccess`.

- [ ] **Step E3.1: Create RequestContext**

```php
<?php
// packages/aero-core/src/ValueObjects/RequestContext.php

namespace Aero\Core\ValueObjects;

final readonly class RequestContext
{
    /**
     * @param string $scope  One of: 'tenant' | 'platform' | 'standalone'
     * @param string $guard  One of: 'web' | 'landlord'
     */
    public function __construct(
        public string $scope,
        public string $guard,
    ) {}

    public function isPlatform(): bool
    {
        return $this->scope === 'platform';
    }

    public function isTenant(): bool
    {
        return $this->scope === 'tenant';
    }

    public function isStandalone(): bool
    {
        return $this->scope === 'standalone';
    }
}
```

- [ ] **Step E3.2: Verify syntax**

```bash
php -l packages/aero-core/src/ValueObjects/RequestContext.php
```

- [ ] **Step E3.3: Commit**

```bash
git add packages/aero-core/src/ValueObjects/RequestContext.php
git commit -m "feat(aero-core): add RequestContext value object — carries resolved scope and guard for current request"
```

---

## Task E4: ResolvePlatformContext and ResolveTenantContext Middleware

**Files:**
- Create: `packages/aero-core/src/Http/Middleware/ResolvePlatformContext.php`
- Create: `packages/aero-core/src/Http/Middleware/ResolveTenantContext.php`

These two middlewares are applied by route groups (not globally). They inject `RequestContext` into the container so `detectGuard()` can read intent instead of sniffing the hostname.

- [ ] **Step E4.1: Create ResolvePlatformContext**

```php
<?php
// packages/aero-core/src/Http/Middleware/ResolvePlatformContext.php

namespace Aero\Core\Http\Middleware;

use Aero\Core\ValueObjects\RequestContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolvePlatformContext
{
    public function handle(Request $request, Closure $next): Response
    {
        app()->instance(RequestContext::class, new RequestContext('platform', 'landlord'));

        return $next($request);
    }
}
```

- [ ] **Step E4.2: Create ResolveTenantContext**

```php
<?php
// packages/aero-core/src/Http/Middleware/ResolveTenantContext.php

namespace Aero\Core\Http\Middleware;

use Aero\Core\ValueObjects\RequestContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        app()->instance(RequestContext::class, new RequestContext('tenant', 'web'));

        return $next($request);
    }
}
```

- [ ] **Step E4.3: Verify syntax**

```bash
php -l packages/aero-core/src/Http/Middleware/ResolvePlatformContext.php
php -l packages/aero-core/src/Http/Middleware/ResolveTenantContext.php
```

- [ ] **Step E4.4: Register middleware aliases in AeroCoreServiceProvider**

In `packages/aero-core/src/AeroCoreServiceProvider.php`, in `registerMiddleware()`, add alongside existing alias registrations:

```php
$router->aliasMiddleware('resolve.platform.context', \Aero\Core\Http\Middleware\ResolvePlatformContext::class);
$router->aliasMiddleware('resolve.tenant.context', \Aero\Core\Http\Middleware\ResolveTenantContext::class);
```

- [ ] **Step E4.5: Commit**

```bash
git add packages/aero-core/src/Http/Middleware/ResolvePlatformContext.php \
        packages/aero-core/src/Http/Middleware/ResolveTenantContext.php \
        packages/aero-core/src/AeroCoreServiceProvider.php
git commit -m "feat(aero-core): add ResolvePlatformContext + ResolveTenantContext middleware — inject RequestContext from route groups"
```

---

## Task E5: Fix detectGuard() — Read Context Not Hostname

**Files:**
- Modify: `packages/aero-core/src/Http/Middleware/CheckModuleAccess.php`

Replace the current `detectGuard()` implementation (which sniffs the hostname and checks for active landlord sessions — the context-bleeding vulnerability) with one that reads `RequestContext` from the container.

- [ ] **Step E5.1: Replace detectGuard() method**

Find and replace the entire `detectGuard()` method in `CheckModuleAccess.php`:

```php
/**
 * Detect which authentication guard to use based on the injected RequestContext.
 *
 * RequestContext is set by ResolvePlatformContext or ResolveTenantContext middleware
 * on route groups. This eliminates hostname sniffing and active-session bleeding.
 *
 * Falls back to 'web' if no context is bound (e.g. during testing or pre-install).
 */
protected function detectGuard(Request $request): string
{
    // Standalone mode always uses 'web' guard — no landlord concept
    if (is_standalone_mode()) {
        return 'web';
    }

    try {
        $context = app(\Aero\Core\ValueObjects\RequestContext::class);
        return $context->guard;
    } catch (\Throwable) {
        // No context bound — default to web guard
        return 'web';
    }
}
```

- [ ] **Step E5.2: Verify syntax**

```bash
php -l packages/aero-core/src/Http/Middleware/CheckModuleAccess.php
```

- [ ] **Step E5.3: Apply ResolvePlatformContext to platform routes**

Open `packages/aero-platform/routes/admin.php` (or wherever landlord routes are registered in `AeroPlatformServiceProvider`). Find the platform route group and add the middleware:

```php
// Before — example of what the platform route group looks like:
Route::middleware(['auth:landlord', ...])
    ->prefix('admin')
    ->group(...)

// After — add resolve.platform.context:
Route::middleware(['auth:landlord', 'resolve.platform.context', ...])
    ->prefix('admin')
    ->group(...)
```

Find the exact route registration by searching:
```bash
grep -rn "auth:landlord\|landlord.*routes" packages/aero-platform/src/ --include="*.php" | head -10
```

Add `'resolve.platform.context'` to the middleware array on the landlord route group.

- [ ] **Step E5.4: Apply ResolveTenantContext to tenant routes**

Open `packages/aero-core/routes/web.php`. Find the main tenant route group (the one inside `Route::domain(...)` or the main `Route::middleware(['web', ...])->group(...)`). Add `'resolve.tenant.context'` to that group's middleware.

- [ ] **Step E5.5: Verify artisan boots cleanly**

```bash
php artisan config:clear && php artisan route:list --compact 2>&1 | head -20
```

Expected: no errors.

- [ ] **Step E5.6: Commit**

```bash
git add packages/aero-core/src/Http/Middleware/CheckModuleAccess.php \
        packages/aero-platform/src/ \
        packages/aero-core/routes/web.php
git commit -m "fix(aero-core): detectGuard reads RequestContext not hostname — eliminates landlord session context bleeding"
```

---

## Task E6: Add Per-Tenant Enablement to ModuleRegistry

**Files:**
- Modify: `packages/aero-core/src/Services/ModuleRegistry.php`

The existing `ModuleRegistry` singleton stores registered providers. The issue: `enabled()` calls `$provider->isEnabled()` which reads from DB — but in Octane/queue workers the singleton persists across requests. Adding explicit per-tenant caching (keyed by tenant ID) makes `flushTenant()` callable when a tenant changes module state.

- [ ] **Step E6.1: Add isEnabledForTenant and flushTenant to ModuleRegistry**

Open `packages/aero-core/src/Services/ModuleRegistry.php`. Add the following two methods and one property after the existing `$modules` property declaration:

```php
/**
 * Per-tenant enablement cache.
 * Key: "{tenantId}:{moduleCode}" → bool
 * Populated lazily, flushed when tenant changes module state.
 */
private array $tenantEnablementCache = [];
```

Add these two methods after `countEnabled()`:

```php
/**
 * Check if a module is enabled for a specific tenant.
 * Uses an in-process cache keyed by tenant ID to survive Octane reuse.
 * Call flushTenant() after a tenant enables/disables a module.
 */
public function isEnabledForTenant(string $moduleCode, int|string $tenantId): bool
{
    $cacheKey = "{$tenantId}:{$moduleCode}";

    if (! isset($this->tenantEnablementCache[$cacheKey])) {
        $provider = $this->get($moduleCode);
        // isEnabled() reads from DB — result cached in-process per tenant
        $this->tenantEnablementCache[$cacheKey] = $provider ? $provider->isEnabled() : false;
    }

    return $this->tenantEnablementCache[$cacheKey];
}

/**
 * Flush the in-process enablement cache for a specific tenant.
 * Call this whenever a tenant enables or disables a module.
 */
public function flushTenant(int|string $tenantId): void
{
    foreach (array_keys($this->tenantEnablementCache) as $key) {
        if (str_starts_with($key, "{$tenantId}:")) {
            unset($this->tenantEnablementCache[$key]);
        }
    }

    $this->clearCache();
}
```

- [ ] **Step E6.2: Verify syntax**

```bash
php -l packages/aero-core/src/Services/ModuleRegistry.php
```

- [ ] **Step E6.3: Commit**

```bash
git add packages/aero-core/src/Services/ModuleRegistry.php
git commit -m "fix(aero-core): ModuleRegistry gains per-tenant enablement cache + flushTenant() — prevents cross-tenant state leak under Octane"
```

---

## Task E7: Fix TenantCache — Remove tenancy() Direct Call

**Files:**
- Modify: `packages/aero-core/src/Support/TenantCache.php`

`TenantCache::key()` calls `tenancy()->initialized` directly — the same fatal-in-standalone bug fixed in Module.php. Replace with `TenantScopeInterface`.

- [ ] **Step E7.1: Replace the key() method**

Open `packages/aero-core/src/Support/TenantCache.php`. Replace the `key()` method:

```php
public static function key(string $key): string
{
    if (is_saas_mode()) {
        try {
            $scope = app(\Aero\Core\Contracts\TenantScopeInterface::class);
            if ($scope->inTenantContext()) {
                $tenantId = $scope->getCurrentTenantId();
                return "tenant:{$tenantId}:{$key}";
            }
        } catch (\Throwable) {
            // TenantScopeInterface not bound during early boot
        }
    }

    return "global:{$key}";
}
```

- [ ] **Step E7.2: Verify syntax**

```bash
php -l packages/aero-core/src/Support/TenantCache.php
```

Expected: `No syntax errors detected`

- [ ] **Step E7.3: Commit**

```bash
git add packages/aero-core/src/Support/TenantCache.php
git commit -m "fix(aero-core): TenantCache replaces tenancy() direct call with TenantScopeInterface — safe in standalone mode"
```

---

## Self-Review

**Spec coverage:**
- Platform bypass (CRITICAL) → E2 ✅ — HRMAC `userCanAccessModule/SubModule/Action()` by code, cascade built in, super-admin auto-bypassed inside HRMAC
- Platform HRMAC roles seeded → E1 ✅ — `Super Platform Admin` gets module-level grant (covers all children), `Platform Admin` gets view actions only
- Context bleeding (CRITICAL) → E3+E4+E5 ✅
- ModuleRegistry cross-tenant (MAJOR) → E6 ✅
- TenantCache not tenant-scoped (MAJOR) → E7 ✅

**Key design decision recorded:** HRMAC (`RoleModuleAccessInterface`) is the single access-control system for both tenant and platform sides. Spatie/laravel-permission is NOT used for module-level access checks. The platform side uses the same `modules → sub_modules → components → actions` hierarchy with the same cascade rules. Platform module hierarchy lives in the central DB (synced via `aero:sync-module` which respects `modules.scope = 'platform'`).

**Deferred (separate plans):** Module::plans() cross-DB, TenantModel/CentralModel, InstallationState, aero-contracts.
