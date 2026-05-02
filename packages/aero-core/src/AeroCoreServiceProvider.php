<?php

namespace Aero\Core;

use Aero\Core\Contracts\EmployeeServiceContract;
use Aero\Core\Contracts\NotificationRoutingContract;
use Aero\Core\Contracts\TenantScopeInterface;
use Aero\Core\Database\Seeders\CoreDatabaseSeeder;
use Aero\Core\Exceptions\Handler;
use Aero\Core\Http\Middleware\CheckModuleAccess;
use Aero\Core\Http\Middleware\DashboardRedirectMiddleware;
use Aero\Core\Http\Middleware\EnsureTenantContext;
use Aero\Core\Http\Middleware\HandleInertiaRequests;
use Aero\Core\Http\Middleware\InitializeTenancyIfNotCentral;
use Aero\Core\Models\User;
use Aero\Core\Providers\CoreModuleProvider;
use Aero\Core\Providers\ModuleRouteServiceProvider;
use Aero\Core\Services\DashboardRegistry;
use Aero\Core\Services\DashboardWidgetRegistry;
use Aero\Core\Services\HrmacNotificationRoutingService;
use Aero\Core\Services\ModuleAccessService;
use Aero\Core\Services\ModuleManager;
use Aero\Core\Services\ModuleRegistry;
use Aero\Core\Services\NavigationRegistry;
use Aero\Core\Services\Notifications\CoreMailContextResolver;
use Aero\Core\Services\Notifications\CoreSmsContextResolver;
use Aero\Core\Services\PlatformErrorReporter;
use Aero\Core\Services\RuntimeLoader;
use Aero\Core\Services\StandaloneTenantScope;
use Aero\Core\Services\UserRelationshipRegistry;
use Aero\Core\Traits\ParsesHostDomain;
use Aero\HRM\Services\EmployeeService;
use Aero\Notifications\Contracts\MailContextResolver;
use Aero\Notifications\Contracts\SmsContextResolver;
use Aero\HRMAC\Contracts\RoleModuleAccessInterface;
use Aero\HRMAC\Services\RoleModuleAccessService;
use Aero\Platform\AeroPlatformServiceProvider;
use Aero\Platform\Http\Middleware\EnsureTenantIsActive;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * AeroCoreServiceProvider
 *
 * Main service provider for the Aero Core package.
 * Handles initialization, configuration, and service registration.
 */
class AeroCoreServiceProvider extends ServiceProvider
{
    use ParsesHostDomain;

