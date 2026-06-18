<?php

namespace Aero\Core\Providers;

use Aero\Contracts\AeroMode;
use Aero\Core\Http\Middleware\InitializeTenancyIfNotCentral;
use Composer\InstalledVersions;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

/**
 * ModuleRouteServiceProvider
 *
 * Context-aware route provider that registers module routes based on the runtime environment:
 * - SaaS Mode: Routes are registered with tenant middleware (subdomain-based)
 * - Standalone Mode: Routes are registered with web middleware (root domain)
 *
 * This provider automatically detects the presence of aero-platform and adjusts
 * route registration strategy accordingly.
 */
class ModuleRouteServiceProvider extends ServiceProvider
{
    /**
     * The modules to register routes for.
     */
    protected array $modules = [];

    /**
     * Create a new service provider instance.
     *
     * @param  Application  $app
     * @return void
     */
    public function __construct($app)
    {
        parent::__construct($app);

        // Don't auto-discover in constructor - defer to boot()
    }

    /**
     * Define your route model bindings, pattern filters, etc.
     */
    public function boot(): void
    {
        parent::boot();

        // Auto-discover modules during boot (when app is fully initialized)
        $this->discoverModules();

        $this->registerModuleRoutes();
    }

    /**
     * Discover available modules from packages directory.
     */
    protected function discoverModules(): void
    {
        try {
            $packagesPath = base_path('packages');

            if (! File::isDirectory($packagesPath)) {
                return;
            }

            $directories = File::directories($packagesPath);

            foreach ($directories as $directory) {
                $moduleName = basename($directory);

                // Check if module is installed via Composer
                if (class_exists(InstalledVersions::class)) {
                    // Convert folder name to package name (e.g. aero-hrm -> aero/hrm)
                    $packageName = 'aero/'.str_replace('aero-', '', $moduleName);

                    // Skip if package is not installed
                    if (! InstalledVersions::isInstalled($packageName) &&
                        ! InstalledVersions::isInstalled($moduleName)) {
                        continue;
                    }
                }

                // Check if module has routes directory
                $routesPath = $directory.'/routes';

                if (File::isDirectory($routesPath)) {
                    $this->modules[$moduleName] = [
                        'path' => $directory,
                        'routes_path' => $routesPath,
                        'namespace' => $this->getModuleNamespace($moduleName),
                    ];
                }
            }
        } catch (\Throwable $e) {
            // Ignore errors during package discovery - modules won't be auto-discovered
        }
    }

    /**
     * Get the namespace for a module.
     */
    protected function getModuleNamespace(string $moduleName): string
    {
        // Convert aero-hrm to Aero\Hrm
        $parts = explode('-', $moduleName);
        $namespace = implode('\\', array_map('ucfirst', $parts));

        return $namespace.'\\Http\\Controllers';
    }

    /**
     * Register routes for all discovered modules.
     */
    protected function registerModuleRoutes(): void
    {
        foreach ($this->modules as $moduleName => $moduleData) {
            $this->registerRoutesForModule($moduleName, $moduleData);
        }
    }

    /**
     * Register routes for a specific module.
     */
    protected function registerRoutesForModule(string $moduleName, array $moduleData): void
    {
        // If the module already registers its own routes via a dedicated provider,
        // skip auto-registration to avoid duplicate/unprefixed routes that miss the
        // expected middleware (e.g., /dashboard without the web group).
        if ($this->isModuleProviderRegistered($moduleName)) {
            return;
        }

        $routesPath = $moduleData['routes_path'];
        $namespace = $moduleData['namespace'];

        // Determine routing strategy based on platform presence
        if ($this->isPlatformActive()) {
            $this->registerSaaSRoutes($moduleName, $routesPath, $namespace);
        } else {
            $this->registerStandaloneRoutes($moduleName, $routesPath, $namespace);
        }
    }

