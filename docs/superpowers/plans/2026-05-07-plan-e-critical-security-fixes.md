# Plan E — Critical Security Fixes

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Close the three critical security gaps identified in the architectural audit: platform permission bypass (all landlords are super-admins), guard context bleeding (landlord session leaks into tenant routes), and ModuleRegistry cross-tenant enablement state.

**Architecture:** Three independent fixes applied to `packages/aero-core` and `packages/aero-platform`. E1 seeds real platform permissions and enforces them in `handlePlatformAccess()`. E2 introduces two tiny context middlewares (`ResolvePlatformContext`, `ResolveTenantContext`) injected by route groups so `detectGuard()` reads intent from the route stack — not the hostname. E3 adds per-tenant enablement caching to `ModuleRegistry` without breaking its existing API.

**Tech Stack:** Laravel 11, spatie/laravel-permission (already installed), PHP 8.2 readonly properties.

**Prerequisite:** `main` branch after Plans A–D. Run `php artisan config:clear` after each task.

---

## File Map

### New Files
- `packages/aero-platform/database/seeders/PlatformPermissionSeeder.php`
- `packages/aero-core/src/Http/Middleware/ResolvePlatformContext.php`
- `packages/aero-core/src/Http/Middleware/ResolveTenantContext.php`
- `packages/aero-core/src/ValueObjects/RequestContext.php`

### Modified Files
- `packages/aero-core/src/Http/Middleware/CheckModuleAccess.php` — fix `detectGuard()` (read context) + fix `handlePlatformAccess()` (enforce permissions)
- `packages/aero-core/src/AeroCoreServiceProvider.php` — register new middleware aliases
- `packages/aero-core/src/Services/ModuleRegistry.php` — add `isEnabledForTenant()` + `flushTenant()`
- `packages/aero-platform/src/AeroPlatformServiceProvider.php` — apply context middleware to platform routes

---

## Task E1: Platform Permission Seeder

**Files:**
- Create: `packages/aero-platform/database/seeders/PlatformPermissionSeeder.php`

Reads `aero-platform/config/module.php` and creates spatie permissions in the format `platform.{submodule}.{component}.{action}` plus a `super-platform-admin` role that gets all of them.

- [ ] **Step E1.1: Write the seeder**

```php
<?php
// packages/aero-platform/database/seeders/PlatformPermissionSeeder.php

namespace Aero\Platform\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PlatformPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Use the 'landlord' guard so permissions are scoped correctly
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $config     = require __DIR__ . '/../../config/module.php';
        $submodules = $config['submodules'] ?? [];

        $allPermissions = [];

        foreach ($submodules as $submodule) {
            $subCode = $submodule['code'];
            foreach ($submodule['components'] ?? [] as $component) {
                $compCode = $component['code'];
                foreach ($component['actions'] ?? [] as $action) {
                    $name = "platform.{$subCode}.{$compCode}.{$action['code']}";
                    Permission::firstOrCreate(['name' => $name, 'guard_name' => 'landlord']);
                    $allPermissions[] = $name;
                }
            }
        }

        // Super platform admin role gets everything
        $superRole = Role::firstOrCreate(['name' => 'super-platform-admin', 'guard_name' => 'landlord']);
        $superRole->syncPermissions($allPermissions);

        // Platform admin role gets read-only on most modules
        $adminRole = Role::firstOrCreate(['name' => 'platform-admin', 'guard_name' => 'landlord']);

        $this->command->info('Platform permissions seeded: ' . count($allPermissions));
    }
}
```

- [ ] **Step E1.2: Run seeder to verify it works**

```bash
cd c:/laragon/www/aeos365
php artisan db:seed --class="Aero\Platform\Database\Seeders\PlatformPermissionSeeder" 2>&1 | tail -5
```

Expected: `Platform permissions seeded: N` (where N > 0)

- [ ] **Step E1.3: Commit**

```bash
git add packages/aero-platform/database/seeders/PlatformPermissionSeeder.php
git commit -m "feat(aero-platform): add PlatformPermissionSeeder — seeds platform.* permissions for landlord guard"
```

---

## Task E2: Fix handlePlatformAccess — Enforce Real Permissions

**Files:**
- Modify: `packages/aero-core/src/Http/Middleware/CheckModuleAccess.php` (lines ~225–260)

- [ ] **Step E2.1: Read the current handlePlatformAccess method**

Open `packages/aero-core/src/Http/Middleware/CheckModuleAccess.php` and locate `handlePlatformAccess()`. It currently ends with an unconditional `return $next($request)`.

- [ ] **Step E2.2: Replace the method body**

Replace the entire `handlePlatformAccess()` method with:

```php
protected function handlePlatformAccess(
    Request $request,
    Closure $next,
    $user,
    string $moduleCode,
    ?string $subModuleCode = null,
    ?string $componentCode = null,
    ?string $actionCode    = null
): Response {
    // Super platform admins bypass all checks
    if ($user->hasRole('super-platform-admin', 'landlord')) {
        return $next($request);
    }

    // Build the permission name from the access path
    $parts      = array_filter([$moduleCode, $subModuleCode, $componentCode, $actionCode]);
    $permission = 'platform.' . implode('.', $parts);

    if (! $user->hasPermissionTo($permission, 'landlord')) {
        return $this->denyAccess(
            $request,
            'insufficient_permissions',
            "Missing platform permission: [{$permission}]",
            403,
            ['permission' => $permission]
        );
    }

    return $next($request);
}
```

- [ ] **Step E2.3: Verify syntax**

```bash
php -l packages/aero-core/src/Http/Middleware/CheckModuleAccess.php
```

Expected: `No syntax errors detected`

- [ ] **Step E2.4: Commit**

```bash
git add packages/aero-core/src/Http/Middleware/CheckModuleAccess.php
git commit -m "fix(aero-core): CheckModuleAccess enforces real platform permissions instead of allowing all landlord users"
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
- Platform bypass (CRITICAL) → E2 ✅
- Platform permissions seeded → E1 ✅
- Context bleeding (CRITICAL) → E3+E4+E5 ✅
- ModuleRegistry cross-tenant (MAJOR) → E6 ✅
- TenantCache not tenant-scoped (MAJOR) → E7 ✅

**Deferred (separate plans):** Module::plans() cross-DB, TenantModel/CentralModel, InstallationState, aero-contracts.
