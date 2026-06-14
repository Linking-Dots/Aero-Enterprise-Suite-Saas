<?php

declare(strict_types=1);

namespace Aero\Auth\Services;

use Aero\Auth\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Advanced Threat Detection Service
 *
 * Provides sophisticated threat detection capabilities including:
 * - Impossible travel detection
 * - Login velocity analysis
 * - Behavioral pattern analysis
 * - Risk scoring
 * - Automated threat response
 *
 * Usage:
 * ```php
 * $threatDetection = app(ThreatDetectionService::class);
 *
 * // Analyze login attempt
 * $riskScore = $threatDetection->analyzeLoginAttempt($user, $request);
 *
 * // Check for impossible travel
 * if ($threatDetection->detectImpossibleTravel($user, $request)) {
 *     // Handle suspicious login
 * }
 * ```
 */
class ThreatDetectionService
{
    protected IpGeolocationService $geolocationService;

    // Risk score thresholds
    protected const RISK_LOW = 1;

    protected const RISK_MEDIUM = 4;

    protected const RISK_HIGH = 7;

    protected const RISK_CRITICAL = 10;

    public function __construct(IpGeolocationService $geolocationService)
    {
        $this->geolocationService = $geolocationService;
    }

    /**
     * Comprehensive threat analysis for login attempts.
     *
     * @param  User  $user  User attempting to login
     * @param  Request  $request  Login request
     * @return array ['risk_level' => string, 'risk_score' => int, 'threats' => array, 'actions' => array]
     */
    public function analyzeLoginAttempt(User $user, Request $request): array
    {
        $threats = [];
        $riskScore = 0;
        $actions = [];

        // 1. Impossible Travel Detection
        if ($this->detectImpossibleTravel($user, $request)) {
            $threats[] = 'impossible_travel';
            $riskScore += 5;
            $actions[] = 'require_additional_verification';
        }

        // 2. Velocity Checks
        $velocityRisk = $this->analyzeLoginVelocity($user, $request);
        if ($velocityRisk > 0) {
            $threats[] = 'high_velocity_logins';
            $riskScore += $velocityRisk;
            if ($velocityRisk >= 3) {
                $actions[] = 'temporary_account_lock';
            }
        }

        // 3. Unusual Location Detection
        if ($this->detectUnusualLocation($user, $request)) {
            $threats[] = 'unusual_location';
            $riskScore += 2;
            $actions[] = 'email_notification';
        }

        // 4. Device Analysis
        $deviceRisk = $this->analyzeDevicePattern($user, $request);
        if ($deviceRisk > 0) {
            $threats[] = 'suspicious_device';
            $riskScore += $deviceRisk;
            $actions[] = 'device_verification_required';
        }

        // 5. Time-based Analysis
        if ($this->detectUnusualLoginTime($user, $request)) {
            $threats[] = 'unusual_time';
            $riskScore += 1;
        }

        // 6. Bot/Automation Detection
        if ($this->detectBotBehavior($request)) {
            $threats[] = 'bot_behavior';
            $riskScore += 4;
            $actions[] = 'captcha_required';
        }

        // 7. Concurrent Session Analysis
        $concurrentRisk = $this->analyzeConcurrentSessions($user);
        if ($concurrentRisk > 0) {
            $threats[] = 'excessive_concurrent_sessions';
            $riskScore += $concurrentRisk;
        }

        $riskLevel = $this->calculateRiskLevel($riskScore);

        // Determine automated actions based on risk level
        $actions = array_merge($actions, $this->getAutomatedActions($riskLevel));

        return [
            'risk_level' => $riskLevel,
            'risk_score' => $riskScore,
            'threats' => $threats,
            'actions' => $actions,
            'metadata' => [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'timestamp' => now()->toISOString(),
            ],
        ];
    }

    /**
     * Detect impossible travel between login locations.
     *
     * @param  User  $user  User attempting login
     * @param  Request  $request  Current request
     * @return bool True if impossible travel detected
     */
    public function detectImpossibleTravel(User $user, Request $request): bool
    {
        if (! config('security.threat_detection.enable_impossible_travel_detection', true)) {
            return false;
        }

        $currentLocation = $this->geolocationService->getLocation($request->ip());
        $lastLogin = $this->getLastLoginLocation($user);

        if (! $lastLogin || empty($lastLogin['location'])) {
            return false;
        }

        $timeDiff = now()->diffInSeconds($lastLogin['timestamp']);

        // If less than 10 minutes, skip check (could be session refresh)
        if ($timeDiff < 600) {
            return false;
        }

        return $this->geolocationService->isImpossibleTravel(
            $lastLogin['location'],
            $currentLocation,
            $timeDiff
        );
    }