    /**
     * Register services.
     */
    public function register(): void
    {
        try {
            // Register the Core module service provider
            // This handles navigation, self-service items, dashboards, etc.
            $this->app->register(CoreModuleProvider::class);

            // Override the Migrator to exclude app's migration directory
            // Core and module packages provide all necessary migrations
            //
            // NOTE: Skip this override if Platform is installed
            // Platform has its own migrator override that restricts landlord migrations
            // to only platform package migrations (tenants, domains, plans, etc.)
            if (! class_exists(AeroPlatformServiceProvider::class)) {
                $this->app->extend('migrator', function ($migrator, $app) {
                    return new class($app['migration.repository'], $app['db'], $app['files'], $app['events']) extends Migrator
                    {
                        public function getMigrationFiles($paths)
                        {
                            // Get all migration files from all paths
                            $files = parent::getMigrationFiles($paths);

                            // Filter out files from app's database/migrations directory
                            $appMigrationPath = database_path('migrations');

                            return collect($files)->reject(function ($path, $name) use ($appMigrationPath) {
                                return str_starts_with($path, $appMigrationPath);
                            })->all();
                        }
                    };
                });
            }

            // Merge configuration
            $this->mergeConfigFrom(__DIR__.'/../config/aero.php', 'aero');
            $this->mergeConfigFrom(__DIR__.'/../config/marketplace.php', 'marketplace');
            $this->mergeConfigFrom(__DIR__.'/../config/security.php', 'security');
            // Module definitions are in config/module.php and loaded by ModuleDiscoveryService
            $this->mergeConfigFrom(__DIR__.'/../config/core.php', 'aero.core');
            $this->mergeConfigFrom(__DIR__.'/../config/permission.php', 'permission');

            // Configure auth to use Core's User model
            config(['auth.providers.users.model' => User::class]);

            // Register Core Singletons
            $this->app->singleton(ModuleRegistry::class);
            $this->app->singleton(NavigationRegistry::class);
            $this->app->singleton(UserRelationshipRegistry::class);
            $this->app->singleton(DashboardWidgetRegistry::class);
            $this->app->singleton(DashboardRegistry::class);

            // Bind notification context resolvers to new aero-notifications package
            $this->app->singleton(MailContextResolver::class, CoreMailContextResolver::class);
            $this->app->singleton(SmsContextResolver::class, CoreSmsContextResolver::class);

            // Register Module Access Services (with error handling for missing tables)
            // These services are lazy-loaded, so they won't cause issues pre-install
            $this->app->singleton(ModuleAccessService::class, function ($app) {
                // Only instantiate if installed to avoid DB queries pre-install
                if (! file_exists(storage_path('app/aeos.installed'))) {
                    return new class
                    {
                        public function __call($method, $args)
                        {
                            return [];
                        }
                    };
                }

                try {
                    return new ModuleAccessService;
                } catch (\Throwable $e) {
                    return new class
                    {
                        public function __call($method, $args)
                        {
                            return [];
                        }
                    };
                }
            });

            $this->app->singleton(RoleModuleAccessService::class, function ($app) {
                // Only instantiate if installed to avoid DB queries pre-install
                if (! file_exists(storage_path('app/aeos.installed'))) {
                    // Return a stub that uses __call for method handling
                    return new class
                    {
                        public function __call($method, $args)
                        {
                            // Return appropriate defaults based on method signature
                            if (str_starts_with($method, 'can') || str_starts_with($method, 'user')) {
                                return false;
                            }
                            if (str_starts_with($method, 'get')) {
                                return $method === 'getFirstAccessibleRoute' ? null : [];
                            }

                            return null;
                        }
                    };
                }

                try {
                    return new RoleModuleAccessService;
                } catch (\Throwable $e) {
                    return new class
                    {
                        public function __call($method, $args)
                        {
                            if (str_starts_with($method, 'can') || str_starts_with($method, 'user')) {
                                return false;
                            }
                            if (str_starts_with($method, 'get')) {
                                return $method === 'getFirstAccessibleRoute' ? null : [];
                            }

                            return null;
                        }
                    };
                }
            });

            // Bind TenantScopeInterface to StandaloneTenantScope as default
            // This can be overridden by aero-platform for SaaS mode
            $this->app->singleton(TenantScopeInterface::class, StandaloneTenantScope::class);

            // Register cross-package contracts for modular architecture
            $this->registerCrossPackageContracts();

            // Register RuntimeLoader as singleton (lazy-loaded)
            $this->app->singleton(RuntimeLoader::class, function ($app) {
                try {
                    $modulesPath = config('aero.runtime_loading.modules_path', base_path('modules'));
                } catch (\Throwable $e) {
                    $modulesPath = base_path('modules');
                }

                return new RuntimeLoader($modulesPath);
            });

            // Register ModuleManager as singleton (lazy-loaded)
            $this->app->singleton('aero.module', function ($app) {
                // Support monorepo structure where packages are in parent directory
                $packagesPath = base_path('packages');
                if (! file_exists($packagesPath)) {
                    // Try monorepo structure: apps/standalone-host -> ../../packages
                    $packagesPath = base_path('../../packages');
                }

                return new ModuleManager(
                    base_path('modules'), // Runtime modules (optional)
                    $packagesPath // Composer packages
                );
            });

            // Register ModuleRouteServiceProvider
            $this->app->register(ModuleRouteServiceProvider::class);

            // Register helper functions
            $this->registerHelpers();
        } catch (\Throwable $e) {
            // Silently fail during package discovery
            // Services will be registered when app fully boots
        }
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Force file-based sessions BEFORE any session driver is instantiated
        // This allows installation to work without database tables
        if (! $this->installed()) {
            // Pre-configure session driver to file (before StartSession middleware runs)
            config(['session.driver' => 'file', 'cache.default' => 'file']);
        }

        // Register Authorization Gates for Role and Module Management
        // These gates control access to admin functionality for managing roles and modules
        $this->registerAuthorizationGates();

        // Load migrations from Core package (takes priority)
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Load seeders from Core package
        $this->publishes([
            __DIR__.'/../database/seeders' => database_path('seeders/Aero/Core'),
        ], 'aero-seeders');

        // Publish configuration
        $this->publishes([
            __DIR__.'/../config/aero.php' => config_path('aero.php'),
        ], 'aero-config');

        $this->publishes([
            __DIR__.'/../config/marketplace.php' => config_path('marketplace.php'),
        ], 'marketplace-config');

        // NOTE: No assets publishing - all frontend is handled by aero/ui package

        // Publish stubs for new installations (optional)
        $this->publishes([
            __DIR__.'/../resources/stubs/User.php.stub' => app_path('Models/User.php'),
            __DIR__.'/../resources/stubs/DatabaseSeeder.php.stub' => database_path('seeders/DatabaseSeeder.php'),
            __DIR__.'/../stubs/web.php.stub' => base_path('routes/web.php'),
        ], 'aero-core-stubs');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'aero-core');

