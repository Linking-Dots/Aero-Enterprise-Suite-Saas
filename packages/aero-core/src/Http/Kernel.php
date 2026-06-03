<?php

namespace Aero\Core\Http;

use Aero\Core\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\ApiSecurityMiddleware;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\CheckMaintenanceMode;
use App\Http\Middleware\CheckModuleAccess;
use App\Http\Middleware\Cors;
use App\Http\Middleware\DeviceAuthMiddleware;
use App\Http\Middleware\EnforceSubscription;
use App\Http\Middleware\EnsurePlatformDomain;
use App\Http\Middleware\EnsureTenantIsSetup;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\PlatformSuperAdmin;
use App\Http\Middleware\PreventRequestsDuringMaintenance;
use App\Http\Middleware\RedirectIfNoAdmin;
use App\Http\Middleware\RoleHierarchyMiddleware;
use App\Http\Middleware\SetDatabaseConnectionFromDomain;
use App\Http\Middleware\TenantSuperAdmin;
use App\Http\Middleware\TrimStrings;
use App\Http\Middleware\TrustProxies;
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
        // \App\Http\Middleware\TrustHosts::class,
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
            'Aero\\I18n\\Http\\Middleware\\SetLocale', // Locale detection before Inertia
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
        // Access control is HRMAC ('hrmac:' middleware) — Spatie role/permission
        // middleware removed.
        // Custom Security Middleware
        'api_security' => ApiSecurityMiddleware::class,
        'custom_permission' => \App\Http\Middleware\PermissionMiddleware::class,
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
