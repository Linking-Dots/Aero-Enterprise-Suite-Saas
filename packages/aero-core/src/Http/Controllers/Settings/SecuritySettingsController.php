<?php

namespace Aero\Core\Http\Controllers\Settings;

use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Core\Services\Audit\AuditService;
use Aero\Core\Services\SettingsSummary;
use Aero\Core\Services\SystemSettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SecuritySettingsController extends Controller
{
    public function __construct(
        private readonly SystemSettingService $settings,
        private readonly AuditService $audit,
    ) {}

    /**
     * Display security settings inside the unified Settings command center.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('Core/Settings/Index', [
            'section' => 'security',
            'summary' => SettingsSummary::build(),
            'security' => [
                'require_2fa_admins' => (bool) $this->settings->get('require_2fa_admins', false),
                'session_lifetime' => (int) $this->settings->get('session_lifetime', 120),
                'max_failed_attempts' => (int) $this->settings->get('max_failed_attempts', 5),
                'lockout_duration' => (int) $this->settings->get('lockout_duration', 15),
            ],
        ]);
    }

    /**
     * Persist security settings. (Previously the route pointed at a missing
     * method — saving 500'd; this closes that gap.)
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'require_2fa_admins' => ['required', 'boolean'],
            'session_lifetime' => ['required', 'integer', 'min:5', 'max:43200'],
            'max_failed_attempts' => ['required', 'integer', 'min:1', 'max:20'],
            'lockout_duration' => ['required', 'integer', 'min:0', 'max:1440'],
        ]);

        foreach ($validated as $key => $value) {
            $this->settings->set($key, $value);
        }

        $this->audit->log(
            AuditEventType::SETTINGS_UPDATED->value,
            'updated',
            null,
            'Security settings updated',
            null,
            null,
            ['keys' => array_keys($validated)]
        );

        return back()->with('success', 'Security settings saved.');
    }
}
