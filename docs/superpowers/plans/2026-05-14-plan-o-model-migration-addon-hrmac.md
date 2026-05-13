# Plan O — Complete Model Migration + AddonInstaller Hardening + HRMAC Audit Trail

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Four independent tracks executed in priority order. Track O1 migrates all 46 aero-platform models to `CentralModel`. Track O2 migrates all 456 feature-package models to `TenantModel`. Track O3 adds opcache invalidation and migration collision detection to `AddonInstaller`. Track O4 adds an HRMAC permission-change audit trail.

**Architecture:** O1 and O2 use the same automated PowerShell script pattern as Plan MN — find `extends Model`, swap import, change extends, write back with no BOM. O3 adds two targeted method additions inside `AddonInstaller`. O4 creates a `hrmac_audit_log` table, `HrmacAuditLog` model, and hooks into `RoleModuleAccessService::syncRoleAccess()`.

**Tech Stack:** PHP 8.2, Laravel 11/12. No new Composer packages.

**Prerequisite:** Plan MN merged to `main` (aero-core models already migrated).

---

## Track O1 — aero-platform Model Migration to CentralModel

All models in `packages/aero-platform/src/Models/` live in the central (landlord) database. They must extend `CentralModel` (which pins `$connection = 'central'`). `Plan.php` already extends `CentralModel` — skip it.

**Total:** 46 models → `CentralModel` (1 already done).

### Task O1.1: Automated migration script

- [ ] **Step O1.1.1: Run the migration script**

```powershell
$utf8NoBom = New-Object System.Text.UTF8Encoding $false
$baseClass = 'CentralModel'
$baseImport = 'Aero\Core\Models\CentralModel'
$changed = 0

Get-ChildItem "c:\laragon\www\Aero-Enterprise-Suite-Saas\packages\aero-platform\src\Models" -Filter "*.php" |
    ForEach-Object {
        $bytes = [System.IO.File]::ReadAllBytes($_.FullName)
        $content = [System.Text.Encoding]::UTF8.GetString($bytes)

        # Skip files that already extend CentralModel or TenantModel
        if ($content -match "extends $baseClass\b" -or $content -match "extends TenantModel\b") { return }

        # Skip files that don't extend Model
        if ($content -notmatch "extends Model\b") { return }

        # Add CentralModel import (after the last existing use statement)
        if ($content -notmatch "use $baseImport;") {
            $content = $content -replace "(use Illuminate\\Database\\Eloquent\\Model;)", "use $baseImport;`n`$1"
        }

        # Change extends
        $content = $content -replace "extends Model\b", "extends $baseClass"

        # Remove Model import only if Model is no longer referenced
        if ($content -notmatch "(?<!CentralModel|TenantModel)\\bModel::" -and
            $content -notmatch "use Illuminate\\Database\\Eloquent\\Model as " -and
            $content -notmatch "\bModel\b(?! as )" -or
            ($content | Select-String "\\bModel\\b" -AllMatches).Matches.Count -le 1) {
            $content = $content -replace "use Illuminate\\Database\\Eloquent\\Model;\n?", ""
        }

        [System.IO.File]::WriteAllBytes($_.FullName, $utf8NoBom.GetBytes($content))
        $changed++
        Write-Host "Migrated: $($_.Name)"
    }

Write-Host "`nTotal migrated: $changed"
```

Expected: output of ~45 filenames + `Total migrated: 45` (or similar — depends on how many already have CentralModel).

- [ ] **Step O1.1.2: Verify**

```powershell
# Must show only Plan.php (already CentralModel)
Select-String -Pattern "extends CentralModel" `
  -Path "c:\laragon\www\Aero-Enterprise-Suite-Saas\packages\aero-platform\src\Models\*.php" |
  Measure-Object | Select-Object -ExpandProperty Count
```

Expected: 46 (all models now extend CentralModel).

```powershell
# Must show zero plain Model extends
Select-String -Pattern "^class \w+ extends Model\b" `
  -Path "c:\laragon\www\Aero-Enterprise-Suite-Saas\packages\aero-platform\src\Models\*.php" |
  Measure-Object | Select-Object -ExpandProperty Count
