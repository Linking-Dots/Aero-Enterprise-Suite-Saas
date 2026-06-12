<?php

namespace Aero\Auth\Http\Controllers\Auth;

use Aero\Auth\Http\Controllers\Controller;
use Aero\Auth\Services\DeviceAuthService;
use Aero\Auth\Models\User;
use Aero\Contracts\AuditServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DeviceController extends Controller
{
    public function __construct(
        protected DeviceAuthService $deviceAuthService,
        protected AuditServiceInterface $auditService
    ) {}

    /**
     * Get all devices for the authenticated user.
     */
    public function index()
    {
        $user = Auth::user();
        $devices = $this->deviceAuthService->getUserDevices($user);

        return Inertia::render('Auth/Devices', [
            'devices' => $devices,
            'singleDeviceEnabled' => $user->single_device_login_enabled,
        ]);
    }

    /**
     * Get all devices for a specific user (admin only).
     */
    public function getUserDevices(Request $request, $userId)
    {
        // Authorization check should be done via middleware or policy
        $user = User::findOrFail($userId);
        $devices = $this->deviceAuthService->getUserDevices($user);

        // Always return Inertia page for browser navigation
        return Inertia::render('Admin/UserDevices', [
            'user' => $user,
            'devices' => $devices,
        ]);
    }

    /**
     * Reset all devices for a user (admin only).
     */
    public function resetDevices(Request $request, $userId)
    {
        $request->validate([
            'reason' => 'nullable|string|max:255',
        ]);

        $user = User::findOrFail($userId);
        $count = $this->deviceAuthService->resetUserDevices($user, $request->input('reason'));

        return response()->json([
            'success' => true,
            'message' => "Successfully reset {$count} device(s) for user {$user->email}.",
            'devices_reset' => $count,
        ]);
    }

    /**
     * Deactivate a specific device (user or admin).
     */
    public function deactivateDevice(Request $request, $deviceId)
    {
        $user = Auth::user();

        // Check if this device belongs to the user
        $success = $this->deviceAuthService->deactivateDevice($user, $deviceId);

        if (! $success) {
            return response()->json([
                'success' => false,
                'message' => 'Device not found or unauthorized.',
            ], 404);
        }

        // Log device removed event
        $this->auditService->logDeviceRemoved($user, "Device #{$deviceId}", $deviceId);

        return response()->json([
            'success' => true,
            'message' => 'Device deactivated successfully.',
        ]);
    }

    /**
     * Deactivate a specific device for any user (admin only).
     */
    public function adminDeactivateDevice(Request $request, $userId, $deviceId)
    {
        $user = User::findOrFail($userId);
        $success = $this->deviceAuthService->deactivateDevice($user, $deviceId);

        if (! $success) {
            return response()->json([
                'success' => false,
                'message' => 'Device not found.',
            ], 404);
        }

        // Log device removed event (admin action)
        $this->auditService->logDeviceRemoved($user, "Device #{$deviceId} (by admin)", $deviceId);

        return response()->json([
            'success' => true,
            'message' => 'Device deactivated successfully.',
        ]);
    }

    /**
     * Toggle single device login for a user (admin only).
     */
    public function toggleSingleDeviceLogin(Request $request, $userId)
    {
        $user = User::findOrFail($userId);

        // Toggle the setting
        $newStatus = ! $user->single_device_login_enabled;

        if ($newStatus) {
            $user->enableSingleDeviceLogin('Enabled by admin');
        } else {
            $user->disableSingleDeviceLogin('Disabled by admin');
        }

        return response()->json([
            'success' => true,
            'message' => 'Single device login '.($newStatus ? 'enabled' : 'disabled').' successfully.',
            'single_device_login_enabled' => $newStatus,
        ]);
    }
}
