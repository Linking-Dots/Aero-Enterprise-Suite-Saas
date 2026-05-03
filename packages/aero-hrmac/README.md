# HRMAC - Hierarchical Role Module Access Control

**A Laravel package for granular, role-based module access control in multi-tenant SaaS applications.**

## Overview

HRMAC (Hierarchical Role Module Access Control) provides a complete solution for managing user access at the module, sub-module, component, and action levels. It's designed for multi-tenant SaaS applications using `stancl/tenancy`.

### Key Features

- 🔐 **4-Level Access Hierarchy**: Module → Sub-Module → Component → Action
- 🏢 **Multi-Tenant Support**: Works seamlessly with tenant and landlord databases
- ⚡ **Performance Optimized**: Built-in caching for access checks
- 🔄 **Module Discovery**: Automatic scanning of package `config/module.php` files
- 🛡️ **Super Admin Bypass**: Configurable super admin roles bypass all checks
- 🎯 **Smart Landing**: Redirects users to their first accessible route

## Installation

The package is designed as part of the aeos365 monorepo. Add it to your host app's `composer.json`:

```json
{
    "require": {
        "aero/hrmac": "*"
    },
    "repositories": [
        {
            "type": "path",
            "url": "../Aero-Enterprise-Suite-Saas/packages/aero-hrmac"
        }
    ]
}
```

Then run:
```bash
composer update
php artisan vendor:publish --tag=hrmac-config
```

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=hrmac-config
```

### Key Configuration Options

```php
// config/hrmac.php

return [
    // Roles that bypass all access checks
    'super_admin_roles' => [
        'Super Administrator',
        'super-admin',
        'tenant_super_administrator',
    ],

    // Caching configuration
    'cache' => [
        'enabled' => true,
        'ttl' => 3600, // 1 hour
        'prefix' => 'hrmac',
    ],

    // Access inheritance rules
    'inheritance' => [
        'module_grants_sub_modules' => true,
        'sub_module_grants_components' => true,
        'component_grants_actions' => true,
    ],
];
```

## Database Structure

HRMAC uses a 5-table structure:

| Table | Description |
|-------|-------------|
| `modules` | Top-level modules (Core, HRM, CRM) |
| `sub_modules` | Features within modules (Dashboard, Employees) |
| `module_components` | UI components/pages within sub-modules |
| `module_component_actions` | Actions within components (view, create, delete) |
| `role_module_access` | Role-to-hierarchy access mappings |

### Migrations

Run migrations to create the tables:

```bash
php artisan migrate
```

Or publish migrations for customization:

```bash
php artisan vendor:publish --tag=hrmac-migrations
```

## Module Discovery & Syncing

### Module Definition Format

Each package should have a `config/module.php` file:

```php
// packages/aero-hrm/config/module.php
return [
    'code' => 'hrm',
    'scope' => 'tenant', // or 'platform'
    'name' => 'Human Resources',
    'description' => 'HR management module',
    'icon' => 'UserGroupIcon',
    'route_prefix' => '/hrm',
    'priority' => 10,
    'is_active' => true,

    'submodules' => [
        [
            'code' => 'employees',
            'name' => 'Employees',
            'route' => '/hrm/employees',
            'priority' => 1,
            'components' => [
                [
                    'code' => 'employee-directory',
                    'name' => 'Employee Directory',
                    'type' => 'page',
                    'route' => '/hrm/employees',
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Employees'],
                        ['code' => 'create', 'name' => 'Create Employee'],
                        ['code' => 'update', 'name' => 'Update Employee'],
                        ['code' => 'delete', 'name' => 'Delete Employee'],
                    ],
                ],
            ],
        ],
    ],
];
```

### Sync Command

Sync modules from all packages to the database:

```bash
# Sync tenant modules (in tenant context)
php artisan hrmac:sync-modules --scope=tenant

# Sync platform modules (in central context)
php artisan hrmac:sync-modules --scope=platform

# Fresh sync (clear existing, then sync)
php artisan hrmac:sync-modules --fresh

# Prune removed modules
php artisan hrmac:sync-modules --prune
```

## Usage

### Using the Service

```php
use Aero\HRMAC\Contracts\RoleModuleAccessInterface;

class SomeController
{
    public function __construct(
        protected RoleModuleAccessInterface $hrmac
    ) {}

    public function index()
    {
        $user = auth()->user();
        
        // Check module access
        if ($this->hrmac->userCanAccessModule($user, 'hrm')) {
            // User has HRM module access
        }

        // Check sub-module access
        if ($this->hrmac->userCanAccessSubModule($user, 'hrm', 'employees')) {
            // User has Employees sub-module access
        }

        // Get first accessible route for smart landing
        $firstRoute = $this->hrmac->getFirstAccessibleRoute($user);
    }
}
```

### Using the Facade

```php
use Aero\HRMAC\Facades\HRMAC;