```

Expected: 0.

- [ ] **Step O1.1.3: Commit**

```powershell
cd "c:\laragon\www\Aero-Enterprise-Suite-Saas"
git add packages/aero-platform/src/Models/
git commit -m "refactor(aero-platform): migrate all 46 platform models to CentralModel -- platform DB writes are now connection-pinned"
```

---

## Track O2 — Feature Package Model Migration to TenantModel

All models in feature packages live in the tenant database. They must extend `TenantModel`. The script runs across all 26 feature packages in one pass.

**Total:** ~456 models across 26 packages → `TenantModel`.

**Packages in scope:**
`aero-analytics`, `aero-assistant`, `aero-blockchain`, `aero-cms`, `aero-commerce`, `aero-compliance`, `aero-crm`, `aero-custom-fields`, `aero-dms`, `aero-eam`, `aero-education`, `aero-field-service`, `aero-finance`, `aero-forms`, `aero-healthcare`, `aero-helpdesk`, `aero-hrm`, `aero-ims`, `aero-integration`, `aero-iot`, `aero-manufacturing`, `aero-mobile`, `aero-pos`, `aero-project`, `aero-quality`, `aero-real-estate`, `aero-rfi`, `aero-scm`, `aero-time-tracking`, `aero-workflow`

### Task O2.1: Automated migration script

- [ ] **Step O2.1.1: Run the migration script**

```powershell
$utf8NoBom = New-Object System.Text.UTF8Encoding $false
$baseClass   = 'TenantModel'
$baseImport  = 'Aero\Core\Models\TenantModel'
$packagesRoot = "c:\laragon\www\Aero-Enterprise-Suite-Saas\packages"

$featurePkgs = @(
    'aero-analytics','aero-assistant','aero-blockchain','aero-cms','aero-commerce',
    'aero-compliance','aero-crm','aero-custom-fields','aero-dms','aero-eam',
    'aero-education','aero-field-service','aero-finance','aero-forms','aero-healthcare',
    'aero-helpdesk','aero-hrm','aero-ims','aero-integration','aero-iot',
    'aero-manufacturing','aero-mobile','aero-pos','aero-project','aero-quality',
    'aero-real-estate','aero-rfi','aero-scm','aero-time-tracking','aero-workflow'
)

$totalChanged = 0

foreach ($pkg in $featurePkgs) {
    $modelsDir = "$packagesRoot\$pkg\src\Models"
    if (-not (Test-Path $modelsDir)) { continue }

    $pkgChanged = 0
    Get-ChildItem $modelsDir -Filter "*.php" | ForEach-Object {
        $bytes = [System.IO.File]::ReadAllBytes($_.FullName)
        $content = [System.Text.Encoding]::UTF8.GetString($bytes)

        # Skip already-migrated or non-Model files
        if ($content -match "extends $baseClass\b" -or $content -match "extends CentralModel\b") { return }
        if ($content -notmatch "extends Model\b") { return }

        # Add TenantModel import
        if ($content -notmatch "use $baseImport;") {
            $content = $content -replace "(use Illuminate\\Database\\Eloquent\\Model;)", "use $baseImport;`n`$1"
        }

        # Change extends
        $content = $content -replace "extends Model\b", "extends $baseClass"

        # Remove Model import if no longer referenced elsewhere
        $modelRefs = ([regex]::Matches($content, '\bModel\b')).Count
        if ($modelRefs -le 1) {
            $content = $content -replace "use Illuminate\\Database\\Eloquent\\Model;\r?\n?", ""
        }

        [System.IO.File]::WriteAllBytes($_.FullName, $utf8NoBom.GetBytes($content))
        $pkgChanged++
    }
    if ($pkgChanged -gt 0) { Write-Host "$pkg`: $pkgChanged models migrated" }
    $totalChanged += $pkgChanged
}

Write-Host "`nTotal migrated across all feature packages: $totalChanged"
```

Expected: output listing each package with its model count, and a total of ~450+.

- [ ] **Step O2.1.2: Verify**

```powershell
$total = 0
foreach ($pkg in @('aero-hrm','aero-finance','aero-crm','aero-commerce','aero-project')) {
    $count = (Select-String -Pattern "extends TenantModel" `
      -Path "c:\laragon\www\Aero-Enterprise-Suite-Saas\packages\$pkg\src\Models\*.php" `
      -ErrorAction SilentlyContinue | Measure-Object).Count
    Write-Host "$pkg`: $count TenantModel"
    $total += $count
}
Write-Host "Sample total: $total"
```

Expected: each sampled package shows its full model count.

- [ ] **Step O2.1.3: Commit**

```powershell
cd "c:\laragon\www\Aero-Enterprise-Suite-Saas"
git add packages/
git commit -m "refactor(feature-packages): migrate ~456 feature-package models to TenantModel -- tenant query isolation now enforced at model level across all modules"
```

---

## Track O3 — AddonInstaller Hardening

**File:** `packages/aero-core/src/Services/AddonInstaller.php`

### Task O3.1: Opcache Invalidation After Extract

After a ZIP is extracted, PHP may serve cached bytecode for the newly-written files. Calling `opcache_reset()` forces the opcache to recompile the new files on next request.

- [ ] **Step O3.1.1: Add opcache invalidation**

In `packages/aero-core/src/Services/AddonInstaller.php`, find the `extract()` private method. After `$zip->close();` inside `extract()`, add:

```php
        // Invalidate opcache for newly extracted files so PHP picks them up immediately
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
```

The `extract()` method currently ends like:
```php
    private function extract(string $zipPath, string $targetDir): void
    {
        if (! is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException("Cannot extract ZIP: {$zipPath}");
        }

        $zip->extractTo($targetDir);
        $zip->close();
    }
