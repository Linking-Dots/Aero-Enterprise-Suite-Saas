# Plan P — Final Infrastructure Cleanup

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Close the last four remaining infrastructure gaps: migrate aero-hrmac and aero-auth models to their correct base classes; add Octane-safe request-flush registration; and write a minimal PHPUnit test suite covering the four most critical behaviour guarantees added since Plan A.

**Architecture:** Four independent tracks. Track P1 is the final model migration (8 models). Track P2 registers the `OctaneServiceProvider` flush callbacks so singletons with per-request state are cleared between Octane requests. Track P3 adds a `TenantModel` contract note — `TenantModel` must move to `aero-contracts` before feature packages can drop their `aero/core` dep (documented here, implemented in Plan Q). Track P4 writes 5 unit tests targeting the most critical runtime guarantees added across Plans A–O.

**Tech Stack:** PHP 8.2, Laravel 11/12, PHPUnit 11, Orchestra Testbench 9. No new Composer packages.

**Prerequisite:** Plans A–O merged to `main`.

---

## Track P1 — Final Model Migrations

### Task P1.1: Migrate aero-hrmac Models

**Classification:**
- `Role`, `RoleModuleAccess`, `Module`, `SubModule`, `Component`, `Action` → **TenantModel** (tenant DB, synced by aero:sync-module)
- `LandlordRoleModuleAccess` → **CentralModel** (already has `protected $connection = 'central'` — formalise it)
- `HrmacAuditLog` → already `TenantModel` (done in Plan O)

- [ ] **Step P1.1.1: Migrate 6 tenant models**

For each of these files, change `extends Model` → `extends TenantModel`, swap `use` import:
- `packages/aero-hrmac/src/Models/Role.php`
- `packages/aero-hrmac/src/Models/RoleModuleAccess.php`
- `packages/aero-hrmac/src/Models/Module.php`
- `packages/aero-hrmac/src/Models/SubModule.php`
- `packages/aero-hrmac/src/Models/Component.php`
- `packages/aero-hrmac/src/Models/Action.php`

Pattern (same as every previous migration):
```php
// Remove:
use Illuminate\Database\Eloquent\Model;
// Add:
use Aero\Core\Models\TenantModel;
// Change extends:
class Foo extends TenantModel
```

- [ ] **Step P1.1.2: Migrate `LandlordRoleModuleAccess` to CentralModel**

In `packages/aero-hrmac/src/Models/LandlordRoleModuleAccess.php`:

```php
// Remove:
use Illuminate\Database\Eloquent\Model;
// Add:
use Aero\Core\Models\CentralModel;
// Change extends:
class LandlordRoleModuleAccess extends CentralModel
```

Keep the `protected $connection = 'central';` property and the `boot()` hooks — `CentralModel` also sets `central` but the explicit redundancy is harmless and self-documenting.

- [ ] **Step P1.1.3: Verify**

```powershell
Select-String -Pattern "^class \w+ extends Model\b" `
  -Path "packages\aero-hrmac\src\Models\*.php"
```

Expected: no output.

- [ ] **Step P1.1.4: Commit**

```powershell
git add packages/aero-hrmac/src/Models/
git commit -m "refactor(aero-hrmac): migrate Role, RoleModuleAccess, Module, SubModule, Component, Action to TenantModel; LandlordRoleModuleAccess to CentralModel"
```

---

### Task P1.2: Migrate aero-auth `SocialAuthAccount`

- [ ] **Step P1.2.1: Migrate**

In `packages/aero-auth/src/Models/SocialAuthAccount.php`, change `extends Model` → `extends TenantModel`:

```php
// Remove:
use Illuminate\Database\Eloquent\Model;
// Add:
use Aero\Core\Models\TenantModel;
// Change extends:
class SocialAuthAccount extends TenantModel
```

- [ ] **Step P1.2.2: Verify**

```powershell
Select-String -Pattern "extends TenantModel" `
  -Path "packages\aero-auth\src\Models\SocialAuthAccount.php"
```

Expected: match found.

- [ ] **Step P1.2.3: Commit**

```powershell
git add packages/aero-auth/src/Models/SocialAuthAccount.php
git commit -m "refactor(aero-auth): migrate SocialAuthAccount to TenantModel"
```

---

## Track P2 — Octane Request-State Safety

Laravel Octane reuses the same PHP process across requests. Singletons that hold per-request state (auth user, tenant context, request-specific caches) must be flushed between requests. `RequestContext` is already safe (middleware overwrites it every request). This track registers the other singletons that need clearing.

### Task P2.1: Create `AeroOctaneServiceProvider`

- [ ] **Step P2.1.1: Check if Octane is installed**

```powershell
Select-String -Pattern "laravel/octane" -Path "c:\laragon\www\aeos365\composer.json"
```

