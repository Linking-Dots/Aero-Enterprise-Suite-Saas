# Plan F — Data Layer Integrity

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Eliminate the three data-layer hazards: cross-database `Module::plans()` join, flat-file installation detection that fails in containers, and the lack of model-layer enforcement of which DB connection a model belongs to.

**Architecture:** F1 removes `Module::plans()` and creates `PlatformPlanService` to expose plan data via service call (not ORM join). F2 creates `InstallationState` service that backs the boolean with a file-cached DB schema check — 27 callers updated. F3 adds `TenantModel` and `CentralModel` abstract base classes that enforce correct connection at the ORM level; existing models are NOT migrated (that is a phased follow-up) — only the base classes are created and documented.

**Tech Stack:** Laravel 11, Eloquent, PHP 8.2.

**Prerequisite:** Plan E complete and merged to `main`.

---

## File Map

### New Files
- `packages/aero-platform/src/Services/PlatformPlanService.php`
- `packages/aero-core/src/Services/InstallationState.php`
- `packages/aero-core/src/Models/TenantModel.php`
- `packages/aero-core/src/Models/CentralModel.php`

### Modified Files
- `packages/aero-core/src/Models/Module.php` — remove `plans()` relationship + `Plan` import
- `packages/aero-core/src/AeroCoreServiceProvider.php` — bind `InstallationState`, replace `installed()` usages
- All 27 files using `file_exists(storage_path('app/aeos.installed'))` → use `InstallationState::isInstalled()`

---

## Task F1: Remove Module::plans() Cross-DB Relationship

**Files:**
- Modify: `packages/aero-core/src/Models/Module.php`
- Create: `packages/aero-platform/src/Services/PlatformPlanService.php`

`Module::plans()` is a `belongsToMany` to `Plan` — but `Plan` is in the central DB and `Module` lives in the tenant DB. The join cannot work across databases. Remove the relationship; expose plan data via a service instead.

- [ ] **Step F1.1: Write the PlatformPlanService**

```php
<?php
// packages/aero-platform/src/Services/PlatformPlanService.php

namespace Aero\Platform\Services;

use Aero\Platform\Models\Plan;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class PlatformPlanService
{
    /**
     * Get all plans that include the given module code.
     * Plans live in the central DB — this service is the ONLY way
     * to query plan data from a tenant context.
     */
    public function getPlansForModule(string $moduleCode): Collection
    {
        return Cache::remember("plan_modules:{$moduleCode}", 300, function () use ($moduleCode) {
            return Plan::active()
                ->whereHas('modules', fn ($q) => $q->where('module_code', $moduleCode))
                ->get(['id', 'name', 'slug', 'monthly_price', 'yearly_price']);
        });
    }

    /**
     * Check if a module is included in any active plan.
     */
    public function isModuleInAnyPlan(string $moduleCode): bool
    {
        return $this->getPlansForModule($moduleCode)->isNotEmpty();
    }
}
```

- [ ] **Step F1.2: Register PlatformPlanService in AeroPlatformServiceProvider**

In `packages/aero-platform/src/AeroPlatformServiceProvider.php`, in `register()`:

```php
$this->app->singleton(\Aero\Platform\Services\PlatformPlanService::class);
```

- [ ] **Step F1.3: Remove plans() from Module.php**

Open `packages/aero-core/src/Models/Module.php`. Delete the `plans()` method and its related imports:

Remove these lines near the top (import):
```php
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
```
(only if BelongsToMany is used nowhere else in the file — check first)

Remove this method entirely:
```php
public function plans(): BelongsToMany
{
    return $this->belongsToMany(Plan::class, 'plan_module')
        ->withPivot('limits', 'is_enabled')
        ->withTimestamps()
        ->wherePivot('is_enabled', true);
}
```

Also remove the `Plan` import if present:
```php
use Aero\Platform\Models\Plan;
```

- [ ] **Step F1.4: Search for callers of $module->plans() and update them**

```bash
grep -rn "->plans()\|module->plans\|Module.*plans" packages/ --include="*.php" | grep -v "PlatformPlanService\|module\.php"
```

For each caller found, replace `$module->plans()` with:
```php
app(\Aero\Platform\Services\PlatformPlanService::class)->getPlansForModule($module->code)
```

If no callers found: safe to proceed.

- [ ] **Step F1.5: Verify syntax**

```bash
php -l packages/aero-core/src/Models/Module.php
php -l packages/aero-platform/src/Services/PlatformPlanService.php
```

- [ ] **Step F1.6: Commit**

