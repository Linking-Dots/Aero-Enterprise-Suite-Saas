# Plan K — aero-contracts Package Extraction

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Extract all stable, cross-package interfaces from `aero-core/src/Contracts/` into a new zero-dependency `packages/aero-contracts/` package, pin all `aero/core: "*"` wildcards to `aero/core: "^1.0"` semver, and update every import across the monorepo atomically — **no backward-compat shims, clean cut**.

**Architecture:** `aero-contracts` requires only `illuminate/support ^11|^12` and `illuminate/database ^11|^12` (much lighter than `laravel/framework`). The 18 moved interfaces/enums are deleted from aero-core immediately; all 96 import sites across the monorepo are updated in a single automated pass. The net result: every package declares `"aero/contracts": "^1.0"` for contract imports and `"aero/core": "^1.0"` for runtime code — proper semver, no diamond conflicts, no migration shims.

**Tech Stack:** PHP 8.2, Composer 2, `illuminate/support ^11|^12`, `illuminate/database ^11|^12`. No new runtime packages added to the application.

**Prerequisite:** Plans A–J merged to `main`. Run `php artisan config:clear` after each task.

---

## What Moves and What Stays

### Moving to `Aero\Contracts\` (18 items)

| Old path in aero-core | Class/Interface | Reason to move |
|---|---|---|
| `Contracts/TenantScopeInterface.php` | interface | Used by 20+ services across all packages |
| `Contracts/LicenseServiceInterface.php` | interface | Used by aero-installation, aero-core |
| `Contracts/ProductAccessInterface.php` | interface | Used by middleware, aero-platform |
| `Contracts/PlanCatalogInterface.php` | interface | Uses `Collection` (illuminate/support) |
| `Contracts/DomainContextContract.php` | interface | Used by middleware, aero-platform |
| `Contracts/DomainEventContract.php` | interface | Used by event classes across modules |
| `Contracts/ModuleSummaryProvider.php` | interface | Used by dashboard summary providers in 8+ packages |
| `Contracts/ModuleProviderInterface.php` | interface | Used by module service providers |
| `Contracts/Searchable.php` | interface | Used by models across packages |
| `Contracts/NotificationChannelInterface.php` | interface | Notification channel contract |
| `Contracts/NotificationRoutingContract.php` | interface | Uses `Collection` |
| `Contracts/MailContextResolverInterface.php` | interface | Created in Plan J |
| `Contracts/SmsContextResolverInterface.php` | interface | Created in Plan J |
| `Contracts/MailSenderInterface.php` | interface | Created in Plan J |
| `Contracts/SmsGatewayInterface.php` | interface | Created in Plan J |
| `Contracts/TranslationDriverInterface.php` | interface | Created in Plan J |
| `Contracts/CoreWidgetCategory.php` | **enum** | Used by ALL widget classes across modules |
| `Contracts/DashboardWidgetInterface.php` | interface | Used by ALL widget classes; references `CoreWidgetCategory` |

### Staying in `Aero\Core\Contracts\` (4 items)

| File | Reason to stay |
|---|---|
| `AbstractDashboardWidget.php` | Abstract class with `Illuminate\Support\Facades\{DB,Log,Schema}` — framework-heavy |
| `UserContract.php` | Uses `Illuminate\Database\Eloquent\Collection` — Eloquent-typed, app-layer |
| `UserRepositoryContract.php` | Uses `Illuminate\Database\Eloquent\Builder` — Eloquent-typed |
| `EmployeeServiceContract.php` | HRM domain-specific, uses `Collection` |

---

## File Map

**New files:**
- `packages/aero-contracts/composer.json`
- `packages/aero-contracts/src/TenantScopeInterface.php` … (18 files)
- `packages/aero-core/src/contract_aliases.php`

**Modified:**
- `packages/aero-core/composer.json` (add `aero/contracts ^1.0` dep + `contract_aliases.php` in autoload.files)
- `packages/aero-*/composer.json` (30 packages: add `aero/contracts ^1.0`; change `aero/core: *` → `aero/core: ^1.0`)
- Every `.php` file in the monorepo that imports a moved contract (96 files, automated)

**Deleted from aero-core:**
- `packages/aero-core/src/Contracts/TenantScopeInterface.php` … (18 files deleted, replaced by aliases)

---

## Task K1: Create `aero-contracts` Package Scaffold

**Files:**
- Create: `packages/aero-contracts/composer.json`