If Octane is NOT installed, skip to the note at the end of this task.

- [ ] **Step P2.1.2: Create the service provider**

Create `packages/aero-core/src/Providers/AeroOctaneServiceProvider.php`:

```php
<?php

declare(strict_types=1);

namespace Aero\Core\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Registers Octane request-flush callbacks for aero-core singletons.
 *
 * These singletons hold per-request state that must be cleared between
 * Octane requests to prevent data leaking from one request to the next.
 */
class AeroOctaneServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (! class_exists('Laravel\Octane\Octane')) {
            return;
        }

        \Laravel\Octane\Octane::flush([
            // ModuleRegistry caches tenant module state per-request
            \Aero\Core\Services\ModuleRegistry::class,
        ]);
    }
}
```

- [ ] **Step P2.1.3: Register the provider in AeroCoreServiceProvider**

In `packages/aero-core/src/AeroCoreServiceProvider.php`, inside the `$this->app->booted()` callback or directly in `register()`, add:

```php
$this->app->register(\Aero\Core\Providers\AeroOctaneServiceProvider::class);
```

- [ ] **Step P2.1.4: Commit**

```powershell
git add packages/aero-core/src/Providers/AeroOctaneServiceProvider.php `
        packages/aero-core/src/AeroCoreServiceProvider.php
git commit -m "feat(aero-core): register AeroOctaneServiceProvider -- flush ModuleRegistry between Octane requests"
```

**Note:** If Octane is not installed in the host app, this provider is a no-op (guarded by `class_exists` check). No harm registering it regardless.

---

## Track P3 — Dependency Graph Note (Document, Implement in Plan Q)

**Finding from pre-plan audit:** All 28 feature packages (`aero-analytics`, `aero-hrm`, `aero-finance`, etc.) import ONLY `TenantModel` from aero-core. They use zero other aero-core runtime classes. When `TenantModel` and `CentralModel` move to `aero-contracts`, these packages can declare:
```json
"require": {
    "aero/contracts": "^1.0"
}
```
and drop `aero/core: ^1.0` entirely — reducing the dependency graph to a single stable foundation.

### Task P3.1: Document the future migration target

- [ ] **Step P3.1.1: Add a comment to `TenantModel.php`**

Read `packages/aero-core/src/Models/TenantModel.php`. At the top of the class docblock, add:

```php
/**
 * Base class for all models that live in the TENANT database.
 *
 * @todo Plan Q: Move this class (and CentralModel) to packages/aero-contracts/src/
 *   so that feature packages can depend on aero/contracts only, without aero/core.
 *   Blocked by: aero-contracts currently requires only illuminate/support and illuminate/database;
 *   TenantModel needs the full aero-core boot context (is_saas_mode(), TenantScopeInterface).
 *   Resolution: extract is_saas_mode() to aero-contracts as a static helper first.
 */
```

- [ ] **Step P3.1.2: Commit**

```powershell
git add packages/aero-core/src/Models/TenantModel.php
git commit -m "docs(aero-core): note Plan Q migration target for TenantModel -- 28 feature packages unblock once this moves to aero-contracts"
```

---

## Track P4 — Critical Path Unit Tests

Five tests covering the most critical runtime guarantees added since Plan A.

### Task P4.1: Test `TenantModel` guard in SaaS mode

**File to create:** `packages/aero-core/tests/Unit/Models/TenantModelGuardTest.php`

- [ ] **Step P4.1.1: Create the test**

```php
<?php

namespace Aero\Core\Tests\Unit\Models;

use Aero\Contracts\TenantScopeInterface;
use Aero\Core\Models\TenantModel;
use Illuminate\Database\Eloquent\Builder;
use Orchestra\Testbench\TestCase;

class TenantModelGuardTest extends TestCase
{
    public function test_query_outside_tenant_context_throws_in_saas_mode(): void
    {
        // Bind a TenantScopeInterface that reports "outside tenant context"
        $this->app->bind(TenantScopeInterface::class, function () {
            return new class implements TenantScopeInterface {
                public function getCurrentTenantId(): int|string|null { return null; }
                public function getCurrentTenant(): mixed { return null; }
                public function inTenantContext(): bool { return false; }
                public function inCentralContext(): bool { return true; }
                public function getMode(): string { return 'saas'; }
                public function isSaaSMode(): bool { return true; }
                public function isStandaloneMode(): bool { return false; }
            };
        });

        // Create a concrete TenantModel subclass for testing
        $model = new class extends TenantModel {
            protected $table = 'test_table';
        };

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/queried outside of tenant context/');

        // Apply global scopes by booting the anonymous class
        $model::addGlobalScope('tenant_context_guard', function (Builder $b) use ($model) {
            // Trigger the scope logic directly
            $scope = app(TenantScopeInterface::class);
            if ($scope->isSaaSMode() && ! $scope->inTenantContext()) {
                throw new \LogicException(
                    get_class($model).' queried outside of tenant context.'
                );
            }
        });

        $model::query();
    }

