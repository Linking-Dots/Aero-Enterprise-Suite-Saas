<?php

namespace App\Support\Module;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

/**
 * Base Module Service Provider
 *
 * All module service providers should extend this class.
 * Provides common functionality for module registration, routes, views, etc.
 */
abstract class BaseModuleServiceProvider extends ServiceProvider
{
    /**
     * Module code (unique identifier)
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
     * Register module services
     */
    public function register(): void
    {
        $this->registerConfig();
        $this->registerServices();
        $this->registerCommands();
    }

    /**
     * Bootstrap module services
     */
    public function boot(): void
    {
        $this->bootRoutes();
        $this->bootMigrations();
        $this->bootTranslations();
        $this->bootViews();
        $this->bootPublishing();
    }

    /**
     * Register module configuration
     */
    protected function registerConfig(): void
    {
        $configPath = $this->modulePath . '/Config/config.php';
        
        if (file_exists($configPath)) {
            $this->mergeConfigFrom($configPath, $this->moduleCode);
        }
    }

    /**
     * Register module services
     */
    protected function registerServices(): void
    {
        // Override in child class to register module-specific services
    }

    /**
     * Register module commands
     */
    protected function registerCommands(): void
    {
        // Override in child class to register module-specific commands
    }

    /**
     * Boot module routes
     */
    protected function bootRoutes(): void
    {
        // Load web routes
        $webRoutesPath = $this->modulePath . '/Routes/web.php';
        if (file_exists($webRoutesPath)) {
            Route::middleware('web')
                ->namespace($this->moduleNamespace . '\\Http\\Controllers')
                ->group($webRoutesPath);
        }

        // Load API routes
        $apiRoutesPath = $this->modulePath . '/Routes/api.php';
        if (file_exists($apiRoutesPath)) {
            Route::prefix('api')
                ->middleware('api')
                ->namespace($this->moduleNamespace . '\\Http\\Controllers\\Api')
                ->group($apiRoutesPath);
        }

        // Load tenant routes
        $tenantRoutesPath = $this->modulePath . '/Routes/tenant.php';
        if (file_exists($tenantRoutesPath)) {
            Route::middleware(['web', 'tenant'])
                ->namespace($this->moduleNamespace . '\\Http\\Controllers')
                ->group($tenantRoutesPath);
        }
    }

    /**
     * Boot module migrations
     */
    protected function bootMigrations(): void
    {
        $migrationsPath = $this->modulePath . '/Database/Migrations';
        
        if (is_dir($migrationsPath)) {
            $this->loadMigrationsFrom($migrationsPath);
        }
    }

    /**
     * Boot module translations
     */
    protected function bootTranslations(): void
    {
        $langPath = $this->modulePath . '/Resources/lang';
        
        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleCode);
        }
    }

    /**
     * Boot module views
     */
    protected function bootViews(): void
    {
        $viewsPath = $this->modulePath . '/Resources/views';
        
        if (is_dir($viewsPath)) {
            $this->loadViewsFrom($viewsPath, $this->moduleCode);
        }
    }

    /**
     * Boot module publishing
     */
    protected function bootPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            // Publish config
            $configPath = $this->modulePath . '/Config/config.php';
            if (file_exists($configPath)) {
                $this->publishes([
                    $configPath => config_path("{$this->moduleCode}.php"),
                ], "{$this->moduleCode}-config");
            }

            // Publish migrations
            $migrationsPath = $this->modulePath . '/Database/Migrations';
            if (is_dir($migrationsPath)) {
                $this->publishes([
                    $migrationsPath => database_path('migrations'),
                ], "{$this->moduleCode}-migrations");
            }

            // Publish views
            $viewsPath = $this->modulePath . '/Resources/views';
            if (is_dir($viewsPath)) {
                $this->publishes([
                    $viewsPath => resource_path("views/vendor/{$this->moduleCode}"),
                ], "{$this->moduleCode}-views");
            }

            // Publish assets
            $assetsPath = $this->modulePath . '/Resources/assets';
            if (is_dir($assetsPath)) {
                $this->publishes([
                    $assetsPath => public_path("vendor/{$this->moduleCode}"),
                ], "{$this->moduleCode}-assets");
            }
        }
    }

    /**
     * Get module code
     */
    public function getModuleCode(): string
    {
        return $this->moduleCode;
    }

    /**
     * Get module path
     */
    public function getModulePath(): string
    {
        return $this->modulePath;
    }

    /**
     * Get module namespace
     */
    public function getModuleNamespace(): string
    {
        return $this->moduleNamespace;
    }
}
