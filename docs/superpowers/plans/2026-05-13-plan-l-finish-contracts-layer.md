# Plan L — Finish the Contracts Layer

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Move the 4 remaining cross-package interfaces out of `aero-hrmac` and `aero-core` into `aero-contracts`, leaving `aero-core/src/Contracts/` with only `AbstractDashboardWidget.php` — completing the contracts layer extraction started in Plan K.

**Architecture:** Same clean-cut approach as Plan K — no backward-compat aliases. Create files in `aero-contracts`, run an automated script to update all import sites, delete the old files, update the one `composer.json` that needs a new dep (`aero-hrmac`). Four interfaces move: `RoleModuleAccessInterface` (aero-hrmac → aero-contracts, 20 import sites), `UserContract` (aero-core → aero-contracts, 2 import sites), `UserRepositoryContract` (aero-core → aero-contracts, 0 external sites), `EmployeeServiceContract` (aero-core → aero-contracts, 4 import sites).

**Tech Stack:** PHP 8.2, Composer 2. No new dependencies. `aero/contracts` already has `illuminate/support` + `illuminate/database` — both contracts that use `Collection` or `Builder` are already covered.

**Prerequisite:** Plan K merged to `main`. `aero-contracts` package exists with 18 interfaces already in `packages/aero-contracts/src/`.

---

## What Moves

| Old location | Interface | New location | Import sites to update |
|---|---|---|---|
| `aero-hrmac/src/Contracts/RoleModuleAccessInterface.php` | `Aero\HRMAC\Contracts\RoleModuleAccessInterface` | `aero-contracts/src/RoleModuleAccessInterface.php` | 19 PHP files + 1 README |
| `aero-core/src/Contracts/UserContract.php` | `Aero\Core\Contracts\UserContract` | `aero-contracts/src/UserContract.php` | 1 PHP file (`User.php`) |
| `aero-core/src/Contracts/UserRepositoryContract.php` | `Aero\Core\Contracts\UserRepositoryContract` | `aero-contracts/src/UserRepositoryContract.php` | 0 external PHP files |
| `aero-core/src/Contracts/EmployeeServiceContract.php` | `Aero\Core\Contracts\EmployeeServiceContract` | `aero-contracts/src/EmployeeServiceContract.php` | 4 PHP files |

## What Stays

| File | Reason |
|---|---|
| `aero-core/src/Contracts/AbstractDashboardWidget.php` | Abstract class using `Illuminate\Support\Facades\{DB,Log,Schema}` and `auth()` helper — framework-coupled. Move deferred to Plan M after full decoupling. |

After Plan L, `aero-core/src/Contracts/` contains **exactly one file**: `AbstractDashboardWidget.php`.

---

## File Map

**New files:**
- `packages/aero-contracts/src/RoleModuleAccessInterface.php`
- `packages/aero-contracts/src/UserContract.php`
- `packages/aero-contracts/src/UserRepositoryContract.php`
- `packages/aero-contracts/src/EmployeeServiceContract.php`

**Deleted:**
- `packages/aero-hrmac/src/Contracts/RoleModuleAccessInterface.php`
- `packages/aero-core/src/Contracts/UserContract.php`
- `packages/aero-core/src/Contracts/UserRepositoryContract.php`
- `packages/aero-core/src/Contracts/EmployeeServiceContract.php`

**Modified:**
- `packages/aero-hrmac/composer.json` — add `"aero/contracts": "^1.0"`
- ~25 PHP files (automated import replacement)
- `packages/aero-hrmac/README.md` — update example import manually

---

## Task L1: Create 4 New Interface Files in aero-contracts

**Files:**
- Create: `packages/aero-contracts/src/RoleModuleAccessInterface.php`
- Create: `packages/aero-contracts/src/UserContract.php`
- Create: `packages/aero-contracts/src/UserRepositoryContract.php`
- Create: `packages/aero-contracts/src/EmployeeServiceContract.php`

- [ ] **Step L1.1: Create `RoleModuleAccessInterface.php`**

Read `packages/aero-hrmac/src/Contracts/RoleModuleAccessInterface.php` for the full method list. It uses `Illuminate\Support\Collection` as a return type. Create `packages/aero-contracts/src/RoleModuleAccessInterface.php`:

