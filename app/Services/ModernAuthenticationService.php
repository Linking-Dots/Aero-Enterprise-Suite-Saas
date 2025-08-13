<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ModernAuthenticationService
{
    protected const MAX_LOGIN_ATTEMPTS = 5;
    protected const LOCKOUT_DURATION = 30; // minutes
    protected const SESSION_LIFETIME = 120; // minutes

    /**
     * Log authentication events with comprehensive tracking
     */
    public function logAuthenticationEvent(
        ?User $user,
        string $eventType,
        string $status,
        Request $request,
        array $metadata = []
    ): void {
        try {
            $riskLevel = $this->calculateRiskLevel($eventType, $status, $request, $user);

            DB::table('authentication_events')->insert([
                'user_id' => $user?->id,
                'event_type' => $eventType,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'metadata' => json_encode(array_merge($metadata, [
                    'route' => $request->route()?->getName(),
                    'method' => $request->method(),
                    'timestamp' => now()->toISOString(),
                    'session_id' => session()->getId(),
                ])),
                'status' => $status,
                'risk_level' => $riskLevel,
                'occurred_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::channel('auth')->info("Authentication Event: {$eventType}", [
                'user_id' => $user?->id,
                'email' => $user?->email ?? $metadata['email'] ?? null,
                'status' => $status,
                'risk_level' => $riskLevel,
                'ip' => $request->ip(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log authentication event', [
                'error' => $e->getMessage(),
                'event_type' => $eventType,
                'user_id' => $user?->id,
            ]);
        }
    }

    /**
     * Track user session with device and location information
     */
    public function trackUserSession(User $user, Request $request): void
    {
        try {
            $sessionId = session()->getId();
            $deviceInfo = $this->extractDeviceInfo($request);
            $locationInfo = $this->getLocationInfo($request->ip());

            // Invalidate old sessions if user has too many active
            $this->cleanupOldSessions($user);

            DB::table('user_sessions')->updateOrInsert(
                ['session_id' => $sessionId],
                [
                    'user_id' => $user->id,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'device_fingerprint' => $this->generateDeviceFingerprint($request),
                    'device_info' => json_encode($deviceInfo),
                    'location_info' => json_encode($locationInfo),
                    'is_current' => true,
                    'last_activity' => now(),
                    'expires_at' => now()->addMinutes(self::SESSION_LIFETIME),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        } catch (\Exception $e) {
            Log::error('Failed to track user session', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);
        }
    }

    /**
     * Update user login statistics
     */
    public function updateLoginStats(User $user, Request $request): void
    {
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
            'login_count' => $user->login_count + 1,
        ]);
    }

    /**
     * Check if account is locked due to failed attempts
     */
    public function isAccountLocked(string $email): bool
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            return false;
        }

        // Check if account is manually locked
        if ($user->account_locked_at && !$this->isLockExpired($user)) {
            return true;
        }

        // Check failed attempts in the last period
        $recentFailures = $this->getRecentFailedAttempts($email);

        if ($recentFailures >= self::MAX_LOGIN_ATTEMPTS) {
            $this->lockAccount($user, 'too_many_failed_attempts');
            return true;
        }

        return false;
    }

    /**
     * Record failed login attempt
     */
    public function recordFailedAttempt(string $email, Request $request, string $reason): void
    {
        DB::table('failed_login_attempts')->insert([
            'email' => $email,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'failure_reason' => $reason,
            'metadata' => json_encode([
                'route' => $request->route()?->getName(),
                'timestamp' => now()->toISOString(),
            ]),
            'attempted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Generate secure password reset token with OTP
     */
    public function generatePasswordResetToken(string $email, Request $request): array
    {
        // Clean up old tokens
        DB::table('password_reset_tokens_secure')
            ->where('email', $email)
            ->where('expires_at', '<', now())
            ->delete();

        $token = Str::random(64);
        $verificationCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table('password_reset_tokens_secure')->insert([
            'email' => $email,
            'token' => Hash::make($token),
            'verification_code' => $verificationCode,
            'is_verified' => false,
            'attempts' => 0,
            'expires_at' => now()->addHours(1),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'token' => $token,
            'verification_code' => $verificationCode,
            'expires_at' => now()->addHours(1),
        ];
    }

    /**
     * Verify password reset token and code
     */
    public function verifyPasswordResetToken(string $email, string $token, string $verificationCode): bool
    {
        $record = DB::table('password_reset_tokens_secure')
            ->where('email', $email)
            ->where('verification_code', $verificationCode)
            ->where('expires_at', '>', now())
            ->where('attempts', '<', 5)
            ->first();

        if (!$record) {
            return false;
        }

        if (!Hash::check($token, $record->token)) {
            // Increment attempts
            DB::table('password_reset_tokens_secure')
                ->where('id', $record->id)
                ->increment('attempts');
            return false;
        }

        // Mark as verified
        DB::table('password_reset_tokens_secure')
            ->where('id', $record->id)
            ->update([
                'is_verified' => true,
                'verified_at' => now(),
            ]);

        return true;
    }

    /**
     * Clean up expired sessions and limit concurrent sessions
     */
    public function cleanupOldSessions(User $user, int $maxSessions = 5): void
    {
        // Remove expired sessions
        DB::table('user_sessions')
            ->where('expires_at', '<', now())
            ->delete();

        // Get active sessions for user
        $activeSessions = DB::table('user_sessions')
            ->where('user_id', $user->id)
            ->where('is_current', true)
            ->orderBy('last_activity', 'desc')
            ->get();

        // If too many sessions, deactivate oldest ones
        if ($activeSessions->count() >= $maxSessions) {
            $sessionsToDeactivate = $activeSessions->slice($maxSessions - 1);

            foreach ($sessionsToDeactivate as $session) {
                DB::table('user_sessions')
                    ->where('id', $session->id)
                    ->update(['is_current' => false]);
            }
        }
    }

    /**
     * Generate device fingerprint for tracking
     */
    protected function generateDeviceFingerprint(Request $request): string
    {
        $components = [
            $request->userAgent(),
            $request->header('accept-language'),
            $request->header('accept-encoding'),
            $request->header('accept'),
            // Note: Not including IP as it can change
        ];

        return hash('sha256', implode('|', array_filter($components)));
    }

    /**
     * Extract device information from user agent
     */
    protected function extractDeviceInfo(Request $request): array
    {
        $userAgent = $request->userAgent();

        return [
            'browser' => $this->getBrowserFromUserAgent($userAgent),
            'platform' => $this->getPlatformFromUserAgent($userAgent),
            'device_type' => $this->getDeviceTypeFromUserAgent($userAgent),
            'is_mobile' => $request->header('sec-ch-ua-mobile') === '?1',
        ];
    }

    /**
     * Get location information from IP address
     */
    protected function getLocationInfo(string $ip): array
    {
        // For localhost/development
        if (in_array($ip, ['127.0.0.1', '::1', 'localhost'])) {
            return [
                'country' => 'Local',
                'city' => 'Development',
                'timezone' => config('app.timezone'),
            ];
        }

        // In production, you might use a service like MaxMind GeoIP2
        // For now, return basic info
        return [
            'country' => 'Unknown',
            'city' => 'Unknown',
            'timezone' => config('app.timezone'),
        ];
    }

    /**
     * Calculate risk level for authentication events
     */
    protected function calculateRiskLevel(string $eventType, string $status, Request $request, ?User $user): string
    {
        $riskFactors = 0;

        // Failed events increase risk
        if ($status === 'failure') {
            $riskFactors += 2;
        }

        // Check for suspicious patterns
        if ($user) {
            // New IP address
            if ($user->last_login_ip && $user->last_login_ip !== $request->ip()) {
                $riskFactors += 1;
            }

            // Multiple recent failures
            $recentFailures = $this->getRecentFailedAttempts($user->email);
            if ($recentFailures > 2) {
                $riskFactors += 2;
            }
        }

        // Time-based risk (outside business hours)
        $currentHour = now()->hour;
        if ($currentHour < 6 || $currentHour > 22) {
            $riskFactors += 1;
        }

        return match (true) {
            $riskFactors >= 4 => 'critical',
            $riskFactors >= 3 => 'high',
            $riskFactors >= 2 => 'medium',
            default => 'low',
        };
    }

    /**
     * Get recent failed attempts for email
     */
    protected function getRecentFailedAttempts(string $email): int
    {
        return DB::table('failed_login_attempts')
            ->where('email', $email)
            ->where('attempted_at', '>=', now()->subMinutes(self::LOCKOUT_DURATION))
            ->count();
    }

    /**
     * Lock user account
     */
    protected function lockAccount(User $user, string $reason): void
    {
        $user->update([
            'account_locked_at' => now(),
            'locked_reason' => $reason,
        ]);
    }

    /**
     * Check if account lock has expired
     */
    protected function isLockExpired(User $user): bool
    {
        if (!$user->account_locked_at) {
            return true;
        }

        return $user->account_locked_at->addMinutes(self::LOCKOUT_DURATION)->isPast();
    }

    /**
     * Simple browser detection from user agent
     */
    protected function getBrowserFromUserAgent(string $userAgent): string
    {
        if (str_contains($userAgent, 'Chrome')) return 'Chrome';
        if (str_contains($userAgent, 'Firefox')) return 'Firefox';
        if (str_contains($userAgent, 'Safari')) return 'Safari';
        if (str_contains($userAgent, 'Edge')) return 'Edge';
        if (str_contains($userAgent, 'Opera')) return 'Opera';

        return 'Unknown';
    }

    /**
     * Simple platform detection from user agent
     */
    protected function getPlatformFromUserAgent(string $userAgent): string
    {
        if (str_contains($userAgent, 'Windows')) return 'Windows';
        if (str_contains($userAgent, 'Macintosh')) return 'macOS';
        if (str_contains($userAgent, 'Linux')) return 'Linux';
        if (str_contains($userAgent, 'Android')) return 'Android';
        if (str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'iPad')) return 'iOS';

        return 'Unknown';
    }

    /**
     * Simple device type detection from user agent
     */
    protected function getDeviceTypeFromUserAgent(string $userAgent): string
    {
        if (str_contains($userAgent, 'Mobile') || str_contains($userAgent, 'Android')) return 'mobile';
        if (str_contains($userAgent, 'Tablet') || str_contains($userAgent, 'iPad')) return 'tablet';

        return 'desktop';
    }
}
