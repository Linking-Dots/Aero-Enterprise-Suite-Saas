# Modular Architecture Proposal - Dual-Use Modules

**Date:** December 7, 2025  
**Status:** Proposed  
**Version:** 1.0.0

## Executive Summary

This document proposes a comprehensive modular architecture for the Aero Enterprise Suite SaaS platform that enables modules to function both as:
1. **Independent standalone software** - Installable and usable by single organizations
2. **Platform-integrated modules** - Dynamically composed within the multi-tenant SaaS platform

### Key Principles

1. **Module Independence**: Each module is a complete, self-contained Composer package
2. **Shared Core**: Common platform/tenant utilities are abstracted into reusable packages
3. **Smart Auto-Detection**: Modules automatically adapt to standalone vs. platform context
4. **Zero Code Duplication**: Shared code is properly abstracted and reused
5. **Flexible Deployment**: Support both monorepo development and distributed packages

---

## 1. Architecture Overview

### 1.1 Package Structure

```
Aero-Enterprise-Suite-Saas/
├── packages/
│   ├── aero-core/                    # Shared core utilities
│   │   ├── platform/                 # Platform-level utilities
│   │   ├── tenant/                   # Tenant-level utilities
│   │   ├── ui/                       # Shared UI components
│   │   └── backend/                  # Backend utilities
│   │
│   ├── aero-hrm/                     # HRM Module (standalone-ready)
│   │   ├── src/                      # Backend code
│   │   ├── resources/js/             # Frontend code
│   │   ├── database/                 # Migrations & seeders
│   │   ├── routes/                   # Module routes
│   │   ├── config/                   # Module configuration
│   │   └── tests/                    # Module tests
│   │
│   ├── aero-crm/                     # CRM Module
│   ├── aero-project/                 # Project Management Module
│   ├── aero-finance/                 # Finance Module
│   └── ... (other modules)
│
├── app/                              # Main platform application
└── resources/                        # Platform resources
```

### 1.2 Module Types

#### Core Modules (Required)
- **aero-core** - Shared utilities (platform, tenant, UI, backend)
- Essential for both standalone and platform installations

#### Business Modules (Optional)
- **aero-hrm** - Human Resources Management
- **aero-crm** - Customer Relationship Management
- **aero-project** - Project Management
- **aero-finance** - Financial Management
- **aero-inventory** - Inventory & Supply Chain
- ... (80+ modules from config/modules.php)

---

## 2. The Shared Core Package (`aero-core`)

### 2.1 Purpose

The `aero-core` package contains all shared code needed by both the platform and individual modules:
- Multi-tenant infrastructure
- Authentication & authorization utilities
- Module registry system
- Shared UI components (React/Inertia)
- Backend utilities (services, traits, helpers)

### 2.2 Structure

```
packages/aero-core/
├── src/
│   ├── Platform/                     # Platform utilities
│   │   ├── Billing/
│   │   │   ├── BillingService.php
│   │   │   └── SubscriptionManager.php
│   │   ├── Tenant/
│   │   │   ├── TenantProvisioner.php
│   │   │   └── TenantManager.php
│   │   └── Auth/
│   │       ├── ModernAuthenticationService.php
│   │       └── MultiFactorAuthService.php
│   │
│   ├── Tenant/                       # Tenant utilities
│   │   ├── Module/
│   │   │   ├── ModuleAccessService.php
│   │   │   └── ModuleRegistry.php
│   │   ├── Profile/
│   │   │   └── ProfileUpdateService.php
│   │   └── Mail/
│   │       └── MailService.php
│   │
│   ├── Shared/                       # Context-agnostic utilities
│   │   ├── Traits/
│   │   │   ├── HasTenantContext.php
│   │   │   └── ModuleAware.php
│   │   └── Helpers/
│   │       ├── ArrayHelper.php
│   │       └── DateHelper.php
│   │
│   └── CoreServiceProvider.php       # Core provider
│
├── resources/js/
│   ├── Components/                   # Shared React components
│   │   ├── Layout/
│   │   │   ├── App.jsx
│   │   │   ├── Sidebar.jsx
│   │   │   └── Header.jsx
│   │   ├── Common/
│   │   │   ├── Card.jsx
│   │   │   ├── Modal.jsx
│   │   │   ├── Table.jsx
│   │   │   └── Button.jsx
│   │   └── Forms/
│   │       ├── Input.jsx
│   │       ├── Select.jsx
│   │       └── DatePicker.jsx
│   │
│   ├── Hooks/                        # Custom React hooks
│   │   ├── useTheme.js
│   │   ├── useModuleAccess.js
│   │   └── useTenantContext.js
│   │
│   ├── Utils/                        # Frontend utilities
│   │   ├── api.js
│   │   ├── toastUtils.js
│   │   └── formatters.js
│   │
│   └── theme/                        # Theme configuration
│       └── heroui.config.js
│
├── config/
│   └── aero-core.php                 # Core configuration
│
└── composer.json                     # Package definition
```

