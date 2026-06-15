# Aero Core - Quick Start Integration Guide

This guide shows how to integrate aero-core into your existing Laravel application or use it as the foundation for a new module.

## 🚀 Installation

### Step 1: Add Package Repository

Edit your main project's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "./aero-core"
        }
    ]
}
```

### Step 2: Install Package

```bash
composer require aero/core
```

The service provider will be automatically registered via Laravel's package auto-discovery.

### Step 3: Run Migrations

```bash
# Run aero-core migrations
php artisan migrate
```

### Step 4: Publish Assets (Optional)

```bash
# Publish config
php artisan vendor:publish --tag=aero-core-config

# Publish migrations (if you want to customize)
php artisan vendor:publish --tag=aero-core-migrations

# Publish JS assets (Inertia components)
php artisan vendor:publish --tag=aero-core-assets

# Publish views
php artisan vendor:publish --tag=aero-core-views
```

### Step 5: Register Middleware

Add to `bootstrap/app.php` (Laravel 11):

```php
use Aero\Core\Http\Middleware\ModuleAccessMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'module.access' => ModuleAccessMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
```

Or for Laravel 10 in `app/Http/Kernel.php`:

```php
protected $middlewareAliases = [
    // ... other middleware
    'module.access' => \Aero\Core\Http\Middleware\ModuleAccessMiddleware::class,
];
```

---

## 🎯 Usage Examples

### Using the User Model

Replace your existing User model with Aero Core's User:

**Option 1: Extend Aero Core User**
```php
// app/Models/User.php
<?php

namespace App\Models;

use Aero\Core\Models\User as AeroCoreUser;

class User extends AeroCoreUser
{
    // Add your custom methods or relationships
    public function customMethod()
    {
        // Your code
    }
}
```

**Option 2: Use Aero Core User Directly**
```php
use Aero\Core\Models\User;

// Anywhere in your code
$user = User::find(1);
```

### Creating Users

```php
use Aero\Core\Models\User;

$user = User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => bcrypt('password'),
]);
// Active/inactive is managed via SoftDeletes: $user->delete() deactivates,
// $user->restore() reactivates (no active/is_active column).

// Assign role
$user->assignRole('manager');
```

### Checking Module Access

```php
use Aero\Core\Models\User;

$user = User::find(1);

// Check module access
if ($user->hasModuleAccess('hrm')) {
    // User can access HRM module
}

// Check component access
if ($user->hasComponentAccess('hrm', 'employee_management', 'employee_list')) {
    // User can access employee list component
}

// Check action permission
if ($user->canPerformAction('hrm', 'employee_management', 'employee_list', 'create')) {
    // User can create employees
}

// Get all accessible modules
$accessibleModules = $user->getAccessibleModules();
```

### Using Services

```php
use Aero\Core\Services\ModuleAccessService;
use Aero\Core\Services\RoleModuleAccessService;

// Module access service
$moduleService = app(ModuleAccessService::class);
$result = $moduleService->canAccessModule($user, 'hrm');

if ($result['allowed']) {
    // Access granted
    $module = $result['module'];
} else {
    // Access denied
    abort(403, $result['reason']);
}

// Role module access service
$roleService = app(RoleModuleAccessService::class);
$role = Role::findByName('manager');

// Assign module access
$roleService->assignModuleAccess($role, 'hrm');

// Get role's access tree (useful for frontend UI)
$accessTree = $roleService->getRoleAccessTree($role);
```

### Protecting Routes

```php
use Illuminate\Support\Facades\Route;
use Aero\Core\Http\Controllers\UserController;

// Protect with module access middleware
Route::middleware('module.access:user_management,users,user_list')->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
});

// Multiple middleware
Route::get('/employees', [EmployeeController::class, 'index'])
    ->middleware(['auth', 'module.access:hrm,employee_management,employee_list']);
```

### Device Management

```php
$user = User::find(1);

// Enable single device login
$user->enableSingleDeviceLogin('Security policy');

// Disable single device login
$user->disableSingleDeviceLogin();

// Reset user's devices (admin action)
$user->resetDevices('Admin requested');