```bash
git add packages/aero-core/src/Models/Module.php \
        packages/aero-platform/src/Services/PlatformPlanService.php \
        packages/aero-platform/src/AeroPlatformServiceProvider.php
git commit -m "fix(aero-core): remove Module::plans() cross-DB relationship; introduce PlatformPlanService for cross-DB plan queries"
```

---

## Task F2: InstallationState Service

**Files:**
- Create: `packages/aero-core/src/Services/InstallationState.php`
- Modify: `packages/aero-core/src/AeroCoreServiceProvider.php` — replace `installed()` helper
- Modify: all 27 callers across packages

The flat file `storage/app/aeos.installed` is a single point of failure — accidental deletion or container ephemeral storage reverts the app to installation mode. Replace with a file-cached DB schema check.

- [ ] **Step F2.1: Create InstallationState**

```php
<?php
// packages/aero-core/src/Services/InstallationState.php

namespace Aero\Core\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class InstallationState
{
    private const CACHE_KEY = 'aeos.installed';

    /**
     * Check whether the application is installed.
     *
     * Uses a file-based cache so it survives PHP-FPM restarts.
     * The cache is validated against actual DB schema — not just a flag file.
     * Falls back to the legacy flat file for backward compatibility.
     */
    public static function isInstalled(): bool
    {
        // Fast path: legacy file still exists and cache confirms it
        $legacyFile = storage_path('app/aeos.installed');

        return Cache::store('file')->rememberForever(self::CACHE_KEY, function () use ($legacyFile) {
            // Legacy flat file is authoritative if present
            if (file_exists($legacyFile)) {
                return true;
            }

            // Validate against actual DB tables
            try {
                return Schema::hasTable('users') && Schema::hasTable('modules');
            } catch (\Throwable) {
                return false;
            }
        });
    }

    /**
     * Mark the application as installed.
     * Writes both the cache entry and the legacy flat file.
     */
    public static function markInstalled(): void
    {
        Cache::store('file')->forever(self::CACHE_KEY, true);
        file_put_contents(storage_path('app/aeos.installed'), now()->toIso8601String());
    }

    /**
     * Clear the installation state cache.
     * Use after uninstalling or during testing.
     */
    public static function clear(): void
    {
        Cache::store('file')->forget(self::CACHE_KEY);
    }
}
```

- [ ] **Step F2.2: Find all 27 callers**

```bash
grep -rn "file_exists.*aeos.installed\|aeos\.installed" packages/ --include="*.php" -l
```

This lists the files. For each file, replace:
```php
file_exists(storage_path('app/aeos.installed'))
```
with:
```php
\Aero\Core\Services\InstallationState::isInstalled()
```

And replace any:
```php
file_put_contents(storage_path('app/aeos.installed'), ...)
```
with:
```php
\Aero\Core\Services\InstallationState::markInstalled()
```

- [ ] **Step F2.3: Replace callers using sed (or edit each file)**

```bash
# Dry run first to see what would change:
grep -rn "file_exists(storage_path('app/aeos.installed'))" packages/ --include="*.php" | head -10
```

For each file listed, open it and apply the substitution. The key files are:
- `packages/aero-core/src/AeroCoreServiceProvider.php` — `installed()` method
- `packages/aero-installation/src/Installation/Steps/FinalizeStep.php` — writes the file
- `packages/aero-core/src/Http/Middleware/BootstrapGuard.php`
- `packages/aero-core/src/Http/Middleware/PreventInstalledAccess.php`
- `packages/aero-core/src/Providers/CoreModuleProvider.php`

For `AeroCoreServiceProvider.php`, update the `installed()` protected method:

```php
protected function installed(): bool
{
    return \Aero\Core\Services\InstallationState::isInstalled();
}
```

For `FinalizeStep.php`, find where it writes the installed file and replace with:
```php
\Aero\Core\Services\InstallationState::markInstalled();
```

- [ ] **Step F2.4: Verify syntax on all modified files**

```bash
for f in $(grep -rln "InstallationState" packages/ --include="*.php"); do
  php -l "$f" 2>&1 | grep -v "No syntax"
done
```

Expected: no output (all clean).

- [ ] **Step F2.5: Commit**

```bash
git add packages/aero-core/src/Services/InstallationState.php
git add $(grep -rln "InstallationState\|aeos.installed" packages/ --include="*.php")
git commit -m "fix(aero-core): replace file_exists(aeos.installed) with InstallationState service — DB-schema-backed, cache-safe, container-friendly"
```

---

## Task F3: TenantModel and CentralModel Base Classes