- [ ] **Step K1.1: Create the package manifest**

Create `packages/aero-contracts/composer.json`:

```json
{
    "name": "aero/contracts",
    "description": "Shared interface contracts for all Aero packages — zero Laravel framework dependency",
    "type": "library",
    "version": "1.0.0",
    "license": "MIT",
    "keywords": ["aero", "contracts", "interfaces", "laravel"],
    "authors": [
        {
            "name": "Aero Team",
            "email": "support@aerosuite.com"
        }
    ],
    "require": {
        "php": "^8.2",
        "illuminate/support": "^11.0|^12.0",
        "illuminate/database": "^11.0|^12.0"
    },
    "autoload": {
        "psr-4": {
            "Aero\\Contracts\\": "src/"
        }
    },
    "extra": {
        "aero": {
            "package": "contracts",
            "version": "1.0.0",
            "category": "foundation",
            "description": "Shared interface contracts for all Aero packages"
        }
    },
    "minimum-stability": "dev",
    "prefer-stable": true,
    "config": {
        "optimize-autoloader": true
    }
}
```

- [ ] **Step K1.2: Verify**

```powershell
Test-Path "packages\aero-contracts\composer.json"
```

Expected: `True`

- [ ] **Step K1.3: Commit**

```powershell
git add packages/aero-contracts/composer.json
git commit -m "feat(aero-contracts): scaffold new contracts package with composer.json"
```

---

## Task K2: Create All 18 Interface Files in aero-contracts

**Files:** 18 new files in `packages/aero-contracts/src/`

- [ ] **Step K2.1: Create `TenantScopeInterface.php`**

```php
<?php

declare(strict_types=1);

namespace Aero\Contracts;

interface TenantScopeInterface
{
    public function getCurrentTenantId(): int|string|null;
    public function getCurrentTenant(): mixed;
    public function inTenantContext(): bool;
    public function inCentralContext(): bool;
    public function getMode(): string;
    public function isSaaSMode(): bool;
    public function isStandaloneMode(): bool;
}
```

- [ ] **Step K2.2: Create `LicenseServiceInterface.php`**

```php
<?php

declare(strict_types=1);

namespace Aero\Contracts;

interface LicenseServiceInterface
{
    /** @throws \Aero\Core\Exceptions\LicenseException if format is invalid */
    public function validateFormat(string $licenseKey): void;

    /** @throws \Aero\Core\Exceptions\LicenseException if activation fails */
    public function activate(string $licenseKey, string $productId): void;

    public function isValid(): bool;

    public function status(): string;

    public function graceSecondsRemaining(): int;
}
```

- [ ] **Step K2.3: Create `ProductAccessInterface.php`**

```php
<?php

declare(strict_types=1);

namespace Aero\Contracts;

interface ProductAccessInterface
{
    public function tenantCanAccessModule(string $tenantId, string $moduleCode): bool;
    public function getAccessibleModuleCodes(string $tenantId): array;
    public function flushCache(string $tenantId): void;
}
```

- [ ] **Step K2.4: Create `PlanCatalogInterface.php`**

```php
<?php

declare(strict_types=1);

namespace Aero\Contracts;

use Illuminate\Support\Collection;

interface PlanCatalogInterface
{
    public function getPlansForModule(string $moduleCode): Collection;
    public function isModuleInAnyPlan(string $moduleCode): bool;
}
```

- [ ] **Step K2.5: Create `DomainContextContract.php`**

```php
<?php

declare(strict_types=1);

namespace Aero\Contracts;

interface DomainContextContract
{
    public const CONTEXT_ADMIN      = 'admin';
    public const CONTEXT_PLATFORM   = 'platform';
    public const CONTEXT_TENANT     = 'tenant';
    public const CONTEXT_STANDALONE = 'standalone';

    public function getContext(): string;
    public function isAdminContext(): bool;
    public function isPlatformContext(): bool;
    public function isTenantContext(): bool;
    public function isCentralContext(): bool;
}
```

- [ ] **Step K2.6: Create `DomainEventContract.php`**

Read `packages/aero-core/src/Contracts/DomainEventContract.php` for the full method list, then create `packages/aero-contracts/src/DomainEventContract.php` with namespace `Aero\Contracts` and identical methods.

- [ ] **Step K2.7: Create `ModuleSummaryProvider.php`**

