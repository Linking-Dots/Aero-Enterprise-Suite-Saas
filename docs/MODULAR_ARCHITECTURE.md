# Modular Architecture Guide

## Overview

The Aero Enterprise Suite follows a **modular architecture** where each module is designed as an independent, reusable software component that can be:

1. **Installed standalone** - Each module can function as its own application
2. **Composed dynamically** - The main SaaS platform combines modules based on tenant subscriptions

This architecture enables:
- **Scalability**: Add new modules without affecting existing ones
- **Maintainability**: Isolated codebases with clear boundaries
- **Reusability**: Modules can be used in multiple projects
- **Flexibility**: Tenants can enable/disable modules as needed
- **Marketplace**: Third-party developers can create modules

## Architecture Principles

### 1. Module Independence
Each module should be self-contained with:
- Own database migrations
- Own routes and controllers
- Own views and assets
- Own configuration
- Own tests

### 2. Loose Coupling
Modules communicate through:
- Well-defined APIs (contracts/interfaces)
- Event-driven architecture
- Shared kernel for core functionality
- Message bus for inter-module communication

### 3. High Cohesion
Related functionality stays within the same module:
- Business logic encapsulated in services
- Domain models within module scope
- Module-specific validations and policies

## Module Structure

### Standard Module Package Layout

```
modules/
├── HRM/                          # Module root directory
│   ├── composer.json             # Module dependencies
│   ├── module.json               # Module metadata
│   ├── Config/                   # Module configuration
│   │   ├── config.php
│   │   ├── permissions.php
│   │   └── routes.php
│   ├── Database/                 # Module database
│   │   ├── Migrations/
│   │   ├── Seeders/
│   │   └── Factories/
│   ├── Http/                     # HTTP layer
│   │   ├── Controllers/
│   │   ├── Requests/
│   │   ├── Resources/
│   │   └── Middleware/
│   ├── Models/                   # Domain models
│   ├── Services/                 # Business logic
│   ├── Policies/                 # Authorization policies
│   ├── Events/                   # Module events
│   ├── Listeners/                # Event listeners
│   ├── Jobs/                     # Background jobs
│   ├── Providers/                # Service providers
│   │   ├── HRMServiceProvider.php
│   │   ├── RouteServiceProvider.php
│   │   └── EventServiceProvider.php
│   ├── Resources/                # Frontend resources
│   │   ├── js/
│   │   ├── css/
│   │   └── views/
│   ├── Routes/                   # Module routes
│   │   ├── web.php
│   │   ├── api.php
│   │   └── tenant.php
│   ├── Tests/                    # Module tests
│   │   ├── Feature/
│   │   └── Unit/
│   ├── Contracts/                # Module interfaces
│   └── README.md                 # Module documentation
```

### Module Metadata (module.json)

```json
{
  "name": "HRM",
  "code": "hrm",
  "version": "1.0.0",
  "description": "Human Resource Management module",
  "type": "business",
  "standalone": true,
  "authors": [
    {
      "name": "Aero Team",
      "email": "dev@aeroerp.com"
    }
  ],
  "dependencies": {
    "core": "^1.0",
    "employee-core": "^1.0"
  },
  "provides": {
    "services": [
      "EmployeeService",
      "PayrollService",
      "AttendanceService"
    ],
    "apis": [
      "/api/hrm/employees",
      "/api/hrm/payroll"
    ]
  },
  "requires": {
    "php": "^8.2",
    "laravel": "^11.0"
  },
  "features": {
    "employees": "Employee Management",
    "attendance": "Attendance Tracking",
    "payroll": "Payroll Management",
    "leave": "Leave Management",
    "recruitment": "Recruitment",
    "performance": "Performance Management"
  },
  "plans": {
    "basic": ["employees", "attendance"],
    "professional": ["employees", "attendance", "payroll", "leave"],
    "enterprise": ["employees", "attendance", "payroll", "leave", "recruitment", "performance"]
  },
  "database": {
    "migrations_path": "Database/Migrations",
    "seeders_path": "Database/Seeders"
  },
  "routes": {
    "web": "Routes/web.php",
    "api": "Routes/api.php",
    "tenant": "Routes/tenant.php"
  },
  "assets": {
    "js": "Resources/js/app.js",
    "css": "Resources/css/app.css"
  },
  "config": {
    "publish": [
      "Config/config.php"
    ]
  }
}
```

## Module Communication

### 1. Service Contracts (Interfaces)

