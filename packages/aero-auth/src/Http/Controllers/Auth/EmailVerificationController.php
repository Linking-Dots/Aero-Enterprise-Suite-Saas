<?php

namespace Aero\Auth\Http\Controllers\Auth;

use Aero\Auth\Http\Controllers\Controller;
use Aero\Auth\Services\ModernAuthenticationService;
use Aero\Kernel\Support\SafeRedirect;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmailVerificationController extends Controller
{
    protected ModernAuthenticationService $authService;

    public function __construct(ModernAuthenticationService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Display the email verification prompt.
     */
    public function prompt(Request $request): Response
    {
        return $request->user()->hasVerifiedEmail()
                ? SafeRedirect::intended('core.dashboard')
                : Inertia::render('Auth/VerifyEmail', ['status' => session('status')]);
    }

    /**
     * Mark the authenticated user's email address as verified.
     */
    public function verify(EmailVerificationRequest $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            $dashboardUrl = SafeRedirect::routeExists('core.dashboard')
                ? route('core.dashboard').'?verified=1'
                : '/?verified=1';

            return redirect($dashboardUrl);
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));

            $this->authService->logAuthenticationEvent(
                $request->user(),
                'email_verified',
                'success',
                $request
            );
        }

        $dashboardUrl = SafeRedirect::routeExists('core.dashboard')
            ? route('core.dashboard').'?verified=1'
            : '/?verified=1';

        return redirect($dashboardUrl);
    }

    /**
     * Send a new email verification notification.
     */
    public function send(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return SafeRedirect::intended('core.dashboard');
        }

        $request->user()->sendEmailVerificationNotification();

        $this->authService->logAuthenticationEvent(
            $request->user(),
            'verification_email_sent',
            'success',
            $request
        );

        return SafeRedirect::back('core.dashboard')->with('status', 'verification-link-sent');
    }
}
