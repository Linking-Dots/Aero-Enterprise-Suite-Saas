<?php

declare(strict_types=1);

namespace Aero\Platform\Auth;

use Aero\Auth\Contracts\AuthContext;
use Aero\Platform\Models\LandlordUser;
use Illuminate\Http\Request;

/**
 * LandlordAuthContext
 *
 * Drives authentication for platform-admin (landlord) users.
 * Uses the 'landlord' session guard backed by Aero\Platform\Models\LandlordUser.
 *
 * Registered by AeroPlatformServiceProvider using a domain-aware closure:
 *
 *   $this->app->bind(AuthContext::class, function ($app) {
 *       return IdentifyDomainContext::isAdmin($app['request'])
 *           ? new LandlordAuthContext()
 *           : new TenantAuthContext();
 *   });
 */
class LandlordAuthContext implements AuthContext
{
    public function guard(): string
    {
        return 'landlord';
    }

    public function userModel(): string
    {
        return LandlordUser::class;
    }

    public function dashboardRoute(): string
    {
        // B-36: the platform admin dashboard route is named platform.admin.dashboard.
        // The old 'admin.dashboard' is undefined, so post-login redirect fell back to
        // core.dashboard (a {tenant} route) and 500'd.
        return 'platform.admin.dashboard';
    }

    public function loginRoute(): string
    {
        return 'admin.login';
    }

    public function loginView(): string
    {
        return 'Platform/Admin/Auth/Login';
    }

    public function throttleKey(Request $request): string
    {
        return 'admin.login:'.strtolower((string) $request->input('email')).'|'.$request->ip();
    }

    public function throttleMaxAttempts(): int
    {
        return 5;
    }

    public function isLandlordContext(): bool
    {
        return true;
    }

    public function passwordBroker(): string
    {
        return 'landlord_users';
    }
}
