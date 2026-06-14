<?php

namespace Aero\Auth\Http\Controllers\Admin;

use Aero\Contracts\SystemSettingServiceInterface;
use Aero\Kernel\Http\Controllers\Controller;
use Aero\Contracts\AuditServiceInterface;
use Aero\Kernel\Audit\AuditEventType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountRecoveryController extends Controller
{
    public function __construct(
        private SystemSettingServiceInterface $settings,
        private AuditServiceInterface $audit,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Core/Identity/AccountRecovery', [
            'config' => [
                'recovery_codes_enabled'         => (bool) $this->settings->get('recovery_codes_enabled', true),
                'recovery_codes_count'           => (int)  $this->settings->get('recovery_codes_count', 8),
                'backup_email_enabled'           => (bool) $this->settings->get('backup_email_enabled', true),
                'security_questions_enabled'     => (bool) $this->settings->get('security_questions_enabled', false),
                'admin_override_enabled'         => (bool) $this->settings->get('admin_recovery_override', true),
                'recovery_rate_limit_per_hour'   => (int)  $this->settings->get('recovery_rate_limit_per_hour', 3),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'recovery_codes_enabled'       => ['boolean'],
            'recovery_codes_count'         => ['integer', 'min:4', 'max:20'],
            'backup_email_enabled'         => ['boolean'],
            'security_questions_enabled'   => ['boolean'],
            'admin_override_enabled'       => ['boolean'],
            'recovery_rate_limit_per_hour' => ['integer', 'min:1', 'max:20'],
        ]);

        $map = [
            'recovery_codes_enabled'       => 'recovery_codes_enabled',
            'recovery_codes_count'         => 'recovery_codes_count',
            'backup_email_enabled'         => 'backup_email_enabled',
            'security_questions_enabled'   => 'security_questions_enabled',
            'admin_override_enabled'       => 'admin_recovery_override',
            'recovery_rate_limit_per_hour' => 'recovery_rate_limit_per_hour',
        ];

        foreach ($map as $field => $key) {
            if (array_key_exists($field, $data)) {
                $this->settings->set($key, $data[$field]);
            }
        }

        $this->audit->log(AuditEventType::SETTINGS_UPDATED->value, 'account_recovery_configured', null, 'Account recovery settings updated');

        return back()->with('success', 'Account recovery settings saved.');
    }
}
