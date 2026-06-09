<?php

namespace Aero\Auth\Services;

use Aero\Auth\Models\User;
use Aero\Auth\Models\UserDevice;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Jenssegers\Agent\Agent;

class DeviceAuthService
{
    protected Agent $agent;

    public function __construct()
    {
        $this->agent = new Agent;
    }

    /**
     * Generate secure device token using HMAC-SHA256 with random salt.
     *
     * @param  string  $deviceId  UUIDv4 from frontend
     * @param  int  $userId  User ID
     * @return array ['token' => string, 'salt' => string]
     */
    public function generateDeviceToken(string $deviceId, int $userId): array
    {
        // Generate cryptographically secure random salt
        $salt = random_bytes(32);
        $saltBase64 = base64_encode($salt);

        // Create data to sign with timestamp for additional entropy
        $timestamp = microtime(true);
        $data = $deviceId.$userId.$saltBase64.$timestamp.config('app.key');

        // Generate HMAC-SHA256 token
        $token = hash_hmac('sha256', $data, config('app.key'));

        return [
            'token' => $token,
            'salt' => $saltBase64,
        ];
    }

    /**
     * Verify device token matches the stored one using stored salt.
     *
     * @param  string  $deviceId  UUIDv4 from frontend
     * @param  int  $userId  User ID
     * @param  string  $storedToken  Token from database
     * @param  string  $storedSalt  Salt from database
     * @param  float  $tokenTimestamp  Original token generation timestamp
     */
    public function verifyDeviceToken(string $deviceId, int $userId, string $storedToken, string $storedSalt, float $tokenTimestamp): bool
    {
        // Recreate the original data that was signed
        $data = $deviceId.$userId.$storedSalt.$tokenTimestamp.config('app.key');
        $calculatedToken = hash_hmac('sha256', $data, config('app.key'));

        // Use hash_equals for timing-safe comparison
        return hash_equals($storedToken, $calculatedToken);
    }

    /**
     * Register a new device for the user.
     *
     * @param  string  $deviceId  UUIDv4 from frontend
     */
    public function registerDevice(User $user, Request $request, string $deviceId): ?UserDevice
    {
        // Validate device_id format (UUIDv4)
        if (! $this->isValidUuid($deviceId)) {
            Log::warning('Invalid device_id format', [
                'user_id' => $user->id,
                'device_id' => $deviceId,
            ]);

            return null;
        }

        // Generate secure device token with salt
        $tokenData = $this->generateDeviceToken($deviceId, $user->id);
        $tokenTimestamp = microtime(true);

        // Get device information
        $deviceInfo = $this->getDeviceInfo($request);

        // Check if device already exists
        $existingDevice = UserDevice::where('user_id', $user->id)
            ->where('device_id', $deviceId)
            ->first();

        if ($existingDevice) {
            // Update existing device with new token and salt
            $existingDevice->update([
                'device_token' => $tokenData['token'],
                'device_salt' => $tokenData['salt'],
                'token_timestamp' => $tokenTimestamp,
                'is_active' => true,
                'last_used_at' => Carbon::now(),
                'ip_address' => $deviceInfo['ip_address'],
                'user_agent' => $deviceInfo['user_agent'],
            ]);

            return $existingDevice;
        }

        // Create new device with salt storage
        return UserDevice::create([
            'user_id' => $user->id,
            'device_id' => $deviceId,
            'device_token' => $tokenData['token'],
            'device_salt' => $tokenData['salt'],
            'token_timestamp' => $tokenTimestamp,
            'device_name' => $deviceInfo['device_name'],
            'device_type' => $deviceInfo['device_type'],
            'browser' => $deviceInfo['browser'],
            'platform' => $deviceInfo['platform'],
            'ip_address' => $deviceInfo['ip_address'],
            'user_agent' => $deviceInfo['user_agent'],
            'is_active' => true,
            'last_used_at' => Carbon::now(),
        ]);
    }

