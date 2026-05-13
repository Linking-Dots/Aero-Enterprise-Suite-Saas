# Plan MN — Dashboard Registry Removal + aero-core Model Migration

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Two independent tracks. Track M removes the central `DashboardWidgetRegistry` pattern — 58 widget PHP files, the registry service, `AbstractDashboardWidget`, and cross-module imports from `AdminDashboardService` — replacing the core dashboard with a lightweight module launcher. Track N migrates all 25 aero-core models from plain `Model` to the correct `TenantModel` or `CentralModel` base class, enforcing multi-tenant query isolation at the model level.

**Architecture:** Track M: each package already owns its own dashboard controller (`HRMDashboardController`, `FinanceDashboardController`, etc.); removing the registry makes this the only pattern. The core `/dashboard` becomes a stateless module launcher showing available module cards from config — no DB queries from feature packages. Track N: `TenantModel` adds a global scope in SaaS mode that throws if queried outside tenant context; in standalone mode it is a no-op, so all existing standalone installs continue to work unchanged.

**Tech Stack:** Laravel 11/12, PHP 8.2. No new dependencies.

**Prerequisite:** Plans A–L merged to `main`.

---

## Track M — Remove Dashboard Widget Registry

### What Is Being Removed

| Item | Location | Count |
|---|---|---|
| Widget PHP classes (feature packages) | `packages/aero-{hrm,finance,dms,ims,pos,project,quality,rfi,scm,compliance}/src/Widgets/` | 43 files |
| Widget PHP classes (aero-core) | `packages/aero-core/src/Widgets/` | 7 files |
| `DashboardWidgetRegistry` service | `packages/aero-core/src/Services/DashboardWidgetRegistry.php` | 1 file |
| `AbstractDashboardWidget` | `packages/aero-core/src/Contracts/AbstractDashboardWidget.php` | 1 file |
| `DashboardWidgetInterface` | `packages/aero-contracts/src/DashboardWidgetInterface.php` | 1 file |
| `CoreWidgetCategory` enum | `packages/aero-contracts/src/CoreWidgetCategory.php` | 1 file |

### What Is Kept

- Per-module dashboard controllers (`HRMDashboardController`, `FinanceDashboardController`, etc.) — these are the correct pattern
- `ModuleSummaryProvider` interface in aero-contracts — still used by module summary services
- aero-platform widgets (platform admin dashboard — out of scope for this plan)
- `aero-notifications` widget (single file, notification-specific — out of scope)

---

### Task M1: Delete All Feature-Package Widget Classes

**Files to delete:** 43 widget PHP files across 10 feature packages.

- [ ] **Step M1.1: `git rm` all feature-package widget directories**

```powershell
git rm -r `
  packages/aero-hrm/src/Widgets/ `
  packages/aero-finance/src/Widgets/ `
  packages/aero-dms/src/Widgets/ `
  packages/aero-ims/src/Widgets/ `
  packages/aero-pos/src/Widgets/ `
  packages/aero-project/src/Widgets/ `
  packages/aero-quality/src/Widgets/ `
  packages/aero-rfi/src/Widgets/ `
  packages/aero-scm/src/Widgets/ `
  packages/aero-compliance/src/Widgets/
```

- [ ] **Step M1.2: Verify they are gone**

```powershell
Get-ChildItem "packages\aero-hrm\src\Widgets" -ErrorAction SilentlyContinue
```

Expected: no output (directory gone).

- [ ] **Step M1.3: Commit**

```powershell
git commit -m "feat(dashboard): delete 43 widget classes from feature packages -- each package now owns its own dashboard directly"
```

---

### Task M2: Remove Widget Registrations from Service Providers

Remove `registerDashboardWidgets()` calls and the entire `registerDashboardWidgets()` method body from each of these service providers:

- `packages/aero-hrm/src/Providers/HRMServiceProvider.php`
- `packages/aero-compliance/src/AeroComplianceServiceProvider.php`
- `packages/aero-project/src/AeroProjectServiceProvider.php`
- `packages/aero-rfi/src/AeroRfiServiceProvider.php`
- `packages/aero-scm/src/Providers/ScmModuleProvider.php`
- `packages/aero-quality/src/Providers/QualityModuleProvider.php`
- `packages/aero-pos/src/Providers/PosModuleProvider.php`
- `packages/aero-ims/src/Providers/ImsModuleProvider.php`
- `packages/aero-finance/src/Providers/FinanceModuleProvider.php`
- `packages/aero-dms/src/Providers/DmsModuleProvider.php`

