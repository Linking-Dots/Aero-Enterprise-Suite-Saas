<?php

declare(strict_types=1);

namespace Aero\Auth\Contracts;

use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;

/**
 * AuthContext — describes the runtime context in which authentication operates.
 *
 * There are two concrete implementations:
 *   - TenantAuthContext   → web guard, Aero\Auth\Models\User, tenant login
 *   - LandlordAuthContext → landlord guard, Aero\Auth\Models\User, admin login
 *
 * Controllers and services consume this interface; they never branch on guard names
 * or model class strings directly.
 */
interface AuthContext
{
    /**
     * The Laravel auth guard name (e.g. 'web', 'landlord').
     */
    public function guard(): string;

    /**
     * The fully-qualified Eloquent model class used for authentication.
     *
     * @return class-string<User>
     */
    public function userModel(): string;

    /**
     * The named route to redirect to after a successful login.
     */
    public function dashboardRoute(): string;

    /**
     * The named route for the login page (used by guest redirects).
     */
    public function loginRoute(): string;

    /**
     * The Inertia component path rendered by the login controller.
     */
    public function loginView(): string;

    /**
     * Build the rate-limiter key for a given request.
     */
    public function throttleKey(Request $request): string;

    /**
     * Maximum login attempts before rate-limiting kicks in.
     */
    public function throttleMaxAttempts(): int;

    /**
     * Whether this context represents the landlord/platform-admin side.
     * Used by controllers to apply extra security steps (active-user check, etc.).
     */
    public function isLandlordContext(): bool;

    /**
     * The password-broker configuration name used for password resets.
     */
    public function passwordBroker(): string;
}