// Check if user has single device login enabled
if ($user->hasSingleDeviceLoginEnabled()) {
    // Single device mode is active
}
```

### Account Management

```php
$user = User::find(1);

// Lock account
$user->lockAccount('Suspicious activity detected');

// Unlock account
$user->unlockAccount();

// Check if locked
if ($user->isLocked()) {
    // Account is locked
}

// Activate/Deactivate (with soft delete)
$user->setActiveStatus(false); // Deactivate and soft delete
$user->setActiveStatus(true);  // Activate and restore
```

---

## 🔐 Role & Permission Setup

### Creating Roles

```php
use Spatie\Permission\Models\Role;

// Create roles
$superAdmin = Role::create(['name' => 'super-admin']);
$admin = Role::create(['name' => 'admin']);
$manager = Role::create(['name' => 'manager']);
$employee = Role::create(['name' => 'employee']);
```

### Assigning Module Access to Roles

```php
use Aero\Core\Services\RoleModuleAccessService;

$roleService = app(RoleModuleAccessService::class);
$managerRole = Role::findByName('manager');

// Method 1: Assign specific levels
$roleService->assignModuleAccess($managerRole, 'hrm');
$roleService->assignSubModuleAccess($managerRole, 'hrm', 'employee_management');
$roleService->assignComponentAccess($managerRole, 'hrm', 'employee_management', 'employee_list');
$roleService->assignActionAccess($managerRole, 'hrm', 'employee_management', 'employee_list', 'view');

// Method 2: Sync from array (useful for frontend UI)
$roleService->syncRoleAccess($managerRole, [
    'modules' => ['dashboard', 'hrm'],
    'sub_modules' => [
        ['module' => 'hrm', 'sub_module' => 'employee_management'],
        ['module' => 'hrm', 'sub_module' => 'attendance_management'],
    ],
    'components' => [
        ['module' => 'hrm', 'sub_module' => 'employee_management', 'component' => 'employee_list'],
        ['module' => 'hrm', 'sub_module' => 'attendance_management', 'component' => 'attendance_list'],
    ],
    'actions' => [
        ['module' => 'hrm', 'sub_module' => 'employee_management', 'component' => 'employee_list', 'action' => 'view'],
        ['module' => 'hrm', 'sub_module' => 'employee_management', 'component' => 'employee_list', 'action' => 'create'],
    ],
]);
```

---

## 📦 Using in Your Own Package

If you're creating a new module (like aero-hrm, aero-crm):

### Step 1: Add Dependency

In your package's `composer.json`:

```json
{
    "name": "aero/your-module",
    "require": {
        "aero/core": "^1.0"
    }
}
```

### Step 2: Use Core Models

```php
<?php

namespace Aero\YourModule\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

### Step 3: Define Your Module

Create `config/modules.php` in your package:

```php
<?php

return [
    'modules' => [
        [
            'code' => 'your_module',
            'name' => 'Your Module',
            'description' => 'Your module description',
            'icon' => 'YourIcon',
            'priority' => 10,
            'is_active' => true,
            'sub_modules' => [
                // Define your sub-modules, components, and actions
            ],
        ],
    ],
];
```

### Step 4: Protect Your Routes

```php
// In your package's routes/web.php
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'module.access:your_module'])->group(function () {
    Route::get('/your-feature', [YourController::class, 'index']);
});
```

---

## 🧪 Testing

### Feature Test Example

```php
<?php

namespace Tests\Feature;

use Aero\Core\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_access_module_with_permission()
    {
        $user = User::factory()->create();
        $user->assignRole('manager');
        
        // Assign module access
        $roleService = app(\Aero\Core\Services\RoleModuleAccessService::class);
        $role = $user->roles->first();
        $roleService->assignModuleAccess($role, 'hrm');
        
        // Assert user has access
        $this->assertTrue($user->hasModuleAccess('hrm'));
    }
    
    public function test_user_cannot_access_module_without_permission()
    {
        $user = User::factory()->create();
        
        // Assert user doesn't have access
        $this->assertFalse($user->hasModuleAccess('hrm'));
    }
}
```

---

## 🔧 Configuration

