# Modular Architecture - Quick Start Guide

## 🚀 Get Started in 5 Minutes

This quick start guide will help you create your first module and understand the modular architecture.

> **📖 Related Guides:**
> - **[Standalone Module Repository Setup](STANDALONE_MODULE_REPOSITORY.md)** - Move module to separate repository with own dependencies
> - **[Module Implementation Guide](MODULE_IMPLEMENTATION_GUIDE.md)** - Detailed implementation guide
> - **[Modular Architecture](MODULAR_ARCHITECTURE.md)** - Complete architecture overview

## Prerequisites

- Aero Enterprise Suite installed
- PHP 8.2+
- Composer
- Basic Laravel knowledge

## Step 1: Enable Module System (30 seconds)

Add `ModuleServiceProvider` to your application providers:

**Option A: Auto-discovery (Laravel 11+)**

Add to `bootstrap/providers.php`:
```php
return [
    App\Providers\AppServiceProvider::class,
    App\Providers\ModuleServiceProvider::class, // Add this line
];
```

**Option B: Manual registration**

Add to `config/app.php`:
```php
'providers' => [
    // ... other providers
    App\Providers\ModuleServiceProvider::class,
],
```

## Step 2: Create Your First Module (1 minute)

```bash
# Create a simple utility module
php artisan make:module TaskManager --standalone --type=business

# Output:
# Creating module: TaskManager
# Module 'TaskManager' created successfully!
# Location: /path/to/project/modules/TaskManager
```

This creates a complete module structure with:
- ✅ Service provider
- ✅ Module manifest (module.json)
- ✅ Routes (web, api, tenant)
- ✅ Controllers
- ✅ Models
- ✅ Migrations
- ✅ Tests
- ✅ Configuration

## Step 3: Discover Your Module (10 seconds)

```bash
php artisan module:discover

# Output:
# Discovering modules...
# Found 1 module(s):
# ┌───────────────┬──────────────┬─────────┬──────────┬────────────┐
# │ Code          │ Name         │ Version │ Type     │ Standalone │
# ├───────────────┼──────────────┼─────────┼──────────┼────────────┤
# │ task-manager  │ TaskManager  │ 1.0.0   │ business │ Yes        │
# └───────────────┴──────────────┴─────────┴──────────┴────────────┘
```

## Step 4: Customize Your Module (2 minutes)

### Update Module Manifest

Edit `modules/TaskManager/module.json`:

```json
{
  "name": "TaskManager",
  "code": "task-manager",
  "version": "1.0.0",
  "description": "Simple task management module",
  "type": "business",
  "standalone": true,
  "features": {
    "tasks": "Task Management",
    "projects": "Project Tracking",
    "reports": "Task Reports"
  },
  "plans": {
    "basic": ["tasks"],
    "professional": ["tasks", "projects"],
    "enterprise": ["tasks", "projects", "reports"]
  }
}
```

### Add a Simple Controller

Edit `modules/TaskManager/Http/Controllers/TaskController.php`:

```php
<?php

namespace Modules\TaskManager\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index()
    {
        return response()->json([
            'message' => 'Task Manager API',
            'version' => '1.0.0',
            'tasks' => []
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date'
        ]);

        // Create task logic here
        
        return response()->json([
            'message' => 'Task created successfully',
            'task' => $validated
        ], 201);
    }
}
```

### Add Routes

Edit `modules/TaskManager/Routes/api.php`:

```php
<?php

use Illuminate\Support\Facades\Route;
use Modules\TaskManager\Http\Controllers\TaskController;

Route::prefix('task-manager')->group(function () {
    Route::get('tasks', [TaskController::class, 'index']);
    Route::post('tasks', [TaskController::class, 'store']);
});
```

## Step 5: Test Your Module (1 minute)

```bash
# Discover again to ensure module is registered
php artisan module:discover

# Start development server
php artisan serve

# Test the API endpoint
curl http://localhost:8000/api/task-manager/tasks

# Response:
# {
#   "message": "Task Manager API",
#   "version": "1.0.0",
#   "tasks": []
# }
```

## Step 6: Add Database Support (Optional)

### Create a Migration

Edit `modules/TaskManager/Database/Migrations/[timestamp]_create_tasks_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending');
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->date('due_date')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
```

