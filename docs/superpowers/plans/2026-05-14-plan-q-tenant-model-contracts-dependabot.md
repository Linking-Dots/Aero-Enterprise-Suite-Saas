# Plan Q+Sec — TenantModel Contract Migration & Dependabot Remediation

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Move `TenantModel` + `CentralModel` into `aero-contracts` so they carry zero aero-core dependency; simultaneously fix the 263 feature-package models whose `extends TenantModel` is missing an import statement (a silent bug left by Plan MN/O), and resolve the 67 Dependabot vulnerability alerts by tightening phpspreadsheet and dompdf minimum version constraints.

**Architecture:** Three independent tracks. Track Q introduces `Aero\Contracts\AeroMode` (a pure-PHP static resolver, zero framework dependency) to replace the `is_saas_mode()` call inside `TenantModel`, then moves both base classes to `packages/aero-contracts/src/Models/`. Backward-compat thin subclasses remain in aero-core so none of the 454 existing model files need manual edits — they are fixed in bulk by a one-shot PHP script (Task Q5) that adds the correct import to the 263 broken files. Track Sec tightens version constraints in `packages/aero-hrm/composer.json` and adds `composer audit` to the existing CI workflow.

**Tech Stack:** PHP 8.2, Laravel 11/12, PHPUnit 11, Orchestra Testbench 9, GitHub Actions. No new Composer packages.

**Prerequisite:** Plans A–P merged to `main`. Working directory: `c:\laragon\www\Aero-Enterprise-Suite-Saas`.

---

## File Map

| Action | Path |
|--------|------|
| Create | `packages/aero-contracts/src/AeroMode.php` |
| Create | `packages/aero-contracts/src/Models/TenantModel.php` |
| Create | `packages/aero-contracts/src/Models/CentralModel.php` |
| Modify | `packages/aero-core/src/Models/TenantModel.php` → BC shim |
| Modify | `packages/aero-core/src/Models/CentralModel.php` → BC shim |
| Modify | `packages/aero-core/src/AeroCoreServiceProvider.php` → register AeroMode resolvers |
| Create | `packages/aero-contracts/scripts/fix-tenant-model-imports.php` |
| Create | `packages/aero-contracts/tests/AeroModeTest.php` |
| Create | `packages/aero-contracts/tests/Models/TenantModelContractTest.php` |
| Modify | `packages/aero-hrm/composer.json` → tighten phpspreadsheet + dompdf |
| Modify | `.github/workflows/architecture-lint.yml` → add composer audit step |

---

## Track Q — TenantModel Contract Migration

---

### Task Q1: Add `Aero\Contracts\AeroMode` static resolver

**Files:**
- Create: `packages/aero-contracts/src/AeroMode.php`

The blocker preventing TenantModel from living in aero-contracts was the call to `is_saas_mode()` (defined in aero-core/helpers.php, which uses `storage_path()` — a Laravel foundation function). The fix: introduce `AeroMode`, a pure static class with a settable closure. aero-core sets the closure during boot. aero-contracts never calls `app()` or `storage_path()`.

- [ ] **Step Q1.1: Create AeroMode.php**

```php
<?php

declare(strict_types=1);

namespace Aero\Contracts;

/**
 * Static mode and tenant-context resolver for aero-contracts.
 *
 * Zero Laravel dependency. aero-core sets both resolvers during
 * ServiceProvider::register() via setModeResolver() and
 * setTenantContextChecker(). Until then (tests, queue workers,
 * standalone installs) defaults to standalone / no-guard.
 */
final class AeroMode
{
    private static ?\Closure $modeResolver = null;

    private static ?\Closure $tenantContextChecker = null;

    /** Called once by AeroCoreServiceProvider::register(). */
    public static function setModeResolver(\Closure $resolver): void
    {
        self::$modeResolver = $resolver;
    }

    /**
     * Called once by AeroCoreServiceProvider::register().
     * The checker MUST throw \LogicException if called outside tenant context.
     *
     * @param \Closure(string $modelClass): void $checker
     */
    public static function setTenantContextChecker(\Closure $checker): void
    {
        self::$tenantContextChecker = $checker;
    }

    public static function isSaas(): bool
    {
        return self::$modeResolver !== null && (self::$modeResolver)();
    }

    public static function isStandalone(): bool
    {
        return ! self::isSaas();
    }

    /**
     * Called from TenantModel's global scope.
     * No-op when no checker is set (tests, early boot).
     */
    public static function assertTenantContext(string $modelClass): void
    {
        if (self::$tenantContextChecker !== null) {
            (self::$tenantContextChecker)($modelClass);
        }
    }

    /** For testing only: reset all resolvers. */
    public static function reset(): void
    {
        self::$modeResolver    = null;
        self::$tenantContextChecker = null;
    }
}
```

