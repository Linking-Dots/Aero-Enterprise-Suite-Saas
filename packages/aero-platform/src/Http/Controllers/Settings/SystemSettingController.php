<?php

namespace Aero\Platform\Http\Controllers\Settings;

use Aero\Core\Models\SystemSetting;
use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Http\Requests\UpdateSystemSettingRequest;
use Aero\Platform\Http\Resources\SystemSettingResource;
use Aero\Notifications\Services\Mail\MailService;
use Aero\Notifications\Services\Sms\SmsGatewayService as RuntimeSmsConfigService;
use Aero\Platform\Services\Settings\SystemSettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SystemSettingController extends Controller
{
    public function __construct(
        private readonly SystemSettingService $service,
        private readonly MailService $mailService,
        private readonly RuntimeSmsConfigService $smsService
    ) {}

    public function index(Request $request): Response|SystemSettingResource
    {
        $setting = SystemSetting::current();

        if ($request->wantsJson()) {
            return new SystemSettingResource($setting);
        }

        return Inertia::render('Core/Settings/SystemSettings', [
            'title' => 'System Settings',
            'systemSettings' => SystemSettingResource::make($setting)->resolve(),
        ]);
    }

    public function update(UpdateSystemSettingRequest $request): JsonResponse
    {
        $setting = SystemSetting::current();

        $updated = $this->service->update(
            $setting,
            $request->validated(),
            [
                'logo_light' => $request->file('logo_light'),
                'logo_dark' => $request->file('logo_dark'),
                'favicon' => $request->file('favicon'),
                'login_background' => $request->file('login_background'),
            ]
        );

        return (new SystemSettingResource($updated))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Send a test email using the current tenant email settings.
     */
    public function sendTestEmail(Request $request): JsonResponse
    {
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
     * Send a test SMS using the current tenant SMS settings.
     */
    public function sendTestSms(Request $request): JsonResponse
    {
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
