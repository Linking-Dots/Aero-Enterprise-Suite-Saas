# Standalone Mode Hardening & Marketplace Distribution Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make every aero-* product package independently distributable as licensed, installable standalone software for marketplace sales (e.g. Codecanyon, direct store), while preserving full SaaS multi-tenant compatibility in the same codebase.

**Architecture:** A "product edition" is the `aeos365` host app pre-configured with a specific subset of aero-* packages; a `ProductManifest` in each edition declares which modules are bundled, its marketplace product ID, and license server URL. A `LicenseService` runs on every boot in standalone mode: it validates the license key (format + checksum offline, online activation once per domain), domain-binds the installation, and enforces a 72-hour offline grace period. A packaging artisan command produces a clean distributable archive per edition. SaaS mode is unaffected — license checks are skipped when `aeos.mode = saas`.

**Tech Stack:** Laravel 11, PHP 8.2+, `aero/core` (existing), `aero/installation` (existing), `spatie/laravel-backup` (existing), no new composer packages required for the core plan.

**Caution notes for implementors:**
- Every task that modifies `aero-core` MUST be tested in both standalone and SaaS mode (they share the same code path).
- Never delete the `storage/app/aeos.mode` file detection — it is the canonical standalone/SaaS switch for running processes.
- The `aero-platform` package must never be required by any feature package. Run `grep -r "aero/platform" packages/*/composer.json` before each commit.
- Treat the license server URL as configuration, never hardcoded, so products can point to different license servers.

---

## File Map

### Files to Modify (bug fixes — must ship before any distribution)
- `packages/aero-core/config/module.php` — remove duplicate `system_health`, collapse duplicate `translations` blocks, fix priority collisions
- `packages/aero-core/src/Models/Module.php` — replace `tenancy()->initialized` with `TenantScopeInterface`
- `packages/aero-core/src/AeroCoreServiceProvider.php` — fix `__call` stub shape, remove `use Aero\Platform\*` from import block, replace with `class_exists` guard
- `packages/aero-installation/src/Installation/ModeDetector.php` — consolidate with `helpers.php` (single source of truth)

### Files to Modify (dependency declarations)
- `packages/aero-automation/composer.json`
- `packages/aero-booking/composer.json`
- `packages/aero-custom-fields/composer.json`
- `packages/aero-forms/composer.json`
- `packages/aero-helpdesk/composer.json`
- `packages/aero-i18n/composer.json`
- `packages/aero-mobile/composer.json`
- `packages/aero-notifications/composer.json`
- `packages/aero-time-tracking/composer.json`
- `packages/aero-workflow/composer.json`
- `packages/aero-installation/composer.json`

### Files to Create (license system)
- `packages/aero-core/src/Services/License/LicenseService.php` — central service: validate, activate, domain-bind, boot-check
- `packages/aero-core/src/Services/License/LicenseValidator.php` — offline format + checksum validation
- `packages/aero-core/src/Services/License/DomainBinding.php` — hash current domain, compare against stored value
- `packages/aero-core/src/Services/License/LicenseCache.php` — wraps file-based cache so license status survives restarts
- `packages/aero-core/src/Contracts/LicenseServiceInterface.php` — interface so test stubs are easy
- `packages/aero-core/src/Exceptions/LicenseException.php` — typed exception for license failures
- `packages/aero-core/config/license.php` — license server URL, grace period, cache TTL, bypass flag

### Files to Create (product manifest system)
- `packages/aero-core/src/Manifests/ProductManifest.php` — typed value object for a distributable product
- `packages/aero-core/src/Services/ProductManifestLoader.php` — reads `config/product.php` from the host app
- `packages/aero-core/src/Console/Commands/ValidateManifests.php` — artisan linter for all module.php files
- `packages/aero-core/src/Console/Commands/PackageProduct.php` — generates distributable archive

### Files to Create (host-app product definitions — lives in aeos365, not packages)
- `config/product.php` in `aeos365` — the product manifest for the full-suite SaaS product
- Example: a standalone HRM edition would have its own `aeos365`-derived host app with a different `config/product.php`

### Files to Modify (installer integration)
- `packages/aero-installation/src/Installation/Steps/LicenseStep.php` — full rewrite: online activation, domain binding, grace period, product-specific key format
- `packages/aero-installation/src/Installation/Steps/FinalizeStep.php` — write `aeos.mode` and `aeos.installed` files atomically

---

## Task 1: Fix Duplicate Submodules in aero-core/config/module.php

**Files:**
- Modify: `packages/aero-core/config/module.php`

These are active bugs causing duplicate navigation items in every tenant.

- [ ] **Step 1.1: Remove the first (incomplete) `system_health` block**

Open `packages/aero-core/config/module.php`. Find the block at approximately line 1038:
```php
[
    'code' => 'system_health',
    'name' => 'System Health',
    'description' => 'System monitoring, performance metrics, and diagnostic tools',
    'icon' => 'HeartIcon',
    'route' => '/system-health',
    'priority' => 15,
    'components' => [
        ['code' => 'overview', ...],
        ['code' => 'database', ...],
        ['code' => 'queue', ...],
        ['code' => 'cache', ...],
        ['code' => 'services', ...],
        ['code' => 'metrics', ...],
        ['code' => 'logs', ...],
        ['code' => 'alerts', ...],
    ],
],
```

Delete this entire block (the one with `priority => 15` and generic health components). Keep the second block (around line 1513, priority 23) which has `health_status`, `performance_metrics`, `storage_usage`, `scheduled_tasks`, and `cache_management` — those are more complete.

- [ ] **Step 1.2: Collapse duplicate `translations_i18n` / `translations` blocks**

There are two submodule definitions covering the same domain:
- `translations_i18n` (~line 714) with `delegated_to => 'aero-i18n'`
- `translations` (~line 1170) with no delegation

Delete the `translations` block (line ~1170) entirely. Keep `translations_i18n` — it is the authoritative one because it has the `delegated_to` key.

- [ ] **Step 1.3: Fix priority collisions**

Both `subscription` (line ~173) and `user_management` (line ~221) have `priority => 2`. Change `user_management` to `priority => 3`. Also audit all other submodule priorities for duplicates:
```
self_service     => 0
dashboard        => 1
subscription     => 2
user_management  => 3  ← was 2, fix this
authentication   => 4  (keep)
roles_permissions => 5
audit_logs       => 6
notifications    => 7
file_manager     => 8
organization     => 9
...
```
Renumber sequentially to eliminate all duplicates.

- [ ] **Step 1.4: Remove the `eam_integration` section**

Delete lines ~1840–1855 (the `eam_integration` array at the bottom). This is EAM-domain topology embedded in a generic foundation config. It does not belong here.

- [ ] **Step 1.5: Verify no runtime regressions**

```bash
php artisan route:list | head -20
php artisan config:clear
php artisan aero:verify-modules  # if command exists, otherwise check logs
```

Expected: no errors, no duplicate navigation keys in output.

- [ ] **Step 1.6: Commit**

```bash
git add packages/aero-core/config/module.php
git commit -m "fix(aero-core): remove duplicate system_health/translations submodules, fix priority collisions"
```

---

## Task 2: Fix `tenancy()` Direct Call in Module.php

**Files:**
- Modify: `packages/aero-core/src/Models/Module.php`

The `permissionRequirements()` relation calls `tenancy()->initialized` directly. In standalone mode without `stancl/tenancy`, this is a fatal undefined function call.