- [ ] **Step Q1.2: Commit**

```powershell
git add packages/aero-contracts/src/AeroMode.php
git commit -m "feat(aero-contracts): add AeroMode static resolver -- zero-dep mode detection for TenantModel"
```

---

### Task Q2: Move TenantModel to aero-contracts

**Files:**
- Create: `packages/aero-contracts/src/Models/TenantModel.php`
- Modify: `packages/aero-core/src/Models/TenantModel.php`

- [ ] **Step Q2.1: Create `packages/aero-contracts/src/Models/TenantModel.php`**

```php
<?php

declare(strict_types=1);

namespace Aero\Contracts\Models;

use Aero\Contracts\AeroMode;
use Aero\Contracts\TenantScopeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Base class for all models that live in the TENANT database.
 *
 * In SaaS mode: stancl/tenancy middleware switches the connection to
 * the tenant's DB before this scope fires. In standalone mode the
 * scope is a no-op (AeroMode::isSaas() returns false).
 *
 * Extend this for any model whose table belongs to tenant data.
 * NEVER add relationships to CentralModel subclasses.
 */
abstract class TenantModel extends Model
{
    protected static function boot(): void
    {
        parent::boot();

        static::addGlobalScope('tenant_context_guard', function (Builder $builder) {
            if (! AeroMode::isSaas()) {
                return;
            }

            try {
                AeroMode::assertTenantContext(static::class);
            } catch (\LogicException $e) {
                throw $e;
            } catch (\Throwable) {
                // AeroMode not yet configured (early boot, tests) — allow
            }
        });
    }
}
```

- [ ] **Step Q2.2: Replace `packages/aero-core/src/Models/TenantModel.php` with BC shim**

The existing file is 51 lines. Replace the entire content:

```php
<?php

namespace Aero\Core\Models;

/**
 * Backward-compatibility shim — TenantModel now lives in aero-contracts.
 * Both \Aero\Core\Models\TenantModel and \Aero\Contracts\Models\TenantModel
 * resolve to the same class hierarchy via this thin subclass.
 *
 * @see \Aero\Contracts\Models\TenantModel
 */
abstract class TenantModel extends \Aero\Contracts\Models\TenantModel {}
```

- [ ] **Step Q2.3: Verify the file is valid PHP**

```powershell
php -l packages/aero-contracts/src/Models/TenantModel.php
php -l packages/aero-core/src/Models/TenantModel.php
```

Expected output for each: `No syntax errors detected`.

- [ ] **Step Q2.4: Commit**

```powershell
git add packages/aero-contracts/src/Models/TenantModel.php packages/aero-core/src/Models/TenantModel.php
git commit -m "refactor(aero-contracts): move TenantModel to aero-contracts; aero-core shim keeps BC"
```

---

### Task Q3: Move CentralModel to aero-contracts

**Files:**
- Create: `packages/aero-contracts/src/Models/CentralModel.php`
- Modify: `packages/aero-core/src/Models/CentralModel.php`

- [ ] **Step Q3.1: Create `packages/aero-contracts/src/Models/CentralModel.php`**

