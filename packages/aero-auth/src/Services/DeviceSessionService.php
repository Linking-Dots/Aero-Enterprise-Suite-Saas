<?php

namespace Aero\Auth\Services;

use Aero\Auth\Models\User;
use Aero\Auth\Models\UserDevice;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

class DeviceSessionService
{
    protected DeviceAuthService $deviceAuthService;

    public function __construct(DeviceAuthService $deviceAuthService)
    {
        $this->deviceAuthService = $deviceAuthService;
    }

    /**
     * Get active sessions for a user with session details.
     */
    public function getActiveSessions(User $user): Collection
    {
        $devices = UserDevice::where('user_id', $user->id)
            ->where('is_active', true)
            ->orderBy('last_used_at', 'desc')
            ->get();

        return $devices->map(function ($device) use ($user) {
            return [
                'id' => $device->id,
                'device_id' => $device->device_id,
                'device_name' => $device->device_name,
                'device_type' => $device->device_type,
                'browser' => $device->browser,
                'platform' => $device->platform,
                'ip_address' => $device->ip_address,
                'last_used_at' => $device->last_used_at,
                'last_used_ago' => $device->last_used_at?->diffForHumans(),
                'is_current' => $this->isCurrentDevice($device),
                'location' => $this->getLocationFromIp($device->ip_address),
                'is_suspicious' => $this->isSuspiciousSession($device, $user),
            ];
        });
    }

    /**
     * Check if a device is the current session's device.
     */
    public function isCurrentDevice(UserDevice $device): bool
    {
        $currentDeviceId = request()->header('X-Device-ID')
            ?? request()->input('device_id')
            ?? session('device_id');

        return $device->device_id === $currentDeviceId;
    }

    /**
     * Terminate a specific session.
     */
    public function terminateSession(User $user, int $deviceId): bool
    {
        $device = UserDevice::where('user_id', $user->id)
            ->where('id', $deviceId)
            ->first();

        if (! $device) {
            return false;
        }

        // Clear any cached session data for this device
        $this->clearDeviceCache($device);

        // Deactivate the device
        $device->update([
            'is_active' => false,
            'deactivated_at' => now(),
            'deactivation_reason' => 'user_terminated',
        ]);

        return true;
    }

    /**
     * Terminate all sessions except current.
     *
     * @return int Number of sessions terminated
     */
    public function terminateOtherSessions(User $user): int
    {
        $currentDeviceId = request()->header('X-Device-ID')
            ?? request()->input('device_id')
            ?? session('device_id');

        $query = UserDevice::where('user_id', $user->id)
            ->where('is_active', true);

        if ($currentDeviceId) {
            $query->where('device_id', '!=', $currentDeviceId);
        }

        $devices = $query->get();

        foreach ($devices as $device) {
            $this->clearDeviceCache($device);
        }

        return $query->update([
            'is_active' => false,
            'deactivated_at' => now(),
            'deactivation_reason' => 'other_sessions_terminated',
        ]);
    }

    /**
     * Terminate all sessions for a user.
     *
     * @return int Number of sessions terminated
     */
    public function terminateAllSessions(User $user): int
    {
        $devices = UserDevice::where('user_id', $user->id)
            ->where('is_active', true)
            ->get();

        foreach ($devices as $device) {
            $this->clearDeviceCache($device);
        }

        return UserDevice::where('user_id', $user->id)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'deactivated_at' => now(),
                'deactivation_reason' => 'all_sessions_terminated',
            ]);
    }

    /**
     * Get session statistics for a user.
     */
    public function getSessionStats(User $user): array
    {
        $devices = UserDevice::where('user_id', $user->id)->get();

        $activeDevices = $devices->where('is_active', true);
        $recentlyActive = $activeDevices->filter(fn ($d) => $d->last_used_at && $d->last_used_at->gt(now()->subHours(24))
        );

        $deviceTypes = $activeDevices->groupBy('device_type')->map->count();

        return [
            'total_devices' => $devices->count(),
            'active_devices' => $activeDevices->count(),
            'recently_active' => $recentlyActive->count(),
            'device_types' => $deviceTypes->toArray(),
            'unique_locations' => $activeDevices->unique('ip_address')->count(),
            'last_activity' => $activeDevices->max('last_used_at'),
            'single_device_enabled' => $user->hasSingleDeviceLoginEnabled(),
        ];
    }

    /**
     * Check for concurrent sessions.
     */
    public function hasConcurrentSessions(User $user): bool
    {
        return UserDevice::where('user_id', $user->id)
            ->where('is_active', true)
            ->where('last_used_at', '>', now()->subMinutes(5))
            ->count() > 1;
    }

    /**
     * Get suspicious session indicators.
     */
    public function isSuspiciousSession(UserDevice $device, User $user): bool
    {
        // Check if IP address has changed recently
        $recentDevices = UserDevice::where('user_id', $user->id)
            ->where('is_active', true)
            ->where('last_used_at', '>', now()->subDay())
            ->get();

        // Flag if device logged in from a different IP range recently
        $uniqueIps = $recentDevices->pluck('ip_address')->unique();

        if ($uniqueIps->count() > 3) {
            return true; // Multiple distinct IPs in short time
        }

        // Check if device type changed within short period (might be spoofing)
        $recentSameDevice = UserDevice::where('user_id', $user->id)
            ->where('device_id', $device->device_id)
            ->where('created_at', '>', now()->subWeek())
            ->first();

        if ($recentSameDevice && $recentSameDevice->device_type !== $device->device_type) {
            return true; // Device type changed for same device_id
        }

        return false;
    }

    /**
     * Clear cached data for a device.
     */
    protected function clearDeviceCache(UserDevice $device): void
    {
        Cache::forget("device_session:{$device->device_id}");
        Cache::forget("user_device:{$device->user_id}:{$device->device_id}");
    }

    /**
     * Get location from IP (simplified version).
     */
    protected function getLocationFromIp(?string $ip): ?string
    {
        if (! $ip || $ip === '127.0.0.1' || str_starts_with($ip, '192.168.') || str_starts_with($ip, '10.')) {
            return 'Local Network';
        }

        // Check cache first
        $cacheKey = "ip_location:{$ip}";
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        // For production, integrate with IP geolocation service
        // For now, return null (not implemented)
        return null;
    }

    /**
     * Prune stale sessions.
     *
     * @return int Number of sessions pruned
     */
    public function pruneStaleDevices(int $inactiveDays = 30): int
    {
        $cutoff = Carbon::now()->subDays($inactiveDays);

        return UserDevice::where('is_active', true)
            ->where(function ($query) use ($cutoff) {
                $query->where('last_used_at', '<', $cutoff)
                    ->orWhereNull('last_used_at');
            })
            ->update([
                'is_active' => false,
                'deactivated_at' => now(),
                'deactivation_reason' => 'inactivity',
            ]);
    }

    /**
     * Record session activity.
     */
    public function recordActivity(UserDevice $device): void
    {
        $device->update(['last_used_at' => now()]);

        // Also cache the activity for quick lookups
        Cache::put(
            "device_session:{$device->device_id}",
            [
                'user_id' => $device->user_id,
                'last_activity' => now()->toIso8601String(),
            ],
            now()->addMinutes(30)
        );
    }

    /**
     * Get the delegate to DeviceAuthService for device operations.
     */
    public function auth(): DeviceAuthService
    {
        return $this->deviceAuthService;
    }
}