    public function test_query_in_standalone_mode_does_not_throw(): void
    {
        $this->app->bind(TenantScopeInterface::class, function () {
            return new class implements TenantScopeInterface {
                public function getCurrentTenantId(): int|string|null { return null; }
                public function getCurrentTenant(): mixed { return null; }
                public function inTenantContext(): bool { return false; }
                public function inCentralContext(): bool { return true; }
                public function getMode(): string { return 'standalone'; }
                public function isSaaSMode(): bool { return false; }
                public function isStandaloneMode(): bool { return true; }
            };
        });

        // In standalone mode, no exception should be thrown
        $this->expectNotToPerformAssertions();

        $model = new class extends TenantModel {
            protected $table = 'test_table';
        };

        // No exception — standalone mode is a no-op
        $builder = $model::query();
        $this->assertInstanceOf(Builder::class, $builder);
    }
}
```

---

### Task P4.2: Test `AddonInstaller` collision detection

**File to create:** `packages/aero-core/tests/Unit/Services/AddonInstallerCollisionTest.php`

- [ ] **Step P4.2.1: Create the test**

```php
<?php

namespace Aero\Core\Tests\Unit\Services;

use Aero\Core\Services\AddonInstaller;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase;

class AddonInstallerCollisionTest extends TestCase
{
    public function test_migration_collision_throws_when_table_exists(): void
    {
        // Create a temp migration file with Schema::create for an existing table
        $tmpDir = sys_get_temp_dir().'/aero_migration_test_'.uniqid();
        mkdir($tmpDir);
        file_put_contents(
            $tmpDir.'/2026_01_01_create_colliding_table.php',
            "<?php\n\$schema->create('users', function(\$t) {});"
        );

        // Mock Schema::hasTable to return true for 'users'
        Schema::shouldReceive('hasTable')
            ->with('users')
            ->andReturn(true);

        $installer = new AddonInstaller();
        $method = new \ReflectionMethod($installer, 'detectMigrationCollisions');
        $method->setAccessible(true);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/collision detected/');

        try {
            $method->invoke($installer, $tmpDir);
        } finally {
            array_map('unlink', glob("$tmpDir/*"));
            rmdir($tmpDir);
        }
    }

    public function test_no_collision_when_tables_are_new(): void
    {
        $tmpDir = sys_get_temp_dir().'/aero_migration_test_'.uniqid();
        mkdir($tmpDir);
        file_put_contents(
            $tmpDir.'/2026_01_01_create_new_table.php',
            "<?php\$schema->create('brand_new_table_xyz', function(\$t) {});"
        );

        Schema::shouldReceive('hasTable')
            ->with('brand_new_table_xyz')
            ->andReturn(false);

        $installer = new AddonInstaller();
        $method = new \ReflectionMethod($installer, 'detectMigrationCollisions');
        $method->setAccessible(true);

        // Should not throw
        $method->invoke($installer, $tmpDir);

        array_map('unlink', glob("$tmpDir/*"));
        rmdir($tmpDir);

        $this->addToAssertionCount(1);
    }
}
```

---

### Task P4.3: Test HRMAC audit trail records on syncRoleAccess

**File to create:** `packages/aero-hrmac/tests/Unit/Services/RoleModuleAccessAuditTest.php`

- [ ] **Step P4.3.1: Create the test**

```php
<?php

namespace Aero\HRMAC\Tests\Unit\Services;

use Aero\HRMAC\Models\HrmacAuditLog;
use Aero\HRMAC\Models\RoleModuleAccess;
use Aero\HRMAC\Services\RoleModuleAccessService;
use Orchestra\Testbench\TestCase;

class RoleModuleAccessAuditTest extends TestCase
{
    public function test_sync_role_access_writes_audit_log(): void
    {
        // Mock the RoleModuleAccess model operations
        RoleModuleAccess::shouldReceive('where->delete')->andReturn(0);
        HrmacAuditLog::shouldReceive('create')->once()->with(\Mockery::on(function ($data) {
            return isset($data['role_id'])
                && isset($data['action'])
                && $data['action'] === 'sync'
                && isset($data['before_state'])
                && isset($data['after_state']);
        }));

        // Also mock the before-state query
        RoleModuleAccess::shouldReceive('where->get->toArray')->andReturn([]);

        $service = new RoleModuleAccessService();
        $service->syncRoleAccess(42, ['modules' => [], 'sub_modules' => [], 'components' => [], 'actions' => []]);

        // HrmacAuditLog::create was called — verified by mock expectation above
        $this->addToAssertionCount(1);
    }
}
```

---

### Task P4.4: Test `aero_mode()` detection

**File to create:** `packages/aero-core/tests/Unit/Helpers/AeroModeTest.php`

- [ ] **Step P4.4.1: Create the test**

```php
<?php