For each file:

- [ ] **Step M2.1: Read each service provider**

For each file, find the `registerDashboardWidgets()` method and remove:
1. The call to `$this->registerDashboardWidgets();` from `boot()`
2. The entire `protected function registerDashboardWidgets(): void { ... }` method body
3. The `use Aero\Core\Services\DashboardWidgetRegistry;` import

- [ ] **Step M2.2: Verify no DashboardWidgetRegistry imports remain in feature packages**

```powershell
Select-String -Pattern "DashboardWidgetRegistry" `
  -Path "packages\aero-hrm\*","packages\aero-finance\*","packages\aero-dms\*", `
         "packages\aero-ims\*","packages\aero-pos\*","packages\aero-project\*", `
         "packages\aero-quality\*","packages\aero-rfi\*","packages\aero-scm\*", `
         "packages\aero-compliance\*" `
  -Recurse |
  Where-Object { $_.Path -notmatch "\\vendor\\" }
```

Expected: no output.

- [ ] **Step M2.3: Commit**

```powershell
git add packages/
git commit -m "feat(dashboard): remove registerDashboardWidgets() from all 10 feature service providers"
```

---

### Task M3: Remove Core Widget Classes + Registry + AbstractDashboardWidget

**Files:**
- Delete: `packages/aero-core/src/Widgets/` (7 files)
- Delete: `packages/aero-core/src/Services/DashboardWidgetRegistry.php`
- Delete: `packages/aero-core/src/Contracts/AbstractDashboardWidget.php`
- Delete: `packages/aero-contracts/src/DashboardWidgetInterface.php`
- Delete: `packages/aero-contracts/src/CoreWidgetCategory.php`
- Modify: `packages/aero-core/src/AeroCoreServiceProvider.php`

- [ ] **Step M3.1: `git rm` the files**

```powershell
git rm -r packages/aero-core/src/Widgets/
git rm packages/aero-core/src/Services/DashboardWidgetRegistry.php
git rm packages/aero-core/src/Contracts/AbstractDashboardWidget.php
git rm packages/aero-contracts/src/DashboardWidgetInterface.php
git rm packages/aero-contracts/src/CoreWidgetCategory.php
```

- [ ] **Step M3.2: Verify `aero-core/src/Contracts/` is now empty**

```powershell
Get-ChildItem "packages\aero-core\src\Contracts" -ErrorAction SilentlyContinue
```

Expected: no output or empty directory.

- [ ] **Step M3.3: Remove registry binding from `AeroCoreServiceProvider`**

Read `packages/aero-core/src/AeroCoreServiceProvider.php`. Find and remove:
1. `use Aero\Core\Services\DashboardWidgetRegistry;` import
2. `$this->app->singleton(DashboardWidgetRegistry::class);` binding
3. Any `DashboardWidgetRegistry`-related boot code
4. `use Aero\Contracts\DashboardWidgetInterface;` import (if present)
5. `use Aero\Contracts\CoreWidgetCategory;` import (if present)

- [ ] **Step M3.4: Verify no remaining references**

```powershell
Select-String -Pattern "DashboardWidgetRegistry|AbstractDashboardWidget|DashboardWidgetInterface|CoreWidgetCategory" `
  -Path "packages\aero-core\src\*" -Recurse |
  Where-Object { $_.Path -notmatch "\\vendor\\" }
```

Expected: no output.

- [ ] **Step M3.5: Commit**

```powershell
git add packages/aero-core/ packages/aero-contracts/
git commit -m "feat(dashboard): delete DashboardWidgetRegistry, AbstractDashboardWidget, core widget classes, DashboardWidgetInterface, CoreWidgetCategory -- registry pattern fully removed"
```

---

### Task M4: Clean Up `AdminDashboardService` Cross-Module Imports

**File:** `packages/aero-core/src/Services/Dashboard/AdminDashboardService.php`