// Check access
HRMAC::userCanAccessModule($user, 'crm');
HRMAC::userCanAccessSubModule($user, 'core', 'dashboard');

// Get accessible routes
HRMAC::getFirstAccessibleRoute($user);
```

### Using Middleware

Protect routes with the `role.access` middleware:

```php
// In routes/web.php
Route::middleware(['auth', 'role.access:hrm,employees'])->group(function () {
    Route::get('/hrm/employees', [EmployeeController::class, 'index']);
});

// Module-level only
Route::middleware(['role.access:crm'])->get('/crm', CRMController::class);

// Smart landing redirect
Route::middleware(['smart.landing'])->get('/', HomeController::class);
```

### Inline Route Checks

```php
Route::get('dashboard', function () {
    // ...
})->middleware('role.access:core,dashboard')->name('core.dashboard');
```

## Granting Access

Access is stored in the `role_module_access` table. Grant access programmatically:

```php
use Aero\HRMAC\Models\RoleModuleAccess;
use Aero\HRMAC\Models\Module;
use Aero\HRMAC\Models\SubModule;

// Grant full module access
$module = Module::where('code', 'hrm')->first();
RoleModuleAccess::create([
    'role_id' => $role->id,
    'module_id' => $module->id,
]);

// Grant specific sub-module access
$subModule = SubModule::whereHas('module', fn($q) => $q->where('code', 'core'))
    ->where('code', 'dashboard')
    ->first();

RoleModuleAccess::create([
    'role_id' => $role->id,
    'module_id' => $subModule->module_id,
    'sub_module_id' => $subModule->id,
]);
```

## Access Inheritance

By default, access cascades down:

- **Module access** grants access to all sub-modules within it
- **Sub-module access** grants access to all components within it  
- **Component access** grants access to all actions within it

This can be configured in `config/hrmac.php`:

```php
'inheritance' => [
    'module_grants_sub_modules' => true,
    'sub_module_grants_components' => true,
    'component_grants_actions' => true,
],
```

## Caching

Access checks are cached per-role to optimize performance:

```php
// Clear cache for a specific role
HRMAC::clearRoleCache($role);

// Clear cache for a user (clears all their roles)
HRMAC::clearUserCache($user);
```

Cache is automatically invalidated when:
- Role access is modified via `syncRoleAccess()`
- Cache TTL expires (default: 1 hour)

## Multi-Tenant Considerations

HRMAC is designed for multi-tenancy:

- **Tenant Context**: Uses tenant database (no explicit connection)
- **Platform Context**: Uses landlord database for platform modules
- **Scope Field**: Each module has `scope: 'tenant'` or `scope: 'platform'`

The models use no explicit `$connection` property, allowing them to work in whatever context is currently active (tenant or landlord).

## Testing

```php
// In a PHPUnit test
public function test_employee_can_access_hrm_module()
{
    $employee = User::factory()->create();
    $employeeRole = Role::where('name', 'Employee')->first();
    $employee->assignRole($employeeRole);

    $hrmac = app(RoleModuleAccessInterface::class);
    
    // Grant HRM access
    $module = Module::where('code', 'hrm')->first();
    RoleModuleAccess::create([
        'role_id' => $employeeRole->id,
        'module_id' => $module->id,
    ]);

    $this->assertTrue($hrmac->userCanAccessModule($employee, 'hrm'));
    $this->assertFalse($hrmac->userCanAccessModule($employee, 'crm'));
}
```

## API Reference

### RoleModuleAccessInterface Methods

| Method | Description |
|--------|-------------|
| `canAccessModule($role, $moduleId)` | Check role has module access by ID |
| `canAccessSubModule($role, $subModuleId)` | Check role has sub-module access by ID |
| `canAccessComponent($role, $componentId)` | Check role has component access by ID |
| `canAccessAction($role, $actionId)` | Check role has action access by ID |
| `userCanAccessModule($user, $moduleCode)` | Check user has module access by code |
| `userCanAccessSubModule($user, $moduleCode, $subModuleCode)` | Check user has sub-module access by codes |
| `getFirstAccessibleRoute($user)` | Get first accessible route for user |
| `getAccessibleModuleIds($role)` | Get all accessible module IDs for role |
| `getUserAccessibleSubModuleIds($user)` | Get all accessible sub-module IDs for user |
| `syncRoleAccess($role, $accessData)` | Sync role access from UI selections |
| `getRoleAccessTree($role)` | Get full access tree for role |
| `clearRoleCache($role)` | Clear cached access data for role |
| `clearUserCache($user)` | Clear cached access data for user |

## License

This package is part of the aeos365 and is proprietary software.

---

Built with ❤️ by the aeos365 team.
