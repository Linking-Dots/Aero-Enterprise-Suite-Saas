<?php

namespace Aero\Auth\Http\Controllers\Auth;

use Aero\Auth\Http\Controllers\Controller;
use Aero\Kernel\Support\SafeRedirect;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Plan 05 (aero-auth) Task 1 of foundation 10/10 push.
 *
 * CRITICAL security hardening of password reset link request:
 *
 *   1. Rate limiting per-email AND per-IP (previously: NONE — anyone
 *      could spam any email at any rate).
 *
 *   2. Account-enumeration mitigation: returns the SAME response shape
 *      whether the email exists or not (previously: distinct success
 *      vs error responses leaked account existence).
 *
 * The actual Password::sendResetLink call still runs — but its result
 * never differentiates the response. Users see "If an account with that
 * email exists, a password reset link has been sent." every time. Real
 * users get the email; attackers learn nothing.
 */
class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/ForgotPassword', [
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming password reset link request.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = strtolower((string) $request->email);

        $perEmailKey = 'pwreset.email.'.sha1($email);
        $perIpKey = 'pwreset.ip.'.$request->ip();

        // Per-email limit: 5 attempts per hour
        if (RateLimiter::tooManyAttempts($perEmailKey, 5)) {
            return $this->uniformResponse($request, 429);
        }
        // Per-IP limit: 10 attempts per 10 minutes (prevents per-IP enumeration)
        if (RateLimiter::tooManyAttempts($perIpKey, 10)) {
            return $this->uniformResponse($request, 429);
        }

        RateLimiter::hit($perEmailKey, 3600); // 1 hour decay
        RateLimiter::hit($perIpKey, 600);     // 10 min decay

        // Send reset link — but ignore success/failure for response shape.
        // Real users with valid emails get the actual reset link via email;
        // the HTTP response shape never differentiates so attackers cannot
        // enumerate registered accounts.
        Password::sendResetLink(['email' => $email]);

        return $this->uniformResponse($request, 200);
    }

    /**
     * Return the same response shape regardless of whether the email exists.
     */
    private function uniformResponse(Request $request, int $status): RedirectResponse|JsonResponse
    {
        $message = __('If an account with that email exists, a password reset link has been sent.');

        if ($request->expectsJson()) {
            return response()->json([
                'status'  => $message,
                'success' => $status === 200,
            ], $status);
        }

        return SafeRedirect::back('login')->with('status', $message);
    }
}