    /**
     * Analyze login velocity patterns.
     *
     * @param  User  $user  User attempting login
     * @param  Request  $request  Current request
     * @return int Risk score (0-5)
     */
    public function analyzeLoginVelocity(User $user, Request $request): int
    {
        $riskScore = 0;
        $now = now();

        // Check logins in last 5 minutes
        $recentLogins = DB::table('authentication_events')
            ->where('user_id', $user->id)
            ->where('event_type', 'login')
            ->where('status', 'success')
            ->where('occurred_at', '>=', $now->subMinutes(5))
            ->count();

        if ($recentLogins > 3) {
            $riskScore += 3; // Very suspicious
        } elseif ($recentLogins > 1) {
            $riskScore += 1; // Moderately suspicious
        }

        // Check for rapid-fire attempts from same IP
        $ipAttempts = DB::table('authentication_events')
            ->where('ip_address', $request->ip())
            ->where('event_type', 'login')
            ->where('occurred_at', '>=', $now->subMinutes(1))
            ->count();

        if ($ipAttempts > 5) {
            $riskScore += 2;
        }

        return min($riskScore, 5);
    }

    /**
     * Detect login from unusual location.
     *
     * @param  User  $user  User attempting login
     * @param  Request  $request  Current request
     * @return bool True if location is unusual
     */
    public function detectUnusualLocation(User $user, Request $request): bool
    {
        $currentLocation = $this->geolocationService->getLocation($request->ip());
        $recentLocations = $this->getRecentLoginLocations($user, 30); // Last 30 days

        if (empty($recentLocations)) {
            return false; // First login or no history
        }

        $currentCountry = $currentLocation['country_code'];
        $recentCountries = array_column($recentLocations, 'country_code');

        // Check if current country has been seen before
        return ! in_array($currentCountry, $recentCountries);
    }

    /**
     * Analyze device patterns for suspicious behavior.
     *
     * @param  User  $user  User attempting login
     * @param  Request  $request  Current request
     * @return int Risk score (0-3)
     */
    public function analyzeDevicePattern(User $user, Request $request): int
    {
        $riskScore = 0;
        $userAgent = $request->userAgent();

        // Check for unusual user agent patterns
        if ($this->isUnusualUserAgent($userAgent)) {
            $riskScore += 2;
        }

        // Check device frequency
        $deviceFingerprint = $this->generateDeviceFingerprint($request);
        if (! $this->isKnownDevice($user, $deviceFingerprint)) {
            $riskScore += 1;
        }

        return min($riskScore, 3);
    }

    /**
     * Detect unusual login times based on user's historical pattern.
     *
     * @param  User  $user  User attempting login
     * @param  Request  $request  Current request
     * @return bool True if time is unusual
     */
    public function detectUnusualLoginTime(User $user, Request $request): bool
    {
        $currentHour = now()->hour;
        $recentLoginHours = $this->getRecentLoginHours($user, 30); // Last 30 days

        if (empty($recentLoginHours)) {
            return false; // No historical pattern
        }

        // Calculate if current hour is within normal range
        $hourCounts = array_count_values($recentLoginHours);
        $totalLogins = array_sum($hourCounts);
        $currentHourPercentage = ($hourCounts[$currentHour] ?? 0) / $totalLogins;

        // Flag as unusual if this hour represents less than 5% of historical logins
        return $currentHourPercentage < 0.05;
    }

