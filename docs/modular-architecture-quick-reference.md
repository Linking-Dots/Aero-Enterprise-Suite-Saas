# Modular Architecture - Quick Reference

## 🎯 Goal

Transform Aero Enterprise Suite SaaS into a modular system where each module:
1. Can be installed and used **standalone** (independent software)
2. Can be integrated into the **multi-tenant platform** (SaaS composition)
3. Shares common code through the **aero-core** package (zero duplication)

---

## 📦 Package Structure

```
packages/
├── aero-core/              # Shared utilities for all modules
│   ├── Platform/           # Platform-level services (billing, tenant management)
│   ├── Tenant/             # Tenant-level services (module access, profiles)
│   ├── Shared/             # Context-agnostic utilities
│   └── UI/                 # Shared React components
│
├── aero-hrm/               # HRM Module (standalone-ready)
├── aero-crm/               # CRM Module
├── aero-project/           # Project Management
└── ... (80+ modules)
```

---

## 🔑 Key Concepts

### 1. Smart Service Provider

Each module auto-detects its environment:
- **Standalone**: Regular Laravel app
- **Platform**: Multi-tenant landlord context
- **Tenant**: Multi-tenant tenant context

```php
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
```

### 2. Shared Core Package

All common code lives in `aero-core`:
- Multi-tenancy utilities
- Authentication services
- Module registry
- Shared UI components
- Backend helpers

Modules only depend on `aero-core`, not the platform.

### 3. Dual Distribution

**Development (Monorepo)**
```json
{
    "repositories": [
        {
            "type": "path",
            "url": "./packages/aero-hrm"
        }
    ],
    "require": {
        "aero-modules/hrm": "@dev"
    }
}
```

**Production (Package Registry)**
```json
{
    "repositories": [
        {
            "type": "composer",
            "url": "https://packages.aero-enterprise.com"
        }
    ],
    "require": {
        "aero-modules/hrm": "^1.0"
    }
}
```

---

## 🚀 Quick Commands

### Analyze Module Dependencies
```bash
php tools/module-tools/module-analyzer.php hrm
```

### Create New Module
```bash
php artisan aero:make-module PayrollManagement --code=payroll
```

### Test Module Standalone
```bash
composer create-project laravel/laravel test-app
cd test-app
composer config repositories.aero-hrm path ../path/to/packages/aero-hrm
composer require aero-modules/hrm:@dev
php artisan vendor:publish --tag=aero-hrm-migrations
php artisan migrate
```

### Test Module in Platform
```bash
cd /path/to/platform
composer config repositories.local-hrm path ./packages/aero-hrm
composer require aero-modules/hrm:@dev
php artisan migrate
```

### Publish Module
```bash
git tag -a v1.0.0 -m "Release v1.0.0"
git push origin v1.0.0
# CI/CD handles publishing
```

---

## 📋 Module Checklist

### Required Files

- [x] `composer.json` - Package definition
- [x] `package.json` - Frontend dependencies
- [x] `README.md` - Installation & usage
- [x] `CHANGELOG.md` - Version history
- [x] `LICENSE` - License information

### Required Directories

- [x] `src/` - Backend code (PSR-4)
- [x] `resources/js/` - Frontend code
- [x] `database/migrations/` - Database schema
- [x] `routes/` - Module routes
- [x] `config/` - Configuration
- [x] `tests/` - Test suite

### Service Provider Must Have

- [x] `detectMode()` - Environment detection
- [x] `registerRoutes()` - Route registration with middleware
- [x] `loadMigrationsFrom()` - Migration loading
- [x] `registerPublishables()` - Asset publishing

---

## 🎨 Frontend Integration

### Standalone Mode
Module has its own entry point:
```javascript
// packages/aero-hrm/resources/js/app.jsx
import { createInertiaApp } from '@inertiajs/react';
import { App as AppLayout } from '@aero-modules/core/Components/Layout/App';

createInertiaApp({
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.jsx', { eager: true });
        return pages[`./Pages/${name}.jsx`];
    },
    // ...
});
```

### Platform Mode
Modules are integrated into main bundle via dynamic imports.