```

After the change:
```php
    private function extract(string $zipPath, string $targetDir): void
    {
        if (! is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException("Cannot extract ZIP: {$zipPath}");
        }

        $zip->extractTo($targetDir);
        $zip->close();

        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
    }
```

- [ ] **Step O3.1.2: Verify**

```powershell
Select-String -Pattern "opcache_reset" `
  -Path "packages\aero-core\src\Services\AddonInstaller.php"
```

Expected: match found.

---

### Task O3.2: Migration Collision Detection

Before running migrations for a new add-on, check if any migration would create a table that already exists. This prevents silent failures or corrupted installs when two add-ons own the same table name.

- [ ] **Step O3.2.1: Add collision detection method**

Add this private method to `AddonInstaller`:

```php
    private function detectMigrationCollisions(string $migrationsPath): void
    {
        $migrationFiles = glob("{$migrationsPath}/*.php");
        if (empty($migrationFiles)) {
            return;
        }

        $collisions = [];

        foreach ($migrationFiles as $file) {
            $content = file_get_contents($file);
            // Extract table names from Schema::create('table_name', ...) calls
            preg_match_all('/Schema::create\s*\(\s*[\'"]([^\'"]+)[\'"]/m', $content, $matches);
            foreach ($matches[1] as $tableName) {
                if (\Illuminate\Support\Facades\Schema::hasTable($tableName)) {
                    $collisions[] = $tableName;
                }
            }
        }

        if (! empty($collisions)) {
            throw new \RuntimeException(
                'Add-on migration collision detected. The following tables already exist and would be overwritten: '
                . implode(', ', array_unique($collisions))
                . '. Remove the conflicting add-on before installing this one.'
            );
        }
    }
```

- [ ] **Step O3.2.2: Call it before running migrations**

In the `install()` method, find the migrations block:
```php
        $migrationsPath = "{$fullPath}/database/migrations";
        if (is_dir($migrationsPath)) {
            Artisan::call('migrate', [
```

Change it to:
```php
        $migrationsPath = "{$fullPath}/database/migrations";
        if (is_dir($migrationsPath)) {
            $this->detectMigrationCollisions($migrationsPath);
            Artisan::call('migrate', [
```

Also add the Schema facade import at the top of the file (if not already present):
```php
use Illuminate\Support\Facades\Schema;
```

- [ ] **Step O3.2.3: Verify**

```powershell
Select-String -Pattern "detectMigrationCollisions|opcache_reset" `
  -Path "packages\aero-core\src\Services\AddonInstaller.php"
```

Expected: both patterns found.

- [ ] **Step O3.2.4: Commit**

```powershell
git add packages/aero-core/src/Services/AddonInstaller.php
git commit -m "fix(aero-core): AddonInstaller -- opcache_reset() after extract + migration collision detection before migrate"
```

---

## Track O4 — HRMAC Permission-Change Audit Trail

Every call to `syncRoleAccess()` records the before/after state to a dedicated audit log table. Answers the question: who changed what role permissions, when, from which IP?

### Task O4.1: Create the Audit Log Migration

**File to create:** `packages/aero-hrmac/database/migrations/2026_05_14_000001_create_hrmac_audit_log_table.php`

- [ ] **Step O4.1.1: Create the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hrmac_audit_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('actor_user_id')->nullable()->index();
            $table->unsignedBigInteger('role_id')->index();
            $table->string('action', 50)->default('sync');
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index(['role_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrmac_audit_log');
    }
};
```

- [ ] **Step O4.1.2: Verify**

```powershell
Test-Path "packages\aero-hrmac\database\migrations\2026_05_14_000001_create_hrmac_audit_log_table.php"
```

Expected: `True`

---

### Task O4.2: Create `HrmacAuditLog` Model

**File to create:** `packages/aero-hrmac/src/Models/HrmacAuditLog.php`

- [ ] **Step O4.2.1: Create the model**

