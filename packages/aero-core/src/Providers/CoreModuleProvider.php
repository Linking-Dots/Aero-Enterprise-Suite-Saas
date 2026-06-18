<?php

namespace Aero\Core\Providers;

use Aero\Core\Console\Commands\SeedCommand;
use Aero\Core\Console\Commands\SyncModuleHierarchy;
use Aero\Core\Console\Commands\SyncModuleMigrations;
use Aero\Core\Console\Commands\SyncModuleRegistryCommand;
use Aero\Core\Console\Commands\TagMigrationsCommand;
use Aero\Core\Console\Commands\VerifyModulesCommand;
use Aero\Core\Http\Middleware\BootstrapGuard;
use Aero\Core\Http\Middleware\DashboardRedirectMiddleware;
use Aero\Core\Http\Middleware\EnsureUserHasRole;
use Aero\Core\Http\Middleware\InitializeTenancyIfNotCentral;
use Aero\Core\Http\Middleware\ModuleAccessMiddleware;
use Aero\Core\Http\Middleware\PermissionMiddleware;
use Aero\Core\Http\Middleware\PreventInstalledAccess;
use Aero\Core\Models\AuditLog;
use Aero\Core\Models\Tag;
use Aero\Core\Models\User;
use Aero\Core\Observers\UserQuotaObserver;
use Aero\Core\Policies\RolePolicy;
use Aero\Core\Policies\UserPolicy;
use Aero\Core\Services\DashboardRegistry;
use Aero\Core\Services\InstallationState;
use Aero\Core\Services\Search\GlobalSearchService;
use Aero\HRMAC\Models\Role;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;

/**
 * Core Module Provider
 *
 * Registers the core tenant module (Dashboard, Users, Roles, Settings).
 */
class CoreModuleProvider extends AbstractModuleProvider
{
    /**
     * Module code.
     */
    protected string $moduleCode = 'core';

    /**
     * Module display name.
     */
    protected string $moduleName = 'Core';

    /**
     * Module description.
     */
    protected string $moduleDescription = 'Core tenant functionality including dashboard, user management, roles, and settings';

    /**
     * Module version.
     */
    protected string $moduleVersion = '1.0.0';

    /**
     * Module category.
     */
    protected string $moduleCategory = 'foundation';

    /**
     * Module icon.
     */
    protected string $moduleIcon = 'HomeIcon';

    /**
     * Module priority.
     */
    protected int $modulePriority = 1;

    /**
     * Module is always enabled.
     */
    protected bool $enabled = true;

    /**
     * No subscription required for core.
     */
    protected ?string $minimumPlan = null;

    /**
     * Get the module hierarchy from config.
     */
    public function getModuleHierarchy(): array
    {
        return config('aero-core.modules.modules', []);
    }

    /**
     * Register the service provider.
     *
     * This runs BEFORE boot() and before route matching.
     * We register the global bootstrap middleware here so it intercepts ALL requests first.
     */
    public function register(): void
    {
        parent::register();

        // Register DashboardRegistry as singleton
        $this->app->singleton(DashboardRegistry::class, function ($app) {
            return new DashboardRegistry;
        });

        // Register GlobalSearchService and bind core searchable models
        $this->app->singleton(GlobalSearchService::class, function ($app) {
            $service = new GlobalSearchService;
            $service->registerModel(User::class);
            $service->registerModel(AuditLog::class);
            $service->registerModel(Tag::class);

            return $service;
        });

        // In standalone mode, push the global BootstrapGuard middleware
        // This runs before route matching and handles:
        // 1. Installation status check
        // 2. Forcing file-based sessions during installation
        // 3. Redirecting to /install if not installed
        if (config('aero.mode') === 'standalone') {
            $kernel = $this->app->make(Kernel::class);
            $kernel->pushMiddleware(BootstrapGuard::class);
        }
    }

    /**
     * Boot the service provider.
     *
     * Conditionally load installation or app routes based on installation status.
     */
    public function boot(): void
    {
        // Force URL scheme based on APP_URL configuration
        // This respects the user's choice - no forced HTTPS if APP_URL is HTTP
        $appUrl = config('app.url');
        if ($appUrl) {
            $scheme = parse_url($appUrl, PHP_URL_SCHEME);
            if ($scheme) {
                URL::forceScheme($scheme);
            }
        }

        // In standalone mode, load installation routes if not installed
        if (config('aero.mode') === 'standalone' && ! InstallationState::isInstalled()) {
            // Installation routes are now handled by aero-installation package
            // Skip loading installation routes here
        }

        // Call parent boot - it loads views, migrations, and web.php (with module prefix)
        // The BootstrapGuard middleware will block access to these routes if not installed
        parent::boot();
    }

