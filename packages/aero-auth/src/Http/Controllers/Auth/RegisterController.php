<?php

namespace Aero\Auth\Http\Controllers\Auth;

use Aero\Auth\Http\Controllers\Controller;
use Aero\Auth\Services\ModernAuthenticationService;
use Aero\Auth\Models\User;
use Aero\Kernel\Support\SafeRedirect;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    protected ModernAuthenticationService $authService;

    public function __construct(ModernAuthenticationService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Shared/Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(Request $request)
    {
        // Rate limiting for registration
        $key = 'register.'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);

            $this->authService->logAuthenticationEvent(
                null,
                'registration_rate_limited',
                'failure',
                $request,
                ['email' => $request->email, 'retry_after' => $seconds]
            );

            throw ValidationException::withMessages([
                'email' => "Too many registration attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'terms' => 'required|accepted',
        ]);

        // Check if registration is allowed (you might want to add business logic here)
        if (! $this->isRegistrationAllowed($request)) {
            $this->authService->logAuthenticationEvent(
                null,
                'registration_not_allowed',
                'failure',
                $request,
                ['email' => $request->email]
            );

            throw ValidationException::withMessages([
                'email' => 'Registration is currently disabled or restricted.',
            ]);
        }

        try {
            $user = User::create([
                'name' => $request->name,
                'user_name' => $request->name, // Using name as username for now
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'security_notifications' => true,
                'notification_preferences' => json_encode([
                    'email_notifications' => true,
                    'security_alerts' => true,
                    'login_notifications' => true,
                ]),
            ]);

            // Clear rate limiting on successful registration
            RateLimiter::clear($key);

            // Log successful registration
            $this->authService->logAuthenticationEvent(
                $user,
                'registration_success',
                'success',
                $request
            );

            event(new Registered($user));

            Auth::login($user);

            // Track initial session
            $this->authService->trackUserSession($user, $request);

            // Update login stats for initial login
            $this->authService->updateLoginStats($user, $request);

            // Use SafeRedirect to ensure dashboard route exists and is accessible
            // This also validates domain context in multi-tenant environments
            return SafeRedirect::withSuccess(
                'dashboard',
                'Welcome! Your account has been created successfully.',
                []
            );

        } catch (\Exception $e) {
            RateLimiter::hit($key, 300); // 5 minutes decay for errors

            $this->authService->logAuthenticationEvent(
                null,
                'registration_failed',
                'failure',
                $request,
                ['email' => $request->email, 'error' => $e->getMessage()]
            );

            throw ValidationException::withMessages([
                'email' => 'Registration failed. Please try again.',
            ]);
        }
    }

    /**
     * Check if registration is allowed
     */
    protected function isRegistrationAllowed(Request $request): bool
    {
        // Add your business logic here
        // For example, you might want to:
        // - Only allow certain email domains
        // - Check if registration is enabled in settings
        // - Limit registrations during certain hours
        // - Require invitation codes

        return true; // Allow all registrations for now
    }
}
