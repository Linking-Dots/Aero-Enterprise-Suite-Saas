<?php

namespace Aero\Platform;

use Aero\Auth\Context\TenantAuthContext;
use Aero\Auth\Contracts\AuthContext;
use Aero\Core\Contracts\TenantScopeInterface;
use Aero\Core\Services\NavigationRegistry;
use Aero\Core\Traits\ParsesHostDomain;
use Aero\HRMAC\Services\RoleModuleAccessService as HRMACRoleModuleAccessService;
use Aero\I18n\Http\Middleware\SetLocale;
use Aero\Platform\Auth\LandlordAuthContext;
use Aero\Platform\Bootstrappers\CachePrefixTenancyBootstrapper;
use Aero\Platform\Console\Commands\CleanupFailedInstallation;
use Aero\Platform\Console\Commands\EnsureSuperAdmin;
use Aero\Platform\Console\Commands\ExpireGracePeriods;
use Aero\Platform\Console\Commands\ProcessPendingSubscriptionChanges;
use Aero\Platform\Console\Commands\ProcessSubscriptionRenewals;
use Aero\Platform\Console\Commands\PurgeExpiredTenants;
use Aero\Platform\Console\Commands\SetupApplication;
use Aero\Platform\Console\Commands\TenantCreate;
use Aero\Platform\Console\Commands\TenantFlush;
use Aero\Platform\Console\Commands\TenantHealth;
use Aero\Platform\Console\Commands\TenantMigrate;
use Aero\Platform\Http\Middleware\BootstrapGuard;
use Aero\Platform\Http\Middleware\CheckMaintenanceMode;
use Aero\Platform\Http\Middleware\CheckModuleAccess;
use Aero\Platform\Http\Middleware\CheckModuleSubscription;
use Aero\Platform\Http\Middleware\CheckRoleModuleAccess;
use Aero\Platform\Http\Middleware\EnforceSubscription;
use Aero\Platform\Http\Middleware\EnforceTenantQuotas;
use Aero\Platform\Http\Middleware\EnsureAdminDomain;
use Aero\Platform\Http\Middleware\EnsureLandlordGuard;
use Aero\Platform\Http\Middleware\EnsurePlatformDomain;
use Aero\Platform\Http\Middleware\EnsureTenantIsActive;
use Aero\Platform\Http\Middleware\EnsureTenantIsSetup;
use Aero\Platform\Http\Middleware\EnsureUserHasRole;
use Aero\Platform\Http\Middleware\ForceFileSessionForInstallation;
use Aero\Platform\Http\Middleware\HandleInertiaRequests;
use Aero\Platform\Http\Middleware\IdentifyDomainContext;
use Aero\Platform\Http\Middleware\ModuleAccessMiddleware;
use Aero\Platform\Http\Middleware\PermissionMiddleware;
use Aero\Platform\Http\Middleware\PlatformSuperAdmin;
use Aero\Platform\Http\Middleware\RequireSaasMode;
use Aero\Platform\Http\Middleware\RequireTenantOnboarding;
use Aero\Platform\Http\Middleware\SetDatabaseConnectionFromDomain;
use Aero\Platform\Http\Middleware\SmartLandingRedirect;
use Aero\Platform\Http\Middleware\TenantSuperAdmin;
use Aero\Platform\Http\Middleware\TrustHosts;
use Aero\Platform\Listeners\TenantCreatedListener;
use Aero\Platform\Models\LandlordUser;
use Aero\Platform\Models\Plan;
use Aero\Platform\Models\Tenant;
use Aero\Platform\Models\Subscription;
use Aero\Platform\Observers\PlanAuditObserver;
use Aero\Platform\Observers\SubscriptionObserver;
use Aero\Platform\Policies\PlanPolicy;
use Aero\Platform\Policies\TenantPolicy;
use Aero\Platform\Providers\TenancyBootstrapServiceProvider;
use Aero\Platform\Services\Billing\SslCommerzService;
use Aero\Platform\Services\Module\ModuleAccessService;
use Aero\Platform\Services\Module\NullRoleModuleAccessService;
use Aero\Platform\Services\Module\RoleModuleAccessService;
use Aero\Platform\Services\Monitoring\Tenant\ErrorLogService;
use Aero\Platform\Services\PlatformSettingService;
use Aero\Platform\Services\PlatformWidgetRegistry;
use Aero\Platform\Services\SaaSTenantScope;
use Aero\Notifications\Contracts\MailContextResolver;
use Aero\Notifications\Contracts\SmsContextResolver;
use Aero\Platform\Services\Notifications\PlatformMailContextResolver;
use Aero\Platform\Services\Notifications\PlatformSmsContextResolver;
use Aero\Platform\Services\Tenant\TenantPurgeService;
use Aero\Platform\Services\Tenant\TenantRetentionService;
use Aero\Platform\Widgets\BillingOverviewWidget;
use Aero\Platform\Widgets\ModuleUsageWidget;
use Aero\Platform\Widgets\PlatformStatsWidget;
use Aero\Platform\Widgets\QuickActionsWidget;
use Aero\Platform\Widgets\RecentActivityWidget;
use Aero\Platform\Widgets\RecentTenantsWidget;
use Aero\Platform\Widgets\SubscriptionDistributionWidget;
use Aero\Platform\Widgets\SystemAlertsWidget;
use Aero\Platform\Widgets\SystemHealthWidget;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;
use Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper;
use Stancl\Tenancy\Bootstrappers\QueueTenancyBootstrapper;
use Stancl\Tenancy\Events\TenantCreated;