Read `packages/aero-core/src/Contracts/ModuleSummaryProvider.php` for the full method list, then create `packages/aero-contracts/src/ModuleSummaryProvider.php` with namespace `Aero\Contracts` and identical methods.

- [ ] **Step K2.8: Create `ModuleProviderInterface.php`**

```php
<?php

declare(strict_types=1);

namespace Aero\Contracts;

interface ModuleProviderInterface
{
    public function getModuleCode(): string;
    public function getModuleName(): string;
    public function getModuleDescription(): string;
    public function getModuleVersion(): string;
    public function getModuleCategory(): string;
    public function getModuleIcon(): string;
    public function getModulePriority(): int;
    public function getModuleHierarchy(): array;
    public function getNavigationItems(): array;
    public function getRoutes(): array;
    public function getDependencies(): array;
    public function isEnabled(): bool;
    public function getMinimumPlan(): ?string;
    public function register(): void;
    public function boot(): void;
}
```

- [ ] **Step K2.9: Create `Searchable.php`**

Read `packages/aero-core/src/Contracts/Searchable.php` for the full method list, then create `packages/aero-contracts/src/Searchable.php` with namespace `Aero\Contracts` and identical methods.

- [ ] **Step K2.10: Create `NotificationChannelInterface.php`**

```php
<?php

declare(strict_types=1);

namespace Aero\Contracts;

interface NotificationChannelInterface
{
    public function send(object $notifiable, object $notification): void;
    public function channelName(): string;
}
```

- [ ] **Step K2.11: Create `NotificationRoutingContract.php`**

Read `packages/aero-core/src/Contracts/NotificationRoutingContract.php` for the full method list (it uses `Illuminate\Support\Collection`), then create `packages/aero-contracts/src/NotificationRoutingContract.php` with namespace `Aero\Contracts`, same methods, same `use Illuminate\Support\Collection;` import.

- [ ] **Step K2.12: Create the 4 Plan-J contracts (already exist in aero-core, copy to new namespace)**

Create these 4 files by reading the originals and changing only the namespace:

- `packages/aero-contracts/src/MailContextResolverInterface.php` — namespace `Aero\Contracts`
- `packages/aero-contracts/src/SmsContextResolverInterface.php` — namespace `Aero\Contracts`
- `packages/aero-contracts/src/MailSenderInterface.php` — namespace `Aero\Contracts`
- `packages/aero-contracts/src/SmsGatewayInterface.php` — namespace `Aero\Contracts`

- [ ] **Step K2.13: Create `TranslationDriverInterface.php`**

Read `packages/aero-core/src/Contracts/TranslationDriverInterface.php`, create `packages/aero-contracts/src/TranslationDriverInterface.php` with namespace `Aero\Contracts` and identical methods (translate, has, getLocale, getSharedProps).

- [ ] **Step K2.14: Create `CoreWidgetCategory.php`** (PHP enum)

Read `packages/aero-core/src/Contracts/CoreWidgetCategory.php` in full, then create `packages/aero-contracts/src/CoreWidgetCategory.php` with namespace `Aero\Contracts` and identical enum cases + methods.

- [ ] **Step K2.15: Create `DashboardWidgetInterface.php`**

Read `packages/aero-core/src/Contracts/DashboardWidgetInterface.php` in full, then create `packages/aero-contracts/src/DashboardWidgetInterface.php` with:
- namespace `Aero\Contracts`
- Change `use Aero\Core\Contracts\CoreWidgetCategory;` → `use Aero\Contracts\CoreWidgetCategory;`
- Identical methods

- [ ] **Step K2.16: Verify all 18 files exist**

```powershell
Get-ChildItem "packages\aero-contracts\src" -Filter "*.php" | Measure-Object | Select-Object -ExpandProperty Count
```

Expected: `18`

- [ ] **Step K2.17: Commit**

```powershell
git add packages/aero-contracts/src/
git commit -m "feat(aero-contracts): add all 18 moved interfaces and enums with Aero\Contracts namespace"
```

---

## Task K3: Wire aero-core to aero-contracts — Clean Cut

**Files:**
- Modify: `packages/aero-core/composer.json`
- Delete: 18 files from `packages/aero-core/src/Contracts/`

No aliases file. The old namespaces are gone; all imports are updated atomically in Task K4.

- [ ] **Step K3.1: Add `aero/contracts` dependency + version to aero-core**

Read `packages/aero-core/composer.json`. Make two changes:

1. Add `"version": "1.0.0"` as a top-level field after `"license"`:
   ```json
   "license": "MIT",
   "version": "1.0.0",
   ```

