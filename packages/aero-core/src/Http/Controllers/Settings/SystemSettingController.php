<?php

namespace Aero\Core\Http\Controllers\Settings;

use Aero\Contracts\MailSenderInterface;
use Aero\Contracts\SmsGatewayInterface;
use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Http\Requests\UpdateSystemSettingRequest;
use Aero\Core\Http\Resources\SystemSettingResource;
use Aero\Core\Models\SystemSetting;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Core\Services\Audit\AuditService;
use Aero\Core\Services\SystemSettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SystemSettingController extends Controller
{
    public function __construct(
        private readonly SystemSettingService $service,
        private readonly AuditService $audit,
        private readonly MailSenderInterface $mailService,
        private readonly SmsGatewayInterface $smsService,
    ) {}

    public function index(Request $request): Response|SystemSettingResource
    {
        $setting = SystemSetting::current();

        if ($request->wantsJson()) {
            return new SystemSettingResource($setting);
        }

        return Inertia::render('Core/Settings/SystemSettings', [
            'title' => 'System Settings',
            'settings' => $this->service->allAsArray(),
            'systemSettings' => SystemSettingResource::make($setting)->resolve(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'app_name' => ['sometimes', 'string', 'max:100'],
            'app_url' => ['sometimes', 'url'],
            'support_email' => ['sometimes', 'email'],
            'timezone' => ['sometimes', 'string'],
            'date_format' => ['sometimes', 'string'],
            'time_format' => ['sometimes', 'string'],
        ]);

        foreach ($validated as $key => $value) {
            $this->service->set($key, $value);
        }

        $this->audit->log(
            AuditEventType::SETTINGS_UPDATED->value,
            'updated',
            null,
            'General settings updated',
            null,
            null,
            ['keys' => array_keys($validated)]
        );

        return back()->with('success', 'Settings saved.');
    }

    public function updateFull(UpdateSystemSettingRequest $request): JsonResponse
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

        $this->smsService->applySmsSettings();

        $result = $this->smsService->sendTestSms($request->input('phone'));

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'],
        ], 422);
    }
}