```php
// modules/HRM/Contracts/EmployeeServiceInterface.php
namespace Modules\HRM\Contracts;

interface EmployeeServiceInterface
{
    public function createEmployee(array $data): Employee;
    public function getEmployee(int $id): ?Employee;
    public function updateEmployee(int $id, array $data): Employee;
    public function deleteEmployee(int $id): bool;
}
```

### 2. Module Events

```php
// modules/HRM/Events/EmployeeCreated.php
namespace Modules\HRM\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmployeeCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $employeeId,
        public readonly array $data
    ) {}
}
```

### 3. Module API

```php
// modules/HRM/Http/Controllers/Api/EmployeeController.php
namespace Modules\HRM\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\HRM\Services\EmployeeService;

class EmployeeController extends Controller
{
    public function __construct(
        private EmployeeService $employeeService
    ) {}

    public function index()
    {
        return $this->employeeService->getAllEmployees();
    }
}
```

## Module Lifecycle

### Installation

```bash
# Install module via Composer
composer require aero/hrm-module

# Register module
php artisan module:install hrm

# Run module migrations
php artisan module:migrate hrm

# Seed module data
php artisan module:seed hrm
```

### Enable/Disable

```bash
# Enable module for tenant
php artisan module:enable hrm --tenant=tenant1

# Disable module for tenant
php artisan module:disable hrm --tenant=tenant1
```

### Update

```bash
# Update module
composer update aero/hrm-module

# Run migrations
php artisan module:migrate hrm
```

### Uninstallation

```bash
# Uninstall module
php artisan module:uninstall hrm --force

# Remove module data
php artisan module:uninstall hrm --with-data
```

## Standalone Module Usage

### Standalone Installation

```bash
# Create new Laravel project
composer create-project laravel/laravel my-hrm-app

# Install module as standalone
cd my-hrm-app
composer require aero/hrm-module

# Configure standalone mode
php artisan module:standalone hrm

# Run setup
php artisan module:setup hrm
```

### Standalone Configuration

```php
// config/hrm.php
return [
    'standalone' => true,
    'tenant_mode' => false,
    'auth' => [
        'enable' => true,
        'provider' => 'users',
        'guard' => 'web',
    ],
    'database' => [
        'connection' => 'mysql',
        'prefix' => 'hrm_',
    ],
];
```

## SaaS Platform Integration

### Dynamic Module Loading

```php
// app/Services/ModuleLoader.php
class ModuleLoader
{
    public function loadModulesForTenant(Tenant $tenant): void
    {
        $enabledModules = $tenant->getEnabledModules();
        
        foreach ($enabledModules as $moduleCode) {
            $this->loadModule($moduleCode);
        }
    }
    
    protected function loadModule(string $code): void
    {
        $module = $this->moduleRegistry->get($code);
        
        if (!$module) {
            throw new ModuleNotFoundException($code);
        }
        
        // Load module service provider
        $this->app->register($module->getServiceProvider());
        
        // Load module routes
        $this->loadRoutes($module);
        
        // Load module assets
        $this->loadAssets($module);
    }
}
```

### Module Provisioning

```php
// app/Services/ModuleProvisioner.php
class ModuleProvisioner
{
    public function provisionModule(Tenant $tenant, string $moduleCode): void
    {
        // Run module migrations for tenant
        $this->runModuleMigrations($tenant, $moduleCode);
        
        // Seed module data
        $this->seedModuleData($tenant, $moduleCode);
        
        // Configure module for tenant
        $this->configureModule($tenant, $moduleCode);
        
        // Enable module
        $tenant->enableModule($moduleCode);
        
        event(new ModuleProvisioned($tenant, $moduleCode));
    }
}
```

## Module Development

### Creating a New Module

```bash
# Generate module scaffold
php artisan make:module CRM

# This creates:
# - modules/CRM/ directory structure
# - Base service provider
# - module.json metadata
# - README.md
```

### Module Generator Template

```php
// app/Console/Commands/MakeModuleCommand.php
class MakeModuleCommand extends Command
{
    protected $signature = 'make:module {name}';
    
    public function handle(): void
    {
        $name = $this->argument('name');
        
        // Create module directory
        $this->createModuleStructure($name);
        
        // Generate module files
        $this->generateServiceProvider($name);
        $this->generateMetadata($name);
        $this->generateConfig($name);
        $this->generateRoutes($name);
        $this->generateReadme($name);
        
        $this->info("Module {$name} created successfully!");
    }
}
```

## Module Testing

### Module-Specific Tests