```php
<?php

declare(strict_types=1);

namespace Aero\Contracts;

use Illuminate\Support\Collection;

interface RoleModuleAccessInterface
{
    public function canAccessModule(mixed $role, int $moduleId): bool;
    public function canAccessSubModule(mixed $role, int $subModuleId): bool;
    public function canAccessComponent(mixed $role, int $componentId): bool;
    public function canAccessAction(mixed $role, int $actionId): bool;
    public function userCanAccessModule(mixed $user, string $moduleCode): bool;
    public function userCanAccessSubModule(mixed $user, string $moduleCode, string $subModuleCode): bool;
    public function userCanAccessAction(mixed $user, string $moduleCode, string $subModuleCode, string $actionCode): bool;
    public function getFirstAccessibleRoute(mixed $user): ?string;
    public function getAccessibleModuleIds(mixed $role): array;
    public function getUserAccessibleSubModuleIds(mixed $user): array;
    public function syncRoleAccess(mixed $role, array $accessData): void;
    public function getRoleAccessTree(mixed $role): array;
    public function clearRoleCache(mixed $role): void;
    public function clearUserCache(mixed $user): void;
    public function getUsersWithSubModuleAccess(string $moduleCode, string $subModuleCode, ?string $actionCode = null): Collection;
    public function getUsersWithActionAccess(string $moduleCode, string $subModuleCode, string $componentCode, string $actionCode): Collection;
}
```

- [ ] **Step L1.2: Create `UserContract.php`**

```php
<?php

declare(strict_types=1);

namespace Aero\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface UserContract
{
    public function getId(): int;
    public function getName(): string;
    public function getEmail(): string;
    public function getPhone(): ?string;
    public function isActive(): bool;
    public function hasVerifiedEmail(): bool;
    public function getProfileImageUrl(): ?string;
    public function getLocale(): string;
    public function getTimezone(): string;
    public function notify($notification);
    public function hasPermission(string $permission): bool;
    public function hasAnyPermission(array $permissions): bool;
    public function hasAllPermissions(array $permissions): bool;
    public function hasRole(string $role): bool;
    public function getRoles();
    public function getPermissions();
    public function getCreatedAt(): \DateTimeInterface;
    public function getUpdatedAt(): \DateTimeInterface;
    public function prefersNotificationChannel(string $channel): bool;
    public function getRelationship(string $relationshipName);
    public function hasRelationship(string $relationshipName): bool;
    public function toArray();
}
```

- [ ] **Step L1.3: Create `UserRepositoryContract.php`**

```php
<?php

declare(strict_types=1);

namespace Aero\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

interface UserRepositoryContract
{
    public function find(int $id): ?UserContract;
    public function findByEmail(string $email): ?UserContract;
    public function findActive(): Collection;
    public function findByRoles(array $roles): Collection;
    public function findByDepartment(int $departmentId): Collection;
    public function findManagers(): Collection;
    public function findHRUsers(): Collection;
    public function findSafetyTeam(): Collection;
    public function getModelClass(): string;
    public function query(): mixed;
}
```

- [ ] **Step L1.4: Create `EmployeeServiceContract.php`**

```php
<?php

declare(strict_types=1);

namespace Aero\Contracts;

use Illuminate\Support\Collection;

interface EmployeeServiceContract
{
    public function getById(int $employeeId): ?array;
    public function getByUserId(int $userId): ?array;
    public function getUserId(int $employeeId): ?int;
    public function getEmployeeId(int $userId): ?int;
    public function getManagerEmployeeId(int $employeeId): ?int;
    public function getDepartmentId(int $employeeId): ?int;
    /** @return Collection<int> Employee IDs */
    public function getDepartmentEmployeeIds(int $departmentId): Collection;
    /** @return Collection<int> Employee IDs */
    public function getDirectReportEmployeeIds(int $managerEmployeeId): Collection;
    /** @return Collection<int> Employee IDs of all managers in chain */
    public function getReportingChainEmployeeIds(int $employeeId): Collection;
    public function isActiveEmployee(int $employeeId): bool;
    public function getEmployeeProfileImageUrl(int $employeeId): ?string;
    public function getEmployeeName(int $employeeId): ?string;
    /** @param Collection<int> $employeeIds @return Collection<int, int> Map of employee_id => user_id */
    public function batchResolveUserIds(Collection $employeeIds): Collection;
}
```

- [ ] **Step L1.5: Verify all 4 files exist**

```powershell
Get-ChildItem "packages\aero-contracts\src" -Filter "*.php" | Measure-Object | Select-Object -ExpandProperty Count
```

Expected: `22` (18 from Plan K + 4 new).

- [ ] **Step L1.6: Commit**

