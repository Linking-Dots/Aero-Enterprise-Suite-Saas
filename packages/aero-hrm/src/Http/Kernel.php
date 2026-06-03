<?php

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     */
    protected $middleware = [
        // \App\Http\Middleware\TrustHosts::class,
        \App\Http\Middleware\TrustProxies::class,
        \Illuminate\Http\Middleware\HandleCors::class,
        \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        \App\Http\Middleware\SetDatabaseConnectionFromDomain::class, // Set DB connection based on domain BEFORE sessions
        \App\Http\Middleware\DeviceAuthMiddleware::class, // Global device authentication
    ];

    /**
     * The application's route middleware groups.
     */
    protected $middlewareGroups = [
        'web' => [
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \Aero\I18n\Http\Middleware\SetLocale::class, // Locale detection before Inertia
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            \App\Http\Middleware\Cors::class,
        ],

        'api' => [
            // \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            \Illuminate\Routing\Middleware\ThrottleRequests::class.':api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    /**
     * The application's middleware aliases.
     */
    protected $middlewareAliases = [
        'auth' => \App\Http\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'auth.session' => \Illuminate\Session\Middleware\AuthenticateSession::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \Aero\Core\Http\Middleware\RedirectIfAuthenticated::class,
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        'precognitive' => \Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
        'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        // Access control is HRMAC ('hrmac:' middleware) — Spatie removed.
        // Custom Security Middleware
        'api_security' => \App\Http\Middleware\ApiSecurityMiddleware::class,
        'custom_permission' => \App\Http\Middleware\PermissionMiddleware::class,
        'role_hierarchy' => \App\Http\Middleware\RoleHierarchyMiddleware::class,
        // Super Admin Protection Middleware (Compliance: Section 13)
        'platform.super_admin' => \App\Http\Middleware\PlatformSuperAdmin::class,
        'tenant.super_admin' => \App\Http\Middleware\TenantSuperAdmin::class,
        // Module Permission Registry Middleware
        'module' => \App\Http\Middleware\CheckModuleAccess::class,
        // Device auth is now global middleware - no need for alias
        'platform.domain' => \App\Http\Middleware\EnsurePlatformDomain::class,
        // Subscription Enforcement for Tenant Apps
        'subscription' => \App\Http\Middleware\EnforceSubscription::class,
        // Tenant Setup Check - ensures admin and onboarding are completed
        'tenant.setup' => \App\Http\Middleware\EnsureTenantIsSetup::class,
        // Redirect to admin-setup if no admin user exists
        'redirect.if.no.admin' => \App\Http\Middleware\RedirectIfNoAdmin::class,
        // Maintenance Mode Gatekeeper (Global + Tenant level)
        'maintenance' => \App\Http\Middleware\CheckMaintenanceMode::class,
    ];
}