- [ ] **Step 2.1: Replace the direct `tenancy()` call**

In `packages/aero-core/src/Models/Module.php`, find the `permissionRequirements()` method (around line 101) and replace:

```php
// BEFORE (breaks in standalone mode)
public function permissionRequirements(): HasMany
{
    $relation = $this->hasMany(ModulePermission::class);

    if (tenancy()->initialized) {
        $relation->getQuery()->getModel()->setConnection('tenant');
    }

    return $relation;
}
```

```php
// AFTER (uses the abstraction you already built)
public function permissionRequirements(): HasMany
{
    $relation = $this->hasMany(ModulePermission::class);

    try {
        $scope = app(\Aero\Core\Contracts\TenantScopeInterface::class);
        if ($scope->inTenantContext() && !$scope->isStandaloneMode()) {
            $relation->getQuery()->getModel()->setConnection('tenant');
        }
    } catch (\Throwable) {
        // TenantScopeInterface not yet bound during early boot — skip
    }

    return $relation;
}
```

- [ ] **Step 2.2: Verify standalone boots without error**

```bash
php artisan tinker --execute="echo app(\Aero\Core\Contracts\TenantScopeInterface::class)->getMode();"
```

Expected output: `standalone`

- [ ] **Step 2.3: Commit**

```bash
git add packages/aero-core/src/Models/Module.php
git commit -m "fix(aero-core): replace tenancy() direct call with TenantScopeInterface in Module::permissionRequirements"
```

---

## Task 3: Fix the `__call` Stub Shape in AeroCoreServiceProvider

**Files:**
- Modify: `packages/aero-core/src/AeroCoreServiceProvider.php`

The stub returns `[]` for all methods. `ModuleAccessService::canAccessModule()` callers expect `['allowed' => bool, 'reason' => string]`. Getting `[]` causes undefined-key errors or silent bypass depending on how callers use the result.

- [ ] **Step 3.1: Replace both `__call` stubs with typed returns**

In `AeroCoreServiceProvider.php`, find the `ModuleAccessService` singleton closure (around line 121). Replace both anonymous class stubs (ModuleAccessService and RoleModuleAccessService) as follows:

```php
// ModuleAccessService stub — replace the inner anonymous class
return new class {
    private function deny(string $reason): array
    {
        return ['allowed' => false, 'reason' => $reason, 'message' => 'System not yet installed.'];
    }

    public function canAccessModule($user, string $moduleCode): array
    {
        return $this->deny('not_installed');
    }

    public function canAccessSubModule($user, string $moduleCode, string $subModuleCode): array
    {
        return $this->deny('not_installed');
    }

    public function canAccessComponent($user, string $m, string $sm, string $c): array
    {
        return $this->deny('not_installed');
    }

    public function canPerformAction($user, string $m, string $sm, string $c, string $a): array
    {
        return $this->deny('not_installed');
    }
};
```

```php
// RoleModuleAccessService stub — replace the inner anonymous class
return new class {
    public function canUserAccessModule(int $userId, string $moduleCode): bool { return false; }
    public function getUserAccessibleModules(int $userId): array { return []; }
    public function getFirstAccessibleRoute(int $userId): ?string { return null; }
    public function __call(string $method, array $args): mixed
    {
        return str_starts_with($method, 'get') ? ($method === 'getFirstAccessibleRoute' ? null : []) : false;
    }
};
```

- [ ] **Step 3.2: Verify `CheckModuleAccess` handles the stub correctly**

In `CheckModuleAccess::handle()`, find the line:
```php
if (! $accessCheck['allowed']) {
```

This already handles `['allowed' => false]` correctly. No change needed — just verify the key exists in both real and stub responses.

```bash
php artisan tinker --execute="
\$svc = app(\Aero\Core\Services\ModuleAccessService::class);
\$result = \$svc->canAccessModule(null, 'core');
var_dump(\$result['allowed']);
"
```

Expected (pre-install): `bool(false)`

- [ ] **Step 3.3: Commit**

```bash
git add packages/aero-core/src/AeroCoreServiceProvider.php
git commit -m "fix(aero-core): give ModuleAccessService pre-install stub correct return shapes"
```

---

## Task 4: Consolidate Mode Detection to a Single Source of Truth

**Files:**
- Modify: `packages/aero-installation/src/Installation/ModeDetector.php`
- Modify: `packages/aero-core/src/helpers.php` (add a note, no logic change)

Currently two mechanisms can disagree:
- `helpers.php` reads `storage/app/aeos.mode` — used by running app
- `ModeDetector.php` calls `class_exists('Aero\Platform\AeroPlatformServiceProvider')` — used by installer

The installer must write the mode file; thereafter the file is canonical. The class_exists check is a fallback for the installer's own initial detection, not for the running app.

- [ ] **Step 4.1: Update `ModeDetector` to use file-first, class_exists as fallback**

Replace `packages/aero-installation/src/Installation/ModeDetector.php` entirely:

```php
<?php

namespace Aero\Installation;

/**
 * ModeDetector
 *
 * Detects installation mode using the canonical aeos.mode file.
 * Falls back to class_exists check only during initial installation
 * before the mode file has been written.
 *
 * The file is written by FinalizeStep and is thereafter immutable.
 * Do NOT change mode detection logic in the running application —
 * use the helpers.php functions (aero_mode(), is_saas_mode(), is_standalone_mode()).
 */
class ModeDetector
{
    private string $modeFilePath;

    public function __construct(?string $modeFilePath = null)
    {
        $this->modeFilePath = $modeFilePath ?? storage_path('app/aeos.mode');
    }

    public function detect(): string
    {
        // 1. Canonical source: the mode file (set during installation)
        if (file_exists($this->modeFilePath)) {
            $mode = trim(file_get_contents($this->modeFilePath));
            if (in_array($mode, ['saas', 'standalone'], true)) {
                return $mode;
            }
        }

        // 2. Pre-install fallback: infer from package presence
        // Only reaches here during the very first run of the installer
        if (class_exists('Aero\\Platform\\AeroPlatformServiceProvider')) {
            return 'saas';
        }

        return 'standalone';
    }

    public function isSaaS(): bool { return $this->detect() === 'saas'; }
    public function isStandalone(): bool { return $this->detect() === 'standalone'; }
}
```

- [ ] **Step 4.2: Verify `aero_mode()` in helpers.php is consistent**

Open `packages/aero-core/src/helpers.php` and confirm the `aero_mode()` function reads `storage_path('app/aeos.mode')` and defaults to `'standalone'`. It is already correct — no change needed. Add a comment at the top:

```php
// CANONICAL MODE SOURCE: storage/app/aeos.mode (written by FinalizeStep during install).
// ModeDetector in aero-installation uses this file + class_exists fallback during installation only.
// The running application ALWAYS uses these helpers, never ModeDetector.
```

- [ ] **Step 4.3: Commit**

```bash
git add packages/aero-installation/src/Installation/ModeDetector.php
git add packages/aero-core/src/helpers.php
git commit -m "fix(aero-installation): consolidate mode detection — file-first, class_exists as installer-only fallback"
```

---

## Task 5: Fix Missing Dependency Declarations in Packages

**Files:**
- Modify: `packages/aero-automation/composer.json`
- Modify: `packages/aero-booking/composer.json`
- Modify: `packages/aero-custom-fields/composer.json`
- Modify: `packages/aero-forms/composer.json`
- Modify: `packages/aero-helpdesk/composer.json`
- Modify: `packages/aero-i18n/composer.json`
- Modify: `packages/aero-mobile/composer.json`
- Modify: `packages/aero-notifications/composer.json`
- Modify: `packages/aero-time-tracking/composer.json`
- Modify: `packages/aero-workflow/composer.json`
- Modify: `packages/aero-installation/composer.json`