2. In the `"require"` block, add:
   ```json
   "aero/contracts": "^1.0",
   ```

Do NOT add a `contract_aliases.php` to autoload.files — there is no aliases file.

- [ ] **Step K3.2: Delete the 18 old contract files from aero-core**

```powershell
git rm `
  packages/aero-core/src/Contracts/TenantScopeInterface.php `
  packages/aero-core/src/Contracts/LicenseServiceInterface.php `
  packages/aero-core/src/Contracts/ProductAccessInterface.php `
  packages/aero-core/src/Contracts/PlanCatalogInterface.php `
  packages/aero-core/src/Contracts/DomainContextContract.php `
  packages/aero-core/src/Contracts/DomainEventContract.php `
  packages/aero-core/src/Contracts/ModuleSummaryProvider.php `
  packages/aero-core/src/Contracts/ModuleProviderInterface.php `
  packages/aero-core/src/Contracts/Searchable.php `
  packages/aero-core/src/Contracts/NotificationChannelInterface.php `
  packages/aero-core/src/Contracts/NotificationRoutingContract.php `
  packages/aero-core/src/Contracts/MailContextResolverInterface.php `
  packages/aero-core/src/Contracts/SmsContextResolverInterface.php `
  packages/aero-core/src/Contracts/MailSenderInterface.php `
  packages/aero-core/src/Contracts/SmsGatewayInterface.php `
  packages/aero-core/src/Contracts/TranslationDriverInterface.php `
  packages/aero-core/src/Contracts/CoreWidgetCategory.php `
  packages/aero-core/src/Contracts/DashboardWidgetInterface.php
```