The service currently imports models from `aero-dms`, `aero-finance`, `aero-hrm`, `aero-project`, `aero-quality` to aggregate cross-module stats. This breaks package isolation — core must not know about feature modules.

- [ ] **Step M4.1: Remove all cross-module imports**

Read `packages/aero-core/src/Services/Dashboard/AdminDashboardService.php`. Remove these `use` statements:
```php
use Aero\DMS\Models\Document;
use Aero\Finance\Models\Invoice;
use Aero\HRM\Models\Department;
use Aero\HRM\Models\Holiday;
use Aero\HRM\Models\LeaveRequest;
use Aero\Project\Models\Task;
use Aero\Quality\Models\NonConformanceReport;
```

- [ ] **Step M4.2: Remove methods that depend on those models**

Find and delete any method in `AdminDashboardService` that queries those removed models (e.g., `getHrmStats()`, `getProjectStats()`, `getDocumentStats()`). Replace the call sites in `DashboardController` or the Inertia props with an empty array or remove the prop entirely.

The service should only use models from `aero-core`: `User`, `AuditLog`, `Announcement`, `UserSession`, `Role` (from aero-hrmac).

- [ ] **Step M4.3: Verify no cross-module imports remain**

```powershell
Select-String -Pattern "use Aero\\(DMS|Finance|HRM|Project|Quality|SCM|IMS|POS|RFI|Compliance)\\" `
  -Path "packages\aero-core\src\*" -Recurse |
  Where-Object { $_.Path -notmatch "\\vendor\\" }
```

Expected: no output.

- [ ] **Step M4.4: Commit**

```powershell
git add packages/aero-core/src/Services/Dashboard/AdminDashboardService.php `
        packages/aero-core/src/Http/Controllers/DashboardController.php
git commit -m "fix(aero-core): remove cross-module imports from AdminDashboardService -- core dashboard only uses core data"
```

---

### Task M5: Update Core Dashboard to Module Launcher

**File:** `packages/aero-ui/resources/js/Pages/Tenant/Dashboard.jsx` (or equivalent)

The core `/dashboard` should be a lightweight **module launcher** — shows a grid of enabled modules with icons and links to each module's own dashboard. No DB queries from feature packages.

- [ ] **Step M5.1: Update `DashboardController::index`**

Replace the `index()` method body in `packages/aero-core/src/Http/Controllers/DashboardController.php` with:

```php
public function index(Request $request): Response
{
    $user = $request->user();

    return Inertia::render('Tenant/Dashboard', [
        'title'          => 'Dashboard',
        'user'           => [
            'name'  => $user?->name ?? 'User',
            'email' => $user?->email,
        ],
        'stats'          => Inertia::defer(fn () => $this->dashboardService->getCoreStats()),
        'recentActivity' => Inertia::defer(fn () => $this->dashboardService->getRecentActivity()),
    ]);
}
```

This defers the data-heavy calls and removes any cross-module data fetching.

- [ ] **Step M5.2: Commit**

```powershell
git add packages/aero-core/src/Http/Controllers/DashboardController.php
git commit -m "feat(dashboard): core /dashboard is now a lightweight launcher with deferred stats -- no cross-module queries"
```

---

## Track N — aero-core Model Migration to TenantModel

### Classification

**TenantModel** (live in tenant DB in SaaS; single DB in standalone — TenantModel is safe both ways):

| Model | Rationale |
|---|---|
| `AuditLog` | Per-tenant audit trail |
| `Announcement` | Per-tenant announcements |
| `CompanySetting` | Per-tenant company config |
| `Comment`, `CommentMention`, `CommentReaction` | Per-tenant social data |
| `DashboardPreference` | Per-user/tenant preferences |
| `UserDevice` | Per-tenant device tracking |
| `UserInvitation` | Per-tenant invitations |
| `UserPreference` | Per-user preferences |
| `UserNavigationPreference` | Per-user nav prefs |
| `UserNavigationAnalytic` | Per-user analytics |
| `UserSession` | Per-tenant sessions |
| `Activity` | Per-tenant activity log |
| `DataExport` | Per-tenant exports |
| `RetentionPolicy` | Per-tenant data retention |
| `SavedView` | Per-tenant saved filters |
| `Tag` | Per-tenant tags |
| `TenantInvitation` | Per-tenant invitations |
| `BackupConfiguration` | Per-tenant backup config |
| `Backup` | Per-tenant backups |
| `Module`, `SubModule`, `Component`, `Action` | Synced to tenant DB by aero:sync-module |
| `ModuleComponent`, `ModuleComponentAction` | Synced to tenant DB |
| `SystemSetting` | Per-tenant settings (in SaaS), global (standalone) |
| `User` | Per-tenant users |