### 2.3 Core Package `composer.json`

```json
{
    "name": "aero-modules/core",
    "description": "Shared core utilities for Aero Enterprise Suite modules",
    "type": "library",
    "license": "proprietary",
    "version": "1.0.0",
    "require": {
        "php": "^8.2",
        "laravel/framework": "^11.0",
        "inertiajs/inertia-laravel": "^2.0",
        "stancl/tenancy": "^3.9|^4.0",
        "spatie/laravel-permission": "^6.0"
    },
    "autoload": {
        "psr-4": {
            "AeroModules\\Core\\": "src/"
        },
        "files": [
            "src/helpers.php"
        ]
    },
    "extra": {
        "laravel": {
            "providers": [
                "AeroModules\\Core\\CoreServiceProvider"
            ]
        }
    }
}
```

---

## 3. Module Package Structure (Example: HRM)

### 3.1 Complete Module Structure

```
packages/aero-hrm/
├── src/
│   ├── HrmServiceProvider.php        # Smart service provider
│   ├── HrmManager.php                # Main module class
│   │
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── EmployeeController.php
│   │   │   ├── DepartmentController.php
│   │   │   ├── AttendanceController.php
│   │   │   └── LeaveController.php
│   │   ├── Requests/
│   │   │   └── EmployeeStoreRequest.php
│   │   └── Middleware/
│   │       └── CheckHrmAccess.php
│   │
│   ├── Models/
│   │   ├── Employee.php
│   │   ├── Department.php
│   │   ├── Designation.php
│   │   ├── Attendance.php
│   │   └── Leave.php
│   │
│   ├── Services/
│   │   ├── EmployeeService.php
│   │   ├── AttendanceService.php
│   │   └── LeaveManagementService.php
│   │
│   ├── Policies/
│   │   └── EmployeePolicy.php
│   │
│   └── Console/
│       └── Commands/
│           └── HrmInstallCommand.php
│
├── resources/
│   ├── js/
│   │   ├── Pages/
│   │   │   ├── Employees/
│   │   │   │   ├── EmployeeList.jsx
│   │   │   │   ├── EmployeeProfile.jsx
│   │   │   │   └── EmployeeForm.jsx
│   │   │   ├── Attendance/
│   │   │   │   └── AttendanceCalendar.jsx
│   │   │   └── Leave/
│   │   │       └── LeaveRequests.jsx
│   │   │
│   │   ├── Components/
│   │   │   ├── EmployeeTable.jsx
│   │   │   └── DepartmentSelector.jsx
│   │   │
│   │   └── app.jsx                   # Module entry (standalone mode)
│   │
│   └── views/
│       └── emails/
│           └── leave-approved.blade.php
│
├── database/
│   ├── migrations/
│   │   ├── 2024_01_01_create_departments_table.php
│   │   ├── 2024_01_02_create_designations_table.php
│   │   └── 2024_01_03_create_employees_table.php
│   │
│   ├── seeders/
│   │   └── HrmSeeder.php
│   │
│   └── factories/
│       └── EmployeeFactory.php
│
├── routes/
│   ├── hrm.php                       # Module routes (web)
│   └── api.php                       # Module API routes
│
├── config/
│   └── aero-hrm.php                  # Module configuration
│
├── tests/
│   ├── Unit/
│   │   └── EmployeeServiceTest.php
│   └── Feature/
│       └── EmployeeManagementTest.php
│
├── docs/
│   ├── README.md
│   ├── installation.md
│   └── api.md
│
├── .gitignore
├── LICENSE
├── README.md
├── CHANGELOG.md
├── composer.json
├── package.json                      # For frontend assets
└── phpunit.xml
```

### 3.2 Module `composer.json`