Packages distributed on a marketplace must declare all dependencies. Without this, Composer installs in a fresh environment will silently succeed with broken packages.

- [ ] **Step 5.1: Audit each package for actual aero/core usage**

Run this for each package listed above:
```bash
grep -r "Aero\\\\Core" packages/aero-automation/src/ --include="*.php" -l
grep -r "use Aero\\\\Core" packages/aero-automation/src/ --include="*.php" | head -5
```

If any file in the package imports from `Aero\Core\*`, add `"aero/core": "*"` to `require`.

- [ ] **Step 5.2: Add missing deps to each `composer.json`**

For each package that uses `Aero\Core\*` (check each one individually using the grep above), add to its `require` block:

```json
"require": {
    "php": "^8.2",
    "laravel/framework": "^11.0|^12.0",
    "aero/core": "*"
}
```

For `aero-i18n` and `aero-notifications` specifically — these are depended on by `aero-core`. They should require `illuminate/support` and `illuminate/contracts` directly (already using Illuminate components), not `aero/core`, because that would create a circular dependency. Verify:
```bash
grep -r "Aero\\\\Core" packages/aero-i18n/src/ --include="*.php" -l
grep -r "Aero\\\\Core" packages/aero-notifications/src/ --include="*.php" -l
```
If they DO import from `Aero\Core`, document it but do not add the circular dep — create a contract interface in aero-core that they bind against instead (this is a follow-up architectural task, not blocking for marketplace release).

- [ ] **Step 5.3: Verify `aero-cms` removes its `aero/hrmac` direct dependency**

`aero-cms` currently has `"aero/hrmac": "*"` in its `require`. This is a cross-layer violation — a product module depending on an infrastructure package by name.

Open `packages/aero-cms/src/` and find where HRMAC is used:
```bash
grep -r "HRMAC\|hrmac\|RoleModuleAccess" packages/aero-cms/src/ --include="*.php" -l
```

For each usage, replace the direct `HRMAC::` call with a call through `app(\Aero\HRMAC\Contracts\RoleModuleAccessInterface::class)` — the interface is already registered as a singleton by `AeroCoreServiceProvider`. Then remove `"aero/hrmac": "*"` from `packages/aero-cms/composer.json`.

- [ ] **Step 5.4: Commit all dependency fixes together**

```bash
git add packages/aero-automation/composer.json packages/aero-booking/composer.json \
        packages/aero-custom-fields/composer.json packages/aero-forms/composer.json \
        packages/aero-helpdesk/composer.json packages/aero-mobile/composer.json \
        packages/aero-time-tracking/composer.json packages/aero-workflow/composer.json \
        packages/aero-installation/composer.json packages/aero-cms/composer.json
git commit -m "fix(packages): declare missing aero/core dependencies, remove aero-cms cross-layer dep on aero/hrmac"
```

---

## Task 6: Implement the License Service

**Files:**
- Create: `packages/aero-core/src/Contracts/LicenseServiceInterface.php`
- Create: `packages/aero-core/src/Exceptions/LicenseException.php`
- Create: `packages/aero-core/src/Services/License/LicenseValidator.php`
- Create: `packages/aero-core/src/Services/License/DomainBinding.php`
- Create: `packages/aero-core/src/Services/License/LicenseCache.php`
- Create: `packages/aero-core/src/Services/License/LicenseService.php`
- Create: `packages/aero-core/config/license.php`
- Modify: `packages/aero-core/src/AeroCoreServiceProvider.php`

The license service handles: format validation (offline), domain binding (write-once on activation), online activation (once per installation), and a 72-hour grace period for offline use. It is a no-op in SaaS mode.

- [ ] **Step 6.1: Write the interface**

Create `packages/aero-core/src/Contracts/LicenseServiceInterface.php`:

```php
<?php

namespace Aero\Core\Contracts;

use Aero\Core\Exceptions\LicenseException;

interface LicenseServiceInterface
{
    /**
     * Validate a license key format offline (no network call).
     * @throws LicenseException if format is invalid
     */
    public function validateFormat(string $licenseKey): void;

    /**
     * Activate a license key against the license server.
     * Stores activation result and binds the current domain.
     * Must be called once during installation.
     * @throws LicenseException if activation fails
     */
    public function activate(string $licenseKey, string $productId): void;

    /**
     * Check whether the current installation has a valid license.
     * Uses cached result; re-checks remotely at most once per 24 hours.
     * Returns true in SaaS mode unconditionally.
     */
    public function isValid(): bool;

    /**
     * Get a human-readable status for the license.
     * Returns one of: 'valid', 'grace', 'invalid', 'not_activated', 'saas'
     */
    public function status(): string;

    /**
     * Get remaining grace period seconds (0 if not in grace period).
     */
    public function graceSecondsRemaining(): int;
}
```

- [ ] **Step 6.2: Write the exception**

Create `packages/aero-core/src/Exceptions/LicenseException.php`:

```php
<?php

namespace Aero\Core\Exceptions;

class LicenseException extends \RuntimeException
{
    public static function invalidFormat(): self
    {
        return new self('License key format is invalid. Expected format: XXXXXXXX-XXXXXXXX-XXXXXXXX-XXXXXXXX');
    }

    public static function activationFailed(string $reason): self
    {
        return new self("License activation failed: {$reason}");
    }

    public static function domainMismatch(string $bound, string $current): self
    {
        return new self("License is bound to domain [{$bound}] but current domain is [{$current}]. Contact support to transfer your license.");
    }

    public static function expired(): self
    {
        return new self('Your license has expired. Please renew at aerosuite.com/renew');
    }

    public static function graceExpired(): self
    {
        return new self('License validation grace period has expired. Please ensure internet connectivity and restart.');
    }
}
```

- [ ] **Step 6.3: Write the offline validator**

Create `packages/aero-core/src/Services/License/LicenseValidator.php`:

```php
<?php

namespace Aero\Core\Services\License;

use Aero\Core\Exceptions\LicenseException;

class LicenseValidator
{
    // Format: XXXXXXXX-XXXXXXXX-XXXXXXXX-XXXXXXXX (alphanumeric segments, uppercase)
    private const FORMAT_PATTERN = '/^[A-Z0-9]{8}-[A-Z0-9]{8}-[A-Z0-9]{8}-[A-Z0-9]{8}$/';

    /**
     * Validate license key format offline.
     * @throws LicenseException
     */
    public function validateFormat(string $licenseKey): void
    {
        $key = strtoupper(trim($licenseKey));
        if (!preg_match(self::FORMAT_PATTERN, $key)) {
            throw LicenseException::invalidFormat();
        }
    }

    /**
     * Verify the Luhn-style checksum embedded in the key.
     * The last segment's first two characters encode a checksum of the first three segments.
     * This is offline-only — it does NOT verify ownership, only that the key was generated by us.
     */
    public function verifyChecksum(string $licenseKey): bool
    {
        $segments = explode('-', strtoupper(trim($licenseKey)));
        if (count($segments) !== 4) {
            return false;
        }

        $dataSegments = implode('', array_slice($segments, 0, 3));
        $expectedChecksum = strtoupper(substr(md5($dataSegments . config('license.checksum_salt', 'aero-license-salt')), 0, 2));
        $providedChecksum = substr($segments[3], 0, 2);

        return hash_equals($expectedChecksum, $providedChecksum);
    }
}
```

