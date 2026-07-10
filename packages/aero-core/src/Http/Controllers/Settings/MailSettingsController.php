<?php

namespace Aero\Core\Http\Controllers\Settings;

use Aero\Contracts\MailSenderInterface;
use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Http\Requests\StoreMailSettingsRequest;
use Aero\Core\Models\SystemSetting;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Core\Services\Audit\AuditService;
use Aero\Core\Services\SystemSettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class MailSettingsController extends Controller
{
    public function __construct(
        private readonly MailSenderInterface $mailService,
        private readonly SystemSettingService $settings,
        private readonly AuditService $audit,
    ) {}

    public function index(Request $request): Response
    {
        $setting = SystemSetting::current();

        $this->audit->logAccess('mail_settings', null, null, ['smtp_credentials']);

        return Inertia::render('Core/Settings/Index', [
            'section' => 'mail',
            'summary' => \Aero\Core\Services\SettingsSummary::build(),
            'emailSettings' => $setting->getSanitizedEmailSettings(),
            'mail' => [
                'driver' => $this->settings->get('mail_driver', 'smtp'),
                'host' => $this->settings->get('mail_host', ''),
                'port' => $this->settings->get('mail_port', '587'),
                'username' => $this->settings->get('mail_username', ''),
                'from_name' => $this->settings->get('mail_from_name', config('app.name')),
                'from_email' => $this->settings->get('mail_from_email', ''),
                'encryption' => $this->settings->get('mail_encryption', 'tls'),
            ],
        ]);
    }

    public function update(StoreMailSettingsRequest $request): JsonResponse|RedirectResponse
    {
        $setting = SystemSetting::current();

        $validatedMail = $request->validatedMailSettings();
        $existingEmail = $setting->email_settings ?? [];
        $mergedEmail = array_merge($existingEmail, $validatedMail);

        $setting->update(['email_settings' => $mergedEmail]);

        // Also persist flat KV keys for the new-style settings surface
        foreach (['driver', 'host', 'port', 'username', 'from_name', 'from_email', 'encryption'] as $key) {
            if (array_key_exists($key, $validatedMail)) {
                $this->settings->set('mail_'.$key, $validatedMail[$key]);
            }
        }

        $this->audit->log(
            AuditEventType::SETTINGS_UPDATED->value,
            'updated',
            null,
            'Mail settings updated',
            null,
            null,
            ['section' => 'mail']
        );

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Mail settings updated successfully.',
                'email_settings' => $setting->refresh()->getSanitizedEmailSettings(),
            ]);
        }

        return back()->with('success', 'Mail settings saved.');
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

    public function testSend(Request $request): RedirectResponse
    {
        $request->validate(['to' => ['required', 'email']]);

        try {
            Mail::raw('This is a test email from AEOS365.', function ($m) use ($request) {
                $m->to($request->to)->subject('AEOS365 Test Email');
            });

            return back()->with('success', "Test email sent to {$request->to}.");
        } catch (\Exception $e) {
            return back()->with('error', "Failed: {$e->getMessage()}");
        }
    }
}