**Stay as plain `Model`** (standalone-only or uncertain):

| Model | Rationale |
|---|---|
| `InstalledAddon` | Standalone-only; no tenant context |
| `ModuleLicense` | Platform licensing; check before migrating |

---

### Task N1: Migrate Leaf Models (No FK Complexity)

These models have no relationships to other tenant models that would be affected by the base class change.

**Files to modify:** `AuditLog`, `Announcement`, `Activity`, `Tag`, `SavedView`, `DataExport`, `RetentionPolicy`, `BackupConfiguration`, `Backup`

- [ ] **Step N1.1: Migrate `AuditLog`**

In `packages/aero-core/src/Models/AuditLog.php`:

Change:
```php
use Illuminate\Database\Eloquent\Model;
// ...
class AuditLog extends Model implements Searchable
```
to:
```php
use Aero\Core\Models\TenantModel;
// ...
class AuditLog extends TenantModel implements Searchable
```
Remove the `use Illuminate\Database\Eloquent\Model;` line.

- [ ] **Step N1.2: Migrate `Announcement`**

In `packages/aero-core/src/Models/Announcement.php`:
Change `extends Model` → `extends TenantModel`, swap `use` import.

- [ ] **Step N1.3: Migrate `Activity`**

In `packages/aero-core/src/Models/Activity.php`:
Change `extends Model` → `extends TenantModel`, swap `use` import.

- [ ] **Step N1.4: Migrate `Tag`**

In `packages/aero-core/src/Models/Tag.php`:
Change `extends Model` → `extends TenantModel`, swap `use` import.

- [ ] **Step N1.5: Migrate `SavedView`**

In `packages/aero-core/src/Models/SavedView.php`:
Change `extends Model` → `extends TenantModel`, swap `use` import.

- [ ] **Step N1.6: Migrate `DataExport`**

In `packages/aero-core/src/Models/DataExport.php`:
Change `extends Model` → `extends TenantModel`, swap `use` import.

- [ ] **Step N1.7: Migrate `RetentionPolicy`**

In `packages/aero-core/src/Models/RetentionPolicy.php`:
Change `extends Model` → `extends TenantModel`, swap `use` import.

- [ ] **Step N1.8: Migrate `BackupConfiguration` and `Backup`**

In `packages/aero-core/src/Models/BackupConfiguration.php`:
Change `extends Model` → `extends TenantModel`, swap `use` import.

In `packages/aero-core/src/Models/Backup.php`:
Change `extends Model` → `extends TenantModel`, swap `use` import.

- [ ] **Step N1.9: Verify**

```powershell
Select-String -Pattern "extends Model\b" `
  -Path "packages\aero-core\src\Models\AuditLog.php","packages\aero-core\src\Models\Announcement.php", `
         "packages\aero-core\src\Models\Activity.php","packages\aero-core\src\Models\Tag.php", `
         "packages\aero-core\src\Models\SavedView.php"
```

Expected: no output.

- [ ] **Step N1.10: Commit**

```powershell
git add packages/aero-core/src/Models/AuditLog.php `
        packages/aero-core/src/Models/Announcement.php `
        packages/aero-core/src/Models/Activity.php `
        packages/aero-core/src/Models/Tag.php `
        packages/aero-core/src/Models/SavedView.php `
        packages/aero-core/src/Models/DataExport.php `
        packages/aero-core/src/Models/RetentionPolicy.php `
        packages/aero-core/src/Models/BackupConfiguration.php `
        packages/aero-core/src/Models/Backup.php