- [ ] **Step 6.4: Write the domain binding service**

Create `packages/aero-core/src/Services/License/DomainBinding.php`:

```php
<?php

namespace Aero\Core\Services\License;

class DomainBinding
{
    private string $bindingFilePath;

    public function __construct()
    {
        $this->bindingFilePath = storage_path('app/aeos.domain');
    }

    /**
     * Get the normalized domain hash for the current request/host.
     */
    public function currentDomainHash(): string
    {
        $host = $this->resolveHost();
        return hash('sha256', strtolower($host));
    }

    /**
     * Write the current domain hash to the binding file.
     * Call once during license activation.
     */
    public function bind(): void
    {
        file_put_contents($this->bindingFilePath, $this->currentDomainHash());
    }

    /**
     * Check if the current domain matches the bound domain.
     * Returns true if not yet bound (pre-activation).
     */
    public function matches(): bool
    {
        if (!file_exists($this->bindingFilePath)) {
            return true; // Not yet bound
        }

        $bound = trim(file_get_contents($this->bindingFilePath));
        return hash_equals($bound, $this->currentDomainHash());
    }

    /**
     * Get stored domain hash (for error messages).
     */
    public function boundHash(): ?string
    {
        return file_exists($this->bindingFilePath)
            ? trim(file_get_contents($this->bindingFilePath))
            : null;
    }

    private function resolveHost(): string
    {
        // CLI / queue workers: use APP_URL domain
        if (php_sapi_name() === 'cli') {
            return parse_url(config('app.url', 'http://localhost'), PHP_URL_HOST) ?? 'localhost';
        }
        return request()->getHost();
    }
}
```

- [ ] **Step 6.5: Write the license cache**

Create `packages/aero-core/src/Services/License/LicenseCache.php`:

```php
<?php

namespace Aero\Core\Services\License;

class LicenseCache
{
    private string $cacheFilePath;

    public function __construct()
    {
        $this->cacheFilePath = storage_path('app/aeos.license-cache');
    }

    /**
     * Store validation result with a timestamp.
     */
    public function store(array $result): void
    {
        $data = array_merge($result, ['cached_at' => time()]);
        file_put_contents($this->cacheFilePath, json_encode($data));
    }

    /**
     * Retrieve cached result if it is not older than $ttlSeconds.
     * Returns null if cache is absent or expired.
     */
    public function get(int $ttlSeconds = 86400): ?array
    {
        if (!file_exists($this->cacheFilePath)) {
            return null;
        }

        $data = json_decode(file_get_contents($this->cacheFilePath), true);
        if (!is_array($data) || !isset($data['cached_at'])) {
            return null;
        }

        if ((time() - $data['cached_at']) > $ttlSeconds) {
            return null; // Expired
        }

        return $data;
    }

    /**
     * Get the timestamp of the last successful validation (regardless of TTL).
     * Used to calculate grace period remaining.
     */
    public function lastSuccessAt(): ?int
    {
        $data = $this->get(PHP_INT_MAX); // No TTL for this call
        return ($data && $data['status'] === 'valid') ? $data['cached_at'] : null;
    }

    public function clear(): void
    {
        if (file_exists($this->cacheFilePath)) {
            unlink($this->cacheFilePath);
        }
    }
}
```

- [ ] **Step 6.6: Write the main LicenseService**

Create `packages/aero-core/src/Services/License/LicenseService.php`:

```php
<?php

namespace Aero\Core\Services\License;

use Aero\Core\Contracts\LicenseServiceInterface;
use Aero\Core\Exceptions\LicenseException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LicenseService implements LicenseServiceInterface
{
    public function __construct(
        private readonly LicenseValidator $validator,
        private readonly DomainBinding $domainBinding,
        private readonly LicenseCache $cache,
    ) {}

    public function validateFormat(string $licenseKey): void
    {
        $this->validator->validateFormat($licenseKey);
    }

    public function activate(string $licenseKey, string $productId): void
    {
        // 1. Format check
        $this->validator->validateFormat($licenseKey);

        // 2. Online activation
        $serverUrl = config('license.server_url');
        try {
            $response = Http::timeout(15)->post("{$serverUrl}/api/activate", [
                'license_key' => $licenseKey,
                'product_id'  => $productId,
                'domain'      => request()->getHost(),
                'php_version' => PHP_VERSION,
                'app_version' => config('app.version', '1.0.0'),
            ]);

            if (!$response->successful()) {
                $message = $response->json('message', 'Unknown error from license server');
                throw LicenseException::activationFailed($message);
            }

            $data = $response->json();

        } catch (LicenseException $e) {
            throw $e;
        } catch (\Throwable $e) {
            // Network failure during activation — do not block install; allow grace period
            Log::warning('License activation network failure', ['error' => $e->getMessage()]);
            $data = ['status' => 'grace', 'message' => 'Activated offline — please verify connectivity'];
        }

        // 3. Store activation data
        $this->storeActivation($licenseKey, $productId);

        // 4. Bind domain (write once)
        $this->domainBinding->bind();

        // 5. Cache result
        $this->cache->store(['status' => $data['status'] ?? 'valid', 'product_id' => $productId]);
    }

    public function isValid(): bool
    {
        // SaaS mode: always valid
        if (is_saas_mode()) {
            return true;
        }

        return in_array($this->status(), ['valid', 'grace', 'saas'], true);
    }

    public function status(): string
    {
        if (is_saas_mode()) {
            return 'saas';
        }

        // Check domain binding first (fast, no network)
        if (!$this->domainBinding->matches()) {
            return 'invalid';
        }

        // Check cache
        $cached = $this->cache->get(config('license.check_ttl_seconds', 86400));
        if ($cached !== null) {
            return $cached['status'];
        }

        // Try online check
        return $this->performOnlineCheck();
    }

    public function graceSecondsRemaining(): int
    {
        $lastSuccess = $this->cache->lastSuccessAt();
        if ($lastSuccess === null) {
            return 0;
        }

        $gracePeriod = config('license.grace_period_seconds', 72 * 3600); // 72 hours
        $elapsed = time() - $lastSuccess;
        return max(0, $gracePeriod - $elapsed);
    }

    private function performOnlineCheck(): string
    {
        $activation = $this->loadActivation();
        if (!$activation) {
            return 'not_activated';
        }

        $serverUrl = config('license.server_url');
        try {
            $response = Http::timeout(10)->post("{$serverUrl}/api/validate", [
                'license_key' => $activation['license_key'],
                'product_id'  => $activation['product_id'],
                'domain_hash' => $this->domainBinding->currentDomainHash(),
            ]);

            $status = $response->successful() ? ($response->json('status', 'invalid')) : 'invalid';

        } catch (\Throwable $e) {
            // Network failure: enter grace period
            Log::info('License server unreachable, entering grace period', ['error' => $e->getMessage()]);
            $gracePeriod = config('license.grace_period_seconds', 72 * 3600);
            $lastSuccess  = $this->cache->lastSuccessAt();
            $status = ($lastSuccess && (time() - $lastSuccess) < $gracePeriod) ? 'grace' : 'invalid';
        }

        $this->cache->store(['status' => $status, 'product_id' => $activation['product_id'] ?? null]);

        return $status;
    }

    private function storeActivation(string $licenseKey, string $productId): void
    {
        $path = storage_path('app/aeos.license');
        file_put_contents($path, json_encode([
            'license_key' => $licenseKey,
            'product_id'  => $productId,
            'activated_at' => now()->toIso8601String(),
        ]));
    }

    private function loadActivation(): ?array
    {
        $path = storage_path('app/aeos.license');
        if (!file_exists($path)) {
            return null;
        }
        return json_decode(file_get_contents($path), true);
    }
}
```

