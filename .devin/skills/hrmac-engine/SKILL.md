---
name: hrmac-engine
description: "Implement and audit HRMAC (Hierarchical Role Module Access Control) for aeos365. Covers config/module.php definitions, route middleware syntax, 4-level hierarchy, access inheritance, caching, and frontend permission checks."
---

# HRMAC Engineering Skill

## What is HRMAC

HRMAC = **Hierarchical Role Module Access Control** — a 4-level access control system:

```
Module → Submodule → Component → Action
  hrm  → employees  → directory  → view
```

Access cascades down by default:
- Module access grants all submodules
- Submodule access grants all components
- Component access grants all actions

## Module Definition: config/module.php

Every package MUST have a `config/module.php` file defining its hierarchy:

```php
<?php

return [
    'code'        => 'hrm',
    'scope'       => 'tenant',      // 'tenant' or 'platform'
    'name'        => 'Human Resources',
    'description' => 'Complete HR management',
    'icon'        => 'UserGroupIcon',
    'route_prefix'=> '/hrm',
    'category'    => 'human_resources',
    'priority'    => 10,
    'is_core'     => false,
    'is_active'   => true,
    'version'     => '1.0.0',
    'min_plan'    => 'basic',
    'license_type'=> 'standard',
    'dependencies'=> ['core'],

    'submodules' => [
        [
            'code'       => 'employees',
            'name'       => 'Employees',
            'route'      => '/hrm/employees',
            'priority'   => 1,
            'icon'       => 'UsersIcon',
            'components' => [
                [
                    'code'    => 'directory',
                    'name'    => 'Employee Directory',
                    'type'    => 'page',
                    'route'   => '/hrm/employees',
                    'actions' => [
                        ['code' => 'view',   'name' => 'View Employees'],
                        ['code' => 'create', 'name' => 'Create Employee'],
                        ['code' => 'update', 'name' => 'Update Employee'],
                        ['code' => 'delete', 'name' => 'Delete Employee'],
                    ],
                ],
            ],
        ],
    ],

    'self_service' => [
        [
            'code'     => 'my-dashboard',
            'name'     => 'My Dashboard',
            'icon'     => 'HomeIcon',
            'route'    => '/hrm/employee/dashboard',
            'priority' => 1,
        ],
    ],
];
```

## Route Middleware Syntax

All tenant-scoped routes MUST use HRMAC middleware:

```php
// Module-level access
Route::middleware(['hrmac:hrm'])
    ->get('/hrm', [HRMDashboardController::class, 'index']);

// Submodule-level access
Route::middleware(['hrmac:hrm.employees'])
    ->get('/hrm/employees', [EmployeeController::class, 'index']);

// Component-level access
Route::middleware(['hrmac:hrm.employees.directory'])
    ->get('/hrm/employees', [EmployeeController::class, 'index']);

// Action-level access (most granular)
Route::middleware(['hrmac:hrm.employees.directory.view'])
    ->get('/hrm/employees', [EmployeeController::class, 'index']);

Route::middleware(['hrmac:hrm.employees.directory.create'])
    ->post('/hrm/employees', [EmployeeController::class, 'store']);

Route::middleware(['hrmac:hrm.employees.directory.update'])
    ->put('/hrm/employees/{employee}', [EmployeeController::class, 'update']);

Route::middleware(['hrmac:hrm.employees.directory.delete'])
    ->delete('/hrm/employees/{employee}', [EmployeeController::class, 'destroy']);
```

**Canonical middleware stack:** `['web', 'auth', 'verified', 'hrmac:*']`

## Service Layer Access Checks

```php
use Aero\HRMAC\Contracts\RoleModuleAccessInterface;

class EmployeeService
{
    public function __construct(
        protected RoleModuleAccessInterface $hrmac,
    ) {}

    public function listForUser(User $user)
    {
        // Check if user can even access the module
        if (! $this->hrmac->userCanAccessModule($user, 'hrm')) {
            throw new AuthorizationException('HRM module access required.');
        }

        // Check specific action
        if (! $this->hrmac->userCanAccessAction($user, 'hrm', 'employees', 'directory', 'view')) {
            throw new AuthorizationException('View employees permission required.');
        }

        return Employee::query()->paginate();
    }
}
```

## Form Request Authorization

```php
class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()
            && app(RoleModuleAccessInterface::class)
                ->userCanAccessAction(auth()->user(), 'hrm', 'employees', 'directory', 'create');
    }
}
```

## Syncing Modules

```bash
# Sync tenant modules
php artisan hrmac:sync-modules --scope=tenant

# Sync platform modules
php artisan hrmac:sync-modules --scope=platform

# Fresh sync (clear existing)
php artisan hrmac:sync-modules --fresh

# Prune removed modules
php artisan hrmac:sync-modules --prune
```

## Caching

Access checks are cached per-role (TTL: 3600s by default). Clear when roles change:

```php
use Aero\HRMAC\Facades\HRMAC;

HRMAC::clearRoleCache($role);
HRMAC::clearUserCache($user);
```

## Frontend Permission Checks

Pass permissions from backend via Inertia props, then use in JSX:

```jsx
const { auth } = usePage().props;
const canCreate = auth?.permissions?.includes('hrm.employees.directory.create');
const canDelete = auth?.permissions?.includes('hrm.employees.directory.delete');
```

Conditionally render action buttons:
```jsx
{canCreate && (
  <Button intent="primary" as={Link} href="/hrm/employees/create">
    <Icon name="plus" size={16} /> Add Employee
  </Button>
)}
```

## Gap Detection

| Violation | Severity | Fix |
|-----------|----------|-----|
| Route without `hrmac:` middleware | **HIGH** | Add `hrmac:{module}.{submodule}.{component}.{action}` |
| Missing `config/module.php` in package | **HIGH** | Create module definition with full hierarchy |
| `auth.permissions?.includes()` used without module prefix | **MEDIUM** | Use full dot-notation path |
| Controller action without authorization check | **HIGH** | Add to Form Request `authorize()` or service layer |

## Reference Files

- HRMAC package: `packages/aero-hrmac/README.md`
- Module config example: `packages/aero-hrm/config/module.php`
- Service provider: `packages/aero-hrmac/src/HRMACServiceProvider.php`
- Middleware: `packages/aero-hrmac/src/Http/Middleware/CheckRoleModuleAccess.php`
- Interface: `packages/aero-hrmac/src/Contracts/RoleModuleAccessInterface.php`
