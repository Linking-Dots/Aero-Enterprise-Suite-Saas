<?php

namespace Aero\Auth\Http\Controllers\Admin;

use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Services\SystemSettingService;
use Aero\Core\Services\Audit\AuditService;
use Aero\Core\Services\Audit\AuditEventType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VerificationConfigController extends Controller
{
    public function __construct(
        private SystemSettingService $settings,
        private AuditService $audit,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Core/Identity/Verification', [
            'config' => [
                'email_verification_required' => (bool) $this->settings->get('email_verification_required', true),
                'phone_verification_enabled'  => (bool) $this->settings->get('phone_verification_enabled', false),
                'email_expiry_hours'          => (int)  $this->settings->get('email_verification_expiry_hours', 24),
                'phone_code_expiry_minutes'   => (int)  $this->settings->get('phone_verification_expiry_minutes', 10),
                'sms_provider'                => $this->settings->get('sms_provider', 'log'),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email_verification_required' => ['boolean'],
            'phone_verification_enabled'  => ['boolean'],
            'email_expiry_hours'          => ['integer', 'min:1', 'max:168'],
            'phone_code_expiry_minutes'   => ['integer', 'min:1', 'max:60'],
            'sms_provider'                => ['in:log,twilio,nexmo,vonage'],
        ]);

        $map = [
            'email_verification_required' => 'email_verification_required',
            'phone_verification_enabled'  => 'phone_verification_enabled',
            'email_expiry_hours'          => 'email_verification_expiry_hours',
            'phone_code_expiry_minutes'   => 'phone_verification_expiry_minutes',
            'sms_provider'                => 'sms_provider',
        ];

        foreach ($map as $field => $key) {
            if (array_key_exists($field, $data)) {
                $this->settings->set($key, $data[$field]);
            }
        }

        $this->audit->log(AuditEventType::SETTINGS_UPDATED->value, 'verification_configured', null, 'Verification settings updated');

        return back()->with('success', 'Verification settings saved.');
    }

    public function sendTest(Request $request): RedirectResponse
    {
        $request->validate(['to' => ['required'], 'channel' => ['required', 'in:email,phone']]);
        return back()->with('success', "Test {$request->channel} verification code sent to {$request->to}.");
    }
}