- [ ] **Step 6.7: Create the license config file**

Create `packages/aero-core/config/license.php`:

```php
<?php

return [
    /*
     * URL of your license server.
     * Override per-product in the host app's config/license.php.
     */
    'server_url' => env('LICENSE_SERVER_URL', 'https://licenses.aerosuite.com'),

    /*
     * How long (seconds) to trust a cached license validation result.
     * Default: 24 hours. After this, the service will re-check online.
     */
    'check_ttl_seconds' => env('LICENSE_CHECK_TTL', 86400),

    /*
     * How long (seconds) to allow operation without a successful online check.
     * Default: 72 hours. After this, the app enters a degraded/locked state.
     */
    'grace_period_seconds' => env('LICENSE_GRACE_PERIOD', 72 * 3600),

    /*
     * Salt used for offline checksum verification of license key format.
     * MUST match the value used by your license key generator.
     */
    'checksum_salt' => env('LICENSE_CHECKSUM_SALT', 'aero-license-salt'),

    /*
     * Set to true to bypass all license checks (development/testing only).
     * NEVER set this to true in production builds.
     */
    'bypass' => env('LICENSE_BYPASS', false),
];
```

- [ ] **Step 6.8: Register LicenseService in AeroCoreServiceProvider**

In `packages/aero-core/src/AeroCoreServiceProvider.php`, add to the `register()` method:

```php
// After existing singleton registrations, add:
$this->mergeConfigFrom(__DIR__.'/../config/license.php', 'license');

$this->app->singleton(\Aero\Core\Contracts\LicenseServiceInterface::class, function ($app) {
    return new \Aero\Core\Services\License\LicenseService(
        new \Aero\Core\Services\License\LicenseValidator(),
        new \Aero\Core\Services\License\DomainBinding(),
        new \Aero\Core\Services\License\LicenseCache(),
    );
});
```

- [ ] **Step 6.9: Verify service resolves without errors**

```bash
php artisan tinker --execute="
\$svc = app(\Aero\Core\Contracts\LicenseServiceInterface::class);
echo \$svc->status();
"
```

Expected: `not_activated` (standalone, no license stored) or `saas` (SaaS mode).

- [ ] **Step 6.10: Commit**

```bash
git add packages/aero-core/src/Contracts/LicenseServiceInterface.php \
        packages/aero-core/src/Exceptions/LicenseException.php \
        packages/aero-core/src/Services/License/ \
        packages/aero-core/config/license.php \
        packages/aero-core/src/AeroCoreServiceProvider.php
git commit -m "feat(aero-core): implement LicenseService with offline validation, domain binding, online activation, and grace period"
```

---

## Task 7: Rewrite LicenseStep in the Installer

**Files:**
- Modify: `packages/aero-installation/src/Installation/Steps/LicenseStep.php`

The current step: reads `env('LICENSE_KEY')` directly, skips silently if empty, has no online activation, no domain binding. This will not pass marketplace review and does not protect the product.

- [ ] **Step 7.1: Rewrite LicenseStep to use LicenseService**

Replace `packages/aero-installation/src/Installation/Steps/LicenseStep.php` entirely:

```php
<?php

namespace Aero\Installation\Installation\Steps;

use Aero\Core\Contracts\LicenseServiceInterface;
use Aero\Core\Exceptions\LicenseException;

/**
 * LicenseStep
 *
 * Activates the product license during installation.
 * - In SaaS mode: skipped (platform manages licensing).
 * - In Standalone mode: required. Calls LicenseService::activate() which
 *   validates format offline, activates online, and binds the domain.
 *   If the license server is unreachable, installation continues with a
 *   grace period — the next boot will retry online validation.
 */
class LicenseStep extends BaseInstallationStep
{
    public function name(): string { return 'license'; }
    public function description(): string { return 'Activate product license'; }
    public function order(): int { return 3; }
    public function dependencies(): array { return ['config', 'database']; }
    public function canSkip(): bool { return false; } // No longer skippable
    public function isRetriable(): bool { return true; }

    public function execute(): array
    {
        // SaaS installations do not need a license key
        if (is_saas_mode()) {
            $this->log('License step skipped — SaaS mode');
            return ['license_status' => 'saas', 'reason' => 'SaaS mode'];
        }

        $licenseKey = $this->getLicenseKey();
        $productId  = config('product.id', 'aero-suite'); // reads from host app's config/product.php

        if (empty($licenseKey)) {
            // Allow installation without a key but mark as not_activated.
            // The admin can activate via Settings > License later.
            $this->log('No license key provided — installation will continue in trial/grace mode');
            return [
                'license_status' => 'not_activated',
                'message'        => 'Activate your license from Settings > License Management after installation.',
            ];
        }

        try {
            /** @var LicenseServiceInterface $licenseService */
            $licenseService = app(LicenseServiceInterface::class);
            $licenseService->activate($licenseKey, $productId);

            $this->log('License activated successfully', ['product_id' => $productId]);
            return [
                'license_status' => 'activated',
                'product_id'     => $productId,
            ];

        } catch (LicenseException $e) {
            // License-specific failure — tell the user exactly what went wrong
            throw new \Exception($e->getMessage());
        } catch (\Throwable $e) {
            // Unexpected failure — log and continue (do not block installation)
            $this->log('License activation encountered an unexpected error: ' . $e->getMessage());
            return [
                'license_status' => 'activation_error',
                'message'        => 'License will be in grace mode. Check Settings > License after installation.',
            ];
        }
    }

    public function validate(): bool
    {
        if (is_saas_mode()) {
            return true;
        }

        $licenseFile = storage_path('app/aeos.license');
        return file_exists($licenseFile) || empty($this->getLicenseKey());
    }

    private function getLicenseKey(): string
    {
        // Priority: posted installer form data > .env > empty string
        return request()->input('license_key')
            ?? env('LICENSE_KEY', '');
    }
}
```

- [ ] **Step 7.2: Commit**

```bash
git add packages/aero-installation/src/Installation/Steps/LicenseStep.php
git commit -m "feat(aero-installation): rewrite LicenseStep — online activation, domain binding, grace mode fallback"
```

---

## Task 8: Create the Product Manifest System

**Files:**
- Create: `packages/aero-core/src/Manifests/ProductManifest.php`
- Create: `packages/aero-core/src/Services/ProductManifestLoader.php`
- Create: `config/product.php` in `aeos365` (the host app)

A "product" is a named bundle of modules sold as a unit. The host app declares which product it is. This declaration drives: license product_id, what modules are mandatory, what the update server is.

- [ ] **Step 8.1: Create the `ProductManifest` value object**

Create `packages/aero-core/src/Manifests/ProductManifest.php`:

```php
<?php

namespace Aero\Core\Manifests;

final class ProductManifest
{
    /**
     * @param string   $id             Matches license server product ID (e.g. 'aero-hrm')
     * @param string   $name           Human-readable name (e.g. 'Aero HRM Suite')
     * @param string   $version        Current installed version (semver)
     * @param string[] $bundledModules Module codes included in this product
     * @param string   $licenseServer  URL of the license server for this product
     * @param string   $updateServer   URL of the update server for this product
     * @param string   $edition        'saas' | 'standalone' | 'both'
     */
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $version,
        public readonly array  $bundledModules,
        public readonly string $licenseServer,
        public readonly string $updateServer,
        public readonly string $edition,
    ) {}

    public static function fromConfig(array $config): self
    {
        $required = ['id', 'name', 'version', 'bundled_modules', 'license_server', 'update_server', 'edition'];
        foreach ($required as $key) {
            if (empty($config[$key])) {
                throw new \InvalidArgumentException("Product manifest missing required key: [{$key}]");
            }
        }

        return new self(
            id:             $config['id'],
            name:           $config['name'],
            version:        $config['version'],
            bundledModules: $config['bundled_modules'],
            licenseServer:  $config['license_server'],
            updateServer:   $config['update_server'],
            edition:        $config['edition'],
        );
    }

    public function supportsStandalone(): bool
    {
        return in_array($this->edition, ['standalone', 'both'], true);
    }

    public function supportsSaaS(): bool
    {
        return in_array($this->edition, ['saas', 'both'], true);
    }
}
```

- [ ] **Step 8.2: Create the `ProductManifestLoader`**

Create `packages/aero-core/src/Services/ProductManifestLoader.php`:

```php
<?php

namespace Aero\Core\Services;

use Aero\Core\Manifests\ProductManifest;

class ProductManifestLoader
{
    private ?ProductManifest $cached = null;

    public function load(): ProductManifest
    {
        if ($this->cached !== null) {
            return $this->cached;
        }

        $config = config('product');

        if (empty($config)) {
            // No product.php — this is the full monorepo SaaS platform (aeos365)
            $config = $this->defaultPlatformManifest();
        }

        return $this->cached = ProductManifest::fromConfig($config);
    }

    private function defaultPlatformManifest(): array
    {
        return [
            'id'              => 'aeos-platform',
            'name'            => 'AEOS365 Platform',
            'version'         => config('app.version', '1.0.0'),
            'bundled_modules' => ['*'], // all modules
            'license_server'  => config('license.server_url', 'https://licenses.aerosuite.com'),
            'update_server'   => env('UPDATE_SERVER_URL', 'https://updates.aerosuite.com'),
            'edition'         => 'both',
        ];
    }
}
```

- [ ] **Step 8.3: Register ProductManifestLoader and bind product config to license**

In `AeroCoreServiceProvider::register()`, add:

```php
$this->app->singleton(ProductManifestLoader::class);

// Bind license.server_url from product manifest if not overridden in .env
$this->app->booted(function ($app) {
    if (!env('LICENSE_SERVER_URL')) {
        try {
            $manifest = $app->make(ProductManifestLoader::class)->load();
            config(['license.server_url' => $manifest->licenseServer]);
        } catch (\Throwable) {
            // No product.php — use default from license.php config
        }
    }
});
```

- [ ] **Step 8.4: Create the host-app `config/product.php` for the full AEOS365 platform**

In the `aeos365` host application (at `c:/laragon/www/aeos365/config/product.php`):

```php
<?php

/*
|--------------------------------------------------------------------------
| Product Manifest
|--------------------------------------------------------------------------
|
| Declares what product this installation represents.
| This file is read by the LicenseService, installer, and packaging tools.
|
| For marketplace standalone editions:
|   - Set 'id' to match the product ID registered in your license server
|   - Set 'edition' to 'standalone'
|   - Set 'bundled_modules' to only the modules included in that edition
|
*/

return [
    'id'      => 'aeos-platform',
    'name'    => 'AEOS365 Platform',
    'version' => '1.0.0',
    'edition' => 'both', // 'saas' | 'standalone' | 'both'

    /*
     * Module codes that are bundled with this product.
     * Use ['*'] to include all installed modules.
     */
    'bundled_modules' => ['*'],

    /*
     * License server — override with LICENSE_SERVER_URL env var.
     */
    'license_server' => env('LICENSE_SERVER_URL', 'https://licenses.aerosuite.com'),

    /*
     * Update server — where update metadata and downloads are served.
     */
    'update_server' => env('UPDATE_SERVER_URL', 'https://updates.aerosuite.com'),
];
```

- [ ] **Step 8.5: Commit**

```bash
git add packages/aero-core/src/Manifests/ProductManifest.php \
        packages/aero-core/src/Services/ProductManifestLoader.php \
        packages/aero-core/src/AeroCoreServiceProvider.php
# Also commit the host app product.php
git add c:/laragon/www/aeos365/config/product.php 2>/dev/null || true
git commit -m "feat(aero-core): add ProductManifest + ProductManifestLoader; add product.php to aeos365 host"
```

---

## Task 9: Module Manifest Validator (CI Gate)

**Files:**
- Create: `packages/aero-core/src/Console/Commands/ValidateManifests.php`
- Modify: `packages/aero-core/src/AeroCoreServiceProvider.php`

This artisan command lints every `config/module.php` in the monorepo and fails with a human-readable report. Run in CI to prevent the bugs found in Task 1 from recurring.

- [ ] **Step 9.1: Create the ValidateManifests command**

Create `packages/aero-core/src/Console/Commands/ValidateManifests.php`:

```php
<?php

namespace Aero\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ValidateManifests extends Command
{
    protected $signature = 'aero:validate-manifests {--strict : Fail on warnings too}';
    protected $description = 'Validate all module.php manifests for structural correctness';

    private array $errors   = [];
    private array $warnings = [];

    public function handle(): int
    {
        $packagesPath = base_path('packages');
        $manifests    = glob("{$packagesPath}/*/config/module.php");

        if (empty($manifests)) {
            $this->warn('No module.php manifests found in packages/*/config/');
            return self::SUCCESS;
        }

        foreach ($manifests as $manifestPath) {
            $this->validateManifest($manifestPath);
        }

        $this->reportResults();

        $hasErrors = count($this->errors) > 0;
        $hasWarnings = count($this->warnings) > 0;

        if ($hasErrors || ($this->option('strict') && $hasWarnings)) {
            return self::FAILURE;
        }

        $this->info('All manifests valid.');
        return self::SUCCESS;
    }

    private function validateManifest(string $path): void
    {
        $packageName = basename(dirname(dirname($path)));
        $config = require $path;

        $this->checkRequired($packageName, $config, ['code', 'scope', 'name', 'version', 'priority']);
        $this->checkSubmoduleDuplicates($packageName, $config['submodules'] ?? []);
        $this->checkPriorityDuplicates($packageName, $config['submodules'] ?? []);
        $this->checkDelegations($packageName, $config['submodules'] ?? []);
        $this->checkScope($packageName, $config);
    }

    private function checkRequired(string $pkg, array $config, array $keys): void
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $config)) {
                $this->errors[] = "[{$pkg}] Missing required key: [{$key}]";
            }
        }
    }

    private function checkSubmoduleDuplicates(string $pkg, array $submodules): void
    {
        $codes = array_column($submodules, 'code');
        $duplicates = array_filter(array_count_values($codes), fn($c) => $c > 1);
        foreach (array_keys($duplicates) as $code) {
            $this->errors[] = "[{$pkg}] Duplicate submodule code: [{$code}]";
        }
    }

    private function checkPriorityDuplicates(string $pkg, array $submodules): void
    {
        $priorities = array_column($submodules, 'priority');
        $duplicates = array_filter(array_count_values($priorities), fn($c) => $c > 1);
        foreach (array_keys($duplicates) as $priority) {
            $this->warnings[] = "[{$pkg}] Duplicate submodule priority: [{$priority}]";
        }
    }

    private function checkDelegations(string $pkg, array $submodules): void
    {
        foreach ($submodules as $sub) {
            if (!empty($sub['delegated_to'])) {
                $delegated = $sub['delegated_to'];
                $packagesPath = base_path('packages');
                if (!is_dir("{$packagesPath}/{$delegated}")) {
                    $this->warnings[] = "[{$pkg}] Submodule [{$sub['code']}] delegated to [{$delegated}] but that package directory does not exist";
                }
            }
        }
    }

    private function checkScope(string $pkg, array $config): void
    {
        $validScopes = ['tenant', 'platform', 'infrastructure', 'both'];
        if (!in_array($config['scope'] ?? '', $validScopes, true)) {
            $this->errors[] = "[{$pkg}] Invalid scope [{$config['scope']}]. Must be one of: " . implode(', ', $validScopes);
        }
    }

    private function reportResults(): void
    {
        foreach ($this->errors as $error) {
            $this->error("ERROR: {$error}");
        }
        foreach ($this->warnings as $warning) {
            $this->warn("WARN:  {$warning}");
        }

        $errorCount = count($this->errors);
        $warnCount  = count($this->warnings);
        $this->line("{$errorCount} error(s), {$warnCount} warning(s)");
    }
}
```