    /**
     * Detect bot or automated behavior.
     *
     * @param  Request  $request  Current request
     * @return bool True if bot behavior detected
     */
    public function detectBotBehavior(Request $request): bool
    {
        $userAgent = $request->userAgent();

        // Common bot indicators
        $botSignatures = [
            'bot', 'crawler', 'spider', 'scraper', 'curl', 'wget', 'python',
            'java', 'go-http', 'okhttp', 'axios', 'postman',
        ];

        foreach ($botSignatures as $signature) {
            if (stripos($userAgent, $signature) !== false) {
                return true;
            }
        }

        // Check for missing common headers that browsers always send
        $requiredHeaders = ['accept', 'accept-language'];
        foreach ($requiredHeaders as $header) {
            if (! $request->hasHeader($header)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Analyze concurrent sessions for unusual patterns.
     *
     * @param  User  $user  User attempting login
     * @return int Risk score (0-2)
     */
    public function analyzeConcurrentSessions(User $user): int
    {
        $activeSessions = DB::table('user_sessions')
            ->where('user_id', $user->id)
            ->where('is_current', true)
            ->where('expires_at', '>', now())
            ->count();

        $maxSessions = config('security.session.max_concurrent_sessions', 5);

        if ($activeSessions > $maxSessions) {
            return 2;
        } elseif ($activeSessions > ($maxSessions * 0.8)) {
            return 1;
        }

        return 0;
    }

    /**
     * Calculate risk level from risk score.
     *
     * @param  int  $riskScore  Total risk score
     * @return string Risk level (low, medium, high, critical)
     */
    protected function calculateRiskLevel(int $riskScore): string
    {
        if ($riskScore >= self::RISK_CRITICAL) {
            return 'critical';
        } elseif ($riskScore >= self::RISK_HIGH) {
            return 'high';
        } elseif ($riskScore >= self::RISK_MEDIUM) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Get automated actions based on risk level.
     *
     * @param  string  $riskLevel  Risk level
     * @return array Actions to take
     */
    protected function getAutomatedActions(string $riskLevel): array
    {
        $actions = [];

        switch ($riskLevel) {
            case 'critical':
                if (config('security.threat_detection.auto_lock_on_high_risk', false)) {
                    $actions[] = 'account_lock';
                }
                if (config('security.threat_detection.notify_admins_on_critical_risk', true)) {
                    $actions[] = 'notify_security_team';
                }
                $actions[] = 'require_2fa';
                $actions[] = 'email_security_alert';
                break;

            case 'high':
                $actions[] = 'require_2fa';
                $actions[] = 'email_security_alert';
                $actions[] = 'extended_session_monitoring';
                break;

            case 'medium':
                if (config('security.threat_detection.require_2fa_on_medium_risk', true)) {
                    $actions[] = 'require_2fa';
                }
                $actions[] = 'email_notification';
                break;

            case 'low':
                // No automatic actions for low risk
                break;
        }

        return $actions;
    }

    // Helper methods for data retrieval

    protected function getLastLoginLocation(User $user): ?array
    {
        $lastLogin = DB::table('authentication_events')
            ->where('user_id', $user->id)
            ->where('event_type', 'login')
            ->where('status', 'success')
            ->orderBy('occurred_at', 'desc')
            ->first();

        if (! $lastLogin) {
            return null;
        }

        $metadata = json_decode($lastLogin->metadata ?? '{}', true);
        $location = $metadata['location'] ?? null;

        return $location ? [
            'location' => $location,
            'timestamp' => $lastLogin->occurred_at,
        ] : null;
    }

    protected function getRecentLoginLocations(User $user, int $days): array
    {
        return DB::table('authentication_events')
            ->where('user_id', $user->id)
            ->where('event_type', 'login')
            ->where('status', 'success')
            ->where('occurred_at', '>=', now()->subDays($days))
            ->get()
            ->map(function ($event) {
                $metadata = json_decode($event->metadata ?? '{}', true);

                return $metadata['location'] ?? null;
            })
            ->filter()
            ->unique('country_code')
            ->values()
            ->toArray();
    }

    protected function getRecentLoginHours(User $user, int $days): array
    {
        return DB::table('authentication_events')
            ->where('user_id', $user->id)
            ->where('event_type', 'login')
            ->where('status', 'success')
            ->where('occurred_at', '>=', now()->subDays($days))
            ->get()
            ->map(fn ($event) => Carbon::parse($event->occurred_at)->hour)
            ->toArray();
    }

    protected function isUnusualUserAgent(string $userAgent): bool
    {
        // Check for very old browsers, suspicious patterns, etc.
        $suspicious = [
            'MSIE 6', 'MSIE 7', 'MSIE 8', // Very old browsers
            'Python', 'Java', 'Go-http', // Programming languages
            'curl', 'wget', 'libcurl',   // Command line tools
        ];

        foreach ($suspicious as $pattern) {
            if (stripos($userAgent, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    protected function generateDeviceFingerprint(Request $request): string
    {
        $components = [
            $request->userAgent(),
            $request->header('accept'),
            $request->header('accept-language'),
            $request->header('accept-encoding'),
        ];

        return hash('sha256', implode('|', array_filter($components)));
    }

    protected function isKnownDevice(User $user, string $fingerprint): bool
    {
        return Cache::remember(
            "known_device:{$user->id}:{$fingerprint}",
            3600,
            fn () => DB::table('user_sessions')
                ->where('user_id', $user->id)
                ->where('device_fingerprint', $fingerprint)
                ->exists()
        );
    }
}
