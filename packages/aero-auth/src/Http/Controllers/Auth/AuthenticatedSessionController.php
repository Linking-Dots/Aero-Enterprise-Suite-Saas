<?php

namespace Aero\Auth\Http\Controllers\Auth;

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
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        $request->session()->regenerate();

        // Context-aware landing: landlord login (admin/platform domain) must NOT
        // go to the tenant-scoped core.dashboard (needs a {tenant} param it can't
        // resolve here). Detect landlords by their table / domain context and send
        // them to the platform dashboard instead.
        $user = Auth::user();
        $isLandlord = $user
            && method_exists($user, 'getTable')
            && $user->getTable() === 'landlord_users';

        $context = $request->attributes->get('domain_context');
        $isAdminContext = in_array($context, [
            \Aero\Contracts\DomainContextContract::CONTEXT_ADMIN,
            \Aero\Contracts\DomainContextContract::CONTEXT_PLATFORM,
        ], true);

        $target = (($isLandlord || $isAdminContext) && \Illuminate\Support\Facades\Route::has('admin.dashboard'))
            ? 'admin.dashboard'
            : 'core.dashboard';

        return SafeRedirect::intended($target, true);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return SafeRedirect::toRoute('login', [], 'login');
    }
}
