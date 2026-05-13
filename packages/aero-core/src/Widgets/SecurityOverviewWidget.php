<?php

declare(strict_types=1);

namespace Aero\Core\Widgets;

use Aero\Core\Contracts\AbstractDashboardWidget;
use Aero\Contracts\CoreWidgetCategory;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Security Overview Widget for Core Dashboard
 *
 * Displays security-related tenant statistics:
 * - Failed login attempts (today)
 * - Active sessions count
 * - Last login time for current user
 * - Registered devices count
 *
 * This is an ALERT widget - draws attention to security events.
 * Uses optimized queries and 2-minute caching.
 */
class SecurityOverviewWidget extends AbstractDashboardWidget
{
    protected string $position = 'sidebar';

    protected int $order = 2;

    protected int|string $span = 1;

    protected CoreWidgetCategory $category = CoreWidgetCategory::ALERT;

    protected array $requiredPermissions = ['dashboard.view_security'];

    public function getKey(): string
    {
        return 'core.security_overview';
    }

    public function getComponent(): string
    {
        return 'Widgets/Core/SecurityOverviewWidget';
    }

    public function getTitle(): string
    {
        return 'Security Overview';
    }

    public function getDescription(): string
    {
        return 'Login attempts, sessions, and security events';
    }

    public function getModuleCode(): string
    {
        return 'core';
    }

    /**
     * Security widget is always enabled.
     */
    public function isEnabled(): bool
    {
        return true;
    }

    /**
     * Get widget data for frontend.
     *
     * Uses optimized queries and 2-minute caching for security stats.
     */
    public function getData(): array
    {
        // Cache security stats for 2 minutes (more frequent updates for security data)
        return Cache::remember('dashboard.security.'.auth()->id(), 120, function () {
            return $this->fetchSecurityStats();
        });
    }

    /**
     * Fetch security statistics from database.
     */
    protected function fetchSecurityStats(): array
    {
        $user = auth()->user();

        // OPTIMIZED: Single query for failed logins
        $failedLoginsToday = $this->getFailedLoginsCount();

        // OPTIMIZED: Single query for active sessions
        $activeSessions = $this->getActiveSessionsCount();

        // Get user's last login (skip current session)
        $lastLogin = $this->getUserLastLogin($user);

        // Get registered devices count
        $registeredDevices = $this->getRegisteredDevicesCount();

        // Get security events today
        $securityEventsToday = $this->getSecurityEventsCount();

        // Determine alert level based on failed logins
        $alertLevel = 'success'; // Green - all good
        if ($failedLoginsToday > 10) {
            $alertLevel = 'danger'; // Red - concerning
        } elseif ($failedLoginsToday > 3) {
            $alertLevel = 'warning'; // Orange - watch
        }

        return [
            'failedLoginsToday' => $failedLoginsToday,
            'activeSessions' => $activeSessions,
            'lastLogin' => $lastLogin,
            'registeredDevices' => $registeredDevices,
            'securityEventsToday' => $securityEventsToday,
            'alertLevel' => $alertLevel,
            'items' => [
                [
                    'label' => 'Failed Logins Today',
                    'value' => $failedLoginsToday,
                    'icon' => 'ShieldExclamationIcon',
                    'status' => $failedLoginsToday > 5 ? 'danger' : ($failedLoginsToday > 0 ? 'warning' : 'success'),
                ],
                [
                    'label' => 'Active Sessions',
                    'value' => $activeSessions,
                    'icon' => 'ComputerDesktopIcon',
                    'status' => 'success',
                ],
                [
                    'label' => 'Registered Devices',
                    'value' => $registeredDevices,
                    'icon' => 'DevicePhoneMobileIcon',
                    'status' => 'default',
                ],
                [
                    'label' => 'Last Login',
                    'value' => $lastLogin ? Carbon::parse($lastLogin)->diffForHumans() : 'First login',
                    'icon' => 'ClockIcon',
                    'status' => 'default',
                ],
            ],
        ];
    }

    /**
     * Get failed logins count for today.
     */
    protected function getFailedLoginsCount(): int
    {
        try {
            if (Schema::hasTable('failed_login_attempts')) {
                return DB::table('failed_login_attempts')
                    ->whereDate('created_at', today())
                    ->count();
            } elseif (Schema::hasTable('authentication_events')) {
                return DB::table('authentication_events')
                    ->where('event_type', 'login_failed')
                    ->whereDate('created_at', today())
                    ->count();
            }
        } catch (\Throwable $e) {
            Log::warning('SecurityOverviewWidget: Failed to fetch failed logins count', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);
        }

        return 0;
    }

    /**
     * Get active sessions count.
     */
    protected function getActiveSessionsCount(): int
    {
        try {
            if (Schema::hasTable('sessions')) {
                return DB::table('sessions')
                    ->where('last_activity', '>', now()->subMinutes(30)->timestamp)
                    ->count();
            }
        } catch (\Throwable $e) {
            Log::warning('SecurityOverviewWidget: Failed to fetch active sessions', [
                'error' => $e->getMessage(),
            ]);
        }

        return 0;
    }

    /**
     * Get user's last login time (excluding current session).
     */
    protected function getUserLastLogin($user): ?string
    {
        if (! $user) {
            return null;
        }

        try {
            if (Schema::hasTable('authentication_events')) {
                $lastLoginEvent = DB::table('authentication_events')
                    ->where('user_id', $user->id)
                    ->where('event_type', 'login')
                    ->orderByDesc('created_at')
                    ->skip(1) // Skip current login
                    ->first();

                return $lastLoginEvent?->created_at;
            }
        } catch (\Throwable $e) {
            Log::warning('SecurityOverviewWidget: Failed to fetch last login', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);
        }

        return null;
    }

    /**
     * Get registered devices count.
     */
    protected function getRegisteredDevicesCount(): int
    {
        try {
            if (Schema::hasTable('user_devices')) {
                return DB::table('user_devices')->count();
            }
        } catch (\Throwable $e) {
            Log::warning('SecurityOverviewWidget: Failed to fetch registered devices', [
                'error' => $e->getMessage(),
            ]);
        }

        return 0;
    }

    /**
     * Get security events count for today.
     */
    protected function getSecurityEventsCount(): int
    {
        try {
            if (Schema::hasTable('security_events')) {
                return DB::table('security_events')
                    ->whereDate('created_at', today())
                    ->count();
            }
        } catch (\Throwable $e) {
            Log::warning('SecurityOverviewWidget: Failed to fetch security events', [
                'error' => $e->getMessage(),
            ]);
        }

        return 0;
    }
}