```json
{
    "name": "aero-modules/hrm",
    "description": "Human Resource Management module for Aero Enterprise Suite",
    "type": "library",
    "license": "proprietary",
    "version": "1.0.0",
    "keywords": [
        "hrm", "human-resources", "employee-management",
        "laravel", "multi-tenant", "aero-enterprise"
    ],
    "authors": [
        {
            "name": "Aero Development Team",
            "email": "dev@aero-enterprise.com"
        }
    ],
    "require": {
        "php": "^8.2",
        "laravel/framework": "^11.0",
        "inertiajs/inertia-laravel": "^2.0",
        "aero-modules/core": "^1.0"
    },
    "require-dev": {
        "phpunit/phpunit": "^10.0",
        "orchestra/testbench": "^9.0"
    },
    "autoload": {
        "psr-4": {
            "AeroModules\\Hrm\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "AeroModules\\Hrm\\Tests\\": "tests/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "AeroModules\\Hrm\\HrmServiceProvider"
            ],
            "aliases": {
                "HRM": "AeroModules\\Hrm\\Facades\\Hrm"
            }
        },
        "aero": {
            "code": "hrm",
            "name": "Human Resource Management",
            "category": "human_resources",
            "version": "1.0.0",
            "min_plan": "professional",
            "dependencies": ["core"],
            "features": [
                "employee_management",
                "department_management",
                "attendance_tracking",
                "leave_management",
                "payroll_integration"
            ]
        }
    },
    "scripts": {
        "test": "phpunit",
        "test-coverage": "phpunit --coverage-html coverage"
    },
    "minimum-stability": "dev",
    "prefer-stable": true
}
```

---

## 4. Smart Service Provider Pattern

### 4.1 Auto-Detection Logic

Each module's service provider automatically detects the environment and adapts:

```php
<?php

namespace AeroModules\Hrm;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use AeroModules\Core\Tenant\Module\ModuleRegistry;

class HrmServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Merge configuration
        $this->mergeConfigFrom(__DIR__.'/../config/aero-hrm.php', 'aero-hrm');
        
        // Register module manager
        $this->app->singleton('hrm', function ($app) {
            return new HrmManager($app);
        });
        
        // Register with platform (if available)
        if ($this->isPlatformMode()) {
            $this->registerWithPlatform();
        }
    }

    public function boot(): void
    {
        $mode = $this->detectMode();
        
        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        
        // Load views
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'aero-hrm');
        
        // Register routes based on mode
        $this->registerRoutes($mode);
        
        // Publishables
        if ($this->app->runningInConsole()) {
            $this->registerPublishables();
            $this->registerCommands();
        }
    }

    /**
     * Detect operating mode: standalone, platform, or tenant
     */
    protected function detectMode(): string
    {
        // Check for multi-tenancy package
        if (!class_exists(\Stancl\Tenancy\Tenancy::class)) {
            return 'standalone';
        }
        
        // Check if in tenant context
        if (function_exists('tenant') && tenant() !== null) {
            return 'tenant';
        }
        
        // Platform/landlord context
        return 'platform';
    }

    /**
     * Check if running in platform mode
     */
    protected function isPlatformMode(): bool
    {
        return class_exists(ModuleRegistry::class);
    }

    /**
     * Register with platform module registry
     */
    protected function registerWithPlatform(): void
    {
        $registry = $this->app->make(ModuleRegistry::class);
        
        $registry->register('hrm', [
            'name' => 'Human Resource Management',
            'version' => '1.0.0',
            'provider' => self::class,
            'config' => $this->getModuleMetadata(),
        ]);
    }

    /**
     * Get module metadata from composer.json
     */
    protected function getModuleMetadata(): array
    {
        $composerPath = __DIR__.'/../composer.json';
        
        if (file_exists($composerPath)) {
            $composer = json_decode(file_get_contents($composerPath), true);
            return $composer['extra']['aero'] ?? [];
        }
        
        return [];
    }

    /**
     * Register routes based on mode
     */
    protected function registerRoutes(string $mode): void
    {
        $middleware = $this->getRouteMiddleware($mode);
        $prefix = $this->getRoutePrefix($mode);

        // Web routes
        Route::middleware($middleware)
            ->prefix($prefix)
            ->name('hrm.')
            ->group(__DIR__.'/../routes/hrm.php');

        // API routes (if needed)
        if (file_exists(__DIR__.'/../routes/api.php')) {
            Route::middleware(array_merge($middleware, ['api']))
                ->prefix('api/' . $prefix)
                ->name('hrm.api.')
                ->group(__DIR__.'/../routes/api.php');
        }
    }

    /**
     * Get middleware based on mode
     */
    protected function getRouteMiddleware(string $mode): array
    {
        $middleware = ['web', 'auth'];
        
        if ($mode === 'tenant') {
            // Add tenant initialization middleware
            $middleware[] = \Stancl\Tenancy\Middleware\InitializeTenancyByDomain::class;
            // Add module access check
            $middleware[] = \AeroModules\Hrm\Http\Middleware\CheckHrmAccess::class;
        }
        
        // Add custom middleware from config
        $customMiddleware = config('aero-hrm.routes.middleware', []);
        
        return array_merge($middleware, $customMiddleware);
    }

    /**
     * Get route prefix based on mode
     */
    protected function getRoutePrefix(string $mode): string
    {
        if ($mode === 'standalone') {
            return config('aero-hrm.routes.prefix', 'hrm');
        }
        
        // In platform mode, use tenant prefix
        return config('aero-hrm.routes.prefix', 'tenant/hrm');
    }

    /**
     * Register publishable resources
     */
    protected function registerPublishables(): void
    {
        // Config
        $this->publishes([
            __DIR__.'/../config/aero-hrm.php' => config_path('aero-hrm.php'),
        ], 'aero-hrm-config');
        
        // Migrations
        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'aero-hrm-migrations');
        
        // Frontend assets
        $this->publishes([
            __DIR__.'/../resources/js' => resource_path('js/vendor/aero-hrm'),
        ], 'aero-hrm-assets');
        
        // Views
        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/aero-hrm'),
        ], 'aero-hrm-views');
    }

    /**
     * Register console commands
     */
    protected function registerCommands(): void
    {
        $this->commands([
            \AeroModules\Hrm\Console\Commands\HrmInstallCommand::class,
        ]);
    }
}
```