    /**
     * Get navigation items for the core module.
     */
    public function getNavigationItems(): array
    {
        return [
            [
                'code' => 'dashboard',
                'name' => 'Dashboard',
                'icon' => 'HomeIcon',
                'route' => 'dashboard.index',
                'priority' => 1,
            ],
            [
                'code' => 'search',
                'name' => 'Search',
                'icon' => 'MagnifyingGlassIcon',
                'route' => 'core.search.index',
                'priority' => 0,
            ],
            [
                'code' => 'users',
                'name' => 'Users',
                'icon' => 'UserGroupIcon',
                'route' => 'users.index',
                'priority' => 2,
            ],
            [
                'code' => 'roles',
                'name' => 'Roles & Permissions',
                'icon' => 'ShieldCheckIcon',
                'route' => 'roles.index',
                'priority' => 3,
            ],
            [
                'code' => 'tags',
                'name' => 'Tags & Labels',
                'icon' => 'TagIcon',
                'route' => 'core.tags.index',
                'priority' => 4,
            ],
            [
                'code' => 'settings',
                'name' => 'Settings',
                'icon' => 'CogIcon',
                'route' => 'settings.general',
                'priority' => 99,
            ],
        ];
    }

    /**
     * Get module routes.
     */
    public function getRoutes(): array
    {
        return [
            'web' => $this->getModulePath('routes/web.php'),
        ];
    }

    /**
     * Get the module path.
     */
    protected function getModulePath(string $path = ''): string
    {
        $basePath = dirname(__DIR__, 2);

        return $path ? $basePath.'/'.$path : $basePath;
    }

    /**
     * Load core routes without a module prefix.
     *
     * Core routes live at the root path (e.g. /dashboard, /login) rather
     * than under a /core/ prefix. Auth middleware is declared inside web.php.
     */
    protected function loadRoutes(): void
    {
        $routesPath = $this->getModulePath('routes');

        if (! file_exists($routesPath.'/web.php')) {
            return;
        }

        if ($this->isPlatformActive()) {
            // SaaS mode: Only load on tenant subdomains using domain constraint
            $platformDomain = config('aero.platform_domain', 'localhost');

            Route::domain('{tenant}.'.$platformDomain)
                ->middleware([
                    'web',
                    InitializeTenancyIfNotCentral::class,
                    'tenant',
                ])->group($routesPath.'/web.php');
        } else {
            Route::middleware(['web'])->group($routesPath.'/web.php');
        }
    }

    /**
     * Boot the core module.
     */
    protected function bootModule(): void
    {
        // Register middleware
        $this->registerMiddleware();

        // Register model observers
        User::observe(UserQuotaObserver::class);

        // Register policies
        $this->registerPolicies();

        // Register commands
        $this->registerCommands();

        // Register Core dashboards in the DashboardRegistry
        $this->registerDashboards();

        // Register navigation items (including self-service items)
        $this->registerNavigation();
    }

    /**
     * Register Core dashboards.
     */
    protected function registerDashboards(): void
    {
        if (! $this->app->bound(DashboardRegistry::class)) {
            return;
        }

        $registry = $this->app->make(DashboardRegistry::class);

        // Register Core Dashboard - route name is 'core.dashboard' (maps to /dashboard)
        $registry->registerMany([
            [
                'route' => 'core.dashboard',
                'label' => 'Core Dashboard',
                'module' => 'core',
                'description' => 'System overview for administrators',
                'icon' => 'HomeIcon',
            ],
        ]);
    }

    /**
     * Register core commands.
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                // aero:sync-module provided solely by aero-hrmac now.
                SyncModuleMigrations::class,
                SeedCommand::class,
                // Phase 1: Installation metadata & tagging
                TagMigrationsCommand::class,
                // Phase 2: Module verification & registry
                VerifyModulesCommand::class,
                SyncModuleRegistryCommand::class,
            ]);
        }
    }

    /**
     * Register core middleware.
     */
    protected function registerMiddleware(): void
    {
        $router = $this->app['router'];

        // Register route middleware aliases
        // Note: 'auth' middleware is provided by Laravel by default
        $router->aliasMiddleware('module.access', ModuleAccessMiddleware::class);
        $router->aliasMiddleware('permission', PermissionMiddleware::class);
        $router->aliasMiddleware('role', EnsureUserHasRole::class);
        $router->aliasMiddleware('prevent.installed', PreventInstalledAccess::class);
        $router->aliasMiddleware('dashboard.redirect', DashboardRedirectMiddleware::class);

        // Note: BootstrapGuard is registered globally in register() method for standalone mode.
        // No additional installation middleware is needed in the web group.
    }

    /**
     * Register core policies.
     */
    protected function registerPolicies(): void
    {
        // Register policies if AuthServiceProvider exists
        if (class_exists('\Illuminate\Support\Facades\Gate')) {
            Gate::policy(
                User::class,
                UserPolicy::class
            );

            Gate::policy(
                Role::class,
                RolePolicy::class
            );
        }
    }
}