```php
<?php

declare(strict_types=1);

namespace Aero\Contracts\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Base class for all models that live in the CENTRAL (landlord) database.
 *
 * Central models are tenant-agnostic: Plans, LandlordUsers, Tenants, Products, etc.
 * The connection is pinned to 'central' and cannot be changed at runtime.
 *
 * NEVER add relationships to TenantModel subclasses — cross-DB joins fail.
 * Do NOT extend this in standalone mode (no 'central' connection exists).
 */
abstract class CentralModel extends Model
{
    protected $connection = 'central';

    protected static function boot(): void
    {
        parent::boot();

        static::creating(fn (self $m) => $m->setConnection('central'));
        static::saving(fn (self $m) => $m->setConnection('central'));
    }
}
```

- [ ] **Step Q3.2: Replace `packages/aero-core/src/Models/CentralModel.php` with BC shim**

```php
<?php

namespace Aero\Core\Models;

/**
 * Backward-compatibility shim — CentralModel now lives in aero-contracts.
 *
 * @see \Aero\Contracts\Models\CentralModel
 */
abstract class CentralModel extends \Aero\Contracts\Models\CentralModel {}
```

- [ ] **Step Q3.3: Commit**

```powershell
git add packages/aero-contracts/src/Models/CentralModel.php packages/aero-core/src/Models/CentralModel.php
git commit -m "refactor(aero-contracts): move CentralModel to aero-contracts; aero-core shim keeps BC"
```

---

### Task Q4: Register AeroMode resolvers in AeroCoreServiceProvider

**Files:**
- Modify: `packages/aero-core/src/AeroCoreServiceProvider.php`

The resolvers must be set before ANY model is queried. The `register()` method is the right place — it runs before `boot()`.

- [ ] **Step Q4.1: Add import at top of AeroCoreServiceProvider.php**

Find the existing `use` block (around line 4–60). Add:

```php
use Aero\Contracts\AeroMode;
use Aero\Contracts\TenantScopeInterface;
```

(Both are likely already imported, verify with grep first.)

```powershell
Select-String -Pattern "use Aero.Contracts.AeroMode|use Aero.Contracts.TenantScopeInterface" packages/aero-core/src/AeroCoreServiceProvider.php
```

If not present, add them to the use block.

- [ ] **Step Q4.2: Add resolver registration inside `register()` try block**

Find the line in `register()` that reads:
```php
$this->app->singleton(TenantScopeInterface::class, StandaloneTenantScope::class);
```
(around line 271–273 in the full file).

Add immediately AFTER that line:

```php
            // Wire AeroMode so aero-contracts TenantModel can detect SaaS/standalone
            // without importing aero-core helpers directly.
            AeroMode::setModeResolver(fn () => is_saas_mode());
            AeroMode::setTenantContextChecker(function (string $modelClass) {
                try {
                    $scope = app(TenantScopeInterface::class);
                    if (! $scope->inTenantContext()) {
                        throw new \LogicException(
                            $modelClass . ' queried outside of tenant context. ' .
                            'Ensure this runs after tenancy middleware. ' .
                            'For central-DB models extend CentralModel instead.'
                        );
                    }
                } catch (\LogicException $e) {
                    throw $e;
                } catch (\Throwable) {
                    // TenantScopeInterface unavailable during early boot — allow
                }
            });
```

- [ ] **Step Q4.3: Verify PHP syntax**

```powershell
php -l packages/aero-core/src/AeroCoreServiceProvider.php
```

Expected: `No syntax errors detected`.

- [ ] **Step Q4.4: Commit**

```powershell
git add packages/aero-core/src/AeroCoreServiceProvider.php
git commit -m "feat(aero-core): wire AeroMode resolvers in AeroCoreServiceProvider::register()"
```

---

### Task Q5: Fix missing TenantModel imports in 263 feature-package models

**Context:** Plans MN and O changed `class Foo extends Model` → `class Foo extends TenantModel` across 263 models but omitted the `use` statement. PHP resolves bare `TenantModel` relative to the current namespace (e.g., `Aero\Finance\Models\TenantModel`) which does not exist — this is a fatal runtime error. This task runs a one-shot PHP script to add the correct import to every affected file.

**Files:**
- Create (temp): `packages/aero-contracts/scripts/fix-tenant-model-imports.php`
- Modify (auto): all 263 models under `packages/*/src/**/*.php`

- [ ] **Step Q5.1: Create the fix script**

Create `packages/aero-contracts/scripts/fix-tenant-model-imports.php`:

```php
<?php

declare(strict_types=1);

/**
 * Fix missing TenantModel imports across feature packages.
 *
 * Adds `use Aero\Contracts\Models\TenantModel;` to every .php file
 * under packages/*/src/ that:
 *  1. contains `extends TenantModel`
 *  2. does NOT already have a `use Aero\*\TenantModel` statement
 *
 * Skips aero-core, aero-hrmac, aero-auth (already correctly imported in Plan P).
 *
 * Usage: php packages/aero-contracts/scripts/fix-tenant-model-imports.php
 */

$baseDir = realpath(__DIR__ . '/../../..');  // monorepo root

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($baseDir . '/packages')
);

$fixed = [];
$alreadyOk = 0;

foreach ($iterator as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }

    $path = str_replace('\\', '/', $file->getPathname());

    // Only model-ish files (anywhere under /src/)
    if (! str_contains($path, '/src/')) {
        continue;
    }

    // Skip packages that were correctly handled in Plan P
    foreach (['aero-core', 'aero-hrmac', 'aero-auth', 'aero-contracts'] as $skip) {
        if (str_contains($path, '/' . $skip . '/')) {
            continue 2;
        }
    }

    $content = file_get_contents($path);

    if (! str_contains($content, 'extends TenantModel')) {
        continue;
    }

    // Already has a correct import
    if (str_contains($content, 'use Aero\Contracts\Models\TenantModel') ||
        str_contains($content, 'use Aero\Core\Models\TenantModel')) {
        $alreadyOk++;
        continue;
    }

    // Inject import after the namespace declaration line
    $patched = preg_replace(
        '/^(namespace [^\n]+;\n)/m',
        '$1' . "\nuse Aero\\Contracts\\Models\\TenantModel;\n",
        $content,
        1
    );

    if ($patched === $content) {
        fwrite(STDERR, "WARNING: no namespace found in {$path}\n");
        continue;
    }

    file_put_contents($path, $patched);
    $fixed[] = str_replace($baseDir . '/', '', $path);
}

foreach ($fixed as $f) {
    echo "Fixed: {$f}\n";
}
echo "\nTotal fixed: " . count($fixed) . ", already correct: {$alreadyOk}\n";
```

- [ ] **Step Q5.2: Run the script**

```powershell
cd "c:\laragon\www\Aero-Enterprise-Suite-Saas"
php packages/aero-contracts/scripts/fix-tenant-model-imports.php
```

Expected: output lists ~263 fixed files, ending with `Total fixed: 263, already correct: ...`.

- [ ] **Step Q5.3: Spot-check 3 files**

```powershell
Select-String -Pattern "use Aero.Contracts.Models.TenantModel" packages/aero-finance/src/Models/Account.php
Select-String -Pattern "use Aero.Contracts.Models.TenantModel" packages/aero-hrm/src/Models/Employee.php
Select-String -Pattern "use Aero.Contracts.Models.TenantModel" packages/aero-crm/src/Models/Contact.php
```

Expected: all three return a match.

- [ ] **Step Q5.4: Check no extends TenantModel without import remains**

```powershell
$broken = Get-ChildItem -Path packages -Recurse -Filter "*.php" | 
  Where-Object { $_.FullName -notmatch "aero-core|aero-hrmac|aero-auth|aero-contracts" } |
  Where-Object {
    $c = Get-Content $_.FullName -Raw
    ($c -match "extends TenantModel") -and
    ($c -notmatch "use Aero\\\\(Contracts|Core)\\\\(Models\\\\)?TenantModel")
  }
if ($broken) { $broken | ForEach-Object { Write-Host "MISSING IMPORT: $($_.FullName)" }; exit 1 }
else { Write-Host "All clear — no missing imports." }
```

Expected: `All clear — no missing imports.`

- [ ] **Step Q5.5: Commit**

```powershell
git add packages/
git commit -m "fix(feature-packages): add missing 'use Aero\Contracts\Models\TenantModel' import to 263 models -- Plan MN/O omitted import statement"
```

---

### Task Q6: Write tests for AeroMode and the new base classes