    /**
     * Register routes for SaaS mode (with tenant middleware).
     */
    protected function registerSaaSRoutes(string $moduleName, string $routesPath, string $namespace): void
    {
        // InitializeTenancyIfNotCentral initializes tenant context on tenant domains
        // 'tenant' middleware (EnsureTenantContext) ensures valid tenant context exists
        $tenancyMiddleware = InitializeTenancyIfNotCentral::class;
        $platformDomain = config('aero.platform_domain', 'localhost');

        // Register tenant routes (subdomain-based, requires auth)
        if (File::exists($routesPath.'/tenant.php')) {
            Route::domain('{tenant}.'.$platformDomain)
                ->middleware(['web', $tenancyMiddleware, 'tenant', 'auth', 'verified'])
                ->group($routesPath.'/tenant.php');
        }

        // Register web routes (for tenant routes without auth requirement)
        if (File::exists($routesPath.'/web.php')) {
            Route::domain('{tenant}.'.$platformDomain)
                ->middleware(['web', $tenancyMiddleware, 'tenant'])
                ->group($routesPath.'/web.php');
        }

        // Register landlord routes (central domain routes - NO tenancy middleware)
        if (File::exists($routesPath.'/landlord.php')) {
            Route::middleware(['web', 'landlord'])
                ->domain(config('tenancy.central_domains')[0] ?? null)
                ->group($routesPath.'/landlord.php');
        }

        // Register API routes (tenant-scoped)
        if (File::exists($routesPath.'/api.php')) {
            Route::domain('{tenant}.'.$platformDomain)
                ->middleware(['api', $tenancyMiddleware, 'tenant', 'auth:sanctum'])
                ->prefix('api')
                ->group($routesPath.'/api.php');
        }
    }

    /**
     * Register routes for Standalone mode (without tenant middleware).
     */
    protected function registerStandaloneRoutes(string $moduleName, string $routesPath, string $namespace): void
    {
        // In standalone mode, all routes go through standard web middleware

        // Register authenticated routes
        if (File::exists($routesPath.'/tenant.php')) {
            Route::middleware(['web', 'auth', 'verified'])
                ->group($routesPath.'/tenant.php');
        }

        // Register web routes
        if (File::exists($routesPath.'/web.php')) {
            Route::middleware(['web'])
                ->group($routesPath.'/web.php');
        }

        // In standalone, no landlord routes needed
        // But if they exist, register them as regular web routes
        if (File::exists($routesPath.'/landlord.php')) {
            Route::middleware(['web', 'auth'])
                ->group($routesPath.'/landlord.php');
        }

        // Register API routes
        if (File::exists($routesPath.'/api.php')) {
            Route::middleware(['api', 'auth:sanctum'])
                ->prefix('api')
                ->group($routesPath.'/api.php');
        }
    }

    /**
     * Determine if aero-platform is active.
     */
    protected function isPlatformActive(): bool
    {
        // Method 1: Canonical mode detection (file-based via contracts API)
        if (AeroMode::isSaas()) {
            return true;
        }

        // Method 2: Check configuration
        if (config('platform.enabled', false)) {
            return true;
        }

        // Method 3: Check if tenancy middleware is available
        if ($this->isTenancyMiddlewareAvailable()) {
            return true;
        }

        return false;
    }

    /**
     * Check if tenancy middleware is available.
     */
    protected function isTenancyMiddlewareAvailable(): bool
    {
        try {
            $middleware = $this->app['router']->getMiddleware();

            return isset($middleware['tenant']) ||
                   class_exists('Stancl\Tenancy\Middleware\InitializeTenancyByDomain');
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Register a module manually (for testing or dynamic loading).
     */
    public function registerModule(string $moduleName, string $routesPath, string $namespace): void
    {
        $this->modules[$moduleName] = [
            'path' => dirname($routesPath),
            'routes_path' => $routesPath,
            'namespace' => $namespace,
        ];

        $this->registerRoutesForModule($moduleName, $this->modules[$moduleName]);
    }

    /**
     * Get all registered modules.
     */
    public function getRegisteredModules(): array
    {
        return $this->modules;
    }

    /**
     * Determine if a module already registers its own routes via a dedicated provider.
     *
     * This prevents duplicate routes when a module-specific service provider
     * (extending AbstractModuleProvider) handles its own route loading.
     */
    protected function isModuleProviderRegistered(string $moduleName): bool
    {
        $moduleCode = str_replace('aero-', '', $moduleName);
        $studly = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $moduleCode)));
        $upper = strtoupper($moduleCode);

        $candidateProviders = [
            "Aero\\{$studly}\\Aero{$studly}ServiceProvider",
            "Aero\\{$studly}\\Providers\\{$studly}ServiceProvider",
            "Aero\\{$studly}\\Providers\\{$studly}ModuleProvider",
            "Aero\\{$upper}\\Aero{$upper}ServiceProvider",
            "Aero\\{$upper}\\Providers\\{$upper}ServiceProvider",
            "Aero\\{$upper}\\Aero{$studly}ServiceProvider",
            "Aero\\{$upper}\\Providers\\{$studly}ModuleProvider",
            // Special-case namespaces that differ from standard patterns
            'Aero\\IoT\\Providers\\IoTServiceProvider',
        ];

        foreach ($candidateProviders as $providerClass) {
            if (class_exists($providerClass)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a module is registered.
     */
    public function isModuleRegistered(string $moduleName): bool
    {
        return isset($this->modules[$moduleName]);
    }
}
