<?php

namespace Aero\Auth\Http\Controllers\Auth;

use Aero\Auth\Http\Controllers\Controller;
use Aero\Auth\Services\DeviceAuthService;
use Aero\Auth\Services\ModernAuthenticationService;
use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Models\User;
use Aero\Core\Models\UserDevice;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Core\Support\SafeRedirect;
use Aero\Platform\Http\Middleware\IdentifyDomainContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    protected ModernAuthenticationService $authService;

    protected DeviceAuthService $deviceAuthService;

    protected AuditServiceInterface $audit;

    public function __construct(
        ModernAuthenticationService $authService,
        DeviceAuthService $deviceAuthService,
        AuditServiceInterface $audit
    ) {
        $this->authService = $authService;
        $this->deviceAuthService = $deviceAuthService;
        $this->audit = $audit;
    }

    /**
     * Display the login view.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => true,
            'status' => session('status'),
            'deviceBlocked' => session('device_blocked', false),
            'deviceMessage' => session('device_message'),
            'blockedDeviceInfo' => session('blocked_device_info'),
            'canRegister' => IdentifyDomainContext::isPlatform($request),
            'oauthProviders' => $this->getAvailableOAuthProviders(),
        ]);
    }

    /**
     * Get list of available OAuth providers.
     */
    protected function getAvailableOAuthProviders(): array
    {
        $providers = [];
        $supportedProviders = ['google', 'microsoft', 'github'];

        foreach ($supportedProviders as $provider) {
            $config = config("services.{$provider}");

            if (! empty($config['client_id']) && ! empty($config['client_secret'])) {
                $providers[] = [
                    'name' => $provider,
                    'label' => match ($provider) {
                        'google' => 'Google',
                        'microsoft' => 'Microsoft',
                        'github' => 'GitHub',
                        default => ucfirst($provider),
                    },
                    'url' => route('auth.social.redirect', ['provider' => $provider]),
                ];
            }
        }

        return $providers;
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
            'remember' => 'boolean',
        ]);

        $email = $request->email;
        $password = $request->password;
        $remember = $request->boolean('remember');

        // Check rate limiting
        $key = 'login.'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            $this->authService->logAuthenticationEvent(
                null,
                'login_rate_limited',
                'failure',
                $request,
                ['email' => $email, 'retry_after' => $seconds]
            );

            throw ValidationException::withMessages([
                'email' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        // Check if account is locked
        if ($this->authService->isAccountLocked($email)) {
            $this->authService->logAuthenticationEvent(
                null,
                'login_account_locked',
                'failure',
                $request,
                ['email' => $email]
            );

            throw ValidationException::withMessages([
                'email' => 'This account has been temporarily locked due to multiple failed login attempts.',
            ]);
        }

        // Find user
        $user = User::where('email', $email)->first();

        // Validate credentials
        if (! $user || ! Hash::check($password, $user->password)) {
            RateLimiter::hit($key, 60); // 1 minute decay

            $this->authService->recordFailedAttempt(
                $email,
                $request,
                $user ? 'invalid_password' : 'invalid_email'
            );

            $this->authService->logAuthenticationEvent(
                $user,
                'login_failed',
                'failure',
                $request,
                ['email' => $email, 'reason' => 'invalid_credentials']
            );

            throw ValidationException::withMessages([
                'email' => 'The provided credentials are incorrect.',
            ]);
        }

        // Check if user account is active
        if (! $user->active) {
            $this->authService->logAuthenticationEvent(
                $user,
                'login_inactive_account',
                'failure',
                $request
            );

            throw ValidationException::withMessages([
                'email' => 'This account has been deactivated. Please contact your administrator.',
            ]);
        }

        // NEW SECURE DEVICE BINDING LOGIC
        // Get device_id from request (UUIDv4 from frontend)
        $deviceId = $request->input('device_id') ?? $request->header('X-Device-ID');

        if (! $deviceId) {
            throw ValidationException::withMessages([
                'device_id' => 'Device identification is required for security.',
            ]);
        }

        // Check if user can login from this device
        $deviceCheck = $this->deviceAuthService->canLoginFromDevice($user, $deviceId);

        if (! $deviceCheck['allowed']) {
            $blockedDevice = $deviceCheck['device'] ?? null;

            $this->authService->logAuthenticationEvent(
                $user,
                'login_device_blocked',
                'failure',
                $request,
                [
                    'device_id' => $deviceId,
                    'blocked_by_device' => $blockedDevice?->id,
                    'message' => $deviceCheck['message'],
                ]
            );

            $deviceBlockedData = [
                'blocked' => true,
                'message' => $deviceCheck['message'],
                'blocked_device_info' => $blockedDevice ? [
                    'device_name' => $blockedDevice->device_name,
                    'browser' => $blockedDevice->browser,
                    'platform' => $blockedDevice->platform,
                    'device_type' => $blockedDevice->device_type,
                    'ip_address' => $blockedDevice->ip_address,
                    'last_used_at' => $blockedDevice->last_used_at ?
                        $blockedDevice->last_used_at->format('M j, Y g:i A') : null,
                ] : null,
            ];

            throw ValidationException::withMessages([
                'device_blocking' => [$deviceCheck['message']],
                'device_blocking_data' => [json_encode($deviceBlockedData)],
            ]);
        }

        // Clear rate limiting on successful login
        RateLimiter::clear($key);

        // Login user
        Auth::login($user, $remember);

        // Regenerate session for security
        $request->session()->regenerate();

        // Register/update device with secure token
        $device = $this->deviceAuthService->registerDevice($user, $request, $deviceId);

        if (! $device) {
            // If device registration failed, log out and throw error
            Auth::logout();

            throw ValidationException::withMessages([
                'device_id' => 'Failed to register device. Please try again.',
            ]);
        }

        // Update login statistics
        $this->authService->updateLoginStats($user, $request);

        // Track session
        $this->authService->trackUserSession($user, $request);

        // Log successful login
        $this->authService->logAuthenticationEvent(
            $user,
            'login_success',
            'success',
            $request
        );

        $redirectTo = $this->determineRedirectPath($request, $user);

        // Use SafeRedirect for secure, domain-validated redirect
        return SafeRedirect::intended($redirectTo, true);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request)
    {
        $user = Auth::user();

        if ($user) {
            // Get device_id from request
            $deviceId = $request->header('X-Device-ID') ?? $request->input('device_id');

            if ($deviceId) {
                // Find and deactivate the device
                $device = UserDevice::where('user_id', $user->id)
                    ->where('device_id', $deviceId)
                    ->first();

                if ($device) {
                    $device->deactivate();
                }
            }

            // Log logout event
            $this->authService->logAuthenticationEvent(
                $user,
                'logout',
                'success',
                $request
            );

            // Update session tracking if exists
            $sessionId = session()->getId();
            DB::table('user_sessions')
                ->where('session_id', $sessionId)
                ->update([
                    'is_current' => false,
                    'updated_at' => now(),
                ]);
        }

        Auth::guard('web')->logout();

        $this->audit->log(
            event:       AuditEventType::LOGOUT->value,
            action:      'logout',
            description: 'User logged out',
        );

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Use SafeRedirect to ensure login route exists
        return SafeRedirect::toRoute('login', [], 'login');
    }

    /**
     * Determine where to redirect the user after authentication.
     */
    protected function determineRedirectPath(Request $request, User $user): string
    {
        $context = IdentifyDomainContext::getContext($request);

        return match ($context) {
            IdentifyDomainContext::CONTEXT_ADMIN => route('admin.dashboard'),
            IdentifyDomainContext::CONTEXT_TENANT => route('core.dashboard'),
            default => $user->hasRole('super-admin')
                ? route('admin.dashboard')
                : route('platform.billing.index'),
        };
    }
}