    /**
     * Verify if user can login from this device.
     *
     * @param  string  $deviceId  UUIDv4 from frontend
     * @return array ['allowed' => bool, 'message' => string, 'device' => UserDevice|null]
     */
    public function canLoginFromDevice(User $user, string $deviceId): array
    {
        // Validate device_id format
        if (! $this->isValidUuid($deviceId)) {
            return [
                'allowed' => false,
                'message' => 'Invalid device identifier format.',
                'device' => null,
            ];
        }

        // Check if single device login is enabled for this user
        if (! $user->hasSingleDeviceLoginEnabled()) {
            // Single device login disabled - allow login but still track devices
            return [
                'allowed' => true,
                'message' => 'Login allowed (single device login disabled).',
                'device' => null,
                'track_only' => true, // Flag to indicate we should still register device for tracking
            ];
        }

        // Single device login is ENABLED - enforce device binding
        // Check if user has any registered devices
        $registeredDevice = UserDevice::where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        // If no devices registered, this is the first login - allow it
        if (! $registeredDevice) {
            return [
                'allowed' => true,
                'message' => 'First device registration.',
                'device' => null,
            ];
        }

        // Check if the provided device_id matches the registered one
        if ($registeredDevice->device_id === $deviceId) {
            return [
                'allowed' => true,
                'message' => 'Login from registered device.',
                'device' => $registeredDevice,
            ];
        }

        // Device mismatch - block login
        return [
            'allowed' => false,
            'message' => 'Device mismatch. Account is locked to another device.',
            'device' => $registeredDevice,
        ];
    }

    /**
     * Verify device on every authenticated request.
     */
    public function verifyDeviceOnRequest(User $user, Request $request): bool
    {
        $deviceId = $request->header('X-Device-ID') ?? $request->input('device_id');

        if (! $deviceId) {
            return false;
        }

        // Check if device exists and is active for this user
        $device = UserDevice::where('user_id', $user->id)
            ->where('device_id', $deviceId)
            ->where('is_active', true)
            ->first();

        if (! $device) {
            return false;
        }

        // Update last used timestamp
        $device->markAsUsed();

        return true;
    }

    /**
     * Reset all devices for a user (admin function).
     *
     * @return int Number of devices deactivated
     */
    public function resetUserDevices(User $user, ?string $reason = null): int
    {
        Log::info('Devices reset for user', [
            'user_id' => $user->id,
            'reason' => $reason,
        ]);

        return UserDevice::where('user_id', $user->id)
            ->update(['is_active' => false]);
    }

    /**
     * Get device information from request.
     */
    protected function getDeviceInfo(Request $request): array
    {
        $this->agent->setUserAgent($request->userAgent());

        $deviceType = 'desktop';
        if ($this->agent->isMobile()) {
            $deviceType = 'mobile';
        } elseif ($this->agent->isTablet()) {
            $deviceType = 'tablet';
        }

        $browser = $this->agent->browser();
        $platform = $this->agent->platform();

        return [
            'device_name' => $this->generateDeviceName($browser, $platform, $deviceType),
            'device_type' => $deviceType,
            'browser' => $browser,
            'platform' => $platform,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];
    }

    /**
     * Generate human-readable device name.
     */
    protected function generateDeviceName(string $browser, string $platform, string $deviceType): string
    {
        return "{$browser} on {$platform} ".ucfirst($deviceType);
    }

    /**
     * Validate if string is a valid UUIDv4.
     */
    protected function isValidUuid(string $uuid): bool
    {
        return (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uuid);
    }

    /**
     * Get all devices for a user.
     *
     * @return Collection
     */
    public function getUserDevices(User $user)
    {
        return UserDevice::where('user_id', $user->id)
            ->orderBy('last_used_at', 'desc')
            ->get();
    }

    /**
     * Deactivate a specific device.
     */
    public function deactivateDevice(User $user, int $deviceId): bool
    {
        $device = UserDevice::where('user_id', $user->id)
            ->where('id', $deviceId)
            ->first();

        if (! $device) {
            return false;
        }

        return $device->deactivate();
    }
}