```powershell
git add packages/aero-contracts/src/RoleModuleAccessInterface.php `
        packages/aero-contracts/src/UserContract.php `
        packages/aero-contracts/src/UserRepositoryContract.php `
        packages/aero-contracts/src/EmployeeServiceContract.php
git commit -m "feat(aero-contracts): add RoleModuleAccessInterface, UserContract, UserRepositoryContract, EmployeeServiceContract"
```

---

## Task L2: Update All Import Sites (Automated + Manual)

**Files:** ~25 PHP files (automated) + 1 README (manual).

- [ ] **Step L2.1: Run automated import replacement**

```powershell
$replacements = @{
    'use Aero\HRMAC\Contracts\RoleModuleAccessInterface;'  = 'use Aero\Contracts\RoleModuleAccessInterface;'
    'Aero\HRMAC\Contracts\RoleModuleAccessInterface'        = 'Aero\Contracts\RoleModuleAccessInterface'
    'use Aero\Core\Contracts\UserContract;'                 = 'use Aero\Contracts\UserContract;'
    'Aero\Core\Contracts\UserContract'                      = 'Aero\Contracts\UserContract'
    'use Aero\Core\Contracts\UserRepositoryContract;'       = 'use Aero\Contracts\UserRepositoryContract;'
    'Aero\Core\Contracts\UserRepositoryContract'            = 'Aero\Contracts\UserRepositoryContract'
    'use Aero\Core\Contracts\EmployeeServiceContract;'      = 'use Aero\Contracts\EmployeeServiceContract;'
    'Aero\Core\Contracts\EmployeeServiceContract'           = 'Aero\Contracts\EmployeeServiceContract'
}

$utf8NoBom = New-Object System.Text.UTF8Encoding $false
$changed = @()

Get-ChildItem -Path "c:\laragon\www\Aero-Enterprise-Suite-Saas\packages" -Recurse -Filter "*.php" |
    Where-Object { $_.FullName -notmatch "\\vendor\\" } |
    ForEach-Object {
        $content = [System.IO.File]::ReadAllText($_.FullName, $utf8NoBom)
        $original = $content
        foreach ($pair in $replacements.GetEnumerator()) {
            $content = $content.Replace($pair.Key, $pair.Value)
        }
        if ($content -ne $original) {
            [System.IO.File]::WriteAllText($_.FullName, $content, $utf8NoBom)
            $changed += $_.FullName
        }
    }

Write-Host "Changed $($changed.Count) files:"
$changed | ForEach-Object { Write-Host "  $(Split-Path $_ -Leaf)" }
```

Expected: approximately 25 files changed.

- [ ] **Step L2.2: Update the README manually**

Read `packages/aero-hrmac/README.md`. Find the example showing:
```php
use Aero\HRMAC\Contracts\RoleModuleAccessInterface;
```
Replace with:
```php
use Aero\Contracts\RoleModuleAccessInterface;
```

- [ ] **Step L2.3: Verify zero old imports remain**

```powershell
$oldPatterns = @(
    'Aero\HRMAC\Contracts\RoleModuleAccessInterface',
    'Aero\Core\Contracts\UserContract',
    'Aero\Core\Contracts\UserRepositoryContract',
    'Aero\Core\Contracts\EmployeeServiceContract'
)
$found = Get-ChildItem -Path "c:\laragon\www\Aero-Enterprise-Suite-Saas\packages" -Recurse -Filter "*.php" |
    Where-Object { $_.FullName -notmatch "\\vendor\\" } |
    Select-String -Pattern ($oldPatterns -join '|')

if ($found) {
    Write-Host "FAIL: $($found.Count) old references remain:"
    $found | ForEach-Object { Write-Host "  $($_.Path):$($_.LineNumber)" }
} else {
    Write-Host "PASS: Zero old imports remain"
}
```

Expected: `PASS: Zero old imports remain`

- [ ] **Step L2.4: Commit**

```powershell
git add packages/
git commit -m "refactor: update all RoleModuleAccessInterface, UserContract, UserRepositoryContract, EmployeeServiceContract imports to Aero\Contracts namespace"
```

---

## Task L3: Delete Old Files + Update aero-hrmac composer.json

**Files:**
- Delete: `packages/aero-hrmac/src/Contracts/RoleModuleAccessInterface.php`
- Delete: `packages/aero-core/src/Contracts/UserContract.php`
- Delete: `packages/aero-core/src/Contracts/UserRepositoryContract.php`
- Delete: `packages/aero-core/src/Contracts/EmployeeServiceContract.php`
- Modify: `packages/aero-hrmac/composer.json`

- [ ] **Step L3.1: `git rm` the 4 old files**

```powershell
git rm packages/aero-hrmac/src/Contracts/RoleModuleAccessInterface.php `
       packages/aero-core/src/Contracts/UserContract.php `
       packages/aero-core/src/Contracts/UserRepositoryContract.php `
       packages/aero-core/src/Contracts/EmployeeServiceContract.php
```