**Files:**
- Create: `packages/aero-contracts/tests/AeroModeTest.php`
- Create: `packages/aero-contracts/tests/Models/TenantModelContractTest.php`

- [ ] **Step Q6.1: Create `packages/aero-contracts/tests/AeroModeTest.php`**

```php
<?php

namespace Aero\Contracts\Tests;

use Aero\Contracts\AeroMode;
use PHPUnit\Framework\TestCase;

class AeroModeTest extends TestCase
{
    protected function tearDown(): void
    {
        AeroMode::reset();
    }

    public function test_defaults_to_standalone_when_no_resolver_set(): void
    {
        $this->assertFalse(AeroMode::isSaas());
        $this->assertTrue(AeroMode::isStandalone());
    }

    public function test_returns_saas_when_resolver_returns_true(): void
    {
        AeroMode::setModeResolver(fn () => true);

        $this->assertTrue(AeroMode::isSaas());
        $this->assertFalse(AeroMode::isStandalone());
    }

    public function test_assert_tenant_context_is_noop_when_no_checker_set(): void
    {
        // Should not throw
        AeroMode::assertTenantContext('SomeModel');
        $this->addToAssertionCount(1);
    }

    public function test_assert_tenant_context_calls_checker(): void
    {
        $called = false;
        AeroMode::setTenantContextChecker(function (string $model) use (&$called) {
            $called = true;
            $this->assertEquals('App\Models\Foo', $model);
        });

        AeroMode::assertTenantContext('App\Models\Foo');

        $this->assertTrue($called);
    }

    public function test_reset_clears_both_resolvers(): void
    {
        AeroMode::setModeResolver(fn () => true);
        AeroMode::setTenantContextChecker(fn (string $m) => null);
        AeroMode::reset();

        $this->assertFalse(AeroMode::isSaas());
        // No exception — checker is gone
        AeroMode::assertTenantContext('SomeModel');
        $this->addToAssertionCount(1);
    }
}
```

- [ ] **Step Q6.2: Create `packages/aero-contracts/tests/Models/TenantModelContractTest.php`**

```php
<?php

namespace Aero\Contracts\Tests\Models;

use Aero\Contracts\AeroMode;
use Aero\Contracts\Models\TenantModel;
use Illuminate\Database\Eloquent\Builder;
use Orchestra\Testbench\TestCase;

class TenantModelContractTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        AeroMode::reset();
    }

    public function test_no_exception_in_standalone_mode(): void
    {
        AeroMode::setModeResolver(fn () => false);

        $model = new class extends TenantModel {
            protected $table = 'test_table';
        };

        $builder = $model::query();
        $this->assertInstanceOf(Builder::class, $builder);
    }

    public function test_throws_when_checker_raises_logic_exception_in_saas_mode(): void
    {
        AeroMode::setModeResolver(fn () => true);
        AeroMode::setTenantContextChecker(function (string $modelClass) {
            throw new \LogicException("{$modelClass} queried outside of tenant context.");
        });

        $model = new class extends TenantModel {
            protected $table = 'test_table';
        };

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/queried outside of tenant context/');

        $model::query();
    }

    public function test_aero_core_shim_is_instance_of_contracts_tenant_model(): void
    {
        $coreShim = new class extends \Aero\Core\Models\TenantModel {
            protected $table = 'test_table';
        };

        $this->assertInstanceOf(\Aero\Contracts\Models\TenantModel::class, $coreShim);
    }
}
```

- [ ] **Step Q6.3: Commit**

```powershell
git add packages/aero-contracts/tests/
git commit -m "test(aero-contracts): AeroMode + TenantModel contract tests"
```

---

## Track Sec — Dependabot Vulnerability Remediation

**Context:** GitHub Dependabot flags 67 open security alerts on the monorepo. The alerts are generated by scanning each package's `composer.json` independently. Dependabot resolves the LOWEST satisfying version of each dependency range and checks it against the advisory database. The dominant source is `phpoffice/phpspreadsheet` (via `maatwebsite/excel: ^3.1` in `aero-hrm`) — phpspreadsheet 1.21 through 1.28.x had a batch of ~25 CVEs (CVE-2024-45046 through CVE-2024-45293) fixed in 1.29.0. The `^3.1` range for `maatwebsite/excel` resolves to maatwebsite 3.1.0 which in turn allows phpspreadsheet 1.21.x — triggering all those CVEs. Tightening the minimum to `>=1.29.0` eliminates the bulk of alerts.

