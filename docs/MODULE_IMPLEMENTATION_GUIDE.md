# Module Implementation Guide

## Quick Start

This guide will walk you through implementing the modular architecture in your Aero Enterprise Suite.

> **📖 Related Guides:**
> - **[Standalone Module Repository Setup](STANDALONE_MODULE_REPOSITORY.md)** - How to move a module to a separate repository with its own dependencies
> - **[Quick Start](QUICK_START_MODULES.md)** - 5-minute getting started guide
> - **[Modular Architecture](MODULAR_ARCHITECTURE.md)** - Complete architecture overview

## Step 1: Register Module Service Provider

Add the `ModuleServiceProvider` to your `config/app.php`:

```php
'providers' => [
    // ... other providers
    App\Providers\ModuleServiceProvider::class,
],
```

Or use auto-discovery in `bootstrap/providers.php`:

```php
return [
    App\Providers\AppServiceProvider::class,
    App\Providers\ModuleServiceProvider::class,
];
```

## Step 2: Create Your First Module

```bash
# Create a new module
php artisan make:module HRM --standalone --type=business

# This creates a complete module structure at modules/HRM/
```

## Step 3: Customize Module Metadata

Edit `modules/HRM/module.json`:

```json
{
  "name": "HRM",
  "code": "hrm",
  "version": "1.0.0",
  "description": "Human Resource Management module",
  "type": "business",
  "standalone": true,
  "features": {
    "employees": "Employee Management",
    "attendance": "Attendance Tracking",
    "payroll": "Payroll Processing"
  },
  "dependencies": {
    "core": "^1.0"
  }
}
```

## Step 4: Implement Module Functionality

### Add a Controller

Create `modules/HRM/Http/Controllers/EmployeeController.php`:

```php
<?php

namespace Modules\HRM\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\HRM\Services\EmployeeService;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function __construct(
        private EmployeeService $employeeService
    ) {}

    public function index()
    {
        $employees = $this->employeeService->getAllEmployees();
        return view('hrm::employees.index', compact('employees'));
    }

    public function store(Request $request)
    {
        $employee = $this->employeeService->createEmployee($request->validated());
        return redirect()->route('hrm.employees.index')
            ->with('success', 'Employee created successfully');
    }
}
```

### Add a Service

Create `modules/HRM/Services/EmployeeService.php`:

```php
<?php

namespace Modules\HRM\Services;

use Modules\HRM\Models\Employee;
use Modules\HRM\Events\EmployeeCreated;

class EmployeeService
{
    public function getAllEmployees()
    {
        return Employee::with(['department', 'designation'])->get();
    }

    public function createEmployee(array $data): Employee
    {
        $employee = Employee::create($data);
        
        event(new EmployeeCreated($employee));
        
        return $employee;
    }

    public function updateEmployee(int $id, array $data): Employee
    {
        $employee = Employee::findOrFail($id);
        $employee->update($data);
        
        return $employee;
    }
}
```

### Add Routes

Edit `modules/HRM/Routes/tenant.php`:

```php
<?php

use Illuminate\Support\Facades\Route;
use Modules\HRM\Http\Controllers\EmployeeController;

Route::prefix('hrm')->middleware(['auth', 'tenant'])->group(function () {
    Route::resource('employees', EmployeeController::class);
});
```

### Add a Migration

Create `modules/HRM/Database/Migrations/2024_01_01_000000_create_employees_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_code')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->foreignId('department_id')->constrained();
            $table->foreignId('designation_id')->constrained();
            $table->date('joining_date');
            $table->enum('status', ['active', 'inactive', 'terminated'])->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
```

## Step 5: Discover and Enable Module

```bash
# Discover modules
php artisan module:discover

# List all modules
php artisan module:list

# Run module migrations
php artisan migrate --path=modules/HRM/Database/Migrations
```

## Step 6: Module Communication

### Using Events

Create `modules/HRM/Events/EmployeeCreated.php`:

```php
<?php

namespace Modules\HRM\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\HRM\Models\Employee;

class EmployeeCreated
{
    use Dispatchable;

    public function __construct(
        public readonly Employee $employee
    ) {}
}
```

Listen to events from other modules:

```php
// In another module's listener
namespace Modules\Payroll\Listeners;

use Modules\HRM\Events\EmployeeCreated;

class SetupEmployeePayroll
{
    public function handle(EmployeeCreated $event)
    {
        // Create payroll record for new employee
        PayrollRecord::create([
            'employee_id' => $event->employee->id,
            'salary_structure_id' => 1,
        ]);
    }
}
```

### Using Service Contracts

Define contract in `modules/HRM/Contracts/EmployeeServiceInterface.php`:

```php
<?php

namespace Modules\HRM\Contracts;

use Modules\HRM\Models\Employee;

interface EmployeeServiceInterface
{
    public function getAllEmployees();
    public function getEmployee(int $id): ?Employee;
    public function createEmployee(array $data): Employee;
    public function updateEmployee(int $id, array $data): Employee;
}
```