git commit -m "refactor(aero-core): migrate leaf models to TenantModel -- AuditLog, Announcement, Activity, Tag, SavedView, DataExport, RetentionPolicy, Backup, BackupConfiguration"
```

---

### Task N2: Migrate User-Related Models

- [ ] **Step N2.1: Migrate user preference + session models**

For each of these files, change `extends Model` → `extends TenantModel` and swap the `use` import:
- `packages/aero-core/src/Models/UserPreference.php`
- `packages/aero-core/src/Models/UserDevice.php`
- `packages/aero-core/src/Models/UserInvitation.php`
- `packages/aero-core/src/Models/UserNavigationPreference.php`
- `packages/aero-core/src/Models/UserNavigationAnalytic.php`
- `packages/aero-core/src/Models/UserSession.php`
- `packages/aero-core/src/Models/DashboardPreference.php`

- [ ] **Step N2.2: Migrate `CompanySetting` and `SystemSetting`**

In `packages/aero-core/src/Models/CompanySetting.php`:
Change `extends Model` → `extends TenantModel`, swap `use` import.

In `packages/aero-core/src/Models/SystemSetting.php`:
Change `extends Model` → `extends TenantModel`, swap `use` import. Note: `SystemSetting` implements `HasMedia` — ensure the import list stays correct.

- [ ] **Step N2.3: Migrate `Comment`, `CommentMention`, `CommentReaction`**

For each:
- `packages/aero-core/src/Models/Comment.php` → `extends TenantModel`
- `packages/aero-core/src/Models/CommentMention.php` → `extends TenantModel`
- `packages/aero-core/src/Models/CommentReaction.php` → `extends TenantModel`

- [ ] **Step N2.4: Migrate `TenantInvitation`**

In `packages/aero-core/src/Models/TenantInvitation.php`:
Change `extends Model` → `extends TenantModel`, swap `use` import.

- [ ] **Step N2.5: Commit**

```powershell
git add packages/aero-core/src/Models/UserPreference.php `
        packages/aero-core/src/Models/UserDevice.php `
        packages/aero-core/src/Models/UserInvitation.php `
        packages/aero-core/src/Models/UserNavigationPreference.php `
        packages/aero-core/src/Models/UserNavigationAnalytic.php `
        packages/aero-core/src/Models/UserSession.php `
        packages/aero-core/src/Models/DashboardPreference.php `
        packages/aero-core/src/Models/CompanySetting.php `
        packages/aero-core/src/Models/SystemSetting.php `
        packages/aero-core/src/Models/Comment.php `
        packages/aero-core/src/Models/CommentMention.php `
        packages/aero-core/src/Models/CommentReaction.php `
        packages/aero-core/src/Models/TenantInvitation.php
git commit -m "refactor(aero-core): migrate user + setting + comment models to TenantModel"
```

---

### Task N3: Migrate Module-Hierarchy Models

These are synced to the tenant DB via `aero:sync-module`.

- [ ] **Step N3.1: Migrate module hierarchy**

For each of these files, change `extends Model` → `extends TenantModel`, swap `use` import:
- `packages/aero-core/src/Models/Module.php`
- `packages/aero-core/src/Models/SubModule.php`
- `packages/aero-core/src/Models/Component.php`
- `packages/aero-core/src/Models/Action.php`
- `packages/aero-core/src/Models/ModuleComponent.php`
- `packages/aero-core/src/Models/ModuleComponentAction.php`

Note: `Module.php` has `use Aero\Core\Models\TenantModel` already referenced somewhere? Read the file first to check. If it already has tenancy handling, just extend `TenantModel` cleanly.

- [ ] **Step N3.2: Commit**

```powershell
git add packages/aero-core/src/Models/Module.php `
        packages/aero-core/src/Models/SubModule.php `
        packages/aero-core/src/Models/Component.php `
        packages/aero-core/src/Models/Action.php `
        packages/aero-core/src/Models/ModuleComponent.php `
        packages/aero-core/src/Models/ModuleComponentAction.php
git commit -m "refactor(aero-core): migrate module-hierarchy models to TenantModel -- Module, SubModule, Component, Action, ModuleComponent, ModuleComponentAction"
```

---

### Task N4: Migrate `User` Model

`User` is the most complex model — it implements `UserContract`, uses several traits, and is the authentication model. Migrate carefully.

- [ ] **Step N4.1: Read `User.php` in full**