/**
 * Aero Platform Service Provider
 *
 * Registers all Aero Platform services, routes, middleware, and assets.
 * This package provides multi-tenancy orchestration, landlord authentication,
 * billing/subscriptions, and platform administration.
 *
 * All configuration is handled programmatically - the host app remains clean.
 */
class AeroPlatformServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Override the AuthContext binding with a domain-aware closure.
        // On admin subdomain requests → LandlordAuthContext (landlord guard).
        // On tenant subdomain requests → TenantAuthContext (web guard).
        $this->app->bind(AuthContext::class, function ($app) {
            $request = $app->make('request');
            $host = $request->getHost();
            $adminHost = config('aero.admin_domain', 'admin.'.config('app.domain', parse_url(config('app.url', ''), PHP_URL_HOST)));

            return str_starts_with($host, 'admin.') || $host === $adminHost
                ? new LandlordAuthContext
                : new TenantAuthContext;
        });

        // Register global BootstrapGuard middleware FIRST
        // This runs before route matching and handles:
        // 1. Installation status check
        // 2. Cross-domain redirect to platform /install if not installed
        $kernel = $this->app->make(Kernel::class);
        $kernel->pushMiddleware(BootstrapGuard::class);

        // CRITICAL: Only register tenancy if installed AND in SaaS mode
        // This prevents tenancy from being enabled during installation
        // or in standalone mode
        if ($this->installed() && $this->isSaasMode()) {
            // Register the TenancyBootstrapServiceProvider
            // CRITICAL: This registers event listeners for TenancyInitialized which
            // runs the DatabaseTenancyBootstrapper to switch DB connections
            $this->app->register(TenancyBootstrapServiceProvider::class);
        }

        // Override Core's migrator to ONLY use platform migrations on landlord database
        // Core, HRM, CRM and other module migrations are for TENANT databases only
        $this->overrideMigratorForLandlord();

        // Merge platform configs
        // Module definitions are in config/module.php and loaded by ModuleDiscoveryService
        $this->mergeConfigFrom(__DIR__.'/../config/tenancy.php', 'tenancy');
        $this->mergeConfigFrom(__DIR__.'/../config/platform.php', 'platform');

        // Merge audit logging config into Laravel's logging channels
        $auditConfig = require __DIR__.'/../config/audit.php';
        config(['logging.channels.audit' => $auditConfig['audit']]);

        // Override TenantScopeInterface binding (Core binds StandaloneTenantScope by default)
        // Platform provides the SaaS implementation using stancl/tenancy
        $this->app->singleton(TenantScopeInterface::class, SaaSTenantScope::class);

        // Register Module Access Services with fallback stubs for pre-install
        // These services are lazy-loaded to avoid DB queries before installation
        $this->app->singleton(HRMACRoleModuleAccessService::class, function ($app) {
            // Before installation: return a null-object stub that satisfies the
            // RoleModuleAccessService type hint without making any DB queries.
            if (! file_exists(storage_path('app/aeos.installed'))) {
                return new NullRoleModuleAccessService;
            }

            try {
                return new HRMACRoleModuleAccessService;
            } catch (\Throwable $e) {
                return new NullRoleModuleAccessService;
            }
        });

        // Alias Platform's RoleModuleAccessService to HRMAC's for backward compatibility
        $this->app->alias(HRMACRoleModuleAccessService::class, RoleModuleAccessService::class);

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
                return new ModuleAccessService($app->make(RoleModuleAccessService::class));
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

        $this->app->singleton(PlatformSettingService::class, function ($app) {
            return new PlatformSettingService;
        });

        $this->app->singleton(ErrorLogService::class);
        $this->app->singleton(SslCommerzService::class);

        // Register tenant lifecycle services
        $this->app->singleton(TenantRetentionService::class);
        $this->app->singleton(TenantPurgeService::class);

        // Register Platform's own widget registry (independent from Core)
        $this->app->singleton(PlatformWidgetRegistry::class);

        // Bind notification context resolvers to platform implementations
        $this->app->singleton(MailContextResolver::class, PlatformMailContextResolver::class);
        $this->app->singleton(SmsContextResolver::class, PlatformSmsContextResolver::class);

        // Configure auth guards and providers programmatically
        $this->configureAuth();

        // Configure database connections programmatically
        $this->configureDatabase();

        // Register event listeners for tenant lifecycle
        $this->registerEventListeners();

        // Configure trusted hosts for security (prevent Host header spoofing)
        $this->configureTrustedHosts();

        // Register platform model policies
        $this->registerPolicies();
    }

    /**
     * Register platform model policies.
     */
    protected function registerPolicies(): void
    {
        if (class_exists('\Illuminate\Support\Facades\Gate')) {
            Gate::policy(
                Plan::class,
                PlanPolicy::class
            );

            Gate::policy(
                Tenant::class,
                TenantPolicy::class
            );
        }
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register audit observers
        Plan::observe(PlanAuditObserver::class);
        Subscription::observe(SubscriptionObserver::class);

        // Configure Cashier to use our unified Subscription model as the single source of truth
        // This eliminates drift between Cashier-managed Stripe data and lifecycle-managed fields.
        Cashier::useSubscriptionModel(\Aero\Platform\Models\Subscription::class);
        Cashier::useCustomerModel(\Aero\Platform\Models\Tenant::class);

        // Override tenancy bootstrappers after all providers registered
        // FilesystemTenancyBootstrapper disabled - causes "Undefined array key 'local'" error
        // Using custom CachePrefixTenancyBootstrapper instead of stancl's CacheTenancyBootstrapper
        // because file/database cache drivers don't support tagging
        Config::set('tenancy.bootstrappers', [
            DatabaseTenancyBootstrapper::class,
            CachePrefixTenancyBootstrapper::class, // Works with all cache drivers
            QueueTenancyBootstrapper::class,
        ]);

        // CRITICAL: Override central_domains to include the platform domain and admin subdomain
        // This ensures stancl/tenancy recognizes these as central (non-tenant) domains
        // Host app's config/tenancy.php may have hardcoded values that need to be overridden
        $this->configureCentralDomains();

        // Force HTTPS for all generated URLs when APP_URL uses https
        if (str_starts_with(config('app.url', ''), 'https://')) {
            URL::forceScheme('https');
        }

        // ONLY register platform routes, middleware, and features in SaaS mode
        // In standalone mode, the platform package might be installed but should not interfere
        if ($this->installed() && $this->isSaasMode()) {
            // Configure guest redirect for authentication middleware
            $this->configureGuestRedirect();

            // Register platform routes (admin + public platform routes)
            $this->registerRoutes();

            // Register platform middleware (HandleInertiaRequests, IdentifyDomainContext, etc.)
            $this->registerMiddleware();
        }

        // Load platform migrations for landlord database (always needed if package is installed)
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Always load installation wizard routes (needed during installation)
        $installationRoutes = __DIR__.'/../routes/installation.php';
        if (file_exists($installationRoutes)) {
            Route::middleware(['web', ForceFileSessionForInstallation::class])
                ->group($installationRoutes);
        }

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                PurgeExpiredTenants::class,
            ]);
        }

        // Publish assets
        $this->registerPublishing();

        // Register views (for email templates, etc.)
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'aero-platform');

        // NOTE: Vite configuration is handled by aero/ui package
        // All frontend code lives in aero/ui - this package is backend-only

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                TenantCreate::class,
                TenantMigrate::class,
                TenantFlush::class,
                TenantHealth::class,
                EnsureSuperAdmin::class,
                SetupApplication::class,
                CleanupFailedInstallation::class,
                ProcessPendingSubscriptionChanges::class,
                ProcessSubscriptionRenewals::class,
                ExpireGracePeriods::class,
            ]);
        }

        // Register platform navigation with NavigationRegistry
        // This allows HandleInertiaRequests to share navigation via Inertia props
        $this->registerPlatformNavigation();

        // Register Platform dashboard widgets
        // These appear on the Admin Dashboard following the Core pattern
        $this->registerPlatformDashboardWidgets();
    }

    /**
     * Register Platform widgets for the Admin Dashboard.
     *
     * Platform widgets are widgets that appear on the admin dashboard:
     * - Welcome widget (position: welcome)
     * - Platform stats widget (position: stats_row)
     * - Recent tenants widget (position: main_left)
     * - Module usage widget (position: main_left)
     * - Recent activity widget (position: main_left)
     * - Quick actions widget (position: main_left)
     * - System alerts widget (position: sidebar)
     * - Subscription distribution widget (position: sidebar)
     * - System health widget (position: sidebar)
     * - Billing overview widget (position: main_right)
     */
    protected function registerPlatformDashboardWidgets(): void
    {
        // Use Platform's own registry, NOT Core's DashboardWidgetRegistry
        $registry = $this->app->make(PlatformWidgetRegistry::class);

        // Register Platform widgets (order matters for display)
        $registry->registerMany([
            // Stats row (full width grid)
            new PlatformStatsWidget,

            // Main content area (left 2/3)
            new RecentTenantsWidget,
            new ModuleUsageWidget,
            new RecentActivityWidget,
            new QuickActionsWidget,

            // Main content area (right 1/3)
            new BillingOverviewWidget,

            // Sidebar area
            new SystemAlertsWidget,
            new SubscriptionDistributionWidget,
            new SystemHealthWidget,
        ]);
    }

    /**
     * Register platform navigation items from config/module.php.
     *
     * Structure: Module → Submodules → Components
     * - Submodules become top-level menu items (flattened by NavigationRegistry)
     * - If submodule has only ONE component, that component becomes the menu item directly
     * - If submodule has multiple components, they become submenu items
     * - Icons are inherited from parent if not specified
     */
    protected function registerPlatformNavigation(): void
    {
        // Only register if NavigationRegistry is bound (Core is loaded)
        if (! $this->app->bound(NavigationRegistry::class)) {
            return;
        }

        $registry = $this->app->make(NavigationRegistry::class);

        // Load platform module config
        $configPath = __DIR__.'/../config/module.php';
        $config = file_exists($configPath) ? require $configPath : [];

        // Build navigation from config submodules
        $submoduleNav = [];
        foreach ($config['submodules'] ?? [] as $submodule) {
            $submoduleCode = $submodule['code'] ?? '';

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
                    'access' => 'platform.'.$submoduleCode.'.'.($component['code'] ?? ''),
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
                        'access' => 'platform.'.$submoduleCode.'.'.($component['code'] ?? ''),
                        'type' => $component['type'] ?? 'page',
                    ];
                }

                // Create submodule navigation item with component children
                $submoduleNav[] = [
                    'name' => $submodule['name'] ?? ucfirst($submoduleCode),
                    'path' => $submodule['route'] ?? null,
                    'icon' => $submoduleIcon,
                    'access' => 'platform.'.$submoduleCode,
                    'priority' => $submodule['priority'] ?? 100,
                    'children' => $componentNav, // Include children for submenu
                ];
            }
        }

        // Sort submodules by priority
        usort($submoduleNav, fn ($a, $b) => ($a['priority'] ?? 100) <=> ($b['priority'] ?? 100));

        // Register platform navigation with highest priority (0)
        // Platform is is_core=true so its children flatten to top level in admin context
        // Scope: 'platform' - Platform navigation is for admin/landlord users only
        $registry->register('platform', [
            [
                'name' => $config['name'] ?? 'Platform Administration',
                'icon' => $config['icon'] ?? 'BuildingOffice2Icon',
                'access' => 'platform',
                'priority' => $config['priority'] ?? 0,
                'children' => $submoduleNav,
            ],
        ], $config['priority'] ?? 0, 'platform');
    }

    /**
     * Register middleware aliases for the platform package.
     * Follows same pattern as Core - push HandleInertiaRequests to web group.
     */
    protected function registerMiddleware(): void
    {
        // Use the booted callback to ensure app is fully initialized (same as Core)
        // This is required because middleware groups are compiled during kernel bootstrap
        $this->app->booted(function () {
            $router = $this->app->make('router');
            $kernel = $this->app->make(Kernel::class);

            // Register TrustHosts middleware globally (prevents Host header spoofing)
            // This must run very early, before any domain-based logic
            $kernel->prependMiddleware(TrustHosts::class);

            // CRITICAL: Register Database Firewall middleware FIRST (before sessions)
            // This ensures correct database connection for session storage on central domains
            $router->prependMiddlewareToGroup('web', SetDatabaseConnectionFromDomain::class);

            // Force file-based sessions/cache for installation routes BEFORE StartSession
            // so the installer can run without database-backed sessions/tables.
            $router->prependMiddlewareToGroup('web', ForceFileSessionForInstallation::class);

            // Note: BootstrapGuard is registered globally in register() method.
            // It handles installation checks before any routing occurs.

            // Register IdentifyDomainContext to set context for the request
            $router->prependMiddlewareToGroup('web', IdentifyDomainContext::class);

            // Register HandleInertiaRequests middleware AFTER session starts
            // Using pushMiddlewareToGroup so it runs AFTER StartSession
            // This ensures auth is available when sharing props
            $router->pushMiddlewareToGroup('web', HandleInertiaRequests::class);
        });

        $router = $this->app->make('router');

        // Register domain middleware aliases for manual use in routes
        $router->aliasMiddleware('identify.domain', IdentifyDomainContext::class);
        $router->aliasMiddleware('set.database.from.domain', SetDatabaseConnectionFromDomain::class);

        // Core platform middleware aliases
        $router->aliasMiddleware('module', ModuleAccessMiddleware::class);
        $router->aliasMiddleware('check.module', CheckModuleAccess::class);
        $router->aliasMiddleware('platform.domain', EnsurePlatformDomain::class);
        $router->aliasMiddleware('admin.domain', EnsureAdminDomain::class);
        $router->aliasMiddleware('enforce.subscription', EnforceSubscription::class);
        $router->aliasMiddleware('maintenance', CheckMaintenanceMode::class);
        $router->aliasMiddleware('permission', PermissionMiddleware::class);
        $router->aliasMiddleware('role', EnsureUserHasRole::class);
        $router->aliasMiddleware('landlord', EnsureLandlordGuard::class);
        $router->aliasMiddleware('tenant.active', EnsureTenantIsActive::class);
        $router->aliasMiddleware('platform.super.admin', PlatformSuperAdmin::class);
        $router->aliasMiddleware('tenant.super.admin', TenantSuperAdmin::class);
        $router->aliasMiddleware('tenant.setup', EnsureTenantIsSetup::class);
        $router->aliasMiddleware('tenant.onboarding', RequireTenantOnboarding::class);
        $router->aliasMiddleware('set.locale', SetLocale::class);
        $router->aliasMiddleware('check.subscription', CheckModuleSubscription::class);
        $router->aliasMiddleware('require.saas', RequireSaasMode::class);

        // Role-based module access middleware (checks role_module_access table)
        $router->aliasMiddleware('role.access', CheckRoleModuleAccess::class);
        $router->aliasMiddleware('smart.landing', SmartLandingRedirect::class);
        $router->aliasMiddleware('quota', EnforceTenantQuotas::class);

        // Optionally push CheckModuleSubscription to 'tenant' middleware group
        // This provides automatic route-based module gating for all tenant routes
        // Uncomment if using stancl/tenancy's 'tenant' middleware group:
        // $router->pushMiddlewareToGroup('tenant', \Aero\Platform\Http\Middleware\CheckModuleSubscription::class);
    }

    /**
     * Configure authentication guards and providers programmatically.
     * This keeps the host app's auth.php clean.
     */
    protected function configureAuth(): void
    {
        // Add landlord guard
        Config::set('auth.guards.landlord', [
            'driver' => 'session',
            'provider' => 'landlord_users',
        ]);

        // Add landlord_users provider
        Config::set('auth.providers.landlord_users', [
            'driver' => 'eloquent',
            'model' => LandlordUser::class,
        ]);

        // Add password reset for landlord users
        Config::set('auth.passwords.landlord_users', [
            'provider' => 'landlord_users',
            'table' => 'landlord_password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ]);
    }

    /**
     * Configure central domains for stancl/tenancy.
     *
     * AUTO-DETECTION: Automatically detects from the current HTTP request.
     * Extracts the root domain from the browser request (e.g., aeos365.test)
     * and automatically includes admin subdomain (admin.aeos365.test).
     *
     * This overrides any hardcoded values in the host app's config/tenancy.php.
     */
    protected function configureCentralDomains(): void
    {
        // Build dynamic central domains list
        $centralDomains = ['localhost', '127.0.0.1'];

        // In web context, detect domain from current request
        if (! app()->runningInConsole() && request()) {
            $currentHost = request()->getHost();
            $hostWithoutPort = preg_replace('/:\d+$/', '', $currentHost);

            // Extract root domain (remove subdomain if present)
            $parts = explode('.', $hostWithoutPort);

            if (count($parts) > 2) {
                // It's a subdomain (e.g., tenant.aeos365.test or admin.aeos365.test)
                // Extract the root domain (aeos365.test)
                $rootDomain = implode('.', array_slice($parts, 1));
            } else {
                // Already a root domain (e.g., aeos365.test)
                $rootDomain = $hostWithoutPort;
            }

            // Add root domain and admin subdomain
            $centralDomains = array_merge([
                $rootDomain,              // e.g., aeos365.test
                'admin.'.$rootDomain,     // e.g., admin.aeos365.test
            ], $centralDomains);
        } else {
            // Console context: use env vars
            $platformDomain = env('PLATFORM_DOMAIN', env('APP_DOMAIN', 'localhost'));
            if ($platformDomain && $platformDomain !== 'localhost') {
                $centralDomains = array_merge([
                    $platformDomain,
                    'admin.'.$platformDomain,
                ], $centralDomains);
            }
        }

        // Remove duplicates and re-index
        $centralDomains = array_values(array_unique($centralDomains));

        // Override the config
        Config::set('tenancy.central_domains', $centralDomains);
    }

    /**
     * Configure trusted hosts to prevent Host header spoofing attacks.
     *
     * This ensures only legitimate domain patterns are accepted for:
     * - Platform domain (e.g., aeos365.com)
     * - Admin subdomain (e.g., admin.aeos365.com)
     * - Tenant subdomains (e.g., *.aeos365.com)
     *
     * SECURITY: Without this, attackers can spoof the Host header to:
     * - Bypass domain-based access controls
     * - Generate malicious URLs in emails
     * - Cause cache poisoning attacks
     */
    protected function configureTrustedHosts(): void
    {
        // Build trusted host patterns
        $trustedPatterns = [];

        // Always trust localhost for development
        $trustedPatterns[] = '^localhost$';
        $trustedPatterns[] = '^127\.0\.0\.1$';

        // Get platform domain from .env or auto-detect
        $platformDomain = env('PLATFORM_DOMAIN');

        if ($platformDomain) {
            // Escape dots for regex
            $escapedDomain = preg_quote($platformDomain, '/');

            // Trust exact platform domain
            $trustedPatterns[] = '^'.$escapedDomain.'$';

            // Trust admin subdomain
            $trustedPatterns[] = '^admin\.'.$escapedDomain.'$';

            // Trust any tenant subdomain
            $trustedPatterns[] = '^[a-z0-9]([a-z0-9-]*[a-z0-9])?\.'.$escapedDomain.'$';
        } else {
            // No platform domain configured - trust .test and .local TLDs for development
            $trustedPatterns[] = '^[a-z0-9.-]+\.test$';
            $trustedPatterns[] = '^[a-z0-9.-]+\.local$';
        }

        // Configure Laravel's trusted hosts
        // This is applied via TrustHosts middleware
        Config::set('app.trusted_hosts', $trustedPatterns);
    }

    /**
     * Configure database connections programmatically.
     * Adds 'central' connection for landlord models.
     *
     * Priority: .env DB_DATABASE > installation_db_config.json > fallback
     */
    protected function configureDatabase(): void
    {
        // Get the default mysql configuration as a base
        $mysqlConfig = config('database.connections.mysql', []);

        // Determine database name from multiple sources
        $database = $this->resolveDatabase();

        // Update the default mysql connection with resolved database
        Config::set('database.connections.mysql.database', $database);

        // Add 'central' connection (same as default, but explicit for landlord models)
        Config::set('database.connections.central', array_merge($mysqlConfig, [
            'database' => $database,
        ]));
    }

    /**
     * Resolve the database name from available sources.
     *
     * Priority:
     * 1. .env DB_DATABASE (if set and non-empty)
     * 2. installation_db_config.json (installation wizard stored config)
     * 3. Fallback to 'eos365'
     */
    protected function resolveDatabase(): string
    {
        // Priority 1: Check .env
        $envDatabase = env('DB_DATABASE');
        if (! empty($envDatabase)) {
            return $envDatabase;
        }

        // Priority 2: Check installation config file
        $configPath = storage_path('installation_db_config.json');
        if (file_exists($configPath)) {
            try {
                $config = json_decode(file_get_contents($configPath), true);
                if (! empty($config['db_database'])) {
                    // Also update host/port/user/pass from installation config
                    $this->applyInstallationDbConfig($config);

                    return $config['db_database'];
                }
            } catch (\Throwable $e) {
                // Silently ignore parse errors
            }
        }

        // Priority 3: Fallback
        return 'eos365';
    }

    /**
     * Apply full database configuration from installation config file.
     */
    protected function applyInstallationDbConfig(array $config): void
    {
        $mysqlConfig = config('database.connections.mysql', []);

        if (! empty($config['db_host'])) {
            Config::set('database.connections.mysql.host', $config['db_host']);
        }
        if (! empty($config['db_port'])) {
            Config::set('database.connections.mysql.port', $config['db_port']);
        }
        if (! empty($config['db_username'])) {
            Config::set('database.connections.mysql.username', $config['db_username']);
        }
        if (isset($config['db_password'])) {
            $password = $config['db_password'];
            // Decrypt if encrypted
            if (! empty($config['db_password_encrypted']) && ! empty($password)) {
                try {
                    $password = Crypt::decryptString($password);
                } catch (\Throwable $e) {
                    // Use as-is if decryption fails
                }
            }
            Config::set('database.connections.mysql.password', $password);
        }
    }

    /**
     * Register package routes.
     *
     * Route Architecture:
     * -------------------
     * aero-platform has exactly 2 route files:
     * 1. web.php - Platform domain (domain.com): Landing, registration, public pages, installation
     * 2. admin.php - Admin domain (admin.domain.com): Landlord management, tenant admin
     *
     * Domain-based routing strategy:
     * - We DO NOT use Laravel's 'domain' constraint in Route::group()
     * - Instead, we rely on IdentifyDomainContext middleware to set context
     * - Route files themselves check context and return 404 if accessed from wrong domain
     * - This approach is more flexible and works without requiring .env configuration
     */
    protected function registerRoutes(): void
    {
        // Get domains from environment variables
        // Note: We use env() here because this runs at boot time before config is fully loaded
        $platformDomain = env('PLATFORM_DOMAIN', env('APP_DOMAIN', 'localhost'));
        $adminDomain = env('ADMIN_DOMAIN', 'admin.'.$platformDomain);

        // Platform web routes (for domain.com ONLY - public pages, registration)
        // Uses explicit domain constraint to prevent matching on admin/tenant subdomains
        Route::group([
            'middleware' => ['web'],
            'domain' => $platformDomain,
        ], function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        });

        // Platform API routes (public, no CSRF)
        Route::group([
            'middleware' => ['api'],
            'prefix' => 'api',
            'domain' => $platformDomain,
        ], function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        });

        // Admin routes (for admin.domain.com - landlord guard)
        // Uses explicit domain constraint for proper route matching
        // This ensures admin.domain.com/login is distinct from domain.com/login
        Route::group([
            'middleware' => ['web'],
            'domain' => $adminDomain,
        ], function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/admin.php');
        });

    }

    /**
     * Get the main platform domain (e.g., aeos365.test).
     * AUTO-DETECTS from the current browser request or falls back to configured domain.
     * Used to restrict platform.php routes to central domain only.
     */
    protected function getPlatformDomain(): string
    {
        // Runtime detection from browser request
        if (request() && request()->getHost()) {
            $currentHost = request()->getHost();
            $hostWithoutPort = preg_replace('/:\d+$/', '', $currentHost);

            // Extract root domain (remove subdomain if present)
            $parts = explode('.', $hostWithoutPort);

            // If it's a subdomain (e.g., tenant.aeos365.test or admin.aeos365.test)
            // Extract the root domain (aeos365.test)
            if (count($parts) > 2) {
                // Remove first part (subdomain), keep domain.tld
                return implode('.', array_slice($parts, 1));
            }

            // Already a root domain (e.g., aeos365.test)
            return $hostWithoutPort;
        }

        // Fallback: Use configured PLATFORM_DOMAIN from .env
        // This is critical for proper route registration during boot
        $platformDomain = env('PLATFORM_DOMAIN');
        if ($platformDomain) {
            // Remove any protocol or trailing slashes
            $platformDomain = preg_replace('#^https?://|/$#', '', $platformDomain);

            return $platformDomain;
        }

        // Last resort fallback
        return 'localhost';
    }

    /**
     * Get the admin subdomain for routing (e.g., admin.aeos365.test).
     */
    protected function getAdminDomain(): string
    {
        // Try ADMIN_DOMAIN first (explicit configuration)
        $adminDomain = env('ADMIN_DOMAIN');
        if ($adminDomain) {
            // Remove any protocol or trailing slashes
            $adminDomain = preg_replace('#^https?://|/$#', '', $adminDomain);

            return $adminDomain;
        }

        // Fallback: admin. + platform domain
        return 'admin.'.$this->getPlatformDomain();
    }

    /**
     * Register package's publishable assets.
     */
    protected function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            // Publish migrations
            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'aero-platform-migrations');

            // Publish config
            $this->publishes([
                __DIR__.'/../config/platform.php' => config_path('platform.php'),
            ], 'aero-platform-config');

            // NOTE: No assets publishing - all frontend is handled by aero/ui package

            // Publish views
            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/aero-platform'),
            ], 'aero-platform-views');

            // Publish Mail templates
            $this->publishes([
                __DIR__.'/../Mail' => app_path('Mail/Platform'),
            ], 'aero-platform-mail');
        }
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            ModuleAccessService::class,
            RoleModuleAccessService::class,
            PlatformSettingService::class,
            ErrorLogService::class,
            SslCommerzService::class,
            TenantScopeInterface::class,
        ];
    }

    /**
     * Register event listeners for tenant lifecycle.
     *
     * - TenantCreated: Runs module migrations on newly created tenant databases
     */
    protected function registerEventListeners(): void
    {
        // Listen for TenantCreated event to run module migrations
        // This ensures all installed modules (HRM, CRM, etc.) are migrated
        Event::listen(TenantCreated::class, TenantCreatedListener::class);
    }

    /**
     * Override the migrator to ONLY use aero-platform migrations on the landlord database.
     *
     * In SaaS mode:
     * - Landlord database: ONLY aero-platform migrations (tenants, domains, plans, etc.)
     * - Tenant databases: aero-core + module migrations (users, employees, etc.)
     *
     * This overrides Core's migrator override which excludes app migrations.
     * Platform further restricts to ONLY platform migrations for landlord.
     *
     * IMPORTANT: This filter only applies when connected to the central/landlord database.
     * When tenancy is initialized (tenant database), all package migrations are allowed.
     */
    protected function overrideMigratorForLandlord(): void
    {
        $platformMigrationsPath = realpath(__DIR__.'/../database/migrations');
        $centralDatabase = config('tenancy.database.central_connection', config('database.default'));

        $this->app->extend('migrator', function ($migrator, $app) use ($platformMigrationsPath, $centralDatabase) {
            return new class($app['migration.repository'], $app['db'], $app['files'], $app['events'], $platformMigrationsPath, $centralDatabase) extends Migrator
            {
                protected string $platformMigrationsPath;

                protected string $centralDatabase;

                public function __construct($repository, $resolver, $files, $dispatcher, string $platformMigrationsPath, string $centralDatabase)
                {
                    parent::__construct($repository, $resolver, $files, $dispatcher);
                    $this->platformMigrationsPath = $platformMigrationsPath;
                    $this->centralDatabase = $centralDatabase;
                }

                public function getMigrationFiles($paths)
                {
                    // Get all migration files from all paths
                    $files = parent::getMigrationFiles($paths);

                    // Check if we're on a tenant database (tenancy is initialized)
                    // If tenancy is initialized, allow ALL package migrations
                    if (function_exists('tenancy') && tenancy()->initialized) {
                        return $files;
                    }

                    // During installation, allow ALL package migrations to run on central DB
                    // Core, HRMAC, and other package migrations are needed for the initial setup
                    if (! file_exists(storage_path('app/aeos.installed'))) {
                        return $files;
                    }

                    // On central/landlord database: ONLY allow migrations from aero-platform package
                    // All other packages (core, hrm, crm, etc.) are for tenant databases
                    return collect($files)->filter(function ($path, $name) {
                        // Normalize path for comparison (resolve ../ and convert slashes)
                        $normalizedPath = realpath($path) ?: $path;

                        // Allow ONLY platform migrations
                        return str_starts_with($normalizedPath, $this->platformMigrationsPath);
                    })->all();
                }
            };
        });
    }

    /**
     * Configure guest redirect for authentication middleware.
     *
     * Redirects unauthenticated users to the appropriate login page
     * based on domain context (admin vs tenant).
     *
     * IMPORTANT: Do NOT set a global default guard with shouldUse().
     * The default guard should remain 'web' for tenant domains.
     * Admin routes explicitly use 'auth:landlord' middleware.
     */
    protected function configureGuestRedirect(): void
    {
        // REMOVED: $this->app['auth']->shouldUse('landlord');
        // This was causing issues - admin routes already use auth:landlord explicitly

        // Configure the Authenticate middleware to redirect to appropriate login based on domain
        $this->app->resolving(Authenticate::class, function ($middleware) {
            $middleware->redirectUsing(function ($request) {
                $host = $request->getHost();

                // Use the ParsesHostDomain trait for consistent domain detection
                $trait = new class
                {
                    use ParsesHostDomain;

                    public function isAdmin(string $host): bool
                    {
                        return $this->isHostAdminDomain($host);
                    }

                    public function isPlatform(string $host): bool
                    {
                        return $this->isHostPlatformDomain($host);
                    }
                };

                // Admin subdomain → admin.login
                if ($trait->isAdmin($host)) {
                    return route('admin.login');
                }

                // Platform domain (no subdomain) → redirect to registration page
                // Platform domain does NOT have a login route - it's for public pages and registration
                if ($trait->isPlatform($host)) {
                    return route('platform.register.index');
                }

                // Tenant subdomain → login
                return route('login');
            });
        });

        // Configure RedirectIfAuthenticated (guest middleware) to redirect authenticated users
        // This is called when an authenticated user tries to access login/register pages
        RedirectIfAuthenticated::redirectUsing(function ($request) {
            $host = $request->getHost();

            // Use the ParsesHostDomain trait for consistent domain detection
            $trait = new class
            {
                use ParsesHostDomain;

                public function isAdmin(string $host): bool
                {
                    return $this->isHostAdminDomain($host);
                }

                public function isPlatform(string $host): bool
                {
                    return $this->isHostPlatformDomain($host);
                }
            };

            // Admin subdomain: Check landlord guard and redirect if authenticated
            if ($trait->isAdmin($host)) {
                if (Auth::guard('landlord')->check()) {
                    return route('admin.dashboard');
                }
            }

            // Platform domain: Don't redirect authenticated users
            // They should be able to access public pages even if authenticated elsewhere
            if ($trait->isPlatform($host)) {
                return null;
            }

            // Tenant subdomain: Check web guard and redirect if authenticated
            if (Auth::guard('web')->check()) {
                // Redirect to dashboard route
                if (Route::has('dashboard')) {
                    return route('dashboard');
                }

                return '/dashboard';
            }

            // No redirect - allow access to guest pages (not authenticated)
            return null;
        });
    }

    /**
     * Check if the system is installed using file-based detection.
     */
    protected function installed(): bool
    {
        return file_exists(storage_path('app/aeos.installed'));
    }

    /**
     * Check if system is in SaaS mode using file-based detection.
     * Mode is set during installation and immutable at runtime.
     */
    protected function isSaasMode(): bool
    {
        if (! file_exists(storage_path('app/aeos.mode'))) {
            return false;
        }

        return trim(file_get_contents(storage_path('app/aeos.mode'))) === 'saas';
    }
}