---

## 5. Frontend Architecture

### 5.1 Shared UI Components

The `aero-core` package provides a complete React component library that all modules can use:

```javascript
// packages/aero-core/resources/js/Components/Common/Card.jsx
import React from 'react';
import { Card as HeroCard, CardHeader, CardBody } from '@heroui/react';
import { useTheme } from '../../Hooks/useTheme';

export const Card = ({ title, children, actions, ...props }) => {
    const { getCardStyle, getCardHeaderStyle } = useTheme();
    
    return (
        <HeroCard
            className="transition-all duration-200"
            style={getCardStyle()}
            {...props}
        >
            {title && (
                <CardHeader
                    style={getCardHeaderStyle()}
                    className="flex justify-between items-center"
                >
                    <h2 className="text-lg font-semibold">{title}</h2>
                    {actions && <div className="flex gap-2">{actions}</div>}
                </CardHeader>
            )}
            <CardBody>{children}</CardBody>
        </HeroCard>
    );
};
```

### 5.2 Module Frontend Integration

#### Standalone Mode
In standalone mode, the module uses its own entry point:

```javascript
// packages/aero-hrm/resources/js/app.jsx
import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import { HeroUIProvider } from '@heroui/react';

// Import shared components from core
import { App as AppLayout } from '@aero-modules/core/Components/Layout/App';

createInertiaApp({
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.jsx', { eager: true });
        const page = pages[`./Pages/${name}.jsx`];
        
        // Wrap with layout
        page.default.layout = page.default.layout || ((page) => (
            <AppLayout>{page}</AppLayout>
        ));
        
        return page;
    },
    setup({ el, App, props }) {
        const root = createRoot(el);
        
        root.render(
            <HeroUIProvider>
                <App {...props} />
            </HeroUIProvider>
        );
    },
});
```

#### Platform Mode
In platform mode, modules are integrated into the main application bundle:

```javascript
// Main platform: resources/js/app.jsx
import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import { HeroUIProvider } from '@heroui/react';
import { App as AppLayout } from './Layouts/App';

createInertiaApp({
    resolve: (name) => {
        // Check if it's a platform page
        if (name.startsWith('Admin/') || name.startsWith('Platform/')) {
            const pages = import.meta.glob('./Admin/Pages/**/*.jsx', { eager: true });
            return pages[`./Admin/Pages/${name}.jsx`];
        }
        
        // Check if it's a tenant page
        if (name.startsWith('Tenant/')) {
            const pages = import.meta.glob('./Tenant/Pages/**/*.jsx', { eager: true });
            return pages[`./Tenant/Pages/${name}.jsx`];
        }
        
        // Try to load from installed modules
        const moduleName = name.split('/')[0];
        try {
            return import(`@aero-modules/${moduleName}/Pages/${name}`);
        } catch (e) {
            console.error(`Page not found: ${name}`);
            throw e;
        }
    },
    setup({ el, App, props }) {
        const root = createRoot(el);
        
        root.render(
            <HeroUIProvider>
                <AppLayout>
                    <App {...props} />
                </AppLayout>
            </HeroUIProvider>
        );
    },
});
```

