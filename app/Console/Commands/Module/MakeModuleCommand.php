<?php

namespace App\Console\Commands\Module;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeModuleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:module {name : The name of the module}
                            {--standalone : Mark module as standalone capable}
                            {--type=business : Module type (business, utility, integration)}
                            {--force : Overwrite existing module}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new module with standard structure';

    /**
     * Module name
     */
    protected string $moduleName;

    /**
     * Module code (kebab-case)
     */
    protected string $moduleCode;

    /**
     * Module path
     */
    protected string $modulePath;

    /**
     * Module namespace
     */
    protected string $moduleNamespace;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->moduleName = $this->argument('name');
        $this->moduleCode = Str::kebab($this->moduleName);
        $this->modulePath = base_path("modules/{$this->moduleName}");
        $this->moduleNamespace = "Modules\\" . str_replace(' ', '', $this->moduleName);

        // Check if module already exists
        if (File::exists($this->modulePath) && !$this->option('force')) {
            $this->error("Module '{$this->moduleName}' already exists!");
            return 1;
        }

        $this->info("Creating module: {$this->moduleName}");

        // Create module directory structure
        $this->createDirectoryStructure();

        // Generate module files
        $this->generateManifest();
        $this->generateServiceProvider();
        $this->generateConfig();
        $this->generateRoutes();
        $this->generateReadme();
        $this->generateComposer();
        $this->generateGitignore();

        // Generate example files
        $this->generateExampleController();
        $this->generateExampleModel();
        $this->generateExampleMigration();

        $this->info("Module '{$this->moduleName}' created successfully!");
        $this->info("Location: {$this->modulePath}");
        $this->newLine();
        $this->info("Next steps:");
        $this->line("1. Review and customize module.json");
        $this->line("2. Implement your module functionality");
        $this->line("3. Run: php artisan module:discover");
        $this->line("4. Run: php artisan module:install {$this->moduleCode}");

        return 0;
    }

    /**
     * Create module directory structure
     */
    protected function createDirectoryStructure(): void
    {
        $directories = [
            'Config',
            'Database/Migrations',
            'Database/Seeders',
            'Database/Factories',
            'Http/Controllers',
            'Http/Controllers/Api',
            'Http/Requests',
            'Http/Resources',
            'Http/Middleware',
            'Models',
            'Services',
            'Policies',
            'Events',
            'Listeners',
            'Jobs',
            'Providers',
            'Resources/js',
            'Resources/css',
            'Resources/views',
            'Routes',
            'Tests/Feature',
            'Tests/Unit',
            'Contracts',
        ];

        foreach ($directories as $directory) {
            File::ensureDirectoryExists("{$this->modulePath}/{$directory}");
        }
    }

    /**
     * Generate module.json manifest
     */
    protected function generateManifest(): void
    {
        $manifest = [
            'name' => $this->moduleName,
            'code' => $this->moduleCode,
            'version' => '1.0.0',
            'description' => "The {$this->moduleName} module",
            'type' => $this->option('type'),
            'standalone' => $this->option('standalone'),
            'authors' => [
                [
                    'name' => 'Your Name',
                    'email' => 'your.email@example.com',
                ],
            ],
            'dependencies' => [],
            'provides' => [
                'services' => [],
                'apis' => [],
            ],
            'requires' => [
                'php' => '^8.2',
                'laravel' => '^11.0',
            ],
            'features' => [],
            'plans' => [
                'basic' => [],
                'professional' => [],
                'enterprise' => [],
            ],
            'database' => [
                'migrations_path' => 'Database/Migrations',
                'seeders_path' => 'Database/Seeders',
            ],
            'routes' => [
                'web' => 'Routes/web.php',
                'api' => 'Routes/api.php',
                'tenant' => 'Routes/tenant.php',
            ],
            'assets' => [
                'js' => 'Resources/js/app.js',
                'css' => 'Resources/css/app.css',
            ],
            'config' => [
                'publish' => [
                    'Config/config.php',
                ],
            ],
        ];

        $json = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        File::put("{$this->modulePath}/module.json", $json);
    }

    /**
     * Generate service provider
     */
    protected function generateServiceProvider(): void
    {
        $name = str_replace(' ', '', $this->moduleName);
        $stub = <<<PHP
<?php

namespace {$this->moduleNamespace}\\Providers;

use App\\Support\\Module\\BaseModuleServiceProvider;

class {$name}ServiceProvider extends BaseModuleServiceProvider
{
    /**
     * Module code (unique identifier)
     */
    protected string \$moduleCode = '{$this->moduleCode}';

    /**
     * Module path
     */
    protected string \$modulePath = __DIR__ . '/..';

    /**
     * Module namespace
     */
    protected string \$moduleNamespace = '{$this->moduleNamespace}';

    /**
     * Register module services
     */
    protected function registerServices(): void
    {
        // Register module-specific services here
        // Example: \$this->app->singleton(ServiceInterface::class, ServiceImplementation::class);
    }

    /**
     * Register module commands
     */
    protected function registerCommands(): void
    {
        // Register module-specific artisan commands here
        // Example: \$this->commands([ExampleCommand::class]);
    }
}
PHP;

        File::put("{$this->modulePath}/Providers/{$name}ServiceProvider.php", $stub);
    }

    /**
     * Generate config file
     */
    protected function generateConfig(): void
    {
        $stub = <<<PHP
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | {$this->moduleName} Module Configuration
    |--------------------------------------------------------------------------
    */

    'enabled' => env('{$this->moduleCode}_ENABLED', true),

    'standalone' => {$this->option('standalone') ? 'true' : 'false'},

    // Add your module-specific configuration here
];
PHP;

        File::put("{$this->modulePath}/Config/config.php", $stub);
    }

    /**
     * Generate route files
     */
    protected function generateRoutes(): void
    {
        // Web routes
        $webStub = <<<PHP
<?php

use Illuminate\\Support\\Facades\\Route;

/*
|--------------------------------------------------------------------------
| {$this->moduleName} Web Routes
|--------------------------------------------------------------------------
*/

Route::prefix('{$this->moduleCode}')->group(function () {
    // Add your web routes here
});
PHP;
        File::put("{$this->modulePath}/Routes/web.php", $webStub);

        // API routes
        $apiStub = <<<PHP
<?php

use Illuminate\\Support\\Facades\\Route;

/*
|--------------------------------------------------------------------------
| {$this->moduleName} API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('{$this->moduleCode}')->group(function () {
    // Add your API routes here
});
PHP;
        File::put("{$this->modulePath}/Routes/api.php", $apiStub);

        // Tenant routes
        $tenantStub = <<<PHP
<?php

use Illuminate\\Support\\Facades\\Route;

/*
|--------------------------------------------------------------------------
| {$this->moduleName} Tenant Routes
|--------------------------------------------------------------------------
*/

Route::prefix('{$this->moduleCode}')->group(function () {
    // Add your tenant-specific routes here
});
PHP;
        File::put("{$this->modulePath}/Routes/tenant.php", $tenantStub);
    }

    /**
     * Generate README file
     */
    protected function generateReadme(): void
    {
        $stub = <<<MD
# {$this->moduleName} Module

## Description

{$this->moduleName} module for Aero Enterprise Suite.

## Installation

```bash
# Install module
php artisan module:install {$this->moduleCode}

# Run migrations
php artisan module:migrate {$this->moduleCode}
```

## Usage

Add documentation about how to use this module.

## Features

- List module features here

## Configuration

Configuration file: `Config/config.php`

## API Endpoints

Document your API endpoints here.

## Development

### Testing

```bash
php artisan test modules/{$this->moduleName}/Tests
```

### Building Assets

```bash
npm run dev
```

## License

Proprietary - Aero Enterprise Suite
MD;

        File::put("{$this->modulePath}/README.md", $stub);
    }

    /**
     * Generate composer.json
     */
    protected function generateComposer(): void
    {
        $namespace = str_replace('\\', '\\\\', $this->moduleNamespace);
        
        $composer = [
            'name' => 'aero/' . $this->moduleCode,
            'description' => "The {$this->moduleName} module for Aero Enterprise Suite",
            'type' => 'library',
            'require' => [
                'php' => '^8.2',
            ],
            'autoload' => [
                'psr-4' => [
                    "{$namespace}\\" => '',
                ],
            ],
        ];

        $json = json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        File::put("{$this->modulePath}/composer.json", $json);
    }

    /**
     * Generate .gitignore
     */
    protected function generateGitignore(): void
    {
        $gitignore = <<<GITIGNORE
/vendor/
/node_modules/
/.idea/
/.vscode/
*.log
.DS_Store
GITIGNORE;

        File::put("{$this->modulePath}/.gitignore", $gitignore);
    }

    /**
     * Generate example controller
     */
    protected function generateExampleController(): void
    {
        $name = str_replace(' ', '', $this->moduleName);
        $stub = <<<PHP
<?php

namespace {$this->moduleNamespace}\\Http\\Controllers;

use App\\Http\\Controllers\\Controller;
use Illuminate\\Http\\Request;

class {$name}Controller extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('{$this->moduleCode}::index');
    }
}
PHP;

        File::put("{$this->modulePath}/Http/Controllers/{$name}Controller.php", $stub);
    }

    /**
     * Generate example model
     */
    protected function generateExampleModel(): void
    {
        $name = str_replace(' ', '', $this->moduleName);
        $stub = <<<PHP
<?php

namespace {$this->moduleNamespace}\\Models;

use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;

class {$name} extends Model
{
    use HasFactory;

    protected \$fillable = [];

    protected \$casts = [];
}
PHP;

        File::put("{$this->modulePath}/Models/{$name}.php", $stub);
    }

    /**
     * Generate example migration
     */
    protected function generateExampleMigration(): void
    {
        $table = Str::snake(Str::plural($this->moduleName));
        $timestamp = date('Y_m_d_His');
        $stub = <<<PHP
<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('{$table}', function (Blueprint \$table) {
            \$table->id();
            \$table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('{$table}');
    }
};
PHP;

        File::put("{$this->modulePath}/Database/Migrations/{$timestamp}_create_{$table}_table.php", $stub);
    }
}