### Run Migration

```bash
php artisan migrate --path=modules/TaskManager/Database/Migrations
```

### Create Model

Create `modules/TaskManager/Models/Task.php`:

```php
<?php

namespace Modules\TaskManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'status',
        'priority',
        'due_date',
        'assigned_to'
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    public function assignee()
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_to');
    }
}
```

## Common Commands

```bash
# Module Management
php artisan make:module {name}        # Create new module
php artisan module:discover           # Find all modules
php artisan module:list                # List all modules
php artisan module:list --enabled      # List enabled modules
php artisan module:list --standalone   # List standalone modules

# Using Module Helpers in Code
$module = module('task-manager');      // Get specific module
$all = modules()->all();               // Get all modules
$enabled = modules()->enabled();       // Get enabled modules
```

## Module Communication Example

### Publishing Events

Create `modules/TaskManager/Events/TaskCreated.php`:

```php
<?php

namespace Modules\TaskManager\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\TaskManager\Models\Task;

class TaskCreated
{
    use Dispatchable;

    public function __construct(
        public readonly Task $task
    ) {}
}
```

Fire the event in your controller:

```php
use Modules\TaskManager\Events\TaskCreated;

$task = Task::create($validated);
event(new TaskCreated($task));
```

### Listening to Events in Another Module

In another module's `EventServiceProvider`:

```php
protected $listen = [
    \Modules\TaskManager\Events\TaskCreated::class => [
        \Modules\Notifications\Listeners\SendTaskNotification::class,
    ],
];
```

## Testing Your Module

Create `modules/TaskManager/Tests/Feature/TaskApiTest.php`:

```php
<?php

namespace Modules\TaskManager\Tests\Feature;

use Tests\TestCase;

class TaskApiTest extends TestCase
{
    public function test_can_list_tasks()
    {
        $response = $this->getJson('/api/task-manager/tasks');
        
        $response->assertStatus(200)
                 ->assertJsonStructure(['message', 'version', 'tasks']);
    }

    public function test_can_create_task()
    {
        $data = [
            'title' => 'Test Task',
            'description' => 'Test description'
        ];

        $response = $this->postJson('/api/task-manager/tasks', $data);
        
        $response->assertStatus(201)
                 ->assertJsonStructure(['message', 'task']);
    }
}
```

Run tests:

```bash
php artisan test modules/TaskManager/Tests
```

## Next Steps

Now that you've created your first module:

1. **Read the full guide**: `docs/MODULE_IMPLEMENTATION_GUIDE.md`
2. **Understand architecture**: `docs/MODULAR_ARCHITECTURE.md`
3. **Review the plan**: `docs/MODULAR_ARCHITECTURE_PLAN.md`
4. **Explore examples**: Check existing modules in `modules/` directory

## Standalone Installation

To use your module standalone (outside the main platform):

```bash
# Create new Laravel project
composer create-project laravel/laravel my-task-app

# Copy your module
cp -r modules/TaskManager my-task-app/modules/TaskManager

# Copy module support
cp -r app/Support/Module my-task-app/app/Support/Module

# Update module config for standalone mode
# Edit: modules/TaskManager/Config/config.php
# Set: 'standalone' => true

# Register module provider in bootstrap/providers.php
# Add: Modules\TaskManager\Providers\TaskManagerServiceProvider::class

# Run migrations
php artisan migrate --path=modules/TaskManager/Database/Migrations
```

## Troubleshooting

### Module Not Found
```bash
# Ensure module directory exists
ls -la modules/

# Re-discover modules
php artisan module:discover
```

### Autoload Issues
```bash
# Dump autoload
composer dump-autoload

# Clear config cache
php artisan config:clear
php artisan cache:clear
```

### Route Not Working
```bash
# Check routes are registered
php artisan route:list | grep task-manager

# Clear route cache
php artisan route:clear
```

## Support

- **Documentation**: Check `docs/` directory
- **Examples**: Review generated module code
- **Issues**: GitHub Issues
- **Community**: Discord/Slack channel

## What's Next?

- Create more complex modules with relationships
- Add authentication to your module endpoints
- Implement module-to-module communication
- Set up module testing
- Deploy your first standalone module

Happy coding! 🚀
