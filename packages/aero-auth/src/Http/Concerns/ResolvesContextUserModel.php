<?php

declare(strict_types=1);

namespace Aero\Auth\Http\Concerns;

use Illuminate\Support\Facades\Auth;

/**
 * Resolves the authenticated-user Eloquent model class from the guard that
 * actually authenticated the current request — never a hardcoded tenant or
 * platform class.
 *
 * This mirrors the context-free pattern aero-hrmac's RoleController already
 * proves out (`config('hrmac.models.user') ?: config('auth.providers.users.model')`),
 * generalised to ANY guard: `web` resolves the tenant `users` provider,
 * `landlord` resolves the central provider — today both point at the same
 * unified `Aero\Auth\Models\User` (Boss's Auth-Identity Unification), but
 * this controller makes zero assumption of that and would keep working if a
 * guard/provider ever diverges again.
 */
trait ResolvesContextUserModel
{
    /**
     * The guard name that authenticated the current request. Falls back to
     * the application's configured default guard when no guard reports an
     * authenticated user (e.g. console/test/unauthenticated contexts).
     */
    protected function resolveGuardName(): string
    {
        foreach (array_keys(config('auth.guards', [])) as $name) {
            if (Auth::guard($name)->check()) {
                return $name;
            }
        }

        return Auth::getDefaultDriver();
    }

    /**
     * The Eloquent user model class backing the resolved guard's provider.
     */
    protected function resolveUserModel(): string
    {
        $guard = $this->resolveGuardName();
        $provider = config("auth.guards.{$guard}.provider");
        $model = $provider ? config("auth.providers.{$provider}.model") : null;

        return $model ?: config('auth.providers.users.model');
    }
}
