# Aero Enterprise Suite - Modules Directory

This directory contains independent, reusable modules for the Aero Enterprise Suite platform.

## 🎯 What are Modules?

Each module in this directory is a **self-contained, independent software package** that can:

1. **Run standalone** - Operate as an independent Laravel application
2. **Compose dynamically** - Integrate seamlessly into the multi-tenant SaaS platform
3. **Communicate loosely** - Interact with other modules through events and contracts

## 📁 Directory Structure

```
modules/
├── README.md           # This file
├── HRM/                # Human Resource Management module
├── CRM/                # Customer Relationship Management module
├── Finance/            # Financial Management module
├── Projects/           # Project Management module
└── ...                 # More modules as they're developed
```

Each module follows this internal structure:

```
{ModuleName}/
├── module.json                     # Module metadata & dependencies
├── composer.json                   # Composer package definition
├── README.md                       # Module documentation
├── Config/                         # Configuration files
├── Database/
│   ├── Migrations/                 # Database migrations
│   ├── Seeders/                    # Database seeders
│   └── Factories/                  # Model factories
├── Http/
│   ├── Controllers/                # Web controllers
│   ├── Controllers/Api/            # API controllers
│   ├── Requests/                   # Form request validation
│   ├── Resources/                  # API resources
│   └── Middleware/                 # Module-specific middleware
├── Models/                         # Eloquent models
├── Services/                       # Business logic services
├── Policies/                       # Authorization policies
├── Events/                         # Module events
├── Listeners/                      # Event listeners
├── Jobs/                           # Background jobs
├── Providers/
│   └── {Module}ServiceProvider.php # Module service provider
├── Resources/
│   ├── js/                         # JavaScript assets
│   ├── css/                        # CSS assets
│   └── views/                      # Blade views
├── Routes/
│   ├── web.php                     # Web routes
│   ├── api.php                     # API routes
│   └── tenant.php                  # Tenant-specific routes
├── Tests/
│   ├── Feature/                    # Feature tests
│   └── Unit/                       # Unit tests
└── Contracts/                      # Service interfaces
```

## 🚀 Quick Start

### Create Your First Module

```bash
# Generate a new module
php artisan make:module MyModule --standalone --type=business

# Discover all modules
php artisan module:discover

# List registered modules
php artisan module:list
```

### Test Your Module

```bash
# Run module tests
php artisan test modules/MyModule/Tests

# Run module migrations
php artisan migrate --path=modules/MyModule/Database/Migrations
```

## 📖 Documentation

- **Quick Start**: See `docs/QUICK_START_MODULES.md` for 5-minute tutorial
- **Implementation Guide**: See `docs/MODULE_IMPLEMENTATION_GUIDE.md` for detailed instructions
- **Standalone Repository**: See `docs/STANDALONE_MODULE_REPOSITORY.md` for moving modules to separate repos
- **Architecture**: See `docs/MODULAR_ARCHITECTURE.md` for architecture overview
- **Planning**: See `docs/MODULAR_ARCHITECTURE_PLAN.md` for roadmap

## 🔧 Module Management Commands

```bash
# Create new module
php artisan make:module {name} [--standalone] [--type=business]

# Discover modules
php artisan module:discover

# List all modules
php artisan module:list

# List enabled modules only
php artisan module:list --enabled

# List standalone-capable modules
php artisan module:list --standalone
```

## 🔗 Module Communication

Modules communicate through:

### 1. Events (Recommended)
```php
// Fire event from Module A
event(new EmployeeCreated($employee));

// Listen in Module B
class SetupPayroll {
    public function handle(EmployeeCreated $event) {
        // Create payroll record
    }
}
```

### 2. Service Contracts
```php
// Define interface
interface EmployeeServiceInterface {
    public function getEmployee(int $id);
}

// Use in another module
public function __construct(
    private EmployeeServiceInterface $employees
) {}
```

### 3. Shared Kernel
```php
// Use shared utilities
use App\Support\Shared\DateHelper;
use App\Support\Shared\MoneyFormatter;
```

## 📦 Module Metadata (module.json)

Each module must have a `module.json` file:

```json
{
  "name": "HRM",
  "code": "hrm",
  "version": "1.0.0",
  "description": "Human Resource Management",
  "type": "business",
  "standalone": true,
  "dependencies": {
    "core": "^1.0"
  },
  "features": {
    "employees": "Employee Management",
    "payroll": "Payroll Processing"
  },
  "plans": {
    "basic": ["employees"],
    "professional": ["employees", "payroll"]
  }
}
```

## 🎨 Module Types

- **business**: Core business functionality (HRM, CRM, Finance)
- **utility**: Helper modules (Reports, Notifications)
- **integration**: Third-party integrations (Stripe, AWS)

## 🔐 Module Access Control

Modules respect:

1. **Plan-based access**: Controlled by tenant subscription
2. **Permission-based access**: RBAC within module
3. **Tenant isolation**: Complete data separation

## 🧪 Testing Modules

```bash
# Run all module tests
php artisan test --testsuite=modules

# Run specific module tests
php artisan test modules/HRM/Tests

# Run specific test
php artisan test --filter=EmployeeManagementTest
```

## 📝 Module Development Workflow

1. **Create**: `php artisan make:module {name}`
2. **Develop**: Implement controllers, models, services
3. **Test**: Write and run tests
4. **Document**: Update module README
5. **Discover**: `php artisan module:discover`
6. **Deploy**: Merge to main branch

## 🌟 Best Practices

### DO ✅
- Keep modules focused on single domain
- Use events for inter-module communication
- Write comprehensive tests
- Document public APIs
- Follow Laravel conventions
- Use service layer for business logic

### DON'T ❌
- Access other module databases directly
- Create tight coupling between modules
- Duplicate code across modules
- Skip documentation
- Bypass module boundaries

## 🔧 Troubleshooting

### Module Not Found
```bash
php artisan module:discover
php artisan cache:clear
composer dump-autoload
```

### Routes Not Loading
```bash
php artisan route:clear
php artisan route:cache
php artisan route:list | grep {module}
```

### Autoload Issues
```bash
composer dump-autoload
php artisan optimize:clear
```

## 🤝 Contributing Modules

Want to contribute a new module?

1. Follow the module structure above
2. Include comprehensive tests (>80% coverage)
3. Document all public APIs
4. Provide usage examples
5. Submit PR with module description

## 📊 Module Status

| Module | Status | Version | Standalone | Tests |
|--------|--------|---------|------------|-------|
| *Awaiting first module* | - | - | - | - |

*Note: This section will be updated as modules are created*

## 🔗 Resources

- [Quick Start Guide](../docs/QUICK_START_MODULES.md)
- [Implementation Guide](../docs/MODULE_IMPLEMENTATION_GUIDE.md)
- [Architecture Documentation](../docs/MODULAR_ARCHITECTURE.md)
- [Project Roadmap](../docs/MODULAR_ARCHITECTURE_PLAN.md)

## 💡 Examples

### Minimal Module Example

See generated modules via:
```bash
php artisan make:module Example --standalone
```

### Real-World Modules

Coming soon:
- HRM Module (Phase 7)
- CRM Module (Phase 7)
- Finance Module (Phase 7)

## 📞 Support

- **Issues**: GitHub Issues
- **Documentation**: Check `docs/` directory
- **Community**: Development team

## 🎓 Learning Resources

1. Start with Quick Start guide (5 minutes)
2. Create test module
3. Review generated code
4. Read implementation guide
5. Explore architecture docs

---

**Ready to create your first module?**

```bash
php artisan make:module YourModule --standalone --type=business
```

Happy coding! 🚀
