# Aero Core Package

**Foundation package for all Aero modules** providing user authentication, role-based access control, and module management system.

## 🎯 Purpose

Aero Core is the foundation library that provides:
- ✅ **User & Authentication Management** - Complete user model with OAuth, 2FA, device management
- ✅ **Role-Based Access Control (RBAC)** - Using Spatie Laravel Permission
- ✅ **Module Access System** - Three-level hierarchy (Module → SubModule → Component → Action)
- ✅ **Multi-Tenancy Support** - Built-in tenant isolation with Stancl Tenancy
- ✅ **Shared Layouts & Components** - Reusable React/Inertia components
- ✅ **API & Web Routes** - Ready-to-use authentication and management routes

## 📦 Package Structure

```
aero-core/
├── composer.json                  # Package definition
├── README.md                      # This file
│
├── config/
│   └── modules.php                # Core module definitions (dashboard, users, roles, settings)
│
├── database/
│   ├── migrations/
│   │   ├── 2024_01_01_000001_create_users_table.php
│   │   ├── 2024_01_01_000002_create_user_devices_table.php
│   │   ├── 2024_01_01_000003_create_modules_table.php
│   │   ├── 2024_01_01_000004_create_sub_modules_table.php
│   │   ├── 2024_01_01_000005_create_components_table.php
│   │   └── 2024_01_01_000006_create_actions_table.php
│   └── seeders/                   # Database seeders
│
├── routes/
│   ├── web.php                    # Web routes (auth, users, roles)
│   └── api.php                    # API routes with Sanctum
│
├── src/
│   ├── Models/
│   │   ├── User.php               # Core user model (622 lines from platform)
│   │   ├── UserDevice.php         # Device management for single-device login
│   │   ├── Module.php             # Top-level module
│   │   ├── SubModule.php          # Sub-module under module
│   │   ├── Component.php          # Component under sub-module
│   │   └── Action.php             # Action within component
│   │
│   ├── Services/
│   │   ├── ModuleAccessService.php        # Module access control logic
│   │   └── RoleModuleAccessService.php    # Role-module assignment
│   │
│   ├── Http/
│   │   ├── Controllers/           # Controllers (to be created)
│   │   └── Middleware/
│   │       └── ModuleAccessMiddleware.php # Route-level access control
│   │
│   ├── Traits/
│   │   └── TenantScoped.php       # Automatic tenant scoping for models
│   │
│   └── Providers/
│       └── AeroCoreServiceProvider.php    # Service registration
│
└── resources/
    ├── js/
    │   ├── Layouts/               # Shared layouts (Sidebar, Header, Auth)
    │   ├── Components/            # Reusable React components
    │   └── Pages/                 # Auth pages
    └── views/                     # Blade views (if needed)
```

## 🚀 Installation

### 1. Add to composer.json

```json
{
    "require": {
        "aero/core": "^1.0"
    },
    "repositories": [
        {
            "type": "path",
            "url": "./aero-core"
        }
    ]
}
```

### 2. Install Package

```bash
composer require aero/core
```

### 3. Publish Assets (Optional)

```bash
# Publish migrations
php artisan vendor:publish --tag=aero-core-migrations

# Publish config
php artisan vendor:publish --tag=aero-core-config

# Publish JS assets (Inertia components)
php artisan vendor:publish --tag=aero-core-assets

# Publish views
php artisan vendor:publish --tag=aero-core-views
```

### 4. Run Migrations

```bash
php artisan migrate
```

## 🔧 Configuration

### Register Service Provider (Laravel 11 auto-discovery)

The service provider is automatically registered via `composer.json`:

```json
"extra": {
    "laravel": {
        "providers": [
            "Aero\\Core\\Providers\\AeroCoreServiceProvider"
        ]
    }
}
```

### Module Configuration

Edit `config/aero-core-modules.php` (after publishing) or use default from package.

