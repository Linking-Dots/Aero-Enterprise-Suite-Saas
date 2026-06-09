<?php

namespace Aero\Project;

use Aero\Core\Http\Middleware\InitializeTenancyIfNotCentral;
use Aero\Core\Services\DashboardRegistry;
use Aero\Project\Adapters\DepartmentResolverAdapter;
use Aero\Project\Adapters\ProjectAuthorizationAdapter;
use Aero\Project\Adapters\UserResolverAdapter;
use Aero\Project\Contracts\DepartmentResolverContract;
use Aero\Project\Contracts\ProjectAuthorizationContract;
use Aero\Project\Contracts\UserResolverContract;
use Aero\Project\Http\Middleware\ProjectHrmacMiddleware;
use Aero\Project\Http\Middleware\ProjectMemberMiddleware;
use Aero\Project\Providers\ProjectModuleProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * AeroProjectServiceProvider
 *
 * Main service provider for the Project Management package.
 * Registers the module service provider which handles navigation, policies, etc.
 *
 * ARCHITECTURAL PRINCIPLE: This package is department-agnostic and uses
 * service contracts for external data (departments, users) to maintain
 * package isolation. All authorization flows through HRMAC.
 */
class AeroProjectServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register the Project module service provider
        $this->app->register(ProjectModuleProvider::class);

        // Register module configuration
        $configPath = __DIR__.'/../config/module.php';
        if (file_exists($configPath)) {
            $this->mergeConfigFrom($configPath, 'modules.project');
        }

        // Register project types configuration
        $projectTypesPath = __DIR__.'/../config/project_types.php';
        if (file_exists($projectTypesPath)) {
            $this->mergeConfigFrom($projectTypesPath, 'project.types');
        }

        // Register service contracts with their default adapters
        $this->registerServiceContracts();
    }

    /**
     * Register service contracts (adapters).
     *
     * These can be overridden by other packages (e.g., HRM can provide
     * a richer DepartmentResolver implementation).
     */
    protected function registerServiceContracts(): void
    {
        // Department resolver - default uses DB table directly
        // Can be overridden by HRM package
        $this->app->singleton(DepartmentResolverContract::class, function ($app) {
            return new DepartmentResolverAdapter;
        });

        // User resolver - default uses DB table directly
        // Can be overridden by Core package
        $this->app->singleton(UserResolverContract::class, function ($app) {
            return new UserResolverAdapter;
        });

        // Project authorization - HRMAC integration
        $this->app->singleton(ProjectAuthorizationContract::class, function ($app) {
            return new ProjectAuthorizationAdapter;
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register middleware aliases
        $this->registerMiddleware();

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');


        // Register routes
        $this->registerRoutes();

        // Register dashboards
        $this->registerDashboards();

        // Publish configuration
        $this->publishConfigurations();
    }

    /**
     * Register middleware aliases.
     */
    protected function registerMiddleware(): void
    {
        $router = $this->app['router'];

        // HRMAC-based project authorization middleware
        $router->aliasMiddleware('project.hrmac', ProjectHrmacMiddleware::class);

        // Project membership middleware
        $router->aliasMiddleware('project.member', ProjectMemberMiddleware::class);
    }

    /**
     * Publish package configurations.
     */
    protected function publishConfigurations(): void
    {
        $configPath = __DIR__.'/../config/module.php';
        $projectTypesPath = __DIR__.'/../config/project_types.php';

        $publishes = [];

        if (file_exists($configPath)) {
            $publishes[$configPath] = config_path('aero-project.php');
        }

        if (file_exists($projectTypesPath)) {
            $publishes[$projectTypesPath] = config_path('project_types.php');
        }

        if (! empty($publishes)) {
            $this->publishes($publishes, 'aero-project-config');
        }
    }

    /**
     * Register module routes.
     */
    protected function registerRoutes(): void
    {
        $webRoutesPath = __DIR__.'/../routes/web.php';
        $apiRoutesPath = __DIR__.'/../routes/api.php';

        // Web routes
        if (file_exists($webRoutesPath)) {
            if (function_exists('is_saas_mode') && is_saas_mode()) {
                $platformDomain = env('PLATFORM_DOMAIN', env('APP_DOMAIN', 'localhost'));

                Route::domain('{tenant}.'.$platformDomain)
                    ->middleware([
                        'web',
                        InitializeTenancyIfNotCentral::class,
                        'tenant',
                    ])->group($webRoutesPath);
            } else {
                Route::middleware(['web'])->group($webRoutesPath);
            }
        }

        // API routes
        if (file_exists($apiRoutesPath)) {
            if (function_exists('is_saas_mode') && is_saas_mode()) {
                $platformDomain = $platformDomain ?? env('PLATFORM_DOMAIN', env('APP_DOMAIN', 'localhost'));

                Route::domain('{tenant}.'.$platformDomain)
                    ->middleware([
                        'api',
                        InitializeTenancyIfNotCentral::class,
                        'tenant',
                    ])->group($apiRoutesPath);
            } else {
                Route::middleware(['api'])->group($apiRoutesPath);
            }
        }
    }

    /**
     * Register Project dashboards with DashboardRegistry.
     */
    protected function registerDashboards(): void
    {
        if (! $this->app->bound(DashboardRegistry::class)) {
            return;
        }

        $registry = $this->app->make(DashboardRegistry::class);

        $registry->register(
            'project.dashboard',
            'Project Dashboard',
            'project',
            'Project management and tracking',
            'BriefcaseIcon',
            'project.dashboard.view'
        );
    }
}