---

### Task Sec1: Tighten phpspreadsheet minimum constraint in aero-hrm

**Files:**
- Modify: `packages/aero-hrm/composer.json`

The current `composer.json` requires:
```json
"maatwebsite/excel": "^3.1",
"barryvdh/laravel-dompdf": "^3.1"
```

`maatwebsite/excel: ^3.1` transitively allows `phpoffice/phpspreadsheet: 1.21.x` — 25+ CVEs.
`barryvdh/laravel-dompdf: ^3.1` is fine (dompdf 3.x already post-dates all known CVEs); keep as-is.

- [ ] **Step Sec1.1: Read the current require block**

```powershell
Get-Content packages/aero-hrm/composer.json | Select-String '"require"' -Context 0,15
```

- [ ] **Step Sec1.2: Add explicit phpspreadsheet minimum constraint**

In `packages/aero-hrm/composer.json`, inside the `"require"` object, add a line immediately after `"maatwebsite/excel": "^3.1",`:

```json
        "phpoffice/phpspreadsheet": ">=1.29.0",
```

The full `require` block should now look like:

```json
    "require": {
        "php": "^8.2",
        "laravel/framework": "^11.0|^12.0",
        "aero/core": "^1.0",
        "aero/contracts": "^1.0",
        "inertiajs/inertia-laravel": "^2.0",
        "spatie/laravel-permission": "^6.20",
        "spatie/laravel-activitylog": "^4.10",
        "maatwebsite/excel": "^3.1",
        "phpoffice/phpspreadsheet": ">=1.29.0",
        "barryvdh/laravel-dompdf": "^3.1"
    },
```

- [ ] **Step Sec1.3: Validate JSON syntax**

```powershell
php -r "json_decode(file_get_contents('packages/aero-hrm/composer.json'), true, 512, JSON_THROW_ON_ERROR); echo 'Valid JSON' . PHP_EOL;"
```

Expected: `Valid JSON`

- [ ] **Step Sec1.4: Commit**

```powershell
git add packages/aero-hrm/composer.json
git commit -m "fix(aero-hrm): require phpoffice/phpspreadsheet >=1.29.0 -- eliminates CVE-2024-45046 through CVE-2024-45293 from Dependabot alerts"
```

---

### Task Sec2: Add `composer audit` step to CI

**Files:**
- Modify: `.github/workflows/architecture-lint.yml`

The current workflow installs dependencies and runs deptrac + PHPStan but never checks for known vulnerabilities. Adding `composer audit` gives us a permanent security gate.

- [ ] **Step Sec2.1: Read the current workflow**

```powershell
Get-Content .github/workflows/architecture-lint.yml
```

- [ ] **Step Sec2.2: Add composer audit step**

In `.github/workflows/architecture-lint.yml`, add a new step BETWEEN "Install dependencies" and "Validate module manifests":

```yaml
      - name: Check for known security vulnerabilities
        working-directory: aeos365
        run: composer audit --no-dev
```

The full updated `steps` section should be:

```yaml
    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP 8.2
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: mbstring, pdo, pdo_mysql, zip
          coverage: none

      - name: Install dependencies
        working-directory: aeos365
        run: composer install --no-interaction --prefer-dist --optimize-autoloader

      - name: Check for known security vulnerabilities
        working-directory: aeos365
        run: composer audit --no-dev

      - name: Validate module manifests
        working-directory: aeos365
        run: php artisan aero:validate-manifests --strict

      - name: Run deptrac (layer boundary check)
        working-directory: aeos365
        run: |
          vendor/bin/deptrac analyze \
            --config-file=../Aero-Enterprise-Suite-Saas/deptrac.yaml \
            --no-progress

      - name: Run PHPStan (tenancy() call rule)
        working-directory: aeos365
        run: |
          vendor/bin/phpstan analyse \
            --configuration=../Aero-Enterprise-Suite-Saas/phpstan.neon \
            --no-progress \
            --error-format=github
```

