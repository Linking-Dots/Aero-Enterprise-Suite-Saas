<?php

namespace Aero\Platform;

use Aero\Auth\Context\TenantAuthContext;
use Aero\Auth\Contracts\AuthContext;
use Aero\Contracts\MailSenderInterface;
use Aero\Contracts\PlanCatalogInterface;
use Aero\Contracts\ProductAccessInterface;
use Aero\Contracts\RoleModuleAccessInterface;
use Aero\Contracts\SmsGatewayInterface;
use Aero\Contracts\TenantScopeInterface;
use Aero\Contracts\TranslationDriverInterface;
use Aero\Core\Services\InstallationState;
use Aero\Core\Services\NavigationRegistry;
use Aero\Kernel\ValueObjects\RequestContext;
use Aero\Core\Traits\ParsesHostDomain;
use Aero\HRMAC\Services\NullRoleModuleAccessService;
use Aero\HRMAC\Services\RoleModuleAccessService as HRMACRoleModuleAccessService;
use Aero\I18n\Http\Middleware\SetLocale;
use Aero\I18n\Services\TranslationService;
use Aero\Notifications\Contracts\MailContextResolver;
use Aero\Notifications\Contracts\SmsContextResolver;
use Aero\Notifications\Services\Mail\MailService;
use Aero\Notifications\Services\Sms\SmsGatewayService;
use Aero\Platform\Auth\LandlordAuthContext;
use Aero\Platform\Bootstrappers\CachePrefixTenancyBootstrapper;
use Aero\Platform\Bootstrappers\FailClosedQueueTenancyBootstrapper;
use Aero\Platform\Console\Commands\CleanupFailedInstallation;
use Aero\Platform\Console\Commands\EnsureSuperAdmin;
use Aero\Platform\Console\Commands\ExpireGracePeriods;
use Aero\Platform\Console\Commands\ProcessPendingSubscriptionChanges;
use Aero\Platform\Console\Commands\ProcessSubscriptionRenewals;
use Aero\Platform\Console\Commands\PurgeExpiredTenants;
use Aero\Platform\Console\Commands\PurgeSuspendedRoleAccess;
use Aero\Platform\Console\Commands\SetupApplication;
use Aero\Platform\Console\Commands\TenantCreate;
use Aero\Platform\Console\Commands\TenantFlush;
use Aero\Platform\Console\Commands\TenantHealth;
use Aero\Platform\Console\Commands\TenantMigrate;
use Aero\Platform\Events\ProductSubscriptionChanged;
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
use Aero\Platform\Listeners\ReactivateRoleAccessOnResubscribe;
use Aero\Platform\Listeners\ResyncTenantModuleCatalog;
use Aero\Platform\Listeners\SuspendUnsubscribedRoleAccess;
use Aero\Platform\Listeners\TenantCreatedListener;
use Aero\Auth\Models\User;
use Aero\Platform\Models\Plan;
use Aero\Platform\Models\ProductSubscription;
use Aero\Platform\Models\Subscription;
use Aero\Platform\Models\Tenant;
use Aero\Platform\Observers\PlanAuditObserver;
use Aero\Platform\Observers\ProductSubscriptionObserver;
use Aero\Platform\Observers\SubscriptionObserver;
use Aero\Platform\Policies\PlanPolicy;
use Aero\Platform\Policies\TenantPolicy;
use Aero\Platform\Providers\TenancyBootstrapServiceProvider;
use Aero\Platform\Services\Billing\SslCommerzService;
use Aero\Platform\Services\DownloadService;
use Aero\Platform\Services\LicenseIssuer;
use Aero\Platform\Services\Module\ModuleAccessService;
use Aero\Platform\Services\Monitoring\Tenant\ErrorLogService;
use Aero\Platform\Services\Notifications\PlatformMailContextResolver;
use Aero\Platform\Services\Notifications\PlatformSmsContextResolver;
use Aero\Platform\Services\PlatformPlanService;
use Aero\Platform\Services\PlatformSettingService;
use Aero\Platform\Services\PlatformWidgetRegistry;
use Aero\Platform\Services\ProductAccessService;
use Aero\Platform\Services\ProductSubscriptionService;
use Aero\Platform\Services\SaaSTenantScope;
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
use Stancl\Tenancy\Bootstrappers\FilesystemTenancyBootstrapper;
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

        // Host-aware DEFAULT RequestContext so the whole admin domain — including
        // GUEST pages (login, forgot-password) that render shared HRMAC-backed nav —
        // resolves as platform context. The ResolvePlatformContext/ResolveTenantContext
        // route middleware still override this where present (authenticated routes).
        // Without this, HRMAC's context guard fail-closes on admin guest pages.
        $this->app->bind(RequestContext::class, function ($app) {
            $host = $app->make('request')->getHost();
            $adminHost = config('aero.admin_domain', 'admin.'.config('app.domain', parse_url(config('app.url', ''), PHP_URL_HOST)));

            return str_starts_with($host, 'admin.') || $host === $adminHost
                ? new RequestContext('platform', 'landlord')
                : new RequestContext('tenant', 'web');
        });

        // Register global BootstrapGuard middleware FIRST
        // This runs before route matching and handles:
        // 1. Installation status check
        // 2. Cross-domain redirect to platform /install if not installed
        $kernel = $this->app->make(Kernel::class);
        $kernel->pushMiddleware(BootstrapGuard::class);

        // B-36: on the bare platform domain, redirect /login -> /signup before routing
        // (the mode-agnostic aero-auth tenant login route is registered without a domain
        // and would otherwise render a dead login form on the marketing domain).
        $kernel->pushMiddleware(\Aero\Platform\Http\Middleware\RedirectCentralLoginToSignup::class);

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

        // SaaS data-decision (Audit D15): tenant module syncs are gated to subscribed
        // products. HRMAC's context-free sync command applies this filter only because
        // the platform (the consumer) binds it here. Standalone never binds it.
        $this->app->singleton(
            \Aero\Contracts\ModuleSyncFilterInterface::class,
            \Aero\Platform\Services\TenantSubscriptionModuleFilter::class
        );

        // Register Module Access Services with fallback stubs for pre-install
        // These services are lazy-loaded to avoid DB queries before installation
        // The RoleModuleAccessInterface (Aero\Contracts) is the single binding key
        // for RBAC. Bind it to the HRMAC implementation, or to HRMAC's NULL impl
        // before installation so RBAC fails CLOSED with no DB queries.
        $this->app->singleton(RoleModuleAccessInterface::class, function ($app) {
            if (! InstallationState::isInstalled()) {
                return new NullRoleModuleAccessService;
            }

            try {
                return new HRMACRoleModuleAccessService;
            } catch (\Throwable $e) {
                return new NullRoleModuleAccessService;
            }
        });

        // Concrete-class alias: callers that still type-hint the HRMAC concrete
        // class resolve the same (gated) instance as the interface.
        $this->app->alias(RoleModuleAccessInterface::class, HRMACRoleModuleAccessService::class);

        $this->app->singleton(ModuleAccessService::class, function ($app) {
            // Only instantiate if installed to avoid DB queries pre-install
            if (! InstallationState::isInstalled()) {
                return new class
                {
                    public function __call($method, $args)
                    {
                        return [];
                    }
                };
            }

            try {
                return new ModuleAccessService($app->make(RoleModuleAccessInterface::class));
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

        $this->app->singleton(ProductAccessService::class);
        $this->app->singleton(ProductSubscriptionService::class);
        $this->app->singleton(LicenseIssuer::class);
        $this->app->singleton(DownloadService::class);
        $this->app->singleton(PlatformPlanService::class);

        // Interface bindings for aero-core compatibility (standalone mode uses class_exists guard)
        $this->app->singleton(
            ProductAccessInterface::class,
            ProductAccessService::class
        );
        $this->app->singleton(
            PlanCatalogInterface::class,
            PlatformPlanService::class
        );

        // Register tenant lifecycle services
        $this->app->singleton(TenantRetentionService::class);
        $this->app->singleton(TenantPurgeService::class);

        // Register Platform's own widget registry (independent from Core)
        $this->app->singleton(PlatformWidgetRegistry::class);

        // Bind notification context resolvers to platform implementations
        $this->app->singleton(MailContextResolver::class, PlatformMailContextResolver::class);
        $this->app->singleton(SmsContextResolver::class, PlatformSmsContextResolver::class);

        // aero-core interface bindings for mail/SMS services
        $this->app->singleton(MailSenderInterface::class, MailService::class);
        $this->app->singleton(SmsGatewayInterface::class, SmsGatewayService::class);

        // aero-core TranslationDriverInterface — override the anonymous-class default registered in Core
        $this->app->singleton(TranslationDriverInterface::class, TranslationService::class);

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
        // Decision 6 (module-model consolidation): the canonical hrmac Module is
        // pure-shareable and must NOT reference platform models. Platform owns the
        // module<->catalog relationship, so it is registered dynamically on the
        // canonical Module at boot (SaaS only). Modules are carried by PRODUCTS
        // (products.module_code); the old plan_module pivot is gone.
        \Aero\HRMAC\Models\Module::resolveRelationUsing('products', function ($module) {
            return $module->hasMany(\Aero\Platform\Models\Product::class, 'module_code', 'code');
        });

        // Auth purity (auth-identity unification): the shared Aero\Auth\Models\User is a
        // PURE identity model with no tenancy/connection knowledge. As the multi-tenant
        // host, PLATFORM (the consumer) applies the tenant-isolation guard to User here —
        // fail-closed for tenant-DB queries, with a no-op escape for central/landlord
        // queries (resolved connection 'central'; the admin domain flips the default
        // connection to central and the landlord provider binds central at retrieval).
        // Standalone/core (no platform) consume the pure User with no guard (single DB).
        // Register audit observers
        Plan::observe(PlanAuditObserver::class);
        Subscription::observe(SubscriptionObserver::class);

        // Audit D15 — product subscription lifecycle drives tenant module catalog.
        // Observer fires ProductSubscriptionChanged; listener re-syncs the catalog.
        ProductSubscription::observe(ProductSubscriptionObserver::class);
        Event::listen(ProductSubscriptionChanged::class, ResyncTenantModuleCatalog::class);

        // Audit D17 — soft-suspend role grants on unsubscribe; restore on re-subscribe.
        // SuspendUnsubscribedRoleAccess marks rows suspended (30-day grace; no access at runtime).
        // ReactivateRoleAccessOnResubscribe flips them back to active within the grace window.
        Event::listen(ProductSubscriptionChanged::class, SuspendUnsubscribedRoleAccess::class);
        Event::listen(ProductSubscriptionChanged::class, ReactivateRoleAccessOnResubscribe::class);

        // Configure Cashier to use our unified Subscription model as the single source of truth
        // This eliminates drift between Cashier-managed Stripe data and lifecycle-managed fields.
        Cashier::useSubscriptionModel(Subscription::class);
        Cashier::useCustomerModel(Tenant::class);

        // Authoritative runtime tenancy bootstrapper list (Axis A A5, 2026-05-30).
        // This MUST match config/tenancy.php — kept in sync; the runtime test
        // TenancyRuntimeConfigTest asserts the BOOTED list (not the file) so this
        // can never silently drift again.
        //
        //  - CachePrefixTenancyBootstrapper: driver-agnostic per-tenant cache key
        //    prefix (works on array/file/database/redis — no tagging requirement),
        //    chosen over Stancl's CacheTenancyBootstrapper which needs a tagging store.
        //  - FilesystemTenancyBootstrapper: per-tenant storage roots. Previously
        //    stripped here with a stale "Undefined array key 'local'" note — that bug
        //    was fixed by adding the tenancy.filesystem config block (Phase 0 T5);
        //    WITHOUT this, tenant uploads share one root = cross-tenant file leak.
        //  - FailClosedQueueTenancyBootstrapper (Audit D5c): refuses jobs for
        //    suspended/deleted tenants instead of the stock bootstrapper.
        Config::set('tenancy.bootstrappers', [
            DatabaseTenancyBootstrapper::class,
            CachePrefixTenancyBootstrapper::class,
            FilesystemTenancyBootstrapper::class,
            FailClosedQueueTenancyBootstrapper::class,
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
                PurgeSuspendedRoleAccess::class, // D17: daily hard-delete after 30-day grace
                \Aero\Platform\Console\Commands\DemoResetCommand::class,
            ]);
        }

        // Register platform navigation with NavigationRegistry
        // This allows HandleInertiaRequests to share navigation via Inertia props
        $this->registerPlatformNavigation();

        // Register Platform dashboard widgets
        // These appear on the Admin Dashboard following the Core pattern
        $this->registerPlatformDashboardWidgets();

        // In testing environment, if default database is sqlite, redirect mysql and central connection resolution to the default connection to share the same :memory: database
        if ($this->app->environment('testing') && config('database.default') === 'sqlite') {
            $this->app->booted(function () {
                try {
                    $db = $this->app->make('db');
                    $db->extend('central', function ($config, $name) use ($db) {
                        return $db->connection('sqlite');
                    });
                    $db->extend('mysql', function ($config, $name) use ($db) {
                        return $db->connection('sqlite');
                    });
                } catch (\Throwable $e) {
                    // Silently ignore if DB is not accessible yet
                }
            });
        }
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

            // Respect explicit nav suppression — used to hide features whose
            // backend exists but whose admin UI is not built yet (no page),
            // so the sidebar never links to a blank screen.
            if (($submodule['show_in_nav'] ?? true) === false) {
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

        // Add landlord_users provider — plain eloquent over the unified
        // Aero\Auth\Models\User. Auth is context-free and carries NO connection logic:
        // the landlord guard authenticates `users` on whatever connection is ACTIVE
        // (on the admin domain SetDatabaseConnectionFromDomain makes that the central DB;
        // on a tenant domain the tenancy layer makes it the tenant DB). Multi-tenancy
        // isolation is the infrastructure's concern, not auth's.
        Config::set('auth.providers.landlord_users', [
            'driver' => 'eloquent',
            'model' => \Aero\Auth\Models\User::class,
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
            // Console context: use config (config-cache-safe; env() is null when cached)
            $platformDomain = config('aero.platform_domain', 'localhost');
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

        // Get platform domain from config (config-cache-safe; env() is null when cached).
        // SECURITY: under config:cache a stale env() read here would null the domain and
        // silently fall through to the dev-only .test/.local trust branch below.
        $platformDomain = config('aero.platform_domain');

        if ($platformDomain && $platformDomain !== 'localhost') {
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
     * 1. Config mysql database or .env DB_DATABASE (if set and non-empty)
     * 2. framework/installation_config.json (new unified config file)
     * 3. installation_db_config.json (legacy flat config file)
     * 4. Fallback to 'aeos365'
     */
    protected function resolveDatabase(): string
    {
        // Priority 1: Check config mysql database (handles cached config)
        $configDatabase = config('database.connections.mysql.database');
        if (! empty($configDatabase) && ! in_array($configDatabase, ['forge', 'laravel', 'database', 'eos365'])) {
            return $configDatabase;
        }

        // Priority 1b: Check .env directly
        $envDatabase = env('DB_DATABASE');
        if (! empty($envDatabase)) {
            return $envDatabase;
        }

        // Priority 2: Check new unified installation config file
        $newConfigPath = storage_path('framework/installation_config.json');
        if (file_exists($newConfigPath)) {
            try {
                $config = json_decode(file_get_contents($newConfigPath), true);
                if (isset($config['database']) && is_array($config['database'])) {
                    $dbName = $config['database']['db_database'] ?? $config['database']['database'] ?? null;
                    if (! empty($dbName)) {
                        $this->applyInstallationDbConfig($config);

                        return $dbName;
                    }
                }
            } catch (\Throwable $e) {
                // Silently ignore parse errors
            }
        }

        // Priority 2b: Check legacy installation config file
        $legacyConfigPath = storage_path('installation_db_config.json');
        if (file_exists($legacyConfigPath)) {
            try {
                $config = json_decode(file_get_contents($legacyConfigPath), true);
                $dbName = $config['db_database'] ?? $config['database'] ?? null;
                if (! empty($dbName)) {
                    $this->applyInstallationDbConfig($config);

                    return $dbName;
                }
            } catch (\Throwable $e) {
                // Silently ignore parse errors
            }
        }

        // Priority 3: Fallback
        return '';
    }

    /**
     * Apply full database configuration from installation config file.
     */
    protected function applyInstallationDbConfig(array $config): void
    {
        // Support both old flat format (db_host, etc.) and new unified format (nested under 'database')
        $dbConfig = $config;
        if (isset($config['database']) && is_array($config['database'])) {
            $dbConfig = $config['database'];
        }

        $host = $dbConfig['db_host'] ?? $dbConfig['host'] ?? null;
        if (! empty($host)) {
            Config::set('database.connections.mysql.host', $host);
        }

        $port = $dbConfig['db_port'] ?? $dbConfig['port'] ?? null;
        if (! empty($port)) {
            Config::set('database.connections.mysql.port', $port);
        }

        $username = $dbConfig['db_username'] ?? $dbConfig['username'] ?? null;
        if (! empty($username)) {
            Config::set('database.connections.mysql.username', $username);
        }

        $password = $dbConfig['db_password'] ?? $dbConfig['password'] ?? null;
        if ($password !== null) {
            // Decrypt if encrypted (only old format had db_password_encrypted, but handle it just in case)
            $isEncrypted = ! empty($dbConfig['db_password_encrypted']) || ! empty($config['db_password_encrypted']);
            if ($isEncrypted && ! empty($password)) {
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
        // Domains come from config (config/aero.php), NOT env(): this runs at boot
        // and also during `route:cache`, where env() outside config files returns null
        // once config has been cached — which previously baked 'admin.localhost' into
        // the cached route manifest and 404'd every admin route.
        $platformDomain = config('aero.platform_domain', 'localhost');
        $adminDomain = config('aero.admin_domain', 'admin.'.$platformDomain);

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
            // Landlord/admin AUTH routes (login/logout/root/session-check/impersonation).
            // Moved out of aero-auth to keep that package mode-agnostic; loaded first
            // so the guest login routes are available, then the protected admin routes.
            $this->loadRoutesFrom(__DIR__.'/../routes/admin-auth.php');
            $this->loadRoutesFrom(__DIR__.'/../routes/admin.php');
        });

        // License API + marketplace catalog — central domain, no CSRF, no auth
        // Called by standalone installations for activation, validation, and catalog browsing
        if (! $this->app->routesAreCached()) {
            Route::group([
                'middleware' => ['api'],
                'domain' => $platformDomain,
            ], function () {
                $this->loadRoutesFrom(__DIR__.'/../routes/license-api.php');
            });
        }

        // Public marketplace (domain.com/marketplace) — Inertia web pages + Stripe webhook
        if (! $this->app->routesAreCached()) {
            Route::group([
                'domain' => $platformDomain,
            ], function () {
                $this->loadRoutesFrom(__DIR__.'/../routes/marketplace.php');
            });
        }

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

        // Fallback: configured platform domain (config-cache-safe; not env()).
        // Critical for route registration at boot / during route:cache.
        $platformDomain = config('aero.platform_domain');
        if ($platformDomain && $platformDomain !== 'localhost') {
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
        // Try the configured admin domain first (config-cache-safe; not env()).
        $adminDomain = config('aero.admin_domain');
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
            RoleModuleAccessInterface::class,
            HRMACRoleModuleAccessService::class,
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
        // The central/landlord DB runs tier=platform + tier=sharable packages (NOT core,
        // NOT product — those are tenant-only). Single source of truth: each package's
        // extra.aero.tier (Phase 4 — see Aero\Kernel\Migration\PackageTier). This replaces the
        // earlier hand-listed baseline_modules-minus-core set, which was missing
        // aero-infrastructure (a sharable: installation_history/progress/module_pricing/
        // system_health_logs). The sharable packages are pure single-schema packages used
        // identically by central and tenants — no per-context columns.
        $platformMigrationPath = realpath(__DIR__.'/../database/migrations') ?: __DIR__.'/../database/migrations';
        $allowedMigrationPaths = array_values(array_filter(array_unique(array_merge(
            [$platformMigrationPath],
            \Aero\Kernel\Migration\PackageTier::migrationPathsForContext('central')
        ))));
        $centralDatabase = config('tenancy.database.central_connection', config('database.default'));

        $this->app->extend('migrator', function ($migrator, $app) use ($allowedMigrationPaths, $centralDatabase, $platformMigrationPath) {
            return new class($app['migration.repository'], $app['db'], $app['files'], $app['events'], $allowedMigrationPaths, $centralDatabase, $platformMigrationPath) extends Migrator
            {
                /** @var array<int, string> */
                protected array $allowedMigrationPaths;

                protected string $centralDatabase;

                protected string $platformMigrationPath;

                public function __construct($repository, $resolver, $files, $dispatcher, array $allowedMigrationPaths, string $centralDatabase, string $platformMigrationPath)
                {
                    parent::__construct($repository, $resolver, $files, $dispatcher);
                    $this->allowedMigrationPaths = $allowedMigrationPaths;
                    $this->centralDatabase = $centralDatabase;
                    $this->platformMigrationPath = $platformMigrationPath;
                }

                public function getMigrationFiles($paths)
                {
                    // Get all migration files from all paths
                    $files = parent::getMigrationFiles($paths);

                    // If running tests with sqlite connection, bypass connection filtering
                    // and return all package migrations to build the complete single-database schema.
                    if (app()->environment('testing') && config('database.default') === 'sqlite') {
                        return collect($files)->unique(function ($path) {
                            return preg_replace('/^\d{4}_\d{2}_\d{2}_\d{6}_/', '', basename($path, '.php'));
                        })->all();
                    }

                    // On a TENANT database (tenancy initialized): tenant tier = core + sharable
                    // + product, which is EVERYTHING EXCEPT platform. Phase-4: exclude the
                    // platform-tier migrations so a raw `tenants:migrate` (e.g. the admin
                    // "re-migrate tenant DB" action, TenantDatabaseController) can never create
                    // landlord/platform tables on a tenant DB. Deny-list (not allow-list) so any
                    // legit app-level/non-package tenant migrations still pass through; no dedup
                    // — a tenant must run all of its distinct package migrations.
                    if (function_exists('tenancy') && tenancy()->initialized) {
                        return collect($files)->reject(function ($path) {
                            $normalizedPath = realpath($path) ?: $path;

                            return str_starts_with($normalizedPath, $this->platformMigrationPath);
                        })->all();
                    }

                    // During installation (not yet installed), allow ALL package migrations.
                    if (! InstallationState::isInstalled() && ! app()->environment('testing')) {
                        // Deduplicate by operation name before returning — keep latest timestamp.
                        return collect($files)->unique(function ($path) {
                            return preg_replace('/^\d{4}_\d{2}_\d{2}_\d{6}_/', '', basename($path, '.php'));
                        })->all();
                    }

                    // On central/landlord database (production + testing):
                    // run aero-platform + the foundational shared packages (auth, ui,
                    // i18n, notifications, hrmac). NOT core or product modules — those are
                    // tenant-only. The shared packages carry the canonical roles/modules/
                    // RBAC schema central needs.
                    $platformFiles = collect($files)->filter(function ($path, $name) {
                        $normalizedPath = realpath($path) ?: $path;
                        foreach ($this->allowedMigrationPaths as $allowed) {
                            if (str_starts_with($normalizedPath, $allowed)) {
                                return true;
                            }
                        }

                        return false;
                    });

                    // Determine the current connection the migrator is running on
                    $currentConn = $this->connection ?: config('database.default');

                    if ($currentConn === $this->centralDatabase) {
                        // Deduplicate within the filtered set by operation name.
                        // This removes any internal duplicates (e.g. create_notification_logs_table
                        // exists in both aero-platform and as a copy from another path).
                        return $platformFiles->unique(function ($path) {
                            return preg_replace('/^\d{4}_\d{2}_\d{2}_\d{6}_/', '', basename($path, '.php'));
                        })->all();
                    } else {
                        // If we are migrating the default connection in a testing environment,
                        // exclude the platform/landlord migrations (as they are run on central connection)
                        // to avoid duplicate column/table definition conflicts on shared sqlite files.
                        if (app()->environment('testing')) {
                            $platformPaths = $platformFiles->keys()->toArray();
                            return collect($files)->except($platformPaths)->all();
                        }
                        return $files;
                    }
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
        return InstallationState::isInstalled();
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
