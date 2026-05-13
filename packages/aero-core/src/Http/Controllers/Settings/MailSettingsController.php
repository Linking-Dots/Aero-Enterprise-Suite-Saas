<?php

namespace Aero\Core\Http\Controllers\Settings;

use Aero\Core\Contracts\MailSenderInterface;
use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Http\Requests\StoreMailSettingsRequest;
use Aero\Core\Models\SystemSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MailSettingsController extends Controller
{
    public function __construct(
        private readonly MailSenderInterface $mailService,
    ) {}

    public function index(Request $request): Response
    {
        $setting = SystemSetting::current();

        return Inertia::render('Core/Settings/Mail', [
            'title' => 'Email (SMTP) Settings',
            'emailSettings' => $setting->getSanitizedEmailSettings(),
        ]);
    }

    public function update(StoreMailSettingsRequest $request): JsonResponse
    {
        $setting = SystemSetting::current();

        $validatedMail = $request->validatedMailSettings();
        $existingEmail = $setting->email_settings ?? [];

        $mergedEmail = array_merge($existingEmail, $validatedMail);

        $setting->update(['email_settings' => $mergedEmail]);
        $setting->refresh();

        return response()->json([
            'message' => 'Mail settings updated successfully.',
            'email_settings' => $setting->getSanitizedEmailSettings(),
        ]);
    }

    public function sendTest(Request $request): JsonResponse
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
}
