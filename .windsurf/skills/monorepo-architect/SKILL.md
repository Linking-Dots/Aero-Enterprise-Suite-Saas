---
name: monorepo-architect
description: "Enforce AEOS365 monorepo package boundaries, service provider naming, host-app isolation, and cross-package contract patterns. Detect host-app leakage and circular dependencies."
---

# Monorepo Architect Skill

## Workspace Structure

```
c:\laragon\www\Aero-Enterprise-Suite-Saas\        ← Monorepo root
├── packages\aero-core\         ← Foundation (auth, users, base models, middleware)
├── packages\aero-platform\     ← SaaS platform (tenancy, billing, subscriptions)
├── packages\aero-hrm\          ← Human Resource Management
├── packages\aero-ui\           ← React design system (@aero/ui)
├── packages\aero-hrmac\        ← Access control
├── packages\aero-auth\         ← Authentication
├── packages\aero-crm\          ← Customer Relationship Management
├── packages\aero-finance\      ← Finance & Accounting
├── packages\aero-scm\          ← Supply Chain Management
├── packages\aero-ims\          ← Inventory Management
├── packages\aero-pos\          ← Point of Sale
├── packages\aero-quality\      ← Quality Management
├── packages\aero-dms\           ← Document Management
├── packages\aero-compliance\   ← Compliance Management
├── packages\aero-cms\          ← Content Management
├── packages\aero-project\      ← Project Management
├── packages\aero-rfi\          ← RFI / Construction
├── packages\aero-analytics\    ← Analytics
├── packages\aero-blockchain\   ← Blockchain
├── packages\aero-commerce\     ← Commerce
├── packages\aero-education\     ← Education
├── packages\aero-eam\          ← Enterprise Asset Management
├── packages\aero-field-service\← Field Service
├── packages\aero-healthcare\   ← Healthcare
├── packages\aero-integration\  ← Integrations
├── packages\aero-iot\          ← IoT
├── packages\aero-manufacturing\← Manufacturing
├── packages\aero-real-estate\ ← Real Estate
├── packages\aero-i18n\         ← Internationalization
├── packages\aero-notifications\ ← Notifications
├── packages\aero-installation\  ← Installation wizard
└── (more packages...)

c:\laragon\www\aeos365\         ← Host app (DUMB WRAPPER — external workspace)
├── .env                         ← Environment config
├── composer.json               ← Package deps with path repos
├── vite.config.js              ← Frontend build config
├── bootstrap\                   ← Laravel bootstrap
├── public\                      ← Compiled assets
└── storage\                    ← Logs, cache, sessions
```

## Host App Isolation (CRITICAL)

**The host app `aeos365` is a DUMB WRAPPER.** It must NEVER contain business logic.

### Allowed in host app:
- `.env` file
- `composer.json` (declares `aero/*` package dependencies with path repositories)
- `vite.config.js` (build configuration pointing to `vendor/aero/ui`)
- `bootstrap/` (Laravel bootstrap)
- `public/` (compiled assets)
- `storage/` (logs, cache, sessions)
- `artisan`

### FORBIDDEN in host app:
- `app/Http/Controllers/` — move to `packages/aero-{module}/src/Http/Controllers/`
- `app/Models/` — move to `packages/aero-core/src/Models/` or appropriate package
- `resources/js/Pages/` — ALL frontend pages live in `packages/aero-ui/resources/js/Pages/`
- `resources/views/` — blade templates go in `packages/aero-{module}/resources/views/`
- `routes/web.php` — routes go in `packages/aero-{module}/routes/`
- `database/migrations/` — migrations go in `packages/aero-{module}/database/migrations/`

### Host app composer.json pattern:
```json
{
    "repositories": [
        {
            "type": "path",
            "url": "C:\\laragon\\www\\Aero-Enterprise-Suite-Saas\\packages/*",
            "options": { "symlink": true }
        }
    ],
    "require": {
        "aero/core": "@dev",
        "aero/hrm": "@dev",
        "aero/ui": "@dev",
        "...": "..."
    }
}
```

## Service Provider Naming