Bind in service provider:

```php
public function registerServices(): void
{
    $this->app->bind(
        \Modules\HRM\Contracts\EmployeeServiceInterface::class,
        \Modules\HRM\Services\EmployeeService::class
    );
}
```

Use in other modules:

```php
use Modules\HRM\Contracts\EmployeeServiceInterface;

class PayrollService
{
    public function __construct(
        private EmployeeServiceInterface $employeeService
    ) {}

    public function processPayroll()
    {
        $employees = $this->employeeService->getAllEmployees();
        // Process payroll...
    }
}
```

## Step 7: Standalone Installation

To use a module standalone (without the main SaaS platform):

### 1. Create New Laravel Project

```bash
composer create-project laravel/laravel my-hrm-standalone
cd my-hrm-standalone
```

### 2. Copy Module Files

```bash
# Copy the module directory
cp -r /path/to/aero/modules/HRM modules/HRM

# Copy module support files
cp -r /path/to/aero/app/Support/Module app/Support/Module
```

### 3. Configure Standalone Mode

Edit `modules/HRM/Config/config.php`:

```php
return [
    'standalone' => true,
    'tenant_mode' => false,
    
    'auth' => [
        'enable' => true,
        'provider' => 'users',
        'guard' => 'web',
    ],
];
```

### 4. Register Module Provider

Add to `bootstrap/providers.php`:

```php
return [
    // ... default providers
    Modules\HRM\Providers\HRMServiceProvider::class,
];
```

### 5. Run Migrations

```bash
php artisan migrate --path=modules/HRM/Database/Migrations
```

### 6. Seed Data (Optional)

```bash
php artisan db:seed --class=Modules\\HRM\\Database\\Seeders\\HRMSeeder
```

## Step 8: Tenant-Specific Module Loading

The system automatically loads modules based on tenant subscription:

```php
// In your tenant model or service
public function getEnabledModules(): array
{
    $planModules = $this->plan?->modules->pluck('code')->toArray() ?? [];
    $customModules = $this->modules ?? [];
    
    return array_unique(array_merge($planModules, $customModules));
}
```

## Step 9: Module Testing

Create `modules/HRM/Tests/Feature/EmployeeManagementTest.php`:

```php
<?php

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
            'department_id' => 1,
            'designation_id' => 1,
            'joining_date' => now()->format('Y-m-d'),
        ];

        $response = $this->postJson('/hrm/employees', $data);

        $response->assertStatus(201);
        $this->assertDatabaseHas('employees', [
            'email' => 'john@example.com'
        ]);
    }
}
```

Run tests:

```bash
php artisan test modules/HRM/Tests
```

## Step 10: Module Updates

### Versioning

Update `module.json`:

```json
{
  "version": "1.1.0",
  "changelog": [
    {
      "version": "1.1.0",
      "date": "2024-12-07",
      "changes": [
        "Added performance management features",
        "Fixed attendance calculation bug"
      ]
    }
  ]
}
```

### Migration for Updates

Create new migration:

```bash
# Create migration file
php artisan make:migration add_performance_fields_to_employees --path=modules/HRM/Database/Migrations
```

## Best Practices

### 1. Module Boundaries
- Keep module scope focused
- Avoid direct database access across modules
- Use events for cross-module communication

### 2. Configuration
- Make modules configurable via config files
- Support environment-based configuration
- Allow tenant-level customization

### 3. Database Design
- Prefix tables with module code (e.g., `hrm_employees`)
- Use polymorphic relationships for shared entities
- Support multi-database scenarios

### 4. API Design
- Version your module APIs
- Document all endpoints
- Use API resources for responses

### 5. Security
- Validate all inputs
- Implement proper authorization
- Sanitize outputs
- Respect tenant boundaries

### 6. Performance
- Cache module metadata
- Lazy load module services
- Optimize queries
- Use queues for heavy operations

## Troubleshooting

### Module Not Loading

Check if module is discovered:
```bash
php artisan module:discover
php artisan module:list
```

### Dependency Issues

Check module dependencies:
```bash
php artisan module:list --json | jq '.[] | select(.code=="hrm") | .dependencies'
```

### Autoloading Issues

Update Composer autoload:
```bash
composer dump-autoload
```

### Route Conflicts

Check registered routes:
```bash
php artisan route:list | grep hrm
```

## Additional Resources

- [Modular Architecture Guide](MODULAR_ARCHITECTURE.md)
- [Module API Reference](API_REFERENCE.md)
- [Example Modules](../modules/examples/)
- [Community Modules](https://marketplace.aeroerp.com)

## Support

For issues or questions:
- GitHub Issues: https://github.com/linking-dots/aero-enterprise-suite/issues
- Documentation: https://docs.aeroerp.com
- Community: https://community.aeroerp.com
