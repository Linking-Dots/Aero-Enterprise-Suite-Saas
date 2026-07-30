<?php

namespace App\Providers;

use App\Support\Module\Module;
use App\Support\Module\ModuleLoader;
use App\Support\Module\ModuleRegistry;
use Illuminate\Support\ServiceProvider;

/**
 * Module Service Provider
 *
 * Registers the module system with the application.
 * Handles module discovery, registration, and loading.
 */
class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register module registry as singleton
        $this->app->singleton(ModuleRegistry::class, function ($app) {
            $registry = new ModuleRegistry();
            
            // Discover modules on registration
            if (!$app->runningInConsole() || $app->runningUnitTests()) {
                $registry->discover();
            }
            
            return $registry;
        });

        // Register module loader
        $this->app->singleton(ModuleLoader::class, function ($app) {
            return new ModuleLoader(
                $app,
                $app->make(ModuleRegistry::class)
            );
        });

        // Add helper functions
        $this->registerHelpers();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load modules if not in console mode (except for specific commands)
        if (!$this->app->runningInConsole() || $this->app->runningUnitTests()) {
            $this->loadModules();
        }

        // Register module commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\Module\MakeModuleCommand::class,
                \App\Console\Commands\Module\ModuleDiscoverCommand::class,
                \App\Console\Commands\Module\ModuleListCommand::class,
            ]);
        }
    }

    /**
     * Load all enabled modules
     */
    protected function loadModules(): void
    {
        try {
            $loader = $this->app->make(ModuleLoader::class);
            
            // In tenant context, load tenant-specific modules
            if (tenancy()->initialized) {
                $tenant = tenant();
                if ($tenant) {
                    $loader->loadForTenant($tenant);
                }
            } else {
                // In landlord context or standalone, load all enabled modules
                $loader->loadAll();
            }
        } catch (\Exception $e) {
            // Log error but don't break the application
            logger()->error('Failed to load modules: ' . $e->getMessage());
        }
    }

    /**
     * Register helper functions
     */
    protected function registerHelpers(): void
    {
        if (!function_exists('module')) {
            /**
             * Get a module by code
             */
            function module(string $code): ?Module {
                $registry = app(ModuleRegistry::class);
                return $registry->get($code);
            }
        }

        if (!function_exists('modules')) {
            /**
             * Get module registry
             */
            function modules(): ModuleRegistry {
                return app(ModuleRegistry::class);
            }
        }
    }
}