- [ ] **Step 9.2: Register the command**

In `AeroCoreServiceProvider::registerCommands()`, add:

```php
Console\Commands\ValidateManifests::class,
```

- [ ] **Step 9.3: Run the validator and confirm it catches the bugs fixed in Task 1**

```bash
# First run BEFORE fixes (should fail):
php artisan aero:validate-manifests --strict

# Expected output should include:
# ERROR: [aero-core] Duplicate submodule code: [system_health]
# WARN:  [aero-core] Duplicate submodule priority: [2]

# After Task 1 fixes:
php artisan aero:validate-manifests --strict
# Expected: "0 error(s), 0 warning(s)"
```

- [ ] **Step 9.4: Commit**

```bash
git add packages/aero-core/src/Console/Commands/ValidateManifests.php \
        packages/aero-core/src/AeroCoreServiceProvider.php
git commit -m "feat(aero-core): add aero:validate-manifests command for CI manifest linting"
```

---

## Task 10: Create the Product Packaging Command

**Files:**
- Create: `packages/aero-core/src/Console/Commands/PackageProduct.php`

This command produces a distributable archive of the application suitable for marketplace upload. It excludes dev dependencies, tests, `.git`, CI configs, and pre-compiles the asset manifest reference.

- [ ] **Step 10.1: Create the PackageProduct command**

Create `packages/aero-core/src/Console/Commands/PackageProduct.php`:

```php
<?php

namespace Aero\Core\Console\Commands;

use Aero\Core\Services\ProductManifestLoader;
use Illuminate\Console\Command;

class PackageProduct extends Command
{
    protected $signature = 'aero:package-product
                            {--output= : Output directory for the archive (default: ./dist)}
                            {--no-verify : Skip manifest validation before packaging}';

    protected $description = 'Generate a distributable product archive for marketplace distribution';

    public function handle(ProductManifestLoader $loader): int
    {
        $manifest = $loader->load();

        if (!$manifest->supportsStandalone()) {
            $this->error("Product [{$manifest->id}] is SaaS-only and cannot be packaged for standalone distribution.");
            return self::FAILURE;
        }

        // Step 1: Validate manifests unless skipped
        if (!$this->option('no-verify')) {
            $this->info('Validating module manifests...');
            $result = $this->callSilent('aero:validate-manifests', ['--strict' => true]);
            if ($result !== self::SUCCESS) {
                $this->error('Manifest validation failed. Fix errors before packaging. Run: php artisan aero:validate-manifests --strict');
                return self::FAILURE;
            }
        }

        $outputDir   = $this->option('output') ?? base_path('dist');
        $archiveName = "{$manifest->id}-v{$manifest->version}-standalone.zip";
        $archivePath = "{$outputDir}/{$archiveName}";

        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $this->info("Packaging [{$manifest->name}] v{$manifest->version}...");

        // Step 2: Dump optimized autoloader (production)
        $this->info('Dumping optimized autoloader...');
        exec('composer dump-autoload --optimize --no-dev 2>&1', $output, $code);
        if ($code !== 0) {
            $this->error('composer dump-autoload failed: ' . implode("\n", $output));
            return self::FAILURE;
        }

        // Step 3: Create archive
        $this->info("Creating archive: {$archivePath}");
        $excludes = $this->buildExcludes();
        $basePath = base_path();

        $zipCommand = "cd {$basePath} && zip -r {$archivePath} . " . implode(' ', $excludes);
        exec($zipCommand, $zipOutput, $zipCode);

        if ($zipCode !== 0) {
            $this->error('Archive creation failed.');
            return self::FAILURE;
        }

        $sizeMb = round(filesize($archivePath) / 1024 / 1024, 2);
        $this->info("Archive created: {$archivePath} ({$sizeMb} MB)");

        // Step 4: Restore dev autoloader
        exec('composer dump-autoload 2>&1');

        $this->newLine();
        $this->table(
            ['Property', 'Value'],
            [
                ['Product', $manifest->name],
                ['Version', $manifest->version],
                ['Product ID', $manifest->id],
                ['Archive', $archivePath],
                ['Size', "{$sizeMb} MB"],
            ]
        );

        return self::SUCCESS;
    }

    private function buildExcludes(): array
    {
        $excludeDirs = [
            '.git', '.github', 'node_modules', 'dist',
            'tests', 'packages/*/tests',
            '.claude', 'docs/superpowers',
            'storage/logs/*', 'storage/framework/cache/*',
            'storage/framework/sessions/*',
        ];

        $excludeFiles = [
            '.env', '.env.example', '*.test', '*.spec',
            'phpunit.xml', 'phpunit.xml.dist',
            'deptrac.yaml', '.php-cs-fixer.php',
            'package.json', 'package-lock.json', 'vite.config.*',
        ];

        $excludeArgs = [];
        foreach (array_merge($excludeDirs, $excludeFiles) as $pattern) {
            $excludeArgs[] = "--exclude=\"{$pattern}\"";
        }

        return $excludeArgs;
    }
}
```

- [ ] **Step 10.2: Register the command**

In `AeroCoreServiceProvider::registerCommands()`, add:

```php
Console\Commands\PackageProduct::class,
```

- [ ] **Step 10.3: Test the command (dry run)**

```bash
php artisan aero:package-product --output=/tmp/aero-dist --no-verify
```

Expected: archive created at `/tmp/aero-dist/aeos-platform-v1.0.0-standalone.zip` (or similar). Inspect the ZIP and verify `.env` is excluded and `vendor/` is included with production-only deps.

- [ ] **Step 10.4: Commit**

```bash
git add packages/aero-core/src/Console/Commands/PackageProduct.php \
        packages/aero-core/src/AeroCoreServiceProvider.php
git commit -m "feat(aero-core): add aero:package-product command for marketplace distribution"
```

---

## Task 11: Boot-Time License Enforcement in Standalone Mode

**Files:**
- Modify: `packages/aero-core/src/Http/Middleware/HandleInertiaRequests.php` OR a new middleware
- Create: `packages/aero-core/src/Http/Middleware/EnforceLicense.php`
- Modify: `packages/aero-core/src/AeroCoreServiceProvider.php`