        // JSON translations are now loaded by aero-i18n package

        // Register routes - conditional on installation status
        $this->registerRoutes();

        // Register Inertia middleware - must be after routes
        $this->registerMiddleware();

        // Register exception handler for unified error reporting
        $this->registerExceptionHandler();

        // Guard against early boot before app is fully initialized
        try {
            // Auto-create modules symlink for Standalone mode
            $this->ensureModulesSymlink();

            // Load runtime modules in standalone mode
            if ($this->shouldLoadRuntimeModules()) {
                $this->loadRuntimeModules();
            }

            // Register console commands
            if ($this->app->runningInConsole()) {
                $this->registerCommands();
                $this->registerSchedule();
            }

            // Only register navigation and widgets if installed
            if ($this->installed()) {
                // Register core navigation from config/module.php
                $this->registerCoreNavigation();

                // Register Core dashboard widgets
                $this->registerDashboardWidgets();

                // Register Core dashboards in the DashboardRegistry
                $this->registerDashboards();
            }
        } catch (\Throwable $e) {
            // Ignore errors during early boot/package discovery
            // These will be called again when the app is fully booted
        }
    }

    /**
     * Register Core widgets for the Dashboard.
     *
     * Core widgets are fundamental widgets that appear on every dashboard:
     * - Welcome/greeting widget (position: welcome)
     * - System stats widget (position: stats_row)
     * - Quick actions widget (position: stats_row)
     * - Recent activity widget (position: main_left)
     * - Notifications widget (position: sidebar)
     * - Security overview widget (position: sidebar)
     * - Active modules widget (position: sidebar)
     *
     * Note: Holidays and Organization widgets are in HRM package
     */
    protected function registerDashboardWidgets(): void
    {
        // Only register if the registry is available
        if (! $this->app->bound(DashboardWidgetRegistry::class)) {
            return;
        }

        $registry = $this->app->make(DashboardWidgetRegistry::class);

        // All core dashboard widgets are now statically rendered in Dashboard.jsx
        // No dynamic widget registrations needed for core dashboard
    }

    /**
     * Register Core dashboards with DashboardRegistry.
     *
     * The DashboardRegistry allows roles to be assigned to specific dashboards.
     * When users navigate to /dashboard, the DashboardRedirectMiddleware
     * will redirect them to their role's assigned dashboard.
     */
    protected function registerDashboards(): void
    {
        // Only register if the registry is available
        if (! $this->app->bound(DashboardRegistry::class)) {
            return;
        }

        $registry = $this->app->make(DashboardRegistry::class);

        // Register Core Dashboard (for system administrators)
        // Route name is 'core.dashboard' which maps to /dashboard
        $registry->register(
            'core.dashboard',
            'Admin Dashboard',
            'core',
            'System overview for administrators',
            'HomeIcon'
        );
    }

    /**
     * Register Core routes.
     *
     * Route Architecture:
     * -------------------
     * Core routes are conditionally loaded based on installation status:
     * - NOT installed: Installation routes are loaded by aero-installation package
     * - Installed: Load runtime routes (web.php) based on mode
     *
     * Domain-based routing (when installed):
     * - In SaaS mode: Routes ONLY on tenant domains (tenant.domain.com)
     * - In Standalone mode: Routes on all domains (domain.com)
     * - Public API routes (/api/version/check, /api/error-log) on ALL domains
     */
    protected function registerRoutes(): void
    {
        $routesPath = __DIR__.'/../routes';

        // Always register public API routes on all domains (version check, error logging)
        $this->registerPublicApiRoutes();

        // Installation routes are now handled by aero-installation package
        // If not installed, aero-installation will handle the installation flow
        if (! $this->installed()) {
            return;
        }

        // System IS installed - load runtime routes based on mode
        $this->loadRuntimeRoutes();
    }

    /**
     * Load runtime routes after installation is complete.
     * Handles both SaaS and standalone modes.
     *
     * In SaaS mode, aero-core serves ONLY tenant subdomains ({tenant}.domain.com).
     * Platform handles all routes on domain.com and admin.domain.com.
     * A Laravel domain() constraint ensures core routes never match central domains,
     * regardless of CLI vs HTTP context.
     */
    protected function loadRuntimeRoutes(): void
    {
        $routesPath = __DIR__.'/../routes';

        if ($this->isSaasMode()) {
            // SaaS Mode: Core routes are ONLY for tenant subdomains ({tenant}.domain.com).
            // Using a domain constraint guarantees this at route-matching time, not request time,
            // so route:list and cached routes cannot leak onto the platform domain.
            $platformDomain = env('PLATFORM_DOMAIN', env('APP_DOMAIN', 'localhost'));

            Route::domain('{tenant}.'.$platformDomain)
                ->middleware([
                    'web',
                    InitializeTenancyIfNotCentral::class,
                    'tenant',
                ])->group($routesPath.'/web.php');
        } else {
            // Standalone Mode: Routes with standard web middleware.
            // No domain constraint needed — standalone apps run on a single domain.
            Route::middleware(['web'])
                ->group($routesPath.'/web.php');
        }
    }

    /**
     * Register public API routes that should be available on ALL domains.
     * These are domain-agnostic routes for version checking and error logging.
     */
    protected function registerPublicApiRoutes(): void
    {
        $reporter = PlatformErrorReporter::class;

        // Version check API - available on all domains (no CSRF required)
        Route::post('/api/version/check', function (Request $request) {
            $clientVersion = $request->input('version', '1.0.0');
            $serverVersion = config('app.version', '1.0.0');

            return response()->json([
                'version_match' => $clientVersion === $serverVersion,
                'client_version' => $clientVersion,
                'server_version' => $serverVersion,
                'timestamp' => now()->toIso8601String(),
            ]);
        })->name('api.version.check')
            ->middleware(['web', 'throttle:30,1'])
            ->withoutMiddleware([VerifyCsrfToken::class]);

        // Error logging API - available on all domains (no CSRF required)
        Route::post('/api/error-log', function (Request $request) use ($reporter) {
            $reporterInstance = app($reporter);
            $traceId = $reporterInstance->reportFrontendError($request->all());

            return response()->json([
                'success' => true,
                'trace_id' => $traceId,
                'message' => 'Error reported successfully',
            ]);
        })->name('api.error-log')
            ->middleware(['web', 'throttle:30,1'])
            ->withoutMiddleware([VerifyCsrfToken::class]);

        // Health check API - available on all domains
        Route::get('/aero-core/health', function () {
            return response()->json([
                'status' => 'ok',
                'package' => 'aero/core',
                'version' => '1.0.0',
                'timestamp' => now()->toIso8601String(),
            ]);
        })->name('api.core.health')->middleware(['web']);
    }

    /**
     * Check if current domain is a central domain.
     * AUTO-DETECTS from browser request - no configuration needed.
     *
     * Logic:
     * - admin.domain.com → Central (admin subdomain)
     * - domain.com → Central (root domain)
     * - tenant.domain.com → Tenant (has subdomain that's not 'admin')
     */
    protected function isOnCentralDomain(string $host): bool
    {
        $hostWithoutPort = preg_replace('/:\d+$/', '', $host);

        // Check for admin subdomain (always central)
        if (str_starts_with($hostWithoutPort, 'admin.')) {
            return true;
        }

        // Check for localhost/127.0.0.1 (always central for local dev)
        if (in_array($hostWithoutPort, ['localhost', '127.0.0.1'], true)) {
            return true;
        }

        // Count dots to determine if it's a subdomain
        $parts = explode('.', $hostWithoutPort);
        $dotCount = count($parts) - 1;

        // For .test, .local, .localhost TLDs:
        // - 1 dot (domain.test) → Central (root domain)
        // - 2+ dots (tenant.domain.test) → Tenant (subdomain)
        if (preg_match('/\.(test|local|localhost)$/i', $hostWithoutPort)) {
            return $dotCount === 1; // Only 1 dot means root domain = central
        }

        // For production domains (e.g., .com, .net, .org):
        // - 1 dot (domain.com) → Central (root domain)
        // - 2+ dots (tenant.domain.com) → Tenant (subdomain)
        return $dotCount === 1; // Only 1 dot means root domain = central
    }

    /**
     * Check if current domain is the admin subdomain.
     * Used in standalone mode to skip Core routes on admin.domain.com
     */
    protected function isHostAdminDomain(string $host): bool
    {
        $hostWithoutPort = preg_replace('/:\d+$/', '', $host);

        return str_starts_with($hostWithoutPort, 'admin.');
    }

    /**
     * Register Core middleware.
     */
    protected function registerMiddleware(): void
    {
        // Use the booted callback to ensure app is fully initialized
        $this->app->booted(function () {
            $router = $this->app->make('router');

            // Register HandleInertiaRequests middleware to web middleware group
            $router->pushMiddlewareToGroup('web', HandleInertiaRequests::class);

            // In SaaS mode, add tenant.active middleware to web group (after auth)
            if (is_saas_mode() && class_exists('\Aero\Platform\Http\Middleware\EnsureTenantIsActive')) {
                $router->pushMiddlewareToGroup('web', EnsureTenantIsActive::class);
            }

            // Register middleware aliases
            $router->aliasMiddleware('module', CheckModuleAccess::class);

            // Register 'tenant' middleware alias for tenant-only routes
            // This ensures requests have a valid tenant context (used after InitializeTenancyIfNotCentral)
            $router->aliasMiddleware('tenant', EnsureTenantContext::class);

            // Register 'dashboard.redirect' middleware alias for role-based dashboard routing
            $router->aliasMiddleware('dashboard.redirect', DashboardRedirectMiddleware::class);
        });
    }

    /**
     * Check if aero-platform is active.
     *
     * @deprecated Use isSaasMode() instead for mode detection
     */
    protected function isPlatformActive(): bool
    {
        return class_exists('Aero\Platform\AeroPlatformServiceProvider');
    }

    /**
     * Check if system is in SaaS mode using file-based detection.
     * Mode is set during installation and immutable at runtime.
     *
     * This is the ONLY authoritative method for mode detection.
     */
    protected function isSaasMode(): bool
    {
        return is_saas_mode();
    }

    /**
     * Determine if runtime modules should be loaded.
     */
    protected function shouldLoadRuntimeModules(): bool
    {
        // Guard against early execution before config is loaded
        if (! $this->app->configurationIsCached() && ! file_exists(config_path('aero.php'))) {
            return false;
        }

        return is_standalone_mode() &&
               config('aero.runtime_loading.enabled', false);
    }

    /**
     * Ensure modules directory is symlinked to public for asset access.
     */
    protected function ensureModulesSymlink(): void
    {
        $modulesPath = base_path('modules');
        $publicModulesPath = public_path('modules');

        // Only create symlink if modules directory exists and symlink doesn't
        if (file_exists($modulesPath) && ! file_exists($publicModulesPath)) {
            try {
                // Try to create symlink
                if (function_exists('symlink')) {
                    @symlink($modulesPath, $publicModulesPath);

                    if (file_exists($publicModulesPath)) {
                        $this->app['log']->info('Aero: Created modules symlink successfully');
                    }
                } else {
                    // Symlinks not available, log warning
                    $this->app['log']->warning(
                        'Aero: Cannot create symlink - function not available. '.
                        'Manually copy modules to public/modules or enable symlink support.'
                    );
                }
            } catch (\Throwable $e) {
                $this->app['log']->warning('Aero: Failed to create modules symlink', [
                    'error' => $e->getMessage(),
                    'hint' => 'You may need to manually copy the modules directory or enable symlink support',
                ]);
            }
        }
    }

    /**
     * Load runtime modules.
     */
    protected function loadRuntimeModules(): void
    {
        try {
            $loader = $this->app->make(RuntimeLoader::class);
            $modules = $loader->loadModules();

            if (count($modules) > 0) {
                $this->app['log']->info('Aero: Loaded '.count($modules).' runtime modules', [
                    'modules' => array_keys($modules),
                ]);
            }
        } catch (\Throwable $e) {
            $this->app['log']->error('Aero: Failed to load runtime modules', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Register helper functions.
     */
    protected function registerHelpers(): void
    {
        $helpersPath = __DIR__.'/helpers.php';

        if (file_exists($helpersPath)) {
            require_once $helpersPath;
        }
    }

    /**
     * Register console commands.
     */
    protected function registerCommands(): void
    {
        $this->commands([
            Console\Commands\InstallCommand::class,
            Console\Commands\SyncModuleHierarchy::class,
            Console\Commands\SyncModuleMigrations::class,
            Console\Commands\SeedCommand::class,
            Console\Commands\CleanupExpiredSessions::class,
        ]);
    }

    /**
     * Register scheduled tasks for Core module.
     */
    protected function registerSchedule(): void
    {
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            // Clean up expired sessions hourly
            $schedule->command('sessions:cleanup')
                ->hourly()
                ->withoutOverlapping()
                ->description('Clean up expired user sessions');
        });
    }

    /**
     * Register hook to automatically call Core seeders when db:seed runs.
     */
    protected function registerSeederHook(): void
    {
        // Hook into the command starting event to inject our seeders
        $this->app['events']->listen('Illuminate\Console\Events\CommandStarting', function ($event) {
            if ($event->command === 'db:seed') {
                // Get the DatabaseSeeder class from the application
                $seederClass = $event->input->getOption('class') ?: 'Database\\Seeders\\DatabaseSeeder';

                // If it's the default DatabaseSeeder and no specific class is requested,
                // we'll call our Core seeder first
                if ($seederClass === 'Database\\Seeders\\DatabaseSeeder') {
                    // Schedule Core seeder to run before the app's seeder
                    $this->app['events']->listen('Illuminate\Database\Events\SeedingDatabase', function () use ($event) {
                        static $coreSeederExecuted = false;

                        // Only execute once per db:seed command
                        if (! $coreSeederExecuted) {
                            $this->callCoreSeeder($event);
                            $coreSeederExecuted = true;
                        }
                    });
                }
            }
        });
    }

    /**
     * Call the Core database seeder.
     *
     * @param  mixed  $event  Command event
     */
    protected function callCoreSeeder($event): void
    {
        try {
            $seeder = new CoreDatabaseSeeder;
            $seeder->setContainer($this->app);
            $seeder->setCommand($event->output);
            $seeder->run();

            if ($this->app->runningInConsole()) {
                $event->output->info('Aero Core seeders executed successfully');
            }
        } catch (\Throwable $e) {
            if ($this->app->runningInConsole()) {
                $event->output->error('Failed to run Aero Core seeders: '.$e->getMessage());
                $this->app['log']->error('Aero Core Seeder Error: '.$e->getMessage(), [
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            RuntimeLoader::class,
            ModuleRegistry::class,
            NavigationRegistry::class,
            UserRelationshipRegistry::class,
            ModuleAccessService::class,
            RoleModuleAccessService::class,
        ];
    }

    /**
     * Register authorization gates for role and module management.
     *
     * Gates define who can manage roles and modules in the system.
     * - 'manage-roles': Create, update, delete roles and manage role-module access
     * - 'manage-modules': View modules, sync role access, manage module hierarchy
     *
     * By default, only Super Administrator and Administrator roles have these permissions.
     * These gates are used by the 'can:' middleware on role and module routes.
     */
    protected function registerAuthorizationGates(): void
    {
        // Only register gates if the system is installed
        // Pre-installation, there are no users or roles to check
        if (! $this->installed()) {
            return;
        }

        try {
            // Gate: manage-roles
            // Allows user to create, update, delete roles and assign module access
            Gate::define('manage-roles', function ($user) {
                // Super Administrators and Administrators can manage roles
                // Check if user has one of the authorized roles
                return $user->hasRole(['Super Administrator', 'Administrator']);
            });

            // Gate: manage-modules
            // Allows user to view modules, sync access, manage module hierarchy
            Gate::define('manage-modules', function ($user) {
                // Super Administrators and Administrators can manage modules
                // Check if user has one of the authorized roles
                return $user->hasRole(['Super Administrator', 'Administrator']);
            });
        } catch (\Throwable $e) {
            // Silently fail if User model or hasRole method not available
            // This can happen during migrations or in test environments
            Log::warning('Failed to register authorization gates', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Register core navigation items from config/module.php.
     *
     * Structure: Module → Submodules → Components
     * - Submodules become top-level menu items (flattened by NavigationRegistry)
     * - If submodule has only ONE component, that component becomes the menu item directly
     * - If submodule has multiple components, they become submenu items
     * - Icons are inherited from parent if not specified
     */
    protected function registerCoreNavigation(): void
    {
        /** @var NavigationRegistry $registry */
        $registry = $this->app->make(NavigationRegistry::class);

        // Load core module config
        $configPath = __DIR__.'/../config/module.php';
        $config = file_exists($configPath) ? require $configPath : [];

        // Build navigation from config submodules
        $submoduleNav = [];
        foreach ($config['submodules'] ?? [] as $submodule) {
            $submoduleCode = $submodule['code'] ?? '';

            // Skip authentication submodule from navigation (it's internal)
            if ($submoduleCode === 'authentication') {
                continue;
            }

            // Get submodule icon for fallback
            $submoduleIcon = $submodule['icon'] ?? 'FolderIcon';
            $components = $submodule['components'] ?? [];

            // If submodule has only ONE component, use it directly as the menu item
            if (count($components) === 1) {
                $component = $components[0];
                $submoduleNav[] = [
                    'name' => $submodule['name'] ?? ucfirst($submoduleCode),
                    'path' => $component['route'] ?? $submodule['route'] ?? null,
                    'icon' => $component['icon'] ?? $submoduleIcon,
                    'access' => 'core.'.$submoduleCode.'.'.($component['code'] ?? ''),
                    'priority' => $submodule['priority'] ?? 100,
                    'type' => $component['type'] ?? 'page',
                    // No children - single component becomes the page
                ];
            } else {
                // Multiple components - build children submenu
                $componentNav = [];
                foreach ($components as $component) {
                    $componentNav[] = [
                        'name' => $component['name'] ?? ucfirst($component['code'] ?? ''),
                        'path' => $component['route'] ?? null,
                        'icon' => $component['icon'] ?? $submoduleIcon, // Inherit parent icon
                        'access' => 'core.'.$submoduleCode.'.'.($component['code'] ?? ''),
                        'type' => $component['type'] ?? 'page',
                    ];
                }

                // Create submodule navigation item with component children
                $submoduleNav[] = [
                    'name' => $submodule['name'] ?? ucfirst($submoduleCode),
                    'path' => $submodule['route'] ?? null,
                    'icon' => $submoduleIcon,
                    'access' => 'core.'.$submoduleCode,
                    'priority' => $submodule['priority'] ?? 100,
                    'children' => $componentNav, // Include children for submenu
                ];
            }
        }

        // Sort submodules by priority
        usort($submoduleNav, fn ($a, $b) => ($a['priority'] ?? 100) <=> ($b['priority'] ?? 100));

        // Register core navigation with highest priority (1)
        // Core uses is_core=true so its children flatten to top level
        // Scope: 'tenant' - Core navigation is for tenant users
        $registry->register('core', [
            [
                'name' => $config['name'] ?? 'Core',
                'icon' => $config['icon'] ?? 'CubeIcon',
                'access' => 'core',
                'priority' => $config['priority'] ?? 1,
                'children' => $submoduleNav,
            ],
        ], $config['priority'] ?? 1, 'tenant');
    }

    /**
     * Register unified exception handler for error reporting to platform
     */
    protected function registerExceptionHandler(): void
    {
        $this->app->singleton(PlatformErrorReporter::class);

        // Use Laravel 11's exception handler registration
        if (method_exists($this->app, 'terminating')) {
            // Register a reportable callback for all exceptions
            // This captures backend errors and reports them to the platform
            $this->app->singleton(
                ExceptionHandler::class,
                Handler::class
            );
        }
    }

    /**
     * Check if the system is installed using file-based detection.
     *
     * This is the ONLY authoritative method for checking installation status.
     * Never use database queries for installation detection.
     */
    protected function installed(): bool
    {
        return file_exists(storage_path('app/aeos.installed'));
    }

    /**
     * Register cross-package contracts for modular architecture.
     *
     * These contracts enable communication between packages without direct dependencies:
     * - EmployeeServiceContract: HRM domain → Core (employee data access)
     * - NotificationRoutingContract: HRMAC-based notification recipient resolution
     */
    protected function registerCrossPackageContracts(): void
    {
        // EmployeeServiceContract - bound by aero-hrm when loaded
        // Core provides a null-safe default that returns empty results
        $this->app->singleton(
            EmployeeServiceContract::class,
            function ($app) {
                // Check if HRM package has registered its implementation
                if ($app->bound(EmployeeService::class)) {
                    return $app->make(EmployeeService::class);
                }

                // Return a null-safe stub if HRM is not installed
                return new class implements EmployeeServiceContract
                {
                    public function getById(int $employeeId): ?object
                    {
                        return null;
                    }

                    public function getByUserId(int $userId): ?object
                    {
                        return null;
                    }

                    public function getUserId(int $employeeId): ?int
                    {
                        return null;
                    }

                    public function getEmployeeId(int $userId): ?int
                    {
                        return null;
                    }

                    public function getManagerEmployeeId(int $employeeId): ?int
                    {
                        return null;
                    }

                    public function getDepartmentId(int $employeeId): ?int
                    {
                        return null;
                    }

                    public function getDepartmentEmployeeIds(int $departmentId): array
                    {
                        return [];
                    }

                    public function getDirectReportEmployeeIds(int $managerEmployeeId): array
                    {
                        return [];
                    }

                    public function getReportingChainEmployeeIds(int $employeeId): array
                    {
                        return [];
                    }

                    public function batchResolveUserIds(array $employeeIds): array
                    {
                        return [];
                    }
                };
            }
        );

        // NotificationRoutingContract - HRMAC-based notification routing
        $this->app->singleton(
            NotificationRoutingContract::class,
            function ($app) {
                // Only instantiate if HRMAC and installed
                if (! file_exists(storage_path('app/aeos.installed'))) {
                    return new class implements NotificationRoutingContract
                    {
                        public function getRecipients(string $moduleCode, string $subModuleCode, ?string $componentCode = null, ?string $actionCode = null, array $context = []): Collection
                        {
                            return collect();
                        }

                        public function getRecipientsByScope(string $moduleCode, string $subModuleCode, string $scope, array $context): Collection
                        {
                            return collect();
                        }

                        public function shouldNotify(int $userId, string $moduleCode, string $subModuleCode, ?string $componentCode = null, ?string $actionCode = null): bool
                        {
                            return false;
                        }
                    };
                }

                try {
                    return new HrmacNotificationRoutingService(
                        $app->make(RoleModuleAccessInterface::class),
                        $app->make(EmployeeServiceContract::class)
                    );
                } catch (\Throwable $e) {
                    return new class implements NotificationRoutingContract
                    {
                        public function getRecipients(string $moduleCode, string $subModuleCode, ?string $componentCode = null, ?string $actionCode = null, array $context = []): Collection
                        {
                            return collect();
                        }

                        public function getRecipientsByScope(string $moduleCode, string $subModuleCode, string $scope, array $context): Collection
                        {
                            return collect();
                        }

                        public function shouldNotify(int $userId, string $moduleCode, string $subModuleCode, ?string $componentCode = null, ?string $actionCode = null): bool
                        {
                            return false;
                        }
                    };
                }
            }
        );
    }
}
