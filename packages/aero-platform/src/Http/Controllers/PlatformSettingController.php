<?php

namespace Aero\Platform\Http\Controllers;

use Aero\Platform\Http\Requests\UpdatePlatformSettingRequest;
use Aero\Platform\Http\Resources\PlatformSettingResource;
use Aero\Platform\Models\PlatformSetting;
use Aero\Notifications\Services\Mail\MailService;
use Aero\Notifications\Services\Sms\SmsGatewayService as RuntimeSmsConfigService;
use Aero\Platform\Services\PlatformSettingService;
use Aero\Platform\TenantDatabaseManagers\CpanelDatabaseManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlatformSettingController extends Controller
{
    public function __construct(
        private readonly PlatformSettingService $service,
        private readonly MailService $mailService,
        private readonly RuntimeSmsConfigService $smsService
    ) {
        // Middleware handled by route group (auth:landlord)
    }

    public function index(Request $request): Response|PlatformSettingResource
    {

        $setting = PlatformSetting::current();

        if ($request->wantsJson()) {
            return new PlatformSettingResource($setting);
        }

        return Inertia::render('Platform/Admin/Settings/Platform', [
            'title' => 'Platform Settings',
            'platformSettings' => PlatformSettingResource::make($setting)->resolve(),
        ]);
    }

    public function update(UpdatePlatformSettingRequest $request): RedirectResponse|JsonResponse
    {
        // Authorization handled by module:system-settings,general-settings,platform-settings,update middleware

        $setting = PlatformSetting::current();

        $this->service->update(
            $setting,
            $request->validated(),
            [
                'logo' => $request->file('logo'),
                'logo_light' => $request->file('logo_light'),
                'logo_dark' => $request->file('logo_dark'),
                'square_logo' => $request->file('square_logo'),
                'favicon' => $request->file('favicon'),
                'social' => $request->file('social'),
            ]
        );

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Platform settings updated successfully.',
            ]);
        }

        return redirect()->back()->with('success', 'Platform settings updated successfully.');
    }

    /**
     * Send a test email using the current platform email settings.
     */
    public function sendTestEmail(Request $request): JsonResponse
    {
        // Authorization handled by module:system-settings,email-settings,email-config,test middleware

        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $result = $this->mailService->sendTestEmail($request->input('email'));

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'using_database_settings' => $result['using_database_settings'],
            ]);
        }

        // Return 422 for configuration errors so they display properly in the UI
        return response()->json([
            'success' => false,
            'message' => $result['message'],
        ], 422);
    }

    /**
     * Render the Infrastructure / Hosting Mode settings page.
     */
    public function infrastructure(Request $request): Response|PlatformSettingResource
    {
        $setting = PlatformSetting::current();

        if ($request->wantsJson()) {
            return new PlatformSettingResource($setting);
        }

        return Inertia::render('Platform/Admin/Settings/Infrastructure', [
            'title' => 'Infrastructure & Hosting',
            'platformSettings' => PlatformSettingResource::make($setting)->resolve(),
        ]);
    }

    /**
     * Test the cPanel API connection using the credentials from the request
     * (or, if not provided, from the stored platform settings).
     *
     * Accepts optional JSON body:
     * { cpanel_host, cpanel_port, cpanel_username, cpanel_api_token }
     * Any omitted field falls back to the stored DB value (decrypted).
     */
    public function testCpanelConnection(Request $request): JsonResponse
    {
        $request->validate([
            'cpanel_host' => ['nullable', 'string', 'max:255'],
            'cpanel_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'cpanel_username' => ['nullable', 'string', 'max:64'],
            'cpanel_api_token' => ['nullable', 'string', 'max:512'],
        ]);

        // Merge request credentials on top of stored (decrypted) credentials
        $stored = PlatformSetting::current()->getHostingSettingsDecrypted();
        $host = $request->input('cpanel_host', $stored['cpanel_host'] ?? null);
        $port = $request->input('cpanel_port', $stored['cpanel_port'] ?? 2083);
        $user = $request->input('cpanel_username', $stored['cpanel_username'] ?? null);
        $token = $request->input('cpanel_api_token', $stored['cpanel_api_token'] ?? null);

        if (! $host || ! $user || ! $token) {
            return response()->json([
                'success' => false,
                'message' => 'cPanel credentials incomplete. Please provide host, username, and API token.',
            ], 422);
        }

        try {
            $manager = new CpanelDatabaseManager;
            $result = $manager->testConnection($host, (int) $port, $user, $token);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => "Connected to cPanel on {$host}. Found ".($result['database_count'] ?? 0).' database(s).',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['error'] ?? 'Connection failed.',
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Send a test SMS using the current platform SMS settings.
     */
    public function sendTestSms(Request $request): JsonResponse
    {
        // Authorization handled by module:system-settings,general-settings,platform-settings,update middleware

        $request->validate([
            'phone' => ['required', 'string'],
        ]);

        // Apply SMS settings from database
        $this->smsService->applySmsSettings();

        // Send test SMS
        $result = $this->smsService->sendTestSms($request->input('phone'));

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
            ]);
        }

        // Return 422 for configuration errors so they display properly in the UI
        return response()->json([
            'success' => false,
            'message' => $result['message'],
        ], 422);
    }
}
