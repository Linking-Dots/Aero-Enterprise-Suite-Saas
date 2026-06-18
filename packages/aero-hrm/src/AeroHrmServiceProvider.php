<?php

namespace Aero\HRM;

use Aero\Core\Http\Middleware\InitializeTenancyIfNotCentral;
use Aero\Core\Services\DashboardRegistry;
use Aero\Contracts\AeroMode;
use Aero\HRM\Providers\HRMServiceProvider;
use Aero\HRM\Providers\HRMServiceProvider as ModuleServiceProvider;
use Aero\HRM\Services\Attendance\AttendanceClockService;
use Aero\HRM\Services\Attendance\OvertimeApprovalService;
use Aero\HRM\Services\Attendance\ShiftSwapService;
use Aero\HRM\Services\Attendance\TimesheetAggregator;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * AeroHrmServiceProvider
 *
 * Main service provider for the HRM package.
 * Registers the module service provider which handles navigation, policies, etc.
 */
class AeroHrmServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register the HRM module service provider
        $this->app->register(ModuleServiceProvider::class);

        // Register module configuration
        $this->mergeConfigFrom(
            __DIR__.'/../config/hrm.php',
            'hrm'
        );

        // Module definitions are in config/module.php and loaded by ModuleDiscoveryService

        // Attendance services
        $this->app->singleton(AttendanceClockService::class);
        $this->app->singleton(OvertimeApprovalService::class);
        $this->app->singleton(TimesheetAggregator::class);
        $this->app->singleton(ShiftSwapService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');


        // Register routes
        $this->registerRoutes();

        // Register HRM dashboards
        $this->registerDashboards();

        // Publish compiled module library (ES module for runtime loading)
        // Built to dist/ directory via npm run build
        $moduleLibrary = __DIR__.'/../dist';
        if (is_dir($moduleLibrary)) {
            $this->publishes([
                $moduleLibrary => public_path('modules/aero-hrm'),
            ], 'aero-hrm-assets');
        }

        // Publish configuration
        $this->publishes([
            __DIR__.'/../config/hrm.php' => config_path('hrm.php'),
        ], 'aero-hrm-config');
    }

    /**
     * Register module routes.
     *
     * Route Architecture:
     * -------------------
     * aero-hrm has exactly 1 route file: web.php
     * Contains all HRM routes under /hrm prefix with hrm.* naming.
     *
     * Domain-based routing:
     * - In SaaS mode: Routes ONLY on tenant domains (tenant.domain.com/hrm/*)
     * - In Standalone mode: Routes on main domain (domain.com/hrm/*)
     */
    protected function registerRoutes(): void
    {
        // HRMServiceProvider (via AbstractModuleProvider) already loads routes
        // with proper domain constraints. Skip here to prevent duplicate registration.
        if ($this->app->providerIsLoaded(HRMServiceProvider::class)) {
            return;
        }

        $routesPath = __DIR__.'/../routes';

        if ($this->isPlatformActive() && $this->isSaaSMode()) {
            $platformDomain = config('aero.platform_domain', 'localhost');

            Route::domain('{tenant}.'.$platformDomain)
                ->middleware([
                    'web',
                    InitializeTenancyIfNotCentral::class,
                    'tenant',
                ])
                ->prefix('hrm')
                ->name('hrm.')
                ->group($routesPath.'/web.php');
        } else {
            Route::middleware(['web', 'auth'])
                ->prefix('hrm')
                ->name('hrm.')
                ->group($routesPath.'/web.php');
        }
    }

    /**
     * Check if running in SaaS mode (multi-tenant).
     */
    protected function isPlatformActive(): bool
    {
        return AeroMode::isSaas();
    }

    /**
     * Check if running in SaaS mode.
     */
    protected function isSaaSMode(): bool
    {
        return is_saas_mode();
    }

    /**
     * Register HRM dashboards with DashboardRegistry.
     */
    protected function registerDashboards(): void
    {
        // Only register if DashboardRegistry is available
        if (! $this->app->bound(DashboardRegistry::class)) {
            return;
        }

        $registry = $this->app->make(DashboardRegistry::class);

        // Register HRM Dashboard (for HR Managers and Staff)
        $registry->register(
            'hrm.dashboard',
            'HRM Dashboard',
            'hrm',
            'Full HR analytics for HR Managers and Staff',
            'UserGroupIcon',
            'hrm.dashboard.view'
        );

        // Register Employee Dashboard (for regular employees)
        $registry->register(
            'hrm.employee.dashboard',
            'Employee Dashboard',
            'hrm',
            'Personal dashboard for employees (leaves, attendance, payslips)',
            'UserIcon',
            'hrm.employee.dashboard.view'
        );
    }
}