- [ ] **Step K3.3: Verify exactly 4 files remain in aero-core/src/Contracts/**

```powershell
Get-ChildItem "packages\aero-core\src\Contracts" -Filter "*.php" | Select-Object Name
```

Expected output (in any order):
```
AbstractDashboardWidget.php
EmployeeServiceContract.php
UserContract.php
UserRepositoryContract.php
```

- [ ] **Step K3.4: Commit**

```powershell
git add packages/aero-core/composer.json
git commit -m "feat(aero-core): add aero/contracts ^1.0 dep + version 1.0.0; delete 18 moved contract files -- clean cut, no aliases"
```

---

## Task K4: Update All Contract Imports Across the Monorepo (Automated)

96 PHP files use `use Aero\Core\Contracts\[moved interface]`. This task replaces them in a single automated pass.

**Files:** Up to 96 `.php` files across all packages (automated — the script determines the exact set)

- [ ] **Step K4.1: Run the automated import replacement script**

```powershell
$movedContracts = @(
    'TenantScopeInterface', 'LicenseServiceInterface', 'ProductAccessInterface',
    'PlanCatalogInterface', 'DomainContextContract', 'DomainEventContract',
    'ModuleSummaryProvider', 'ModuleProviderInterface', 'Searchable',
    'NotificationChannelInterface', 'NotificationRoutingContract',
    'MailContextResolverInterface', 'SmsContextResolverInterface',
    'MailSenderInterface', 'SmsGatewayInterface', 'TranslationDriverInterface',
    'CoreWidgetCategory', 'DashboardWidgetInterface'
)

$phpFiles = Get-ChildItem -Path "c:\laragon\www\Aero-Enterprise-Suite-Saas\packages" `
    -Recurse -Filter "*.php" |
    Where-Object { $_.FullName -notmatch "\\vendor\\" }

$changedFiles = @()

foreach ($file in $phpFiles) {
    $content = Get-Content $file.FullName -Raw -Encoding UTF8
    $original = $content
    foreach ($contract in $movedContracts) {
        # Replace: use Aero\Core\Contracts\X;
        $content = $content -replace "use Aero\\\\Core\\\\Contracts\\\\$contract;", "use Aero\\Contracts\\$contract;"
        # Replace: Aero\Core\Contracts\X (FQCNs in code)
        $content = $content -replace "Aero\\\\Core\\\\Contracts\\\\$contract", "Aero\\Contracts\\$contract"
    }
    if ($content -ne $original) {
        Set-Content $file.FullName -Value $content -Encoding UTF8 -NoNewline
        $changedFiles += $file.FullName
    }
}

Write-Host "Changed $($changedFiles.Count) files:"
$changedFiles | ForEach-Object { Write-Host "  $_" }
```

Expected: script reports a non-zero count of changed files (should be ~96).

- [ ] **Step K4.2: Verify zero old imports remain**

```powershell
$movedContracts = @(
    'TenantScopeInterface', 'LicenseServiceInterface', 'ProductAccessInterface',
    'PlanCatalogInterface', 'DomainContextContract', 'DomainEventContract',
    'ModuleSummaryProvider', 'ModuleProviderInterface', 'Searchable',
    'NotificationChannelInterface', 'NotificationRoutingContract',
    'MailContextResolverInterface', 'SmsContextResolverInterface',
    'MailSenderInterface', 'SmsGatewayInterface', 'TranslationDriverInterface',
    'CoreWidgetCategory', 'DashboardWidgetInterface'
)

$remaining = Get-ChildItem -Path "c:\laragon\www\Aero-Enterprise-Suite-Saas\packages" `
    -Recurse -Filter "*.php" |
    Where-Object { $_.FullName -notmatch "\\vendor\\" } |
    Select-String -Pattern "use Aero\\\\Core\\\\Contracts\\\\($($movedContracts -join '|'))"

if ($remaining) {
    Write-Host "FAIL: $($remaining.Count) old imports remain:"
    $remaining | ForEach-Object { Write-Host "  $($_.Path):$($_.LineNumber)" }
} else {
    Write-Host "PASS: Zero old imports remain."
}
```

Expected: `PASS: Zero old imports remain.`

- [ ] **Step K4.3: Commit**

```powershell
git add packages/
git commit -m "refactor: update all contract imports from Aero\Core\Contracts\ to Aero\Contracts\ across 96 files"
```

---

## Task K5: Update All Package `composer.json` Files

30 packages have `"aero/core": "*"`. This task pins them to `"^1.0"` and adds `"aero/contracts": "^1.0"`.

- [ ] **Step K5.1: Pin `aero/core` to semver in every package**

First, add `"version": "1.0.0"` to aero-core's composer.json (already started in K3.1 — verify it's there):

```powershell
Select-String -Pattern '"version"' -Path "packages\aero-core\composer.json"
```

Expected: `"version": "1.0.0"` present.

If missing, add it manually to `packages/aero-core/composer.json` right after the `"license"` field.

- [ ] **Step K5.2: Run the automated composer.json update script**

```powershell
$packageDirs = Get-ChildItem -Path "c:\laragon\www\Aero-Enterprise-Suite-Saas\packages" -Directory |
    Where-Object { $_.Name -ne 'aero-core' -and $_.Name -ne 'aero-contracts' }

foreach ($dir in $packageDirs) {
    $composerFile = Join-Path $dir.FullName "composer.json"
    if (-not (Test-Path $composerFile)) { continue }

    $json = Get-Content $composerFile -Raw | ConvertFrom-Json

    $changed = $false

    # Pin aero/core wildcard to ^1.0
    if ($json.require.'aero/core' -and $json.require.'aero/core' -in @('*', '@dev')) {
        $json.require.'aero/core' = '^1.0'
        $changed = $true
    }

    # Add aero/contracts ^1.0 if not already present
    if (-not $json.require.'aero/contracts') {
        $json.require | Add-Member -NotePropertyName 'aero/contracts' -NotePropertyValue '^1.0' -Force
        $changed = $true
    }

    if ($changed) {
        $json | ConvertTo-Json -Depth 10 | Set-Content $composerFile -Encoding UTF8
        Write-Host "Updated: $composerFile"
    }
}
```

- [ ] **Step K5.3: Verify no `"aero/core": "*"` remains**

```powershell
Select-String -Pattern '"aero/core":\s*"\*"' -Path "packages\*\composer.json"
```

Expected: no output.

- [ ] **Step K5.4: Add aero-contracts as a path repository to host app composer.json**

Read `c:\laragon\www\aeos365\composer.json`. Find the `"repositories"` array that lists `"../Aero-Enterprise-Suite-Saas/packages/*"`. Confirm `packages/aero-contracts` will be auto-discovered by the wildcard path repository.

If the path repo is NOT a wildcard (e.g., it lists each package individually), add:
```json
{
    "type": "path",
    "url": "../Aero-Enterprise-Suite-Saas/packages/aero-contracts",
    "options": { "symlink": true }
}
```

Do the same for `c:\laragon\www\aeos365-standalone\composer.json`.

- [ ] **Step K5.5: Commit**

```powershell
git add packages/*/composer.json
git commit -m "chore: pin aero/core to ^1.0 + add aero/contracts ^1.0 in all 30 package manifests"
```

---

## Task K6: Update deptrac + Verify the New Layer

**Files:**
- Modify: `deptrac.yaml`

- [ ] **Step K6.1: Add aero-contracts to deptrac paths and define Contracts layer**

Read `deptrac.yaml`. Make these changes:

1. Add to `parameters.paths`:
   ```yaml
   - packages/aero-contracts/src
   ```

2. Add a new `Contracts` layer **before** the `Core` layer:
   ```yaml
   layers:
     - name: Contracts
       collectors:
         - type: className
           regex: ^Aero\\Contracts\\
   
     - name: Core
       collectors:
         - type: className
           regex: ^Aero\\Core\\
   ```

3. Update `ruleset` — Contracts has no dependencies; Core can depend on Contracts:
   ```yaml
   ruleset:
     Contracts: []
     Core: [Contracts]
     Platform: [Core, Contracts]
     Infrastructure: [Core, Contracts]
     Features: [Core, Infrastructure, Contracts]
   ```

- [ ] **Step K6.2: Verify deptrac config is valid**

```powershell
cd "c:\laragon\www\aeos365"
vendor/bin/deptrac analyze --config-file=../Aero-Enterprise-Suite-Saas/deptrac.yaml --no-progress 2>&1 | Select-Object -Last 10
```

Expected: `[OK]` or only the pre-existing skip_violations violations. If new violations appear, add them to `skip_violations` with a `# TODO: Plan L` annotation.

