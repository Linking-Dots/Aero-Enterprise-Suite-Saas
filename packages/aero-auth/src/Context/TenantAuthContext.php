<?php

declare(strict_types=1);

namespace Aero\Auth\Context;

use Aero\Auth\Contracts\AuthContext;
use Aero\Auth\Models\User;
use Illuminate\Http\Request;

/**
 * TenantAuthContext
 *
 * Drives authentication for tenant users (SaaS tenant subdomains + standalone mode).
 * Uses the default 'web' session guard backed by Aero\Auth\Models\User.
 *
 * Registered by AeroCoreServiceProvider:
 *   $this->app->bind(AuthContext::class, TenantAuthContext::class);
 */
class TenantAuthContext implements AuthContext
{
    public function guard(): string
    {
        return 'web';
    }

    public function userModel(): string
    {
        return User::class;
    }

    public function dashboardRoute(): string
    {
        return 'core.dashboard';
    }

    public function loginRoute(): string
    {
        return 'login';
    }

    public function loginView(): string
    {
        return 'Shared/Auth/Login';
    }

    public function throttleKey(Request $request): string
    {
        return 'login:'.strtolower((string) $request->input('email')).'|'.$request->ip();
    }

    public function throttleMaxAttempts(): int
    {
        return 5;
    }

    public function isLandlordContext(): bool
    {
        return false;
    }

    public function passwordBroker(): string
    {
        return 'users';
    }
}
