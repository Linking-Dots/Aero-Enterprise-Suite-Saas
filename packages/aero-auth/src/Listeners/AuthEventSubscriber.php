<?php

namespace Aero\Auth\Listeners;

use Aero\Core\Models\User;
use Aero\Notifications\Models\UserNotificationPreference;
use Illuminate\Auth\Events\Attempting;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Auth\Events\CurrentDeviceLogout;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\OtherDeviceLogout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Validated;
use Illuminate\Auth\Events\Verified;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;

class AuthEventSubscriber
{
    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            Login::class => 'handleLogin',
            Logout::class => 'handleLogout',
            Failed::class => 'handleFailed',
            Lockout::class => 'handleLockout',
            Registered::class => 'handleRegistered',
            PasswordReset::class => 'handlePasswordReset',
            Verified::class => 'handleVerified',
            Attempting::class => 'handleAttempting',
            Authenticated::class => 'handleAuthenticated',
            Validated::class => 'handleValidated',
            CurrentDeviceLogout::class => 'handleCurrentDeviceLogout',
            OtherDeviceLogout::class => 'handleOtherDeviceLogout',
        ];
    }

    /**
     * Handle user login events.
     */
    public function handleLogin(Login $event): void
    {
        $this->logActivity($event->user, 'login', 'User logged in successfully', [
            'guard' => $event->guard ?? 'web',
            'remember' => $event->remember ?? false,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        Log::channel('auth')->info('User login', [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
            'ip' => request()->ip(),
        ]);
    }

    /**
     * Handle user logout events.
     */
    public function handleLogout(Logout $event): void
    {
        if ($event->user) {
            $this->logActivity($event->user, 'logout', 'User logged out', [
                'guard' => $event->guard ?? 'web',
                'ip' => request()->ip(),
            ]);

            Log::channel('auth')->info('User logout', [
                'user_id' => $event->user->id,
                'email' => $event->user->email,
                'ip' => request()->ip(),
            ]);
        }
    }

    /**
     * Handle failed login attempts.
     */
    public function handleFailed(Failed $event): void
    {
        $userId = $event->user?->id;
        $email = $event->credentials['email'] ?? 'unknown';

        if ($event->user) {
            $this->logActivity($event->user, 'login_failed', 'Failed login attempt', [
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'guard' => $event->guard ?? 'web',
            ]);
        }

        Log::channel('auth')->warning('Failed login attempt', [
            'email' => $email,
            'user_id' => $userId,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Handle lockout events.
     */
    public function handleLockout(Lockout $event): void
    {
        $email = $event->request->input('email', 'unknown');

        Log::channel('auth')->warning('Account lockout', [
            'email' => $email,
            'ip' => $event->request->ip(),
            'user_agent' => $event->request->userAgent(),
            'lockout_duration' => config('auth.lockout.duration', 60),
        ]);

        // Try to find user and log activity
        $user = User::where('email', $email)->first();
        if ($user) {
            $this->logActivity($user, 'lockout', 'Account locked out due to too many failed attempts', [
                'ip' => $event->request->ip(),
                'duration' => config('auth.lockout.duration', 60),
            ]);
        }
    }

    /**
     * Handle user registration events.
     */
    public function handleRegistered(Registered $event): void
    {
        $this->logActivity($event->user, 'registered', 'New user registration', [
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'referrer' => request()->header('referer'),
        ]);

        // Seed default notification preferences for the new user
        try {
            UserNotificationPreference::seedDefaultsForUser($event->user->id);
        } catch (\Throwable $e) {
            Log::warning('Failed to seed default notification preferences', [
                'user_id' => $event->user->id,
                'error' => $e->getMessage(),
            ]);
        }

        Log::channel('auth')->info('New user registered', [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
            'ip' => request()->ip(),
        ]);
    }

    /**
     * Handle password reset events.
     */
    public function handlePasswordReset(PasswordReset $event): void
    {
        $this->logActivity($event->user, 'password_reset', 'Password was reset', [
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        Log::channel('auth')->info('Password reset', [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
            'ip' => request()->ip(),
        ]);
    }

    /**
     * Handle email verification events.
     */
    public function handleVerified(Verified $event): void
    {
        $this->logActivity($event->user, 'email_verified', 'Email address verified', [
            'ip' => request()->ip(),
        ]);

        Log::channel('auth')->info('Email verified', [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
        ]);
    }

    /**
     * Handle authentication attempt events.
     */
    public function handleAttempting(Attempting $event): void
    {
        Log::channel('auth')->debug('Authentication attempt', [
            'email' => $event->credentials['email'] ?? 'unknown',
            'guard' => $event->guard ?? 'web',
            'remember' => $event->remember ?? false,
            'ip' => request()->ip(),
        ]);
    }

    /**
     * Handle authenticated events (credentials validated, pre-login).
     */
    public function handleAuthenticated(Authenticated $event): void
    {
        Log::channel('auth')->debug('User authenticated', [
            'user_id' => $event->user->id,
            'guard' => $event->guard ?? 'web',
        ]);
    }

    /**
     * Handle validated events.
     */
    public function handleValidated(Validated $event): void
    {
        Log::channel('auth')->debug('Credentials validated', [
            'user_id' => $event->user->id,
            'guard' => $event->guard ?? 'web',
        ]);
    }

    /**
     * Handle current device logout events.
     */
    public function handleCurrentDeviceLogout(CurrentDeviceLogout $event): void
    {
        if ($event->user) {
            $this->logActivity($event->user, 'current_device_logout', 'Logged out from current device', [
                'ip' => request()->ip(),
            ]);

            Log::channel('auth')->info('Current device logout', [
                'user_id' => $event->user->id,
            ]);
        }
    }

    /**
     * Handle other device logout events.
     */
    public function handleOtherDeviceLogout(OtherDeviceLogout $event): void
    {
        if ($event->user) {
            $this->logActivity($event->user, 'other_device_logout', 'Logged out from other devices', [
                'ip' => request()->ip(),
            ]);

            Log::channel('auth')->info('Other devices logout', [
                'user_id' => $event->user->id,
            ]);
        }
    }

    /**
     * Log auth activity through the canonical AuditService.
     *
     * Plan 05 (aero-auth) Task 5 — Phase 1 audit found three competing audit
     * channels in the auth package:
     *   1. AuditService::log() — used by LoginController logout
     *   2. authService.logAuthenticationEvent() — writes to authentication_events table
     *   3. Spatie activity()->log() — used here in AuthEventSubscriber
     *   4. Log::channel('auth')->info() — also used here
     *
     * Channels 1 & 2 are canonical (structured + queryable). Channel 3 (Spatie)
     * is being phased out across the codebase. This refactor swaps Spatie for
     * AuditService so EVERY auth event lands in the same place compliance
     * dashboards query.
     *
     * The Log::channel('auth') calls in individual handlers stay — they're for
     * SIEM tailing / short-term ops dashboards, not for compliance.
     */
    protected function logActivity($user, string $event, string $description, array $properties = []): void
    {
        if (! $user instanceof User) {
            return;
        }

        try {
            app(\Aero\Contracts\AuditServiceInterface::class)->log(
                event: 'auth.'.$event,
                action: $event,
                subject: $user,
                description: $description,
                metadata: array_merge($properties, [
                    'event_type' => $event,
                    'timestamp' => now()->toIso8601String(),
                ]),
            );
        } catch (\Throwable $e) {
            // Audit failure must not break the auth flow — fall back to the
            // log channel so the signal isn't lost entirely.
            Log::warning('Failed to log auth activity via AuditService', [
                'error' => $e->getMessage(),
                'event' => $event,
                'user_id' => $user->id,
            ]);
        }
    }
}
