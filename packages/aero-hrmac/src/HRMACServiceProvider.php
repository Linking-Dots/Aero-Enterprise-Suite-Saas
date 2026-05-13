<?php

declare(strict_types=1);

namespace Aero\HRMAC;

use Aero\HRMAC\Console\Commands\SyncModuleHierarchy;
use Aero\Contracts\RoleModuleAccessInterface;
use Aero\HRMAC\Http\Middleware\CheckRoleModuleAccess;
use Aero\HRMAC\Http\Middleware\SmartLandingRedirect;
use Aero\HRMAC\Services\ModuleDiscoveryService;
use Aero\HRMAC\Services\RoleModuleAccessService;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class HRMACServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__.'/../config/hrmac.php',
            'hrmac'
        );

        // Register ModuleDiscoveryService as singleton
        $this->app->singleton(ModuleDiscoveryService::class, function ($app) {
            return new ModuleDiscoveryService;
        });

        // Register RoleModuleAccessService as singleton
        $this->app->singleton(RoleModuleAccessInterface::class, function ($app) {
            return new RoleModuleAccessService;
        });

        // Register aliases for convenience
        $this->app->alias(RoleModuleAccessInterface::class, 'hrmac');
        $this->app->alias(RoleModuleAccessInterface::class, RoleModuleAccessService::class);
        $this->app->alias(ModuleDiscoveryService::class, 'hrmac.discovery');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register middleware
        $this->registerMiddleware();

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                SyncModuleHierarchy::class,
            ]);

            // Publish configuration
            $this->publishes([
                __DIR__.'/../config/hrmac.php' => config_path('hrmac.php'),
            ], 'hrmac-config');

            // Publish migrations
            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'hrmac-migrations');
        }
    }

    /**
     * Register middleware aliases.
     */
    protected function registerMiddleware(): void
    {
        /** @var Router $router */
        $router = $this->app->make(Router::class);

        // Register middleware aliases
        // 'hrmac' is the primary alias used in route definitions (e.g., 'hrmac:module.submodule.component.action')
        $router->aliasMiddleware('hrmac', CheckRoleModuleAccess::class);
        // 'role.access' is kept for backwards compatibility
        $router->aliasMiddleware('role.access', CheckRoleModuleAccess::class);
        $router->aliasMiddleware('smart.landing', SmartLandingRedirect::class);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<string>
     */
    public function provides(): array
    {
        return [
            RoleModuleAccessInterface::class,
            RoleModuleAccessService::class,
            'hrmac',
        ];
    }
}