```php
<?php

declare(strict_types=1);

namespace Aero\HRMAC\Models;

use Aero\Core\Models\TenantModel;

class HrmacAuditLog extends TenantModel
{
    protected $table = 'hrmac_audit_log';

    protected $fillable = [
        'actor_user_id',
        'role_id',
        'action',
        'before_state',
        'after_state',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'before_state' => 'array',
        'after_state'  => 'array',
    ];

    /**
     * Scope to entries for a specific role.
     */
    public function scopeForRole($query, int $roleId)
    {
        return $query->where('role_id', $roleId);
    }

    /**
     * Scope to recent entries.
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
```

---

### Task O4.3: Hook Audit Recording into `syncRoleAccess()`

**File:** `packages/aero-hrmac/src/Services/RoleModuleAccessService.php`

- [ ] **Step O4.3.1: Add audit recording to `syncRoleAccess()`**

Read the current `syncRoleAccess()` method (at line ~407). It:
1. Gets role ID
2. Deletes existing access
3. Creates new access records
4. (Implicit) clears cache at end

**Wrap the method to capture before/after state:**

Replace the opening of `syncRoleAccess()` to capture the before state, then at the end write the audit log:

```php
    public function syncRoleAccess(mixed $role, array $accessData): void
    {
        $roleId = is_object($role) ? $role->id : $role;

        // Capture before state for audit
        $beforeState = $this->modelForCurrentContext()::where('role_id', $roleId)
            ->get(['module_id', 'sub_module_id', 'component_id', 'action_id', 'access_scope'])
            ->toArray();

        // Clear existing access for this role
        $this->modelForCurrentContext()::where('role_id', $roleId)->delete();

        // ... (rest of creates — do NOT change these) ...

        // [at end, after all creates and cache clearing:]

        // Audit log
        try {
            \Aero\HRMAC\Models\HrmacAuditLog::create([
                'actor_user_id' => auth()->id(),
                'role_id'       => $roleId,
                'action'        => 'sync',
                'before_state'  => $beforeState,
                'after_state'   => $accessData,
                'ip_address'    => request()->ip(),
                'user_agent'    => request()->userAgent(),
            ]);
        } catch (\Throwable) {
            // Audit failure must not break the access sync
        }
    }
```

**Important:** Do NOT change any of the existing create loops. Only add:
1. The `$beforeState` capture at the very beginning (after `$roleId` assignment)
2. The `HrmacAuditLog::create(...)` block inside a try/catch at the very end of the method, after the existing cache-clearing code

- [ ] **Step O4.3.2: Verify**

```powershell
Select-String -Pattern "HrmacAuditLog|beforeState|actor_user_id" `
  -Path "packages\aero-hrmac\src\Services\RoleModuleAccessService.php"
```

Expected: all three terms found.

---

### Task O4.4: Commit Track O4

- [ ] **Step O4.4.1: Commit**

```powershell
git add packages/aero-hrmac/database/migrations/2026_05_14_000001_create_hrmac_audit_log_table.php `
        packages/aero-hrmac/src/Models/HrmacAuditLog.php `
        packages/aero-hrmac/src/Services/RoleModuleAccessService.php
git commit -m "feat(aero-hrmac): HRMAC permission-change audit trail -- hrmac_audit_log table + HrmacAuditLog model + hook in syncRoleAccess()"
```

---

## Task O5: Push + Final Count

- [ ] **Step O5.1: Confirm total TenantModel count across all packages**

```powershell
$total = 0
Get-ChildItem "c:\laragon\www\Aero-Enterprise-Suite-Saas\packages" -Recurse -Filter "*.php" |
    Where-Object { $_.FullName -notmatch "\\vendor\\" } |
    Select-String -Pattern "extends TenantModel\b" -Quiet |
    ForEach-Object { $total++ }
Write-Host "Total TenantModel subclasses: $total"
```

- [ ] **Step O5.2: Push**

```powershell
cd "c:\laragon\www\Aero-Enterprise-Suite-Saas"
git push origin main
```

---

## Self-Review

**Track O1 coverage:**
- 46 aero-platform models → CentralModel (automated script) ✅

**Track O2 coverage:**
- ~456 feature-package models → TenantModel (automated script, 26 packages) ✅

**Track O3 coverage:**
- `opcache_reset()` after extract ✅
- Migration collision detection before `artisan migrate` ✅

**Track O4 coverage:**
- `hrmac_audit_log` migration ✅
- `HrmacAuditLog` model ✅
- `syncRoleAccess()` records before/after state ✅
- Audit failure never breaks the sync (try/catch) ✅

**Not in scope (deferred):**
- `grantModuleAccess()` / `revokeModuleAccess()` audit hooks (only `syncRoleAccess` is hooked)
- Audit log UI / query endpoint (backend-only in this plan)
- aero-hrmac model migration (HRMAC models like `RoleModuleAccess`, `Role`, `Module` live in tenant DB but are owned by aero-hrmac — separate plan)
