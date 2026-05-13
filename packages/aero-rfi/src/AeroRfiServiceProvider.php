<?php

namespace Aero\Rfi;

use Aero\Core\Http\Middleware\InitializeTenancyIfNotCentral;
use Aero\Core\Services\DashboardRegistry;
use Aero\Platform\AeroPlatformServiceProvider;
use Aero\Rfi\Contracts\NcrBlockingServiceInterface;
use Aero\Rfi\Providers\RfiModuleProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * AeroRfiServiceProvider
 *
 * Main service provider for the RFI package.
 * Registers the module service provider which handles navigation, policies, etc.
 */
class AeroRfiServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register the RFI module service provider
        $this->app->register(RfiModuleProvider::class);

        // Register module configuration
        $this->mergeConfigFrom(
            __DIR__.'/../config/rfi.php',
            'rfi'
        );

        // Register module definitions
        $this->mergeConfigFrom(
            __DIR__.'/../config/module.php',
            'modules.rfi'
        );

        // Register fallback for NcrBlockingServiceInterface if Quality package is not installed
        $this->registerQualityFallback();
    }

    /**
     * Register a fallback implementation for Quality package interfaces.
     * This allows RFI to work without the Quality package installed.
     */
    protected function registerQualityFallback(): void
    {
        $interfaceName = NcrBlockingServiceInterface::class;

        // Only register fallback if not already bound
        if (! $this->app->bound($interfaceName)) {
            $this->app->singleton($interfaceName, function () {
                // Return a null implementation that doesn't block anything
                return new class implements NcrBlockingServiceInterface
                {
                    public function getBlockingNcrsInRange(int $projectId, float $startChainage, float $endChainage): array
                    {
                        return [];
                    }

                    public function hasBlockingNcrs(int $projectId, float $startChainage, float $endChainage): bool
                    {
                        return false;
                    }
                };
            });
        }
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Load views (if any - for email templates, PDFs, etc.)
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'rfi');

        // Register routes
        $this->registerRoutes();

        // Register RFI dashboards
        $this->registerDashboards();

        // Publish configuration
        $this->publishes([
            __DIR__.'/../config/rfi.php' => config_path('rfi.php'),
        ], 'aero-rfi-config');

        // NOTE: Frontend is handled by aero/ui package
        // This package is backend-only (controllers, models, services)
    }

    /**
     * Register module routes.
     *
     * Route Architecture:
     * -------------------
     * aero-rfi has exactly 1 route file: web.php
     * Contains all RFI routes under /rfi prefix with rfi.* naming.
     *
     * Domain-based routing:
     * - In SaaS mode: Routes ONLY on tenant domains (tenant.domain.com/rfi/*)
     * - In Standalone mode: Routes on main domain (domain.com/rfi/*)
     */
    protected function registerRoutes(): void
    {
        $routesPath = __DIR__.'/../routes';

        if (function_exists('is_saas_mode') && is_saas_mode()) {
            $platformDomain = env('PLATFORM_DOMAIN', env('APP_DOMAIN', 'localhost'));

            Route::domain('{tenant}.'.$platformDomain)
                ->middleware([
                    'web',
                    InitializeTenancyIfNotCentral::class,
                    'tenant',
                ])
                ->prefix('rfi')
                ->name('rfi.')
                ->group($routesPath.'/web.php');
        } else {
            Route::middleware(['web'])
                ->prefix('rfi')
                ->name('rfi.')
                ->group($routesPath.'/web.php');
        }

        // ========================================================================
        // PATENTABLE CORE IP - API Routes for GPS & Layer Continuity Validation
        // ========================================================================
        $this->registerApiRoutes();
    }

    /**
     * Register API routes for patentable features.
     *
     * These routes provide:
     * - GPS validation for anti-fraud location verification
     * - Layer continuity validation for construction sequence enforcement
     * - AI-powered work location suggestions
     */
    protected function registerApiRoutes(): void
    {
        $apiRoutesPath = __DIR__.'/../routes/api.php';

        if (! file_exists($apiRoutesPath)) {
            return;
        }

        if (function_exists('is_saas_mode') && is_saas_mode()) {
            $platformDomain = env('PLATFORM_DOMAIN', env('APP_DOMAIN', 'localhost'));

            Route::domain('{tenant}.'.$platformDomain)
                ->middleware([
                    'api',
                    InitializeTenancyIfNotCentral::class,
                    'tenant',
                ])->group($apiRoutesPath);
        } else {
            Route::middleware(['api'])
                ->group($apiRoutesPath);
        }
    }

    /**
     * Check if aero-platform is active.
     *
     * @deprecated Use global isPlatformActive() or is_saas_mode() helper instead
     */
    protected function isPlatformActive(): bool
    {
        // Use global helper if available
        if (function_exists('isPlatformActive')) {
            return isPlatformActive();
        }

        return class_exists(AeroPlatformServiceProvider::class);
    }

    /**
     * Check if running in SaaS mode.
     *
     * @deprecated Use global is_saas_mode() helper instead
     */
    protected function isSaaSMode(): bool
    {
        if (function_exists('is_saas_mode')) {
            return is_saas_mode();
        }

        return is_saas_mode();
    }

    /**
     * Register RFI dashboards with DashboardRegistry.
     */
    protected function registerDashboards(): void
    {
        if (! $this->app->bound(DashboardRegistry::class)) {
            return;
        }

        $registry = $this->app->make(DashboardRegistry::class);

        $registry->register(
            'rfi.dashboard',
            'RFI Dashboard',
            'rfi',
            'Site inspections and RFI tracking',
            'MapPinIcon',
            'rfi.dashboard.view'
        );
    }
}