**Files:**
- Create: `packages/aero-core/src/Models/TenantModel.php`
- Create: `packages/aero-core/src/Models/CentralModel.php`

These are abstract base classes. **No existing models are migrated in this task** — that is a follow-up phased migration. The classes are created and documented so new models can use them immediately.

- [ ] **Step F3.1: Create TenantModel**

```php
<?php
// packages/aero-core/src/Models/TenantModel.php

namespace Aero\Core\Models;

use Aero\Core\Contracts\TenantScopeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Base class for all models that live in the TENANT database.
 *
 * In SaaS mode: the connection is switched to the current tenant's DB
 *   by stancl/tenancy middleware before any query runs.
 * In standalone mode: a single DB is used; no switching occurs.
 *
 * Extend this class for any model whose table belongs to tenant data.
 * NEVER add cross-DB relationships to Plan, LandlordUser, or other central models.
 */
abstract class TenantModel extends Model
{
    protected static function boot(): void
    {
        parent::boot();

        // Guard: throw if queried outside tenant context in SaaS mode.
        // This catches accidental central-context queries at development time.
        static::addGlobalScope('tenant_context_guard', function (Builder $builder) {
            if (! is_saas_mode()) {
                return; // Standalone: single DB, always valid
            }

            try {
                $scope = app(TenantScopeInterface::class);
                if (! $scope->inTenantContext()) {
                    throw new \LogicException(
                        static::class . ' was queried outside of tenant context. ' .
                        'Ensure this query runs after tenancy middleware has initialized. ' .
                        'For central-DB data, extend CentralModel instead.'
                    );
                }
            } catch (\LogicException $e) {
                throw $e;
            } catch (\Throwable) {
                // TenantScopeInterface not yet bound — allow during early boot/testing
            }
        });
    }
}
```

- [ ] **Step F3.2: Create CentralModel**

```php
<?php
// packages/aero-core/src/Models/CentralModel.php

namespace Aero\Core\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Base class for all models that live in the CENTRAL (landlord) database.
 *
 * Central models are tenant-agnostic: Plans, LandlordUsers, Tenants, Products, etc.
 * The connection is pinned to 'central' and cannot be changed at runtime.
 *
 * Extend this class for any model whose table belongs to the platform, not a tenant.
 * NEVER add belongsToMany or hasMany to TenantModel subclasses — cross-DB joins fail.
 *
 * In standalone mode: there is no 'central' connection — do NOT extend CentralModel
 * in standalone-only packages. Use plain Model instead.
 */
abstract class CentralModel extends Model
{
    /** @var string Always use the central (landlord) DB connection. */
    protected $connection = 'central';

    protected static function boot(): void
    {
        parent::boot();

        // Prevent accidental connection switching at runtime
        static::creating(fn (self $m) => $m->setConnection('central'));
        static::saving(fn (self $m) => $m->setConnection('central'));
    }
}
```

- [ ] **Step F3.3: Verify syntax**

```bash
php -l packages/aero-core/src/Models/TenantModel.php
php -l packages/aero-core/src/Models/CentralModel.php
```

- [ ] **Step F3.4: Update Plan model to extend CentralModel**

`Plan` is a clear central-DB model. Update it as the first concrete adoption:

Open `packages/aero-platform/src/Models/Plan.php`. Change:
```php
use Illuminate\Database\Eloquent\Model;
// ...
class Plan extends Model
```
to:
```php
use Aero\Core\Models\CentralModel;
// ...
class Plan extends CentralModel
```

Remove the `protected $connection = 'central'` line if it already exists in Plan (CentralModel provides it).

- [ ] **Step F3.5: Verify Plan still lints cleanly**

```bash
php -l packages/aero-platform/src/Models/Plan.php
```

- [ ] **Step F3.6: Commit**

```bash
git add packages/aero-core/src/Models/TenantModel.php \
        packages/aero-core/src/Models/CentralModel.php \
        packages/aero-platform/src/Models/Plan.php
git commit -m "feat(aero-core): add TenantModel and CentralModel abstract bases — enforces DB connection intent; migrate Plan to CentralModel"
```

---

## Self-Review

**Spec coverage:**
- Cross-DB Module::plans() (MAJOR) → F1 ✅
- Installation detection flat file (MINOR) → F2 ✅
- TenantModel/CentralModel enforcement (Section 4 enforcement) → F3 ✅

**Not in this plan (separate work):**
- Migrating existing models to TenantModel/CentralModel — phased migration, do package by package
- aero-contracts package extraction — Plan G
