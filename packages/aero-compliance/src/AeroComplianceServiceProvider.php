<?php

namespace Aero\Compliance;

use Aero\Compliance\Providers\ComplianceModuleProvider;
use Aero\Core\Http\Middleware\InitializeTenancyIfNotCentral;
use Aero\Core\Services\DashboardRegistry;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * AeroComplianceServiceProvider
 *
 * Main service provider for the Compliance package.
 * Registers migrations, module provider, and routes.
 */
class AeroComplianceServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register the Compliance module service provider
        $this->app->register(ComplianceModuleProvider::class);

        // Register module configuration
        $this->mergeConfigFrom(
            __DIR__.'/../config/module.php',
            'modules.compliance'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Load views (if any - for email templates, PDFs, etc.)
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'compliance');

        // Register routes
        $this->registerRoutes();

        // Register dashboards
        $this->registerDashboards();

        // Publish configuration
        $this->publishes([
            __DIR__.'/../config/module.php' => config_path('modules/compliance.php'),
        ], 'compliance-config');

        // Publish migrations
        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'compliance-migrations');
    }

    /**
     * Register routes.
     */
    protected function registerRoutes(): void
    {
        $isSaas = function_exists('is_saas_mode') && is_saas_mode();
        $platformDomain = env('PLATFORM_DOMAIN', env('APP_DOMAIN', 'localhost'));
        $adminDomain = env('ADMIN_DOMAIN', 'admin.'.$platformDomain);

        // API routes (tenant-scoped)
        if (file_exists(__DIR__.'/../routes/api.php')) {
            $route = Route::middleware(['api'])
                ->prefix('api/compliance')
                ->name('api.compliance.');

            if ($isSaas) {
                $route->domain('{tenant}.'.$platformDomain);
            }

            $route->group(__DIR__.'/../routes/api.php');
        }

        // Web routes (tenant-scoped)
        if (file_exists(__DIR__.'/../routes/tenant.php')) {
            $route = Route::prefix('compliance')
                ->name('compliance.');

            if ($isSaas) {
                $route->domain('{tenant}.'.$platformDomain)
                    ->middleware([
                        'web',
                        InitializeTenancyIfNotCentral::class,
                        'tenant',
                    ]);
            } else {
                $route->middleware(['web']);
            }

            $route->group(__DIR__.'/../routes/tenant.php');
        }

        // Admin routes (landlord) — on admin subdomain only
        if (file_exists(__DIR__.'/../routes/admin.php')) {
            $route = Route::middleware(['web'])
                ->prefix('admin/compliance')
                ->name('admin.compliance.');

            if ($isSaas) {
                $route->domain($adminDomain);
            }

            $route->group(__DIR__.'/../routes/admin.php');
        }
    }

    /**
     * Register Compliance dashboards with DashboardRegistry.
     */
    protected function registerDashboards(): void
    {
        if (! $this->app->bound(DashboardRegistry::class)) {
            return;
        }

        $registry = $this->app->make(DashboardRegistry::class);

        $registry->register(
            'compliance.dashboard',
            'Compliance Dashboard',
            'compliance',
            'Regulatory compliance and HSE tracking',
            'ShieldCheckIcon',
            'compliance.dashboard.view'
        );
    }
}