```php
// modules/HRM/Tests/Feature/EmployeeManagementTest.php
namespace Modules\HRM\Tests\Feature;

use Tests\TestCase;
use Modules\HRM\Models\Employee;

class EmployeeManagementTest extends TestCase
{
    public function test_can_create_employee()
    {
        $data = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
        ];
        
        $response = $this->postJson('/api/hrm/employees', $data);
        
        $response->assertStatus(201);
        $this->assertDatabaseHas('employees', ['email' => 'john@example.com']);
    }
}
```

### Running Module Tests

```bash
# Run all module tests
php artisan test --testsuite=modules

# Run specific module tests
php artisan test modules/HRM/Tests
```

## Module Dependencies

### Declaring Dependencies

```json
{
  "dependencies": {
    "core": "^1.0",
    "employee-core": "^1.0",
    "payroll-engine": "^2.0"
  },
  "dev-dependencies": {
    "module-dev-tools": "^1.0"
  }
}
```

### Dependency Resolution

```php
// app/Services/ModuleDependencyResolver.php
class ModuleDependencyResolver
{
    public function resolve(Module $module): array
    {
        $dependencies = [];
        
        foreach ($module->getDependencies() as $depCode => $version) {
            $dep = $this->moduleRegistry->get($depCode);
            
            if (!$dep) {
                throw new DependencyNotFoundException($depCode);
            }
            
            if (!$this->versionMatches($dep->getVersion(), $version)) {
                throw new VersionMismatchException($depCode, $version);
            }
            
            // Recursive resolution
            $dependencies = array_merge(
                $dependencies,
                $this->resolve($dep),
                [$dep]
            );
        }
        
        return $dependencies;
    }
}
```

## Migration Strategy

### Phase 1: Infrastructure
1. Create module base structure
2. Implement module loader and registry
3. Build module CLI commands
4. Set up module testing framework

### Phase 2: Core Modules Migration
1. Migrate Core/Settings module
2. Migrate HRM module
3. Validate multi-tenant operation
4. Test standalone installation

### Phase 3: Additional Modules
1. Migrate CRM module
2. Migrate Project Management
3. Migrate Finance module
4. Migrate other modules incrementally

### Phase 4: Enhancement
1. Build module marketplace
2. Implement hot-reload for development
3. Create advanced analytics
4. Optimize performance

## Best Practices

### Module Design
- Keep modules focused on a single business domain
- Avoid tight coupling between modules
- Use events for cross-module communication
- Implement clear API boundaries
- Document module interfaces

### Code Organization
- Follow Laravel conventions
- Use service layer for business logic
- Implement repository pattern for data access
- Use form requests for validation
- Apply policies for authorization

### Testing
- Write comprehensive unit tests
- Create integration tests for module APIs
- Test module lifecycle (install, enable, disable, uninstall)
- Test tenant isolation
- Test module dependencies

### Documentation
- Document module features and capabilities
- Provide installation instructions
- Document API endpoints
- Create developer guides
- Maintain changelog

## Security Considerations

### Module Isolation
- Prevent modules from accessing other modules' data directly
- Validate all inter-module communication
- Implement module-level permissions
- Audit module actions

### Tenant Isolation
- Ensure modules respect tenant boundaries
- Use tenant-aware queries
- Validate tenant access in module APIs
- Implement tenant-level module configuration

## Performance Optimization

### Module Loading
- Lazy load modules on demand
- Cache module metadata
- Use service container efficiently
- Minimize module bootstrap time

### Database
- Use separate connections per module if needed
- Index module tables appropriately
- Implement query optimization
- Use database transactions

## Future Enhancements

1. **Module Marketplace**: Public registry for third-party modules
2. **Visual Module Builder**: GUI for creating modules
3. **Module Analytics**: Track module usage and performance
4. **Module Versioning**: Advanced version management
5. **Module Hot-swap**: Update modules without downtime
6. **Module Sandboxing**: Isolated testing environments
7. **Module Licensing**: Commercial module support
8. **Module Templates**: Pre-built module scaffolds

## Resources

- [Laravel Package Development](https://laravel.com/docs/packages)
- [Domain Driven Design](https://martinfowler.com/tags/domain%20driven%20design.html)
- [Microservices Patterns](https://microservices.io/patterns/)
- [Module Federation](https://webpack.js.org/concepts/module-federation/)

## Support

For questions or issues:
- GitHub Issues: https://github.com/aero-erp/issues
- Documentation: https://docs.aeroerp.com
- Community Forum: https://community.aeroerp.com