### 5.3 Module `package.json`

```json
{
    "name": "@aero-modules/hrm",
    "version": "1.0.0",
    "type": "module",
    "description": "HRM module frontend assets",
    "main": "resources/js/app.jsx",
    "peerDependencies": {
        "@aero-modules/core": "^1.0.0",
        "@heroui/react": "^2.8.0",
        "@inertiajs/react": "^2.0.0",
        "react": "^18.0.0",
        "react-dom": "^18.0.0"
    },
    "devDependencies": {
        "@vitejs/plugin-react": "^4.2.0",
        "vite": "^6.0.0"
    }
}
```

---

## 6. Installation Scenarios

### 6.1 Scenario A: Standalone Installation

#### Step 1: Install Module via Composer

```bash
# Create new Laravel project
composer create-project laravel/laravel my-hrm-system

cd my-hrm-system

# Add private repository (if using)
composer config repositories.aero-modules composer https://packages.aero-enterprise.com

# Install HRM module (includes core as dependency)
composer require aero-modules/hrm
```

#### Step 2: Publish Assets

```bash
# Publish configuration
php artisan vendor:publish --tag=aero-hrm-config

# Publish and run migrations
php artisan vendor:publish --tag=aero-hrm-migrations
php artisan migrate

# Publish frontend assets
php artisan vendor:publish --tag=aero-hrm-assets

# (Optional) Publish views
php artisan vendor:publish --tag=aero-hrm-views
```

#### Step 3: Configure Frontend

```javascript
// vite.config.js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/js/app.js',
                'resources/js/vendor/aero-hrm/app.jsx', // Module entry
            ],
            refresh: true,
        }),
        react(),
    ],
    resolve: {
        alias: {
            '@aero-modules/core': './vendor/aero-modules/core/resources/js',
            '@': './resources/js',
        },
    },
});
```

#### Step 4: Build and Run

```bash
# Install npm dependencies
npm install

# Build assets
npm run build

# Serve application
php artisan serve
```

### 6.2 Scenario B: Platform Installation

#### Step 1: Add Module to Platform

```bash
cd /path/to/Aero-Enterprise-Suite-Saas

# For development (local packages)
composer config repositories.local-hrm path ./packages/aero-hrm
composer require aero-modules/hrm:@dev

# For production (from package registry)
composer require aero-modules/hrm
```

#### Step 2: Auto-Discovery
The module is automatically discovered and registered with the platform. No additional configuration needed!

#### Step 3: Build Platform
The platform's build process automatically includes all module assets:

```bash
npm run build
```

---

## 7. Module Development Workflow

### 7.1 Create New Module

```bash
# Run module scaffolding command
php artisan aero:make-module ProjectManagement --code=project

# This creates:
# packages/aero-project/
#   ├── src/
#   ├── resources/
#   ├── database/
#   ├── routes/
#   ├── config/
#   ├── tests/
#   ├── composer.json
#   └── README.md
```

### 7.2 Develop Module Locally

```bash
cd packages/aero-project

# Install dependencies
composer install

# Run tests
composer test

# Run local dev server (for standalone testing)
php artisan serve --env=testing
```

### 7.3 Test in Platform Context

```bash
# Return to platform root
cd ../../

# Link module for development
composer config repositories.local-project path ./packages/aero-project
composer require aero-modules/project:@dev

# Run platform tests
php artisan test --filter=ProjectModule
```

### 7.4 Publish Module

```bash
cd packages/aero-project

# Update version in composer.json
# Update CHANGELOG.md

# Tag release
git tag -a v1.0.0 -m "Release version 1.0.0"
git push origin v1.0.0

# Publish to private registry (automated via CI/CD)
# Or manually:
composer archive --format=zip --dir=dist
```

---

## 8. Dependency Management

### 8.1 Module Dependencies

Modules can depend on other modules:

```json
{
    "require": {
        "aero-modules/core": "^1.0",
        "aero-modules/crm": "^1.0"
    }
}
```

