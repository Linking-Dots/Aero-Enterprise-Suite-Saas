# Modular Architecture - Implementation Guide

## Quick Start

This guide walks you through implementing the modular architecture for Aero Enterprise Suite SaaS.

## Table of Contents

1. [Overview](#overview)
2. [Prerequisites](#prerequisites)
3. [Phase 1: Setup Core Package](#phase-1-setup-core-package)
4. [Phase 2: Module Analysis](#phase-2-module-analysis)
5. [Phase 3: Extract First Module](#phase-3-extract-first-module)
6. [Phase 4: Testing](#phase-4-testing)
7. [Phase 5: Distribution](#phase-5-distribution)

---

## Overview

Our modular architecture enables modules to work both:
- **Standalone**: Independent installations for single organizations
- **Platform-integrated**: Multi-tenant SaaS composition

### Key Principles

- ✅ Module Independence
- ✅ Shared Core Utilities  
- ✅ Smart Auto-Detection
- ✅ Zero Code Duplication
- ✅ Flexible Deployment

---

## Prerequisites

### Required
- PHP 8.2+
- Composer 2.x
- Node.js 18+ & npm 9+
- Laravel 11
- Git

### Knowledge
- Laravel package development
- React/Inertia.js
- Multi-tenancy concepts
- Composer package management

---

## Phase 1: Setup Core Package

### Step 1: Create Core Package Structure

```bash
mkdir -p packages/aero-core/src/{Platform,Tenant,Shared}
mkdir -p packages/aero-core/resources/js/{Components,Hooks,Utils,theme}
mkdir -p packages/aero-core/config

touch packages/aero-core/composer.json
touch packages/aero-core/package.json
touch packages/aero-core/README.md
```

### Step 2: Create Core `composer.json`

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
        }
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

### Step 3: Move Shared Services to Core

#### Identify Shared Services
```bash
# Services used by multiple modules
app/Services/Shared/Module/ModuleAccessService.php
app/Services/Shared/Module/RoleModuleAccessService.php
app/Services/Shared/Profile/ProfileUpdateService.php
app/Services/Shared/MailService.php

# Platform Services
app/Services/Platform/Monitoring/Tenant/TenantProvisioner.php
app/Services/Platform/Billing/
```

#### Move Services
```bash
# Example: Move ModuleAccessService
mkdir -p packages/aero-core/src/Tenant/Module
cp app/Services/Shared/Module/ModuleAccessService.php \
   packages/aero-core/src/Tenant/Module/

# Update namespace
# Old: namespace App\Services\Shared\Module;
# New: namespace AeroModules\Core\Tenant\Module;
```

### Step 4: Extract Shared UI Components

```bash
# Create shared component structure
mkdir -p packages/aero-core/resources/js/Components/{Common,Layout,Forms}

# Move shared components
cp resources/js/Components/StatsCards.jsx \
   packages/aero-core/resources/js/Components/Common/

cp resources/js/Components/PageHeader.jsx \
   packages/aero-core/resources/js/Components/Common/

cp resources/js/Components/EnhancedModal.jsx \
   packages/aero-core/resources/js/Components/Common/

# Move layouts
cp resources/js/Layouts/App.jsx \
   packages/aero-core/resources/js/Components/Layout/
```

### Step 5: Create Core Service Provider

Create `packages/aero-core/src/CoreServiceProvider.php`:

```php
<?php

namespace AeroModules\Core;

use Illuminate\Support\ServiceProvider;

class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register core services
        $this->app->singleton(
            \AeroModules\Core\Tenant\Module\ModuleRegistry::class
        );
    }

    public function boot(): void
    {
        // Load config
        $this->mergeConfigFrom(
            __DIR__.'/../config/aero-core.php', 'aero-core'
        );

        // Publish config
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/aero-core.php' => config_path('aero-core.php'),
            ], 'aero-core-config');
        }
    }
}
```

### Step 6: Link Core Package Locally

In main `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "./packages/aero-core"
        }
    ],
    "require": {
        "aero-modules/core": "@dev"
    }
}
```

```bash
composer update aero-modules/core
```

### Step 7: Update Imports

Search and replace old imports:
```bash
# Find all files using old namespaces
grep -r "App\\Services\\Shared\\Module" app/ resources/
grep -r "use App\\Services\\Shared\\Module\\ModuleAccessService" --include="*.php" -l

# Update to new namespace
# Old: use App\Services\Shared\Module\ModuleAccessService;
# New: use AeroModules\Core\Tenant\Module\ModuleAccessService;
```

---

## Phase 2: Module Analysis

### Step 1: Run Module Analyzer

```bash
# Analyze HRM module
php tools/module-tools/module-analyzer.php hrm

# This generates:
# - Console output with findings
# - JSON report in storage/module-analysis-hrm.json
```

### Step 2: Review Analysis Report

The analyzer will show:
- 📦 Models and relationships
- 🎮 Controllers and middleware
- 🗄️  Migrations and foreign keys
- 🎨 Frontend pages and components
- 🔗 Dependencies on core services
- 📚 Shared code usage

### Step 3: Plan Extraction

Based on the report:

1. **Identify Module Boundaries**
   - Which models belong to the module?
   - Which controllers are module-specific?
   - Which routes are module routes?

2. **Map Dependencies**
   - What does this module depend on?
   - Does it depend on other modules?
   - What shared services does it use?

3. **Plan Migration Strategy**
   - How to handle foreign keys?
   - What's the migration order?
   - Any data migration needed?

---

## Phase 3: Extract First Module

### Step 1: Create Module Package Structure

```bash
# Create HRM module structure
mkdir -p packages/aero-hrm/src/{Http/{Controllers,Requests,Middleware},Models,Services,Policies,Console/Commands}
mkdir -p packages/aero-hrm/resources/js/{Pages,Components,Forms}
mkdir -p packages/aero-hrm/database/{migrations,seeders,factories}
mkdir -p packages/aero-hrm/routes
mkdir -p packages/aero-hrm/config
mkdir -p packages/aero-hrm/tests/{Unit,Feature}
```

### Step 2: Create Module `composer.json`

```json
{
    "name": "aero-modules/hrm",
    "description": "Human Resource Management module for Aero Enterprise Suite",
    "type": "library",
    "license": "proprietary",
    "version": "1.0.0",
    "require": {
        "php": "^8.2",
        "laravel/framework": "^11.0",
        "aero-modules/core": "^1.0"
    },
    "autoload": {
        "psr-4": {
            "AeroModules\\Hrm\\": "src/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "AeroModules\\Hrm\\HrmServiceProvider"
            ]
        },
        "aero": {
            "code": "hrm",
            "name": "Human Resource Management",
            "category": "human_resources",
            "version": "1.0.0",
            "min_plan": "professional",
            "dependencies": ["core"]
        }
    }
}
```

### Step 3: Move Module Files

```bash
# Move models
cp app/Models/Employee.php packages/aero-hrm/src/Models/
cp app/Models/Department.php packages/aero-hrm/src/Models/
cp app/Models/Designation.php packages/aero-hrm/src/Models/
# ... etc

# Move controllers
cp app/Http/Controllers/Tenant/HRM/Employee/EmployeeController.php \
   packages/aero-hrm/src/Http/Controllers/

# Move migrations
cp database/migrations/*employees* packages/aero-hrm/database/migrations/
cp database/migrations/*departments* packages/aero-hrm/database/migrations/

# Move frontend
cp -r resources/js/Tenant/Pages/Employees packages/aero-hrm/resources/js/Pages/
```

### Step 4: Update Namespaces

For each moved file, update the namespace:

```php
// Old namespace
namespace App\Models;

// New namespace
namespace AeroModules\Hrm\Models;
```

```php
// Old controller namespace
namespace App\Http\Controllers\Tenant\HRM\Employee;

// New controller namespace
namespace AeroModules\Hrm\Http\Controllers;
```

### Step 5: Create Service Provider

Create `packages/aero-hrm/src/HrmServiceProvider.php`:

```php
<?php

namespace AeroModules\Hrm;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class HrmServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/aero-hrm.php', 'aero-hrm');
        
        $this->app->singleton('hrm', function ($app) {
            return new HrmManager($app);
        });
    }

    public function boot(): void
    {
        $mode = $this->detectMode();
        
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'aero-hrm');
        
        $this->registerRoutes($mode);
        
        if ($this->app->runningInConsole()) {
            $this->registerPublishables();
        }
    }

    protected function detectMode(): string
    {
        if (!class_exists(\Stancl\Tenancy\Tenancy::class)) {
            return 'standalone';
        }
        
        if (function_exists('tenant') && tenant() !== null) {
            return 'tenant';
        }
        
        return 'platform';
    }

    protected function registerRoutes(string $mode): void
    {
        $middleware = $this->getRouteMiddleware($mode);
        $prefix = config('aero-hrm.routes.prefix', 'hrm');

        Route::middleware($middleware)
            ->prefix($prefix)
            ->name('hrm.')
            ->group(__DIR__.'/../routes/hrm.php');
    }

    protected function getRouteMiddleware(string $mode): array
    {
        $middleware = ['web', 'auth'];
        
        if ($mode === 'tenant') {
            $middleware[] = \Stancl\Tenancy\Middleware\InitializeTenancyByDomain::class;
        }
        
        return $middleware;
    }

    protected function registerPublishables(): void
    {
        $this->publishes([
            __DIR__.'/../config/aero-hrm.php' => config_path('aero-hrm.php'),
        ], 'aero-hrm-config');
        
        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'aero-hrm-migrations');
        
        $this->publishes([
            __DIR__.'/../resources/js' => resource_path('js/vendor/aero-hrm'),
        ], 'aero-hrm-assets');
    }
}
```

### Step 6: Create Module Routes

Create `packages/aero-hrm/routes/hrm.php`:

```php
<?php

use Illuminate\Support\Facades\Route;
use AeroModules\Hrm\Http\Controllers\EmployeeController;
use AeroModules\Hrm\Http\Controllers\DepartmentController;

// Employee routes
Route::resource('employees', EmployeeController::class);

// Department routes
Route::resource('departments', DepartmentController::class);

// Attendance routes
Route::prefix('attendance')->name('attendance.')->group(function () {
    // ... attendance routes
});
```

### Step 7: Link Module to Platform

In main `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "./packages/aero-core"
        },
        {
            "type": "path",
            "url": "./packages/aero-hrm"
        }
    ],
    "require": {
        "aero-modules/core": "@dev",
        "aero-modules/hrm": "@dev"
    }
}
```

```bash
composer update aero-modules/hrm
```

---

## Phase 4: Testing

### Step 1: Test Module in Standalone Mode

```bash
# Create test Laravel application
composer create-project laravel/laravel test-hrm-standalone
cd test-hrm-standalone

# Add local repository
composer config repositories.aero-hrm path ../Aero-Enterprise-Suite-Saas/packages/aero-hrm
composer config repositories.aero-core path ../Aero-Enterprise-Suite-Saas/packages/aero-core

# Install HRM module
composer require aero-modules/hrm:@dev

# Publish assets
php artisan vendor:publish --tag=aero-hrm-config
php artisan vendor:publish --tag=aero-hrm-migrations

# Run migrations
php artisan migrate

# Test
php artisan serve
# Visit http://localhost:8000/hrm/employees
```

### Step 2: Test Module in Platform Mode

```bash
# Return to main platform
cd ../Aero-Enterprise-Suite-Saas

# Clear caches
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Test platform
php artisan serve
# Visit http://localhost:8000/tenant/hrm/employees
```

### Step 3: Run Tests

```bash
# Run module tests
cd packages/aero-hrm
composer test

# Run platform tests
cd ../../
php artisan test --filter=HRM
```

---

## Phase 5: Distribution

### Option A: GitHub Packages (Quick Start)

#### Step 1: Setup GitHub Packages

In your module's root, create `.github/workflows/publish.yml`:

```yaml
name: Publish Package

on:
  release:
    types: [created]

jobs:
  publish:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
      
      - name: Publish to GitHub Packages
        run: |
          composer config repositories.github composer \
            https://composer.pkg.github.com/Linking-Dots
          composer publish
        env:
          COMPOSER_AUTH: ${{ secrets.GITHUB_TOKEN }}
```

#### Step 2: Create Release

```bash
cd packages/aero-hrm

# Tag release
git tag -a v1.0.0 -m "Release version 1.0.0"
git push origin v1.0.0

# GitHub Actions will automatically publish
```

#### Step 3: Install from GitHub Packages

In consumer's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "composer",
            "url": "https://composer.pkg.github.com/Linking-Dots"
        }
    ],
    "require": {
        "aero-modules/hrm": "^1.0"
    }
}
```

### Option B: Satis (Self-Hosted)

#### Step 1: Setup Satis

```bash
# Install Satis
composer create-project composer/satis --stability=dev --keep-vcs

cd satis

# Create config
cat > satis.json <<EOF
{
    "name": "Aero Enterprise Packages",
    "homepage": "https://packages.aero-enterprise.com",
    "repositories": [
        { "type": "vcs", "url": "https://github.com/Linking-Dots/aero-core" },
        { "type": "vcs", "url": "https://github.com/Linking-Dots/aero-hrm" }
    ],
    "require-all": true
}
EOF

# Build repository
php bin/satis build satis.json public/
```

#### Step 2: Host Satis

```nginx
# nginx config
server {
    listen 80;
    server_name packages.aero-enterprise.com;
    root /var/www/satis/public;
    
    location / {
        try_files $uri $uri/ =404;
    }
}
```

#### Step 3: Use Satis Repository

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

---

## Troubleshooting

### Issue: "Class not found" after extraction

**Solution**: Update autoload and clear caches

```bash
composer dump-autoload
php artisan config:clear
php artisan cache:clear
```

### Issue: Frontend components not loading

**Solution**: Rebuild assets

```bash
npm run build
# or
npm run dev
```

### Issue: Routes not working

**Solution**: Clear route cache and check middleware

```bash
php artisan route:clear
php artisan route:list | grep hrm
```

### Issue: Migrations fail due to foreign keys

**Solution**: Run migrations in correct order

```bash
# First run core/user migrations
php artisan migrate --path=database/migrations

# Then run module migrations
php artisan migrate --path=vendor/aero-modules/hrm/database/migrations
```

---

## Best Practices

### 1. Version Control

- Use semantic versioning (MAJOR.MINOR.PATCH)
- Maintain detailed CHANGELOG.md
- Tag releases properly
- Use Git tags for versions

### 2. Documentation

- Complete README.md for each module
- Document installation steps
- Provide usage examples
- Maintain API documentation

### 3. Testing

- Write unit tests for services
- Write feature tests for controllers
- Test both standalone and platform modes
- Maintain >80% code coverage

### 4. Dependencies

- Minimize external dependencies
- Document all dependencies
- Keep dependencies up to date
- Use security scanners

### 5. Backwards Compatibility

- Don't break existing APIs
- Deprecate before removing
- Provide migration guides
- Version breaking changes properly

---

## Next Steps

1. ✅ Complete core package extraction
2. ✅ Extract and test first module (HRM)
3. 🔄 Create automated tools
4. 🔄 Extract remaining modules
5. 🔄 Setup package repository
6. 🔄 Create CI/CD pipeline
7. 🔄 Documentation and training
8. 🔄 Launch standalone products

---

## Resources

- [Full Architecture Proposal](./modular-architecture-proposal.md)
- [Laravel Package Development](https://laravel.com/docs/11.x/packages)
- [Composer Documentation](https://getcomposer.org/doc/)
- [Stancl Tenancy](https://tenancyforlaravel.com/)

---

## Support

For questions or issues:
- GitHub Issues: [Aero Enterprise Suite](https://github.com/Linking-Dots/Aero-Enterprise-Suite-Saas/issues)
- Email: dev@aero-enterprise.com

---

**Last Updated:** December 7, 2025  
**Version:** 1.0.0