```powershell
Get-Content "packages\aero-core\src\Models\User.php" | Select-Object -First 30
```

Confirm the current extends clause and all traits in use.

- [ ] **Step N4.2: Change to extend `TenantModel`**

In `packages/aero-core/src/Models/User.php`:

1. Remove: `use Illuminate\Database\Eloquent\Model;` (if present) OR keep `use Illuminate\Foundation\Auth\User as Authenticatable;`
2. If `User` currently extends `Authenticatable` (which extends `Model`), you must make it extend BOTH `Authenticatable` AND get the `TenantModel` global scope. The cleanest approach: add `TenantModel`'s boot logic as a trait instead of changing the parent class.

Since `User` must extend `Authenticatable` (Laravel auth requirement), add the tenant guard as a trait:

Create `packages/aero-core/src/Models/Concerns/EnforcesTenantContext.php`:

```php
<?php

namespace Aero\Core\Models\Concerns;

use Aero\Contracts\TenantScopeInterface;
use Illuminate\Database\Eloquent\Builder;

trait EnforcesTenantContext
{
    protected static function bootEnforcesTenantContext(): void
    {
        static::addGlobalScope('tenant_context_guard', function (Builder $builder) {
            if (! is_saas_mode()) {
                return;
            }
            try {
                $scope = app(TenantScopeInterface::class);
                if (! $scope->inTenantContext()) {
                    throw new \LogicException(
                        static::class . ' queried outside of tenant context.'
                    );
                }
            } catch (\LogicException $e) {
                throw $e;
            } catch (\Throwable) {
                // Allow during early boot
            }
        });
    }
}
```

Then in `User.php`, add `use EnforcesTenantContext;` to the class body.

- [ ] **Step N4.3: Commit**

```powershell
git add packages/aero-core/src/Models/Concerns/EnforcesTenantContext.php `
        packages/aero-core/src/Models/User.php
git commit -m "refactor(aero-core): User model uses EnforcesTenantContext trait -- cannot extend TenantModel directly due to Authenticatable chain"
```

---

### Task N5: Final Verification

- [ ] **Step N5.1: Count remaining plain `Model` extends in aero-core**

```powershell
Select-String -Pattern "^class \w+ extends Model\b" `
  -Path "packages\aero-core\src\Models\*.php" |
  Select-Object Filename, Line
```

Expected: only `InstalledAddon.php` and `ModuleLicense.php` (standalone-only models that intentionally stay as `Model`).

- [ ] **Step N5.2: Count `TenantModel` extends**

```powershell
Select-String -Pattern "extends TenantModel" `
  -Path "packages\aero-core\src\Models\*.php" |
  Measure-Object | Select-Object -ExpandProperty Count
```

Expected: 23 (all tenant models migrated).

- [ ] **Step N5.3: Push all commits**

```powershell
cd "c:\laragon\www\Aero-Enterprise-Suite-Saas"
git push origin main
```

---

## Self-Review

**Track M coverage:**
- 43 feature-package widget classes deleted → M1 ✅
- Widget registrations removed from 10 service providers → M2 ✅
- `DashboardWidgetRegistry` deleted → M3 ✅
- `AbstractDashboardWidget` deleted (aero-core/Contracts now empty) → M3 ✅
- `DashboardWidgetInterface` + `CoreWidgetCategory` deleted from aero-contracts → M3 ✅
- `AdminDashboardService` cross-module imports removed → M4 ✅
- `DashboardController` simplified to deferred launcher → M5 ✅

**Track N coverage:**
- 9 leaf models → TenantModel → N1 ✅
- 13 user/setting/comment models → TenantModel → N2 ✅
- 6 module-hierarchy models → TenantModel → N3 ✅
- `User` model uses `EnforcesTenantContext` trait → N4 ✅
- `InstalledAddon`, `ModuleLicense` intentionally left as `Model` (standalone-only) → N5 ✅

**Deferred:**
- aero-platform model migration (45+ models → `CentralModel`) — separate plan due to scope
- Feature-package model migrations (aero-hrm, aero-finance models) — separate plan, package by package
- `ModuleLicense` classification (needs investigation)
- aero-notifications widget (kept, out of scope)
- aero-platform widget registry (kept, platform admin dashboard — different concern)
