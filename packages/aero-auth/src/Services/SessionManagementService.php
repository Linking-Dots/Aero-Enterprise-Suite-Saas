<?php

declare(strict_types=1);

namespace Aero\Auth\Services;

use Aero\Auth\Models\User;
use Aero\Auth\Models\UserSession;
use Aero\Contracts\AuditServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Session Management Service
 *
 * Tracks and manages user sessions across devices.
 *
 * Features:
 * - Track active sessions per user
 * - Session metadata (device, browser, IP, location)
 * - Terminate sessions remotely
 * - Concurrent session limits
 * - Session activity monitoring
 *
 * Usage:
 * ```php
 * $sessionService = app(SessionManagementService::class);
 *
 * // Create session on login
 * $session = $sessionService->createSession($user, $request);
 *
 * // Get user's active sessions
 * $sessions = $sessionService->getUserSessions($user);
 *
 * // Terminate a specific session
 * $sessionService->terminateSession($user, $sessionId);
 *
 * // Terminate all other sessions
 * $sessionService->terminateOtherSessions($user, $currentSessionId);
 * ```
 */
class SessionManagementService
{
    /**
     * Maximum concurrent sessions per user (0 = unlimited).
     */
    protected int $maxSessions = 5;

    /**
     * Session inactivity timeout in minutes.
     */
    protected int $inactivityTimeout = 60;

    public function __construct(protected ?AuditServiceInterface $auditService = null)
    {
        $this->maxSessions = config('auth.max_sessions', 5);
        $this->inactivityTimeout = config('auth.session_timeout', 60);
        $this->auditService = $auditService ?? app(AuditServiceInterface::class);
    }

    /**
     * Create a new session for a user.
     */
    public function createSession(User $user, Request $request): UserSession
    {
        // Check concurrent session limit
        $this->enforceSessionLimit($user);

        $sessionToken = Str::random(64);

        $session = UserSession::create([
            'user_id' => $user->id,
            'session_token' => hash('sha256', $sessionToken),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'device_type' => $this->detectDeviceType($request->userAgent()),
            'browser' => $this->detectBrowser($request->userAgent()),
            'platform' => $this->detectPlatform($request->userAgent()),
            'location' => $this->getLocationFromIp($request->ip()),
            'is_current' => true,
            'last_active_at' => now(),
            'expires_at' => now()->addMinutes($this->inactivityTimeout),
        ]);

        // Store the plain token for response (only shown once)
        $session->plain_token = $sessionToken;

        // Log session creation
        $deviceName = $session->device_type.' - '.$session->browser;
        $this->auditService?->logSessionCreated($user, $deviceName);

        return $session;
    }

    /**
     * Update session activity timestamp.
     */
    public function touchSession(string $sessionToken): void
    {
        UserSession::where('session_token', hash('sha256', $sessionToken))
            ->update([
                'last_active_at' => now(),
                'expires_at' => now()->addMinutes($this->inactivityTimeout),
            ]);
    }

    /**
     * Get all active sessions for a user.
     *
     * @return Collection
     */
    public function getUserSessions(User $user)
    {
        return UserSession::where('user_id', $user->id)
            ->where('expires_at', '>', now())
            ->orderByDesc('last_active_at')
            ->get()
            ->map(function ($session) {
                return [
                    'id' => $session->id,
                    'device_type' => $session->device_type,
                    'browser' => $session->browser,
                    'platform' => $session->platform,
                    'ip_address' => $session->ip_address,
                    'location' => $session->location,
                    'is_current' => $session->is_current,
                    'last_active_at' => $session->last_active_at->diffForHumans(),
                    'created_at' => $session->created_at->diffForHumans(),
                ];
            });
    }

    /**
     * Terminate a specific session.
     */
    public function terminateSession(User $user, int $sessionId): bool
    {
        $result = UserSession::where('user_id', $user->id)
            ->where('id', $sessionId)
            ->delete() > 0;

        if ($result) {
            // Log session termination
            $this->auditService?->logSessionTerminated($user, (string) $sessionId, true);
        }

        return $result;
    }

    /**
     * Terminate all sessions except the current one.
     *
     * @return int Number of sessions terminated
     */
    public function terminateOtherSessions(User $user, int $currentSessionId): int
    {
        return UserSession::where('user_id', $user->id)
            ->where('id', '!=', $currentSessionId)
            ->delete();
    }

