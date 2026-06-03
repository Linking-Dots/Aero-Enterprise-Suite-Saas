<?php

namespace Aero\Auth\Http\Controllers\Auth;

use Aero\Auth\Contracts\AuthContext;
use Aero\Auth\Http\Controllers\Controller;
use Aero\Core\Support\SafeRedirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => true,
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request, AuthContext $context): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Resolve guard + post-login target from the active AuthContext. aero-auth
        // stays mode-agnostic: standalone/tenant binds TenantAuthContext (web ->
        // core.dashboard); the SaaS host rebinds LandlordAuthContext on the admin
        // domain (landlord -> admin.dashboard). No mode/guard branching here.
        if (! Auth::guard($context->guard())->attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        $request->session()->regenerate();

        return SafeRedirect::intended($context->dashboardRoute(), true);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request, AuthContext $context): RedirectResponse
    {
        Auth::guard($context->guard())->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return SafeRedirect::toRoute($context->loginRoute(), [], 'login');
    }
}