**Core Modules Included:**
- `dashboard` - Main dashboard and overview
- `user_management` - User accounts and profiles
- `roles_permissions` - Role-based access control
- `settings` - Application settings

## 🎭 Usage

### Using User Model

```php
use Aero\Core\Models\User;

// Create user
$user = User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => bcrypt('password'),
]);

// Assign role
$user->assignRole('admin');

// Check module access
if ($user->hasModuleAccess('hrm')) {
    // User has access to HRM module
}

// Check component access
if ($user->hasComponentAccess('hrm', 'employee_management', 'employee_list')) {
    // User can access employee list
}

// Get accessible modules
$modules = $user->getAccessibleModules();
```

### Module Access Service

```php
use Aero\Core\Services\ModuleAccessService;

$service = app(ModuleAccessService::class);

// Check module access
$result = $service->canAccessModule($user, 'hrm');
if ($result['allowed']) {
    // Access granted
} else {
    // Access denied: $result['reason']
}

// Check action access
$result = $service->canPerformAction($user, 'hrm', 'employee_management', 'employee_list', 'create');
```

### Role Module Access Service

```php
use Aero\Core\Services\RoleModuleAccessService;
use Spatie\Permission\Models\Role;

$service = app(RoleModuleAccessService::class);
$role = Role::findByName('manager');

// Assign module access
$service->assignModuleAccess($role, 'hrm');

// Assign component access
$service->assignComponentAccess($role, 'hrm', 'employee_management', 'employee_list');

// Sync access from array
$service->syncRoleAccess($role, [
    'modules' => ['hrm', 'crm'],
    'components' => [
        ['module' => 'hrm', 'sub_module' => 'employee_management', 'component' => 'employee_list'],
    ],
    'actions' => [
        ['module' => 'hrm', 'sub_module' => 'employee_management', 'component' => 'employee_list', 'action' => 'create'],
    ],
]);

// Get role access tree (for frontend UI)
$accessTree = $service->getRoleAccessTree($role);
```

### Middleware Usage

```php
// In routes/web.php
Route::middleware('module.access:user_management,users,user_list')->group(function () {
    Route::get('/users', [UserController::class, 'index']);
});

// Multiple middleware
Route::get('/employees', [EmployeeController::class, 'index'])
    ->middleware('module.access:hrm,employee_management,employee_list');
```

## 📊 Module Hierarchy

```
Module (e.g., "HRM")
  └── SubModule (e.g., "Employee Management")
      └── Component (e.g., "Employee List")
          └── Action (e.g., "Create", "Edit", "Delete")
```

**Permission Structure:**
- `module.hrm` - Access to HRM module
- `submodule.hrm.employee_management` - Access to Employee Management
- `component.hrm.employee_management.employee_list` - Access to Employee List
- `action.hrm.employee_management.employee_list.create` - Can create employees

## 🔐 Authentication Routes

**Web Routes (with `core` prefix):**
- `GET /core/login` - Login page
- `POST /core/login` - Login action
- `GET /core/register` - Registration page
- `POST /core/register` - Registration action
- `POST /core/logout` - Logout action
- `GET /core/dashboard` - Dashboard
- `GET /core/profile` - User profile
- `PATCH /core/profile` - Update profile
- `GET /core/users` - User list (requires permission)
- `GET /core/roles` - Role list (requires permission)

**API Routes (with `api/core` prefix):**
- `GET /api/core/users` - List users
- `GET /api/core/users/{id}` - Show user
- `POST /api/core/users` - Create user
- `PATCH /api/core/users/{id}` - Update user
- `DELETE /api/core/users/{id}` - Delete user
- `GET /api/core/roles` - List roles
- `GET /api/core/roles/{id}/access-tree` - Get role access tree
- `GET /api/core/modules` - List modules
- `GET /api/core/modules/accessible` - Get accessible modules for current user

## 🧪 Testing

