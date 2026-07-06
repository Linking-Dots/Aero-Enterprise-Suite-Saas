<?php

declare(strict_types=1);

namespace Aero\Auth\Http\Concerns;

use Illuminate\Support\Facades\Auth;

/**
 * Identity is ONE shared model — `Aero\Auth\Models\User` (Boss's Auth-Identity
 * Unification). Both guards resolve to it (`web` via the legacy
 * `Aero\Core\Models\User` class_alias; `landlord` directly), and the model is
 * connection-agnostic, so tenancy alone decides whether a query hits the tenant
 * DB or the central DB — the CLASS never changes between contexts. There is no
 * per-context user model.
 *
 * What genuinely differs per context is the GUARD (web vs landlord), which
 * impersonation needs to log the target user into the correct guard.
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
     * The one shared user model. Connection-agnostic — tenancy swaps the default
     * connection, so the same class serves tenant and platform.
     *
     * @return class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected function resolveUserModel(): string
    {
        return \Aero\Auth\Models\User::class;
    }
}