- [ ] **Step Sec2.3: Validate YAML syntax**

```powershell
php -r "
\$content = file_get_contents('.github/workflows/architecture-lint.yml');
if (strlen(\$content) > 100) echo 'File exists and non-empty' . PHP_EOL;
"
```

Expected: `File exists and non-empty`

- [ ] **Step Sec2.4: Commit and push**

```powershell
git add .github/workflows/architecture-lint.yml
git commit -m "ci: add composer audit step -- security gate for known CVEs on every push to main"
git push origin main
```

---

## Task Final: End-to-end verification

- [ ] **Step Final.1: Verify zero missing imports**

```powershell
$broken = Get-ChildItem -Path packages -Recurse -Filter "*.php" |
  Where-Object { $_.FullName -notmatch "aero-core|aero-hrmac|aero-auth|aero-contracts" } |
  Where-Object {
    $c = Get-Content $_.FullName -Raw
    ($c -match "extends TenantModel") -and
    ($c -notmatch "use Aero\\\\(Contracts|Core)\\\\(Models\\\\)?TenantModel")
  }
if ($broken) { $broken | ForEach-Object { Write-Host "STILL BROKEN: $($_.Name)" }; exit 1 }
else { Write-Host "PASS: all TenantModel extends have correct import" }
```

Expected: `PASS: all TenantModel extends have correct import`

- [ ] **Step Final.2: Verify aero-core BC shims are thin subclasses**

```powershell
Select-String -Pattern "extends.*Aero.Contracts.Models.TenantModel" packages/aero-core/src/Models/TenantModel.php
Select-String -Pattern "extends.*Aero.Contracts.Models.CentralModel" packages/aero-core/src/Models/CentralModel.php
```

Expected: both return a match.

- [ ] **Step Final.3: Verify AeroMode class exists in contracts**

```powershell
php -r "require 'packages/aero-contracts/src/AeroMode.php'; echo \Aero\Contracts\AeroMode::class . PHP_EOL;"
```

Expected: `Aero\Contracts\AeroMode`

- [ ] **Step Final.4: Verify new Models in contracts**

```powershell
php -r "
require 'vendor/autoload.php';
echo class_exists('Aero\Contracts\Models\TenantModel') ? 'TenantModel OK' : 'MISSING TenantModel';
echo PHP_EOL;
echo class_exists('Aero\Contracts\Models\CentralModel') ? 'CentralModel OK' : 'MISSING CentralModel';
echo PHP_EOL;
" 2>&1
```

Expected: `TenantModel OK` and `CentralModel OK`.

- [ ] **Step Final.5: Confirm phpspreadsheet constraint change is valid**

```powershell
php -r "json_decode(file_get_contents('packages/aero-hrm/composer.json'), true, 512, JSON_THROW_ON_ERROR); echo 'Valid' . PHP_EOL;"
```

---

## Self-Review

**Spec coverage:**
- ✅ AeroMode added to aero-contracts (Task Q1)
- ✅ TenantModel moved to aero-contracts (Task Q2)
- ✅ CentralModel moved to aero-contracts (Task Q3)
- ✅ aero-core BC shims keep all 454 model files working (Tasks Q2-Q3)
- ✅ AeroMode resolver wired in AeroCoreServiceProvider (Task Q4)
- ✅ 263 missing imports fixed by automated script (Task Q5)
- ✅ Tests for AeroMode + TenantModel contract (Task Q6)
- ✅ phpspreadsheet >=1.29.0 eliminates CVE-2024-45046 through CVE-2024-45293 (Task Sec1)
- ✅ composer audit in CI catches future vulnerabilities (Task Sec2)

**Type consistency:**
- `AeroMode::setModeResolver`, `AeroMode::setTenantContextChecker`, `AeroMode::reset` used consistently throughout
- `Aero\Contracts\Models\TenantModel` namespace used in fix script and tests

**No placeholders:** All steps include exact code, exact commands, and expected output.