```bash
# Run tests
php artisan test --filter=AeroCore

# Test module access
php artisan test --filter=ModuleAccessTest

# Test role assignment
php artisan test --filter=RoleModuleAccessTest
```

## 🔄 Multi-Tenancy

All models use the `TenantScoped` trait for automatic tenant isolation:

```php
use Aero\Core\Traits\TenantScoped;

class YourModel extends Model
{
    use TenantScoped; // Automatically scopes queries to current tenant
}

// Query without tenant restriction (admin use)
User::withoutTenantRestriction()->get();
```

## 📝 Extending Aero Core

### Adding Custom User Fields

Create a migration:

```bash
php artisan make:migration add_custom_fields_to_users_table
```

```php
Schema::table('users', function (Blueprint $table) {
    $table->string('employee_id')->nullable()->unique();
    $table->date('hire_date')->nullable();
});
```

### Creating Custom Services

```php
namespace App\Services;

use Aero\Core\Services\ModuleAccessService;

class CustomModuleService extends ModuleAccessService
{
    // Override or extend methods
}
```

## 🤝 Integration with Other Packages

### aero-hrm Package

```php
// aero-hrm/composer.json
{
    "require": {
        "aero/core": "^1.0"
    }
}
```

```php
// Use core User model in HRM
use Aero\Core\Models\User;

class Employee extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

### aero-platform Package

```php
// Platform orchestrates all modules
use Aero\Core\Models\Module;

$modules = Module::with('activeSubModules.activeComponents')->get();
```

## 📚 API Reference

### User Model

**Methods:**
- `hasModuleAccess(string $moduleCode): bool`
- `hasSubModuleAccess(string $moduleCode, string $subModuleCode): bool`
- `hasComponentAccess(string $moduleCode, string $subModuleCode, string $componentCode): bool`
- `canPerformAction(string $moduleCode, string $subModuleCode, string $componentCode, string $actionCode): bool`
- `getAccessibleModules(): array`
- `setActiveStatus(bool $status): void`
- `lockAccount(?string $reason = null): void`
- `unlockAccount(): void`
- `resetDevices(?string $reason = null): bool`

### Module Models

**Module:** Top-level module (e.g., HRM, CRM)
- `subModules(): HasMany`
- `activeSubModules()`
- `components()`

**SubModule:** Sub-module under module
- `module(): BelongsTo`
- `components(): HasMany`
- `activeComponents()`

**Component:** Component under sub-module
- `subModule(): BelongsTo`
- `actions(): HasMany`
- `activeActions()`

**Action:** Action within component
- `component(): BelongsTo`

## 🐛 Troubleshooting

### Migration Issues

```bash
# Reset migrations
php artisan migrate:fresh

# Rollback specific migration
php artisan migrate:rollback --step=1
```

### Cache Issues

```bash
# Clear module access cache
php artisan cache:clear
```

### Permission Issues

```bash
# Reset cached permissions
php artisan permission:cache-reset

# Re-sync permissions
php artisan db:seed --class=PermissionSeeder
```

## 📄 License

Proprietary - aeos365

## 🔗 Related Packages

- **aero-hrm** - Human Resource Management module
- **aero-platform** - Platform orchestration and landlord system
- **aero-crm** - Customer Relationship Management module (future)
- **aero-finance** - Finance Management module (future)

## ✨ Features Status

| Feature | Status |
|---------|--------|
| User Model | ✅ Complete |
| Authentication | ✅ Complete |
| Role Management | ✅ Complete |
| Module Access Control | ✅ Complete |
| API Routes | ✅ Complete |
| Middleware | ✅ Complete |
| Migrations | ✅ Complete |
| Controllers | ⏳ In Progress |
| Layouts (React) | ⏳ In Progress |
| Tests | ⏳ Pending |
| Documentation | ✅ Complete |

---

**Created:** January 2024  
**Version:** 1.0.0  
**Maintainer:** Aero Development Team