The module registry validates dependencies at runtime:

```php
// In CoreServiceProvider
public function boot(): void
{
    $registry = $this->app->make(ModuleRegistry::class);
    
    // Validate all registered modules
    $registry->validateDependencies();
}
```

### 8.2 Shared Dependencies

Common dependencies are managed in `aero-core`:

```json
{
    "require": {
        "laravel/framework": "^11.0",
        "inertiajs/inertia-laravel": "^2.0",
        "stancl/tenancy": "^3.9|^4.0",
        "spatie/laravel-permission": "^6.0",
        "@heroui/react": "^2.8.0"
    }
}
```

Individual modules only need to require `aero-core`.

---

## 9. Database Considerations

### 9.1 Standalone Mode

Modules run their migrations directly:

```php
// In HrmServiceProvider
public function boot(): void
{
    $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
}
```

### 9.2 Platform Mode (Multi-Tenant)

Migrations are tenant-aware:

```php
// Migration file
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            
            // Tenant context is handled by tenancy package
            // No need for manual tenant_id columns
            
            $table->string('employee_code')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->foreignId('department_id')->constrained();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
```

The `stancl/tenancy` package automatically handles tenant context.

---

## 10. Configuration Management

### 10.1 Module Configuration

Each module has its own config file:

```php
// packages/aero-hrm/config/aero-hrm.php
return [
    // Module metadata
    'name' => 'Human Resource Management',
    'code' => 'hrm',
    'version' => '1.0.0',
    
    // Routes
    'routes' => [
        'prefix' => 'hrm',
        'middleware' => [],
    ],
    
    // Features
    'features' => [
        'employee_management' => true,
        'attendance_tracking' => true,
        'leave_management' => true,
        'payroll' => false, // Premium feature
    ],
    
    // Permissions
    'permissions' => [
        'employee.view',
        'employee.create',
        'employee.update',
        'employee.delete',
        'attendance.view',
        'attendance.manage',
        'leave.view',
        'leave.approve',
    ],
];
```

### 10.2 Environment-Specific Config

```php
// In service provider
public function register(): void
{
    $this->mergeConfigFrom(__DIR__.'/../config/aero-hrm.php', 'aero-hrm');
    
    // Override for platform mode
    if ($this->isPlatformMode()) {
        config([
            'aero-hrm.routes.prefix' => 'tenant/hrm',
            'aero-hrm.routes.middleware' => ['tenant.init', 'module.access:hrm'],
        ]);
    }
}
```

---

## 11. Testing Strategy

### 11.1 Module Tests

Each module has its own test suite:

```php
// packages/aero-hrm/tests/Unit/EmployeeServiceTest.php
namespace AeroModules\Hrm\Tests\Unit;

use AeroModules\Hrm\Services\EmployeeService;
use AeroModules\Hrm\Tests\TestCase;

class EmployeeServiceTest extends TestCase
{
    protected EmployeeService $service;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new EmployeeService();
    }
    
    public function test_can_create_employee(): void
    {
        $employee = $this->service->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'department_id' => 1,
        ]);
        
        $this->assertNotNull($employee->id);
        $this->assertEquals('John', $employee->first_name);
    }
}
```

### 11.2 Integration Tests

Test modules in platform context:

```php
// tests/Feature/Modules/HrmIntegrationTest.php
namespace Tests\Feature\Modules;

use Tests\TestCase;
use AeroModules\Core\Tenant\Module\ModuleRegistry;

class HrmIntegrationTest extends TestCase
{
    public function test_hrm_module_is_registered(): void
    {
        $registry = app(ModuleRegistry::class);
        
        $this->assertTrue($registry->isRegistered('hrm'));
        $this->assertEquals('Human Resource Management', $registry->getName('hrm'));
    }
    
    public function test_hrm_routes_are_accessible(): void
    {
        $this->actingAs($this->createUser())
            ->get('/tenant/hrm/employees')
            ->assertOk();
    }
}
```

---

## 12. Distribution & Versioning

### 12.1 Package Repository Options

#### Option A: GitHub Packages (Recommended for Start)

```json
// Platform composer.json
{
    "repositories": [
        {
            "type": "composer",
            "url": "https://composer.pkg.github.com/Linking-Dots"
        }
    ]
}
```

#### Option B: Private Packagist (Recommended for Production)

```json
{
    "repositories": [
        {
            "type": "composer",
            "url": "https://aero-enterprise.composer.sh"
        }
    ]
}
```

