<?php

use Aero\Core\Http\Middleware\RedirectIfAuthenticated;
use Aero\I18n\Http\Middleware\SetLocale;
use Aero\Platform\Http\Middleware\ApiSecurityMiddleware;
use Aero\Platform\Http\Middleware\Authenticate;
use Aero\Platform\Http\Middleware\CheckMaintenanceMode;
use Aero\Platform\Http\Middleware\CheckModuleAccess;
use Aero\Platform\Http\Middleware\Cors;
use Aero\Platform\Http\Middleware\DeviceAuthMiddleware;
use Aero\Platform\Http\Middleware\EnforceSubscription;
use Aero\Platform\Http\Middleware\EnsurePlatformDomain;
use Aero\Platform\Http\Middleware\EnsureTenantIsSetup;
use Aero\Platform\Http\Middleware\HandleInertiaRequests;
use Aero\Platform\Http\Middleware\PermissionMiddleware;
use Aero\Platform\Http\Middleware\PlatformSuperAdmin;
use Aero\Platform\Http\Middleware\PreventRequestsDuringMaintenance;
use Aero\Platform\Http\Middleware\RedirectIfNoAdmin;
use Aero\Platform\Http\Middleware\RoleHierarchyMiddleware;
use Aero\Platform\Http\Middleware\SetDatabaseConnectionFromDomain;
use Aero\Platform\Http\Middleware\TenantSuperAdmin;
use Aero\Platform\Http\Middleware\TrimStrings;
use Aero\Platform\Http\Middleware\TrustProxies;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Http\Middleware\ValidatePostSize;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Middleware\SetCacheHeaders;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ValidateSignature;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     */
    protected $middleware = [
        // Aero\Platform\Http\Middleware\TrustHosts::class,
        TrustProxies::class,
        HandleCors::class,
        PreventRequestsDuringMaintenance::class,
        ValidatePostSize::class,
        TrimStrings::class,
        ConvertEmptyStringsToNull::class,
        SetDatabaseConnectionFromDomain::class, // Set DB connection based on domain BEFORE sessions
        DeviceAuthMiddleware::class, // Global device authentication
    ];

    /**
     * The application's route middleware groups.
     */
    protected $middlewareGroups = [
        'web' => [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            ShareErrorsFromSession::class,
            ValidateCsrfToken::class,
            SubstituteBindings::class,
            SetLocale::class, // Locale detection before Inertia
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            Cors::class,
        ],

        'api' => [
            // \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            ThrottleRequests::class.':api',
            SubstituteBindings::class,
        ],
    ];

    /**
     * The application's middleware aliases.
     */
    protected $middlewareAliases = [
        'auth' => Authenticate::class,
        'auth.basic' => AuthenticateWithBasicAuth::class,
        'auth.session' => AuthenticateSession::class,
        'cache.headers' => SetCacheHeaders::class,
        'can' => Authorize::class,
        'guest' => RedirectIfAuthenticated::class,
        'password.confirm' => RequirePassword::class,
        'precognitive' => HandlePrecognitiveRequests::class,
        'signed' => ValidateSignature::class,
        'throttle' => ThrottleRequests::class,
        'verified' => EnsureEmailIsVerified::class,
        // Access control is HRMAC ('hrmac:' middleware) — Spatie removed.
        // Custom Security Middleware
        'api_security' => ApiSecurityMiddleware::class,
        'custom_permission' => PermissionMiddleware::class,
        'role_hierarchy' => RoleHierarchyMiddleware::class,
        // Super Admin Protection Middleware (Compliance: Section 13)
        'platform.super_admin' => PlatformSuperAdmin::class,
        'tenant.super_admin' => TenantSuperAdmin::class,
        // Module Permission Registry Middleware
        'module' => CheckModuleAccess::class,
        // Device auth is now global middleware - no need for alias
        'platform.domain' => EnsurePlatformDomain::class,
        // Subscription Enforcement for Tenant Apps
        'subscription' => EnforceSubscription::class,
        // Tenant Setup Check - ensures admin and onboarding are completed
        'tenant.setup' => EnsureTenantIsSetup::class,
        // Redirect to admin-setup if no admin user exists
        'redirect.if.no.admin' => RedirectIfNoAdmin::class,
        // Maintenance Mode Gatekeeper (Global + Tenant level)
        'maintenance' => CheckMaintenanceMode::class,
    ];
}
