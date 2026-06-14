<?php

declare(strict_types=1);

namespace Aero\Auth;

use Aero\Auth\Console\Commands\AuthSecurityAudit;
use Illuminate\Support\Facades\Route;
use Aero\Auth\Context\TenantAuthContext;
use Aero\Auth\Contracts\AuthContext;
use Aero\Auth\Listeners\AuthEventSubscriber;
use Aero\Auth\Services\DeviceAuthService;
use Aero\Auth\Services\DeviceSessionService;
use Aero\Auth\Services\EncryptedSessionHandler;
use Aero\Auth\Services\IpGeolocationService;
use Aero\Auth\Services\IPWhitelistService;
use Aero\Auth\Services\ModernAuthenticationService;
use Aero\Auth\Services\PasswordPolicyService;
use Aero\Auth\Services\SamlService;
use Aero\Auth\Services\SessionEncryptionService;
use Aero\Auth\Services\SessionManagementService;
use Aero\Auth\Services\ThreatDetectionService;
use Aero\Auth\Services\TwoFactorAuthService;
use Aero\Auth\Services\UserImpersonationService;
use Aero\Auth\Services\UserInvitationService;
use Aero\Contracts\UserInvitationServiceInterface;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;

class AeroAuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // AuthEventSubscriber writes to the 'auth' log channel on EVERY auth event
        // (login/logout/failed/lockout/...). It was only defined in the publishable
        // config/logging.php — so an unpublished/stale host had no 'auth' channel and
        // every auth event threw "Log [auth] is not defined". Merge it at runtime so
        // the package owns its own dependency and the host stays dumb (no publish needed).
        if (! config()->has('logging.channels.auth')) {
            $authLogging = require __DIR__.'/../config/logging.php';
            config([
                'logging.channels.auth' => $authLogging['channels']['auth'] ?? [
                    'driver' => 'daily',
                    'path' => storage_path('logs/auth.log'),
                    'level' => env('LOG_LEVEL', 'debug'),
                    'days' => 14,
                ],
            ]);
        }

        // Disable Fortify's auto-routes — aero-core and aero-platform define their own
        // domain-partitioned auth routes that point at Aero\Auth controllers.
        Fortify::ignoreRoutes();

        // Default context: tenant (web guard). aero-platform overrides this binding
        // with a domain-aware closure when running in SaaS mode.
        $this->app->bind(AuthContext::class, TenantAuthContext::class);

        // User invitations — auth owns the identity-invitation capability. Consumers
        // (auth's own InvitationController public-accept flow + core's admin surface)
        // depend on the token-acceptance contract; the concrete service carries the
        // richer admin surface (list/invite/resend/cancel).
        $this->app->singleton(UserInvitationServiceInterface::class, UserInvitationService::class);

        // Auth services — singletons so they are instantiated once per request
        $this->app->singleton(ModernAuthenticationService::class);
        $this->app->singleton(DeviceAuthService::class);
        $this->app->singleton(DeviceSessionService::class);
        $this->app->singleton(TwoFactorAuthService::class);
        $this->app->singleton(UserImpersonationService::class);
        $this->app->singleton(PasswordPolicyService::class);
        $this->app->singleton(IPWhitelistService::class);
        $this->app->singleton(IpGeolocationService::class);
        $this->app->singleton(ThreatDetectionService::class);
        $this->app->singleton(SamlService::class);
        $this->app->singleton(SessionEncryptionService::class);
        $this->app->singleton(SessionManagementService::class);

        // Register encrypted session handler when session encryption is enabled
        if (config('session.encrypt', false)) {
            $this->app->extend('session', function ($manager) {
                $manager->extend('encrypted_database', function ($app) {
                    $connection = $app['db']->connection($app['config']['session.connection']);
                    $encryptionService = $app[SessionEncryptionService::class];

                    return new EncryptedSessionHandler(
                        $connection,
                        $app['config']['session.table'],
                        $app['config']['session.lifetime'],
                        $encryptionService
                    );
                });

                return $manager;
            });
        }
    }

    public function boot(): void
    {
        // Auth event subscriber
        Event::subscribe(AuthEventSubscriber::class);

        // Artisan commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                AuthSecurityAudit::class,
            ]);

            // Publish configs
            $this->publishes([
                __DIR__.'/../config/logging.php' => config_path('logging.php'),
                __DIR__.'/../config/activitylog.php' => config_path('activitylog.php'),
            ], 'aero-auth-config');
        }

        // Merge configs
        $this->mergeConfigFrom(__DIR__.'/../config/logging.php', 'logging');
        $this->mergeConfigFrom(__DIR__.'/../config/activitylog.php', 'activitylog');

        // Auth-specific migrations (devices, sessions, impersonations, social auth, password reset tokens)
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Auth views (e.g. the user-invitation email markdown template).
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'aero-auth');

        // Tenant auth routes — wrapped in the 'web' middleware group so that
        // HandleInertiaRequests, session, CSRF, and all web-group middleware run.
        Route::middleware(['web'])
            ->group(__DIR__.'/../routes/tenant.php');

        // Identity admin routes (SSO/SAML/OIDC/MFA/Session/SCIM config) — tenant-scoped.
        Route::middleware(['web'])
            ->group(__DIR__.'/../routes/identity.php');

        // NOTE: landlord/admin auth routes now live in aero-platform
        // (routes/admin-auth.php), keeping aero-auth mode-agnostic. They used to
        // be here behind a `! class_exists(Platform)` gate + an `admin.domain`
        // pass-through shim; both are gone. Historical context retained below.
        //
        // Admin/landlord auth routes are SaaS-only: admin.php is wrapped in the
        // `admin.domain` middleware (an admin-subdomain guard registered by the
        // platform provider) and uses the `landlord` auth guard — neither of
        // which exists in standalone. In SaaS, AeroPlatformServiceProvider loads
        // admin.php with the proper domain constraint. Standalone auth is fully
        // served by tenant.php (/login, /logout, password reset, MFA on the web
        // guard with device binding), so admin.php must NOT be loaded here —
        // doing so 500s ("Target class [admin.domain] does not exist" /
        // "Auth guard [landlord] is not defined") and shadows the tenant /login.
    }
}