### Custom Super Admin Role

Edit published config `config/aero-core-modules.php`:

```php
'access_control' => [
    'super_admin_role' => 'your-super-admin-role',
    'cache_ttl' => 3600,
],
```

### Cache Configuration

```php
// Clear all module access cache
$moduleService = app(\Aero\Core\Services\ModuleAccessService::class);
$moduleService->clearAllCache();

// Clear specific user's cache
$moduleService->clearUserCache($user);

// Clear role's cache
$roleService = app(\Aero\Core\Services\RoleModuleAccessService::class);
$roleService->clearRoleCache($role);
```

---

## 🚨 Common Issues

### Issue: Migrations Already Exist

**Solution:** Use `--force` flag or skip core migrations if you already have users table.

```bash
php artisan migrate --path=vendor/aero/core/database/migrations --force
```

### Issue: Service Provider Not Loading

**Solution:** Clear config cache:

```bash
php artisan config:clear
composer dump-autoload
```

### Issue: Middleware Not Found

**Solution:** Make sure you've registered the middleware alias in `bootstrap/app.php` or `Kernel.php`.

### Issue: Permissions Not Working

**Solution:** Clear permission cache:

```bash
php artisan permission:cache-reset
```

---

## 📚 API Reference

### User Model Methods

```php
// Module Access
$user->hasModuleAccess(string $moduleCode): bool
$user->hasSubModuleAccess(string $moduleCode, string $subModuleCode): bool
$user->hasComponentAccess(string $moduleCode, string $subModuleCode, string $componentCode): bool
$user->canPerformAction(string $moduleCode, string $subModuleCode, string $componentCode, string $actionCode): bool
$user->getAccessibleModules(): array
$user->getModuleAccessTree(): array

// Account Management
$user->setActiveStatus(bool $status): void
$user->lockAccount(?string $reason = null): void
$user->unlockAccount(): void
$user->isLocked(): bool

// Device Management
$user->enableSingleDeviceLogin(?string $reason = null): bool
$user->disableSingleDeviceLogin(?string $reason = null): bool
$user->resetDevices(?string $reason = null): bool
$user->hasSingleDeviceLoginEnabled(): bool
```

### Service Methods

```php
// ModuleAccessService
$service->canAccessModule(User $user, string $moduleCode): array
$service->canAccessSubModule(User $user, string $moduleCode, string $subModuleCode): array
$service->canAccessComponent(User $user, string $moduleCode, string $subModuleCode, string $componentCode): array
$service->canPerformAction(User $user, string $moduleCode, string $subModuleCode, string $componentCode, string $actionCode): array
$service->getAccessibleModules(User $user): array
$service->clearUserCache(User $user): void
$service->clearAllCache(): void

// RoleModuleAccessService
$service->getRoleAccessTree(Role $role): array
$service->assignModuleAccess(Role $role, string $moduleCode): bool
$service->assignSubModuleAccess(Role $role, string $moduleCode, string $subModuleCode): bool
$service->assignComponentAccess(Role $role, string $moduleCode, string $subModuleCode, string $componentCode): bool
$service->assignActionAccess(Role $role, string $moduleCode, string $subModuleCode, string $componentCode, string $actionCode): bool
$service->syncRoleAccess(Role $role, array $accessData): bool
$service->clearRoleCache(Role $role): void
```

---

## 🎓 Best Practices

1. **Always use aero-core User model** - Don't create duplicate user tables
2. **Use module access middleware** - Protect all routes that need authorization
3. **Cache aggressively** - Module access checks are cached automatically
4. **Use service layer** - Don't access models directly from controllers
5. **Follow permission naming** - Use dot notation (e.g., `module.hrm.employee_management.employee_list.create`)
6. **Test thoroughly** - Write feature tests for all access control logic
7. **Clear cache on changes** - Clear cache when roles/permissions change

---

## 📞 Support

For issues or questions:
- Check the main [README.md](README.md)
- Review [BATCH_1_COMPLETION.md](BATCH_1_COMPLETION.md) for implementation details
- Contact: support@aerosuite.com

---

**Last Updated:** January 2025  
**Version:** 1.0.0