The license should be checked on every web request in standalone mode. Invalid licenses should show a friendly "activate your license" page, not a crash or a silent bypass.

- [ ] **Step 11.1: Create `EnforceLicense` middleware**

Create `packages/aero-core/src/Http/Middleware/EnforceLicense.php`:

```php
<?php

namespace Aero\Core\Http\Middleware;

use Aero\Core\Contracts\LicenseServiceInterface;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceLicense
{
    // Routes exempt from license enforcement
    private const EXEMPT_ROUTES = [
        'install*',
        'api/version/check',
        'api/error-log',
        'aero-core/health',
        'license/activate',  // allow in-app activation page
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // SaaS mode: never enforce here (platform handles billing)
        if (is_saas_mode()) {
            return $next($request);
        }

        // Bypass in local/testing environments
        if (config('license.bypass', false) || app()->environment('testing')) {
            return $next($request);
        }

        // Exempt installation and health routes
        foreach (self::EXEMPT_ROUTES as $pattern) {
            if ($request->is($pattern)) {
                return $next($request);
            }
        }

        /** @var LicenseServiceInterface $license */
        $license = app(LicenseServiceInterface::class);
        $status  = $license->status();

        if ($status === 'valid' || $status === 'not_activated') {
            // 'not_activated' allows access so admins can activate after install
            return $next($request);
        }

        if ($status === 'grace') {
            $remaining = $license->graceSecondsRemaining();
            $hours = ceil($remaining / 3600);
            // Allow access but attach a warning to the Inertia shared data
            // This will be read by the frontend to show a banner
            session()->flash('license_warning',
                "License verification pending. {$hours} hour(s) remaining in grace period."
            );
            return $next($request);
        }

        // 'invalid', 'expired' — block access
        if ($request->header('X-Inertia')) {
            return response()->json([
                'component' => 'License/LicenseRequired',
                'props'     => ['status' => $status],
                'url'       => $request->url(),
                'version'   => '',
            ], 402, ['X-Inertia' => 'true']);
        }

        return response()->view('aero-core::license.required', ['status' => $status], 402);
    }
}
```

- [ ] **Step 11.2: Register `EnforceLicense` in the web middleware group**

In `AeroCoreServiceProvider::registerMiddleware()`, after registering the existing middleware aliases, add:

```php
// License enforcement runs on all web requests in standalone mode
// It is a no-op in SaaS mode (checked inside the middleware)
$router->pushMiddlewareToGroup('web', EnforceLicense::class);
```

- [ ] **Step 11.3: Create a minimal blade view for the blocked state**

Create `packages/aero-core/resources/views/license/required.blade.php`:

```blade
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>License Required</title>
    <style>
        body { font-family: system-ui, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; background: #f9fafb; }
        .card { background: white; padding: 2rem; border-radius: 1rem; max-width: 480px; text-align: center; box-shadow: 0 4px 24px rgba(0,0,0,.08); }
        h1 { color: #111; margin-bottom: .5rem; }
        p { color: #6b7280; margin-bottom: 1.5rem; }
        a { display: inline-block; background: #2563eb; color: white; padding: .75rem 2rem; border-radius: .5rem; text-decoration: none; }
    </style>
</head>
<body>
<div class="card">
    <h1>License Verification Required</h1>
    <p>
        @if($status === 'expired')
            Your license has expired. Please renew to continue using this software.
        @elseif($status === 'invalid')
            Your license could not be verified. Please check your license key.
        @else
            Please activate your license to continue.
        @endif
    </p>
    <a href="/license/activate">Activate License</a>
</div>
</body>
</html>
```

- [ ] **Step 11.4: Verify enforcement works**

Temporarily set `LICENSE_BYPASS=false` in `.env` and remove `storage/app/aeos.license` if it exists. Load any authenticated page. Expected: the license blade view is served (402) OR the Inertia `License/LicenseRequired` component.

Then restore your license file or set `LICENSE_BYPASS=true` for development.

- [ ] **Step 11.5: Commit**

```bash
git add packages/aero-core/src/Http/Middleware/EnforceLicense.php \
        packages/aero-core/resources/views/license/required.blade.php \
        packages/aero-core/src/AeroCoreServiceProvider.php
git commit -m "feat(aero-core): add EnforceLicense middleware — blocks invalid/expired licenses, grace period warning, SaaS no-op"
```

---

## Task 12: Wire `aero:validate-manifests` into CI

**Files:**
- Modify: `.github/workflows/ci.yml` (or equivalent CI config)

The manifest validator must run as a pre-merge gate so bugs like the duplicate `system_health` can never be merged again.

- [ ] **Step 12.1: Find or create the CI workflow file**

```bash
ls .github/workflows/ 2>/dev/null || ls .gitlab-ci.yml 2>/dev/null || echo "No CI file found — create one"
```

- [ ] **Step 12.2: Add the manifest validation step**

In your CI pipeline (adapt syntax for GitHub Actions / GitLab CI / Bitbucket):

```yaml
# GitHub Actions example — add to existing workflow
- name: Validate module manifests
  run: php artisan aero:validate-manifests --strict
  # This runs BEFORE tests — manifest errors block everything
```

For GitLab CI:
```yaml
validate-manifests:
  stage: lint
  script:
    - php artisan aero:validate-manifests --strict
  only:
    - merge_requests
    - main
```

- [ ] **Step 12.3: Commit**

```bash
git add .github/workflows/ 2>/dev/null || git add .gitlab-ci.yml 2>/dev/null || true
git commit -m "ci: add aero:validate-manifests --strict as pre-merge gate"
```

---

## Self-Review

**Spec coverage check:**

| Requirement | Covered by |
|---|---|
| Standalone mode must not crash | Tasks 2, 3, 4 (tenancy() fix, stub fix, mode detection) |
| Module.php bugs (duplicate nav items) | Task 1 |
| Platform permission bypass | Not covered — documented as CRITICAL in audit, deferred. Add to backlog. |
| Product definition for marketplace | Task 8 |
| License system with domain binding | Tasks 6, 7, 11 |
| Online activation + offline grace | Task 6 (LicenseService) |
| Packaging command for distribution | Task 10 |
| Dependency declarations fixed | Task 5 |
| CI enforcement | Tasks 9, 12 |
| aero-core upward dep on aero/i18n | Not covered — deferred to architectural refactor plan. |
| aero-contracts extraction | Not covered — deferred. Does not block marketplace launch. |
| deptrac boundary enforcement | Not covered — deferred. |

**Deferred items (separate plan recommended):**
1. `aero-contracts` package extraction (interfaces only) — architectural refactor
2. `aero-core` upward dependency inversion (`aero/i18n`, `aero/notifications`)
3. Platform permission bypass (landlord TODO in CheckModuleAccess)
4. deptrac architectural boundary CI gate
5. License server implementation (the server-side of the activation API)

**Placeholder scan:** No TBD/TODO placeholders present. All code blocks are complete.

**Type consistency:** `LicenseServiceInterface::status()` returns `string` — `EnforceLicense` uses `'valid' | 'grace' | 'invalid' | 'not_activated' | 'saas'`. `LicenseService::status()` returns these same values. Consistent.

**ProductManifest.fromConfig()** is called by `ProductManifestLoader::load()` with `config('product')` — the `config/product.php` in Task 8.4 provides all required keys. Consistent.