---

## 🔧 Development Workflow

### 1. Plan
- Review module in config/modules.php
- Run dependency analyzer
- Document extraction plan

### 2. Extract
- Create package structure
- Move models, controllers, migrations
- Update namespaces
- Create service provider
- Create routes

### 3. Test
- Unit tests for services
- Feature tests for controllers
- Test standalone mode
- Test platform mode

### 4. Document
- Complete README.md
- Document API endpoints
- Add usage examples

### 5. Publish
- Tag release
- Update CHANGELOG
- CI/CD publishes to registry

---

## 📚 Module Template

### composer.json
```json
{
    "name": "aero-modules/module-name",
    "description": "Module description",
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
            "AeroModules\\ModuleName\\": "src/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "AeroModules\\ModuleName\\ModuleNameServiceProvider"
            ]
        },
        "aero": {
            "code": "module-code",
            "name": "Module Display Name",
            "category": "module_category",
            "version": "1.0.0",
            "min_plan": "professional",
            "dependencies": ["core"]
        }
    }
}
```

### Service Provider Template
```php
<?php

namespace AeroModules\ModuleName;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class ModuleNameServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/module.php', 'module-code');
    }

    public function boot(): void
    {
        $mode = $this->detectMode();
        
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
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
        $middleware = ['web', 'auth'];
        
        if ($mode === 'tenant') {
            $middleware[] = \Stancl\Tenancy\Middleware\InitializeTenancyByDomain::class;
        }

        Route::middleware($middleware)
            ->prefix('module-prefix')
            ->name('module.')
            ->group(__DIR__.'/../routes/module.php');
    }

    protected function registerPublishables(): void
    {
        $this->publishes([
            __DIR__.'/../config/module.php' => config_path('module.php'),
        ], 'module-config');
        
        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'module-migrations');
    }
}
```

---

## ⚠️ Common Pitfalls

### 1. Namespace Issues
❌ `namespace App\Models;`
✅ `namespace AeroModules\ModuleName\Models;`

### 2. Route Middleware
❌ Hardcoded middleware
✅ Dynamic middleware based on mode

### 3. Frontend Imports
❌ `import from '@/Components/Card';`
✅ `import from '@aero-modules/core/Components/Card';`

### 4. Service Dependencies
❌ `use App\Services\SomeService;`
✅ `use AeroModules\Core\Tenant\SomeService;`

### 5. Database Context
❌ Manual `tenant_id` columns
✅ Use `stancl/tenancy` auto-context

---

## 📖 Documentation Links

- [Full Architecture Proposal](./modular-architecture-proposal.md)
- [Implementation Guide](./modular-architecture-implementation.md)
- [Module Analysis Tool](../tools/module-tools/module-analyzer.php)

---

## 🎓 Best Practices

### DO ✅
- Always use semantic versioning
- Write comprehensive tests
- Document all public APIs
- Keep dependencies minimal
- Test both modes (standalone & platform)
- Maintain CHANGELOG.md
- Use type hints everywhere
- Follow PSR-12 coding standards

### DON'T ❌
- Don't hardcode platform-specific logic
- Don't duplicate code across modules
- Don't break backwards compatibility
- Don't skip testing
- Don't forget to update documentation
- Don't mix business logic in controllers
- Don't ignore security best practices

---

## 🏆 Success Metrics

### Technical
- [x] Each module installable standalone
- [x] Zero code duplication between modules
- [x] Module builds complete in < 30s
- [x] Test coverage > 80%
- [x] No circular dependencies

### Business
- [ ] Time to develop new module
- [ ] Standalone module sales
- [ ] Customer satisfaction score
- [ ] Module maintenance effort
- [ ] Platform stability metrics

---

## 📞 Support

- **Issues**: [GitHub Issues](https://github.com/Linking-Dots/Aero-Enterprise-Suite-Saas/issues)
- **Email**: dev@aero-enterprise.com
- **Docs**: `/docs` directory

---

**Version:** 1.0.0  
**Last Updated:** December 7, 2025  
**Status:** Active Implementation