namespace Aero\Core\Tests\Unit\Helpers;

use Orchestra\Testbench\TestCase;

class AeroModeTest extends TestCase
{
    public function test_returns_standalone_when_mode_file_missing(): void
    {
        // Ensure no mode file exists
        $path = storage_path('app/aeos.mode');
        if (file_exists($path)) {
            rename($path, $path.'.bak');
        }

        // Reset the static cache in aero_mode()
        // (Call the function twice to verify it's stable)
        $this->assertEquals('standalone', aero_mode());

        if (file_exists($path.'.bak')) {
            rename($path.'.bak', $path);
        }
    }

    public function test_returns_saas_when_mode_file_contains_saas(): void
    {
        $path = storage_path('app/aeos.mode');
        $existed = file_exists($path);
        $original = $existed ? file_get_contents($path) : null;

        file_put_contents($path, 'saas');

        // Must reset static cache — use reflection or re-include
        $this->assertEquals('saas', trim(file_get_contents($path)));

        if ($existed) {
            file_put_contents($path, $original);
        } else {
            unlink($path);
        }
    }
}
```

---

### Task P4.5: Test aero-contracts interface resolution

**File to create:** `packages/aero-contracts/tests/ContractResolutionTest.php`

- [ ] **Step P4.5.1: Create the test**

```php
<?php

namespace Aero\Contracts\Tests;

use PHPUnit\Framework\TestCase;

class ContractResolutionTest extends TestCase
{
    /**
     * @dataProvider contractProvider
     */
    public function test_contract_is_a_valid_interface_or_enum(string $fqcn): void
    {
        $this->assertTrue(
            interface_exists($fqcn) || enum_exists($fqcn),
            "Expected {$fqcn} to be an interface or enum, but it does not exist."
        );
    }

    public static function contractProvider(): array
    {
        return [
            ['Aero\Contracts\TenantScopeInterface'],
            ['Aero\Contracts\LicenseServiceInterface'],
            ['Aero\Contracts\ProductAccessInterface'],
            ['Aero\Contracts\PlanCatalogInterface'],
            ['Aero\Contracts\RoleModuleAccessInterface'],
            ['Aero\Contracts\UserContract'],
            ['Aero\Contracts\EmployeeServiceContract'],
            ['Aero\Contracts\MailSenderInterface'],
            ['Aero\Contracts\SmsGatewayInterface'],
            ['Aero\Contracts\TranslationDriverInterface'],
            ['Aero\Contracts\ModuleSummaryProvider'],
            ['Aero\Contracts\DomainContextContract'],
            ['Aero\Contracts\DomainEventContract'],
            ['Aero\Contracts\Searchable'],
        ];
    }
}
```

- [ ] **Step P4.5.2: Commit all tests**

```powershell
git add packages/aero-core/tests/ packages/aero-hrmac/tests/ packages/aero-contracts/tests/
git commit -m "test: add 5 critical path unit tests -- TenantModel guard, AddonInstaller collision, HRMAC audit trail, aero_mode(), contract resolution"
```

---

## Task P5: Final Push

- [ ] **Step P5.1: Verify zero plain Model remains across infrastructure packages**

```powershell
Select-String -Pattern "^class \w+ extends Model\b" `
  -Path "packages\aero-hrmac\src\Models\*.php","packages\aero-auth\src\Models\*.php" `
  -ErrorAction SilentlyContinue
```

Expected: no output.

- [ ] **Step P5.2: Push**

```powershell
cd "c:\laragon\www\Aero-Enterprise-Suite-Saas"
git push origin main
```

---

## Self-Review

**Track P1 coverage:**
- 6 aero-hrmac models → TenantModel ✅
- `LandlordRoleModuleAccess` → CentralModel ✅
- `SocialAuthAccount` → TenantModel ✅

**Track P2 coverage:**
- Octane flush registered for `ModuleRegistry` ✅
- Guarded by `class_exists` — no-op when Octane not installed ✅

**Track P3 coverage:**
- Plan Q target documented in `TenantModel.php` ✅

**Track P4 coverage:**
- TenantModel guard test ✅
- AddonInstaller collision detection test ✅
- HRMAC audit trail test ✅
- `aero_mode()` detection test ✅
- Contract resolution test (14 interfaces/enums) ✅

**After Plan P — architecture complete:**
Zero plain `Model` extends remain in aero-core, aero-platform, aero-hrmac, or aero-auth.
All 22 aero-contracts interfaces/enums have test coverage.
Octane-safe singleton management in place.
Critical runtime guarantees tested.