    /**
     * Terminate all sessions for a user.
     *
     * @return int Number of sessions terminated
     */
    public function terminateAllSessions(User $user): int
    {
        $count = UserSession::where('user_id', $user->id)->count();
        $deleted = UserSession::where('user_id', $user->id)->delete();

        if ($deleted > 0) {
            // Log all sessions terminated
            $this->auditService?->logAllSessionsTerminated($user, $count);
        }

        return $deleted;
    }

    /**
     * Validate a session token.
     */
    public function validateSession(string $sessionToken): ?UserSession
    {
        $session = UserSession::where('session_token', hash('sha256', $sessionToken))
            ->where('expires_at', '>', now())
            ->first();

        if ($session) {
            $this->touchSession($sessionToken);
        }

        return $session;
    }

    /**
     * Get active session count for a user.
     */
    public function getActiveSessionCount(User $user): int
    {
        return UserSession::where('user_id', $user->id)
            ->where('expires_at', '>', now())
            ->count();
    }

    /**
     * Clean up expired sessions.
     *
     * @return int Number of sessions cleaned
     */
    public function cleanupExpiredSessions(): int
    {
        return UserSession::where('expires_at', '<', now())->delete();
    }

    /**
     * Enforce maximum session limit for a user.
     */
    protected function enforceSessionLimit(User $user): void
    {
        if ($this->maxSessions <= 0) {
            return; // Unlimited
        }

        $activeCount = $this->getActiveSessionCount($user);

        if ($activeCount >= $this->maxSessions) {
            // Terminate oldest session
            UserSession::where('user_id', $user->id)
                ->where('expires_at', '>', now())
                ->orderBy('last_active_at')
                ->limit($activeCount - $this->maxSessions + 1)
                ->delete();
        }
    }

    /**
     * Detect device type from user agent.
     */
    protected function detectDeviceType(?string $userAgent): string
    {
        if (! $userAgent) {
            return 'unknown';
        }

        $userAgent = strtolower($userAgent);

        if (preg_match('/(tablet|ipad|playbook|silk)|(android(?!.*mobile))/i', $userAgent)) {
            return 'tablet';
        }

        if (preg_match('/(mobile|iphone|ipod|phone|android|blackberry|opera mini|iemobile)/i', $userAgent)) {
            return 'mobile';
        }

        return 'desktop';
    }

    /**
     * Detect browser from user agent.
     */
    protected function detectBrowser(?string $userAgent): string
    {
        if (! $userAgent) {
            return 'Unknown';
        }

        $browsers = [
            'Edge' => '/edg/i',
            'Chrome' => '/chrome/i',
            'Firefox' => '/firefox/i',
            'Safari' => '/safari/i',
            'Opera' => '/opera|opr/i',
            'IE' => '/msie|trident/i',
        ];

        foreach ($browsers as $browser => $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return $browser;
            }
        }

        return 'Unknown';
    }

    /**
     * Detect platform/OS from user agent.
     */
    protected function detectPlatform(?string $userAgent): string
    {
        if (! $userAgent) {
            return 'Unknown';
        }

        $platforms = [
            'Windows' => '/windows/i',
            'macOS' => '/macintosh|mac os/i',
            'Linux' => '/linux/i',
            'iOS' => '/iphone|ipad|ipod/i',
            'Android' => '/android/i',
        ];

        foreach ($platforms as $platform => $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return $platform;
            }
        }

        return 'Unknown';
    }

    /**
     * Get approximate location from IP address.
     */
    protected function getLocationFromIp(?string $ip): ?string
    {
        // Skip for local IPs
        if (! $ip || in_array($ip, ['127.0.0.1', '::1', 'localhost'])) {
            return 'Local';
        }

        // In production, integrate with a geolocation service like:
        // - MaxMind GeoIP
        // - ip-api.com
        // - ipinfo.io

        return null;
    }

    /**
     * Get sessions that will expire soon (within X minutes).
     *
     * @return Collection
     */
    public function getSessionsExpiringSoon(int $minutes = 5)
    {
        return UserSession::where('expires_at', '>', now())
            ->where('expires_at', '<', now()->addMinutes($minutes))
            ->get();
    }

    /**
     * Get session statistics for a user.
     */
    public function getSessionStats(User $user): array
    {
        $sessions = UserSession::where('user_id', $user->id)
            ->where('expires_at', '>', now())
            ->get();

        return [
            'total_active' => $sessions->count(),
            'max_allowed' => $this->maxSessions ?: 'Unlimited',
            'by_device' => $sessions->groupBy('device_type')->map->count(),
            'by_browser' => $sessions->groupBy('browser')->map->count(),
            'by_platform' => $sessions->groupBy('platform')->map->count(),
        ];
    }
}