#### Option C: Satis (Self-Hosted)

```json
{
    "repositories": [
        {
            "type": "composer",
            "url": "https://packages.aero-enterprise.com"
        }
    ]
}
```

### 12.2 Semantic Versioning

All modules follow [Semantic Versioning](https://semver.org/):

- **Major (1.x.x)**: Breaking changes
- **Minor (x.1.x)**: New features, backward compatible
- **Patch (x.x.1)**: Bug fixes, backward compatible

```json
{
    "require": {
        "aero-modules/core": "^1.0",
        "aero-modules/hrm": "^1.2",
        "aero-modules/crm": "~2.1.0"
    }
}
```

### 12.3 Release Process

```bash
# 1. Update version in composer.json
# 2. Update CHANGELOG.md
# 3. Commit changes
git add composer.json CHANGELOG.md
git commit -m "Release v1.2.0"

# 4. Tag release
git tag -a v1.2.0 -m "Release version 1.2.0"
git push origin v1.2.0

# 5. Automated CI/CD publishes to package repository
```

---

## 13. Security Considerations

### 13.1 License Validation

```php
// packages/aero-core/src/Platform/License/LicenseValidator.php
namespace AeroModules\Core\Platform\License;

use Illuminate\Support\Facades\Http;

class LicenseValidator
{
    public function validateModule(string $moduleCode): bool
    {
        // For standalone mode, check local license
        if (!class_exists(\Stancl\Tenancy\Tenancy::class)) {
            return $this->validateStandaloneLicense($moduleCode);
        }
        
        // For platform mode, validate via API
        return $this->validatePlatformLicense($moduleCode);
    }
    
    protected function validateStandaloneLicense(string $moduleCode): bool
    {
        $license = config('aero-core.license_key');
        
        if (!$license) {
            throw new \Exception('License key not found');
        }
        
        $response = Http::post('https://license.aero-enterprise.com/validate', [
            'license_key' => $license,
            'module' => $moduleCode,
            'domain' => request()->getHost(),
        ]);
        
        if (!$response->successful()) {
            throw new \Exception('License validation failed');
        }
        
        return $response->json('valid') === true;
    }
    
    protected function validatePlatformLicense(string $moduleCode): bool
    {
        // Platform license is validated at tenant subscription level
        // Check if tenant has access to this module via their plan
        $tenant = tenant();
        
        return $tenant->subscription?->plan
            ?->hasModule($moduleCode) ?? false;
    }
}
```

### 13.2 Dependency Security

```bash
# Run security audit on all modules
composer audit

# Check for vulnerabilities in npm packages
npm audit

# Use GitHub Dependabot for automated security updates
```

---

## 14. Migration Path

### 14.1 Phase 1: Extract Core (Week 1-2)

- [ ] Create `packages/aero-core` structure
- [ ] Move shared utilities from `app/Services/Shared` to core
- [ ] Extract shared UI components to core
- [ ] Update imports across codebase
- [ ] Test platform functionality

### 14.2 Phase 2: Extract First Module (Week 3-4)

- [ ] Choose pilot module (HRM recommended, already partially done)
- [ ] Complete HRM extraction to `packages/aero-hrm`
- [ ] Test standalone installation
- [ ] Test platform integration
- [ ] Document lessons learned

### 14.3 Phase 3: Automate & Scale (Week 5-8)

- [ ] Create module scaffolding tool
- [ ] Create dependency analyzer
- [ ] Extract 3-5 more modules
- [ ] Setup package repository
- [ ] Create CI/CD pipeline

### 14.4 Phase 4: Full Migration (Week 9-12)

- [ ] Extract remaining modules
- [ ] Update documentation
- [ ] Create marketplace (if desired)
- [ ] Launch standalone products

---

## 15. Success Metrics

### 15.1 Technical Metrics

- **Module Independence**: Each module can install and run standalone
- **Zero Duplication**: No duplicated code between modules and platform
- **Build Time**: Module builds complete in < 30 seconds
- **Test Coverage**: Each module has > 80% test coverage
- **Bundle Size**: Module JS bundles < 500KB each

### 15.2 Business Metrics

- **Standalone Sales**: Number of standalone module installations
- **Development Velocity**: Time to develop new modules
- **Maintenance Effort**: Time spent on module maintenance
- **Customer Satisfaction**: User feedback on module quality

---

## 16. Tools & Automation

### 16.1 Module Scaffolding

```bash
# Create new module
php artisan aero:make-module PayrollManagement --code=payroll --category=human_resources

# Generates complete package structure with:
# - Service provider
# - Base models
# - Controllers
# - Migrations
# - Frontend components
# - Tests
# - Documentation
```

### 16.2 Dependency Analysis

```bash
# Analyze module dependencies
php artisan aero:analyze-module hrm

# Output:
# ✓ Models: 5 found
# ✓ Controllers: 8 found
# ✓ Migrations: 12 found
# ⚠ External Dependencies: 3 found
#   - App\Models\User (from core)
#   - App\Services\MailService (should move to core)
#   - App\Services\Billing\InvoiceService (external module)
```

### 16.3 Module Validator

```bash
# Validate module package structure
php artisan aero:validate-module hrm

# Output:
# ✓ composer.json valid
# ✓ Service provider found
# ✓ Migrations present
# ✓ Routes defined
# ✗ Tests missing (12 warnings)
# ✗ README.md incomplete
```

---

## 17. Conclusion

This modular architecture provides:

1. **True Module Independence** - Each module can run standalone or integrated
2. **Clean Code Separation** - Shared code properly abstracted in core package
3. **Flexible Deployment** - Support both monorepo dev and distributed packages
4. **Easy Maintenance** - Clear boundaries and responsibilities
5. **Business Opportunities** - Ability to sell modules individually

### Next Steps

1. Review and approve this proposal
2. Create `aero-core` package structure
3. Complete HRM module extraction
4. Setup package repository
5. Create development tools
6. Begin systematic module extraction

---

## Appendix A: File Structure Reference

Complete file structure for reference:

```
Aero-Enterprise-Suite-Saas/
├── packages/
│   ├── aero-core/                           # Shared core
│   │   ├── src/
│   │   │   ├── Platform/                    # Platform utilities
│   │   │   │   ├── Billing/
│   │   │   │   ├── Tenant/
│   │   │   │   ├── Auth/
│   │   │   │   └── License/
│   │   │   ├── Tenant/                      # Tenant utilities
│   │   │   │   ├── Module/
│   │   │   │   ├── Profile/
│   │   │   │   └── Mail/
│   │   │   ├── Shared/                      # Context-agnostic
│   │   │   │   ├── Traits/
│   │   │   │   └── Helpers/
│   │   │   └── CoreServiceProvider.php
│   │   ├── resources/js/
│   │   │   ├── Components/                  # Shared React components
│   │   │   ├── Hooks/                       # Custom hooks
│   │   │   ├── Utils/                       # Frontend utilities
│   │   │   └── theme/                       # Theme config
│   │   ├── config/aero-core.php
│   │   ├── composer.json
│   │   ├── package.json
│   │   └── README.md
│   │
│   ├── aero-hrm/                            # HRM Module
│   │   ├── src/
│   │   │   ├── HrmServiceProvider.php
│   │   │   ├── HrmManager.php
│   │   │   ├── Http/
│   │   │   ├── Models/
│   │   │   ├── Services/
│   │   │   ├── Policies/
│   │   │   └── Console/
│   │   ├── resources/
│   │   │   ├── js/
│   │   │   │   ├── Pages/
│   │   │   │   ├── Components/
│   │   │   │   └── app.jsx
│   │   │   └── views/
│   │   ├── database/
│   │   ├── routes/
│   │   ├── config/
│   │   ├── tests/
│   │   ├── composer.json
│   │   ├── package.json
│   │   └── README.md
│   │
│   └── [other modules...]
│
├── app/                                      # Main platform
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/                       # Platform admin
│   │   │   └── Platform/                    # Public pages
│   │   └── Middleware/
│   ├── Models/
│   ├── Policies/
│   └── Providers/
│
├── resources/
│   ├── js/
│   │   ├── Admin/Pages/                     # Platform admin UI
│   │   ├── Platform/Pages/                  # Public UI
│   │   ├── Layouts/
│   │   └── app.jsx
│   └── views/
│
├── routes/
│   ├── admin.php
│   ├── platform.php
│   └── api.php
│
├── config/
│   ├── modules.php
│   ├── tenancy.php
│   └── ...
│
├── composer.json                             # Main platform composer
├── package.json                              # Main platform package
├── vite.config.js
└── README.md
```

---

**Document Version:** 1.0.0  
**Last Updated:** December 7, 2025  
**Status:** Proposed - Awaiting Approval