### Correct:
- `AeroCoreServiceProvider`
- `AeroHrmServiceProvider`
- `AeroPlatformServiceProvider`

### Incorrect (legacy — fix when editing):
- `CmsServiceProvider` → `AeroCmsServiceProvider`
- `BlockchainServiceProvider` → `AeroBlockchainServiceProvider`
- `EducationServiceProvider` → `AeroEducationServiceProvider`

## Package composer.json Requirements

Every package MUST include:

```json
{
    "name": "aero/{module}",
    "autoload": {
        "psr-4": {
            "Aero\\{Module}\\": "src/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "Aero\\{Module}\\Aero{Module}ServiceProvider"
            ]
        },
        "aero": {
            "package": "{code}",
            "version": "1.0.0",
            "category": "human_resources|finance|crm|...",
            "description": "What this package does"
        }
    }
}
```

## Cross-Package Rules

### Core Package MUST be Independent
`packages/aero-core` MUST NOT import concrete classes from other packages.

### Wrong:
```php
// In AeroCoreServiceProvider.php
use Aero\HRM\Services\EmployeeService;  // ❌ Core depends on HRM
```

### Right:
```php
// Define a contract in aero-core
namespace Aero\Core\Contracts;
interface EmployeeServiceContract {
    public function resolveForUser($user);
}

// Implement in aero-hrm
namespace Aero\HRM\Services;
class EmployeeService implements EmployeeServiceContract { ... }

// Bind in HRM service provider
$this->app->singleton(EmployeeServiceContract::class, EmployeeService::class);
```

## Route File Conventions

Each package has these route files:
- `routes/web.php` — Tenant-scoped web routes (with `hrmac:` middleware)
- `routes/admin.php` — Platform admin routes (on central domain)
- `routes/api.php` — API routes
- `routes/tenant.php` — Additional tenant-only routes (optional)

Route prefix and name are auto-applied by `AbstractModuleProvider::loadRoutes()`:
```php
// In web.php, you write:
Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');

// Result after AbstractModuleProvider applies prefix:
// URL: /hrm/employees
// Name: hrm.employees.index
```

## New Package Scaffold

When creating a new `aero-{name}` package, the structure must be:

```
packages/aero-{name}/
├── composer.json
├── config/
│   ├── {name}.php          ← Settings config
│   └── module.php          ← HRMAC hierarchy definition
├── database/
│   ├── migrations/
│   ├── factories/
│   └── seeders/
├── resources/
│   ├── js/
│   │   └── Pages/          ← Inertia pages (if package has frontend)
│   └── views/              ← Blade templates
├── routes/
│   ├── web.php
│   ├── api.php
│   └── admin.php
├── src/
│   ├── Aero{Name}ServiceProvider.php
│   ├── Http/
│   │   ├── Controllers/
│   │   ├── Middleware/
│   │   └── Requests/
│   ├── Models/
│   ├── Services/
│   └── Policies/
└── tests/
    ├── Feature/
    └── Unit/
```

## Gap Detection

| Violation | Severity | Fix |
|-----------|----------|-----|
| File created in `aeos365/app/` or `aeos365/resources/` | **CRITICAL** | Move to appropriate `packages/aero-*/` |
| Service provider not named `Aero*ServiceProvider` | **MEDIUM** | Rename to standard pattern |
| Missing `extra.aero` in `composer.json` | **MEDIUM** | Add metadata block |
| Core package imports HRM/Finance/etc classes | **HIGH** | Extract to contract/interface |
| Missing `config/module.php` | **HIGH** | Create HRMAC definition |
| AppServiceProvider has business logic | **CRITICAL** | Move to package service provider |

## Reference Files

- Host app composer: `c:/laragon/www/aeos365/composer.json`
- Host app Vite: `c:/laragon/www/aeos365/vite.config.js`
- Core provider: `packages/aero-core/src/AeroCoreServiceProvider.php`
- Abstract module provider: `packages/aero-core/src/Providers/AbstractModuleProvider.php`
- HRM provider (correct naming): `packages/aero-hrm/src/AeroHrmServiceProvider.php`