- [ ] **Step L3.2: Add `aero/contracts` to aero-hrmac**

Read `packages/aero-hrmac/composer.json`. In the `"require"` block, add:
```json
"aero/contracts": "^1.0",
```
above `"laravel/framework"`.

- [ ] **Step L3.3: Verify exactly 1 file remains in aero-core/src/Contracts/**

```powershell
Get-ChildItem "packages\aero-core\src\Contracts" -Filter "*.php" | Select-Object Name
```

Expected output:
```
AbstractDashboardWidget.php
```

- [ ] **Step L3.4: Verify aero-contracts now has 22 files**

```powershell
Get-ChildItem "packages\aero-contracts\src" -Filter "*.php" | Measure-Object | Select-Object -ExpandProperty Count
```

Expected: `22`

- [ ] **Step L3.5: Commit**

```powershell
git add packages/aero-hrmac/composer.json packages/aero-hrmac/README.md
git commit -m "feat(aero-contracts): delete moved contract files from aero-hrmac/aero-core; add aero/contracts dep to aero-hrmac -- aero-core/Contracts now has only AbstractDashboardWidget"
```

---

## Task L4: Smoke-Test

- [ ] **Step L4.1: Dump autoload and verify resolution**

```powershell
cd "c:\laragon\www\aeos365"
composer dump-autoload --no-interaction 2>&1 | Select-Object -Last 3
```

- [ ] **Step L4.2: Run contract verification**

Create `c:\laragon\www\aeos365\check_l.php`:

```php
<?php
require __DIR__ . '/vendor/autoload.php';

$checks = [
    'Aero\Contracts\RoleModuleAccessInterface',
    'Aero\Contracts\UserContract',
    'Aero\Contracts\UserRepositoryContract',
    'Aero\Contracts\EmployeeServiceContract',
];

$pass = 0; $fail = 0;
foreach ($checks as $c) {
    if (interface_exists($c) || class_exists($c) || enum_exists($c)) {
        echo "PASS: $c\n"; $pass++;
    } else {
        echo "FAIL: $c\n"; $fail++;
    }
}
echo "\n$pass passed, $fail failed\n";
```

Run and clean up:
```powershell
php check_l.php; Remove-Item check_l.php
```

Expected: `4 passed, 0 failed`

- [ ] **Step L4.3: Verify the old namespaces do NOT resolve**

```powershell
php -r "require 'vendor/autoload.php'; var_dump(interface_exists('Aero\HRMAC\Contracts\RoleModuleAccessInterface'));"
```

Expected: `bool(false)` — clean cut, no aliases.

- [ ] **Step L4.4: Push**

```powershell
cd "c:\laragon\www\Aero-Enterprise-Suite-Saas"
git push origin main
```

---

## Self-Review

**Spec coverage:**
- `RoleModuleAccessInterface` moved from aero-hrmac → aero-contracts → L1+L2+L3 ✅
- `UserContract` moved from aero-core → aero-contracts → L1+L2+L3 ✅
- `UserRepositoryContract` moved from aero-core → aero-contracts → L1+L2+L3 ✅
- `EmployeeServiceContract` moved from aero-core → aero-contracts → L1+L2+L3 ✅
- aero-hrmac gets `aero/contracts ^1.0` dep → L3 ✅
- README updated → L2 ✅
- Zero old imports remain → L2.3 ✅
- `aero-core/src/Contracts/` has exactly 1 file left → L3.3 ✅
- Smoke test: 4 new contracts resolve → L4 ✅
- Clean cut: old namespaces do not resolve → L4.3 ✅

**After Plan L:**
`aero-core/src/Contracts/` contains only `AbstractDashboardWidget.php`.
`aero-contracts/src/` contains 22 interfaces/enums — the complete contracts layer.

**Deferred to Plan M (optional):**
- Decouple `AbstractDashboardWidget` from `Illuminate\Support\Facades\{DB,Log,Schema}` and `auth()` so it can also move to aero-contracts
- Remove `aero/core` dep from packages that now ONLY use `aero/contracts` (pure contract packages can drop the heavier dep)