- [ ] **Step K6.3: Commit**

```powershell
git add deptrac.yaml
git commit -m "ci: add Contracts layer to deptrac; aero-contracts is the new zero-dependency foundation"
```

---

## Task K7: Smoke-Test the Full Integration

- [ ] **Step K7.1: Confirm composer install still works**

```powershell
cd "c:\laragon\www\aeos365"
composer install --no-interaction 2>&1 | Select-Object -Last 5
```

Expected: exits without errors.

- [ ] **Step K7.2: Confirm aero-contracts is installed**

```powershell
cd "c:\laragon\www\aeos365"
composer show aero/contracts 2>&1
```

Expected: shows the aero-contracts package details.

- [ ] **Step K7.3: Confirm aliases work**

```powershell
cd "c:\laragon\www\aeos365"
php artisan tinker --execute="echo class_exists('Aero\Core\Contracts\TenantScopeInterface') ? 'ALIAS OK' : 'ALIAS BROKEN';"
```

Expected: `ALIAS OK`

- [ ] **Step K7.4: Confirm new namespace works**

```powershell
cd "c:\laragon\www\aeos365"
php artisan tinker --execute="echo interface_exists('Aero\Contracts\TenantScopeInterface') ? 'CONTRACT OK' : 'CONTRACT BROKEN';"
```

Expected: `CONTRACT OK`

- [ ] **Step K7.5: Final commit if any changes**

```powershell
git add .
git status
```

If clean (no untracked/modified), no commit needed. If there are adjustments, commit them:
```powershell
git commit -m "fix(aero-contracts): address smoke-test regressions"
```

---

## Self-Review

**Spec coverage:**
- New `aero-contracts` package created → K1 ✅
- 18 interfaces/enums moved with `Aero\Contracts\` namespace → K2 ✅
- Old 18 files deleted from aero-core (clean cut, no aliases) → K3 ✅
- All 96 existing imports updated to new namespace → K4 ✅
- `aero/core: "*"` → `aero/core: "^1.0"` in all 30 packages → K5 ✅
- `aero/contracts: "^1.0"` added to all packages → K5 ✅
- deptrac `Contracts` layer defined → K6 ✅
- Smoke test confirms aliases + new namespace both work → K7 ✅

**Stays in aero-core (not moved):**
- `AbstractDashboardWidget` — abstract class with Facades (move in Plan L when decoupled)
- `UserContract`, `UserRepositoryContract` — Eloquent-typed (move when Eloquent dependency is acceptable in aero-contracts or extracted further)
- `EmployeeServiceContract` — HRM domain; move to aero-hrm in Plan L

**Deferred to Plan L:**
- `RoleModuleAccessInterface` (aero-hrmac) → move to aero-contracts
- Drop `aero/core` dependency entirely from pure-contract packages
- `AbstractDashboardWidget` decoupling from Facades
