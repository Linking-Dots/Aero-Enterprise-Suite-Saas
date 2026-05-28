<?php

namespace Aero\Core\Http\Controllers\Admin;

use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Core\Services\Audit\AuditService;
use Aero\Core\Services\SystemSettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MaintenanceModeController extends Controller
{
    public function __construct(
        private SystemSettingService $settings,
        private AuditService $audit,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Core/Maintenance/Index', [
            'config' => [
                'is_enabled' => (bool) $this->settings->get('maintenance_enabled', false),
                'message' => $this->settings->get('maintenance_message', 'We are currently performing scheduled maintenance. Please check back soon.'),
                'allowed_ips' => $this->settings->get('maintenance_allowed_ips', ''),
                'scheduled_at' => $this->settings->get('maintenance_scheduled_at'),
                'end_at' => $this->settings->get('maintenance_end_at'),
            ],
        ]);
    }

    public function enable(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'message' => ['nullable', 'string', 'max:500'],
            'allowed_ips' => ['nullable', 'string'],
            'end_at' => ['nullable', 'date'],
        ]);

        $this->settings->set('maintenance_enabled', true);
        $this->settings->set('maintenance_message', $data['message'] ?? 'Scheduled maintenance in progress.');
        $this->settings->set('maintenance_allowed_ips', $data['allowed_ips'] ?? '');
        if (! empty($data['end_at'])) {
            $this->settings->set('maintenance_end_at', $data['end_at']);
        }

        $this->audit->log(AuditEventType::SETTINGS_UPDATED->value, 'maintenance_enabled', null, 'Maintenance mode enabled');

        return back()->with('success', 'Maintenance mode enabled.');
    }

    public function disable(Request $request): RedirectResponse
    {
        $this->settings->set('maintenance_enabled', false);
        $this->audit->log(AuditEventType::SETTINGS_UPDATED->value, 'maintenance_disabled', null, 'Maintenance mode disabled');

        return back()->with('success', 'Maintenance mode disabled.');
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'message' => ['nullable', 'string', 'max:500'],
            'allowed_ips' => ['nullable', 'string'],
            'end_at' => ['nullable', 'date'],
        ]);

        foreach ($data as $key => $value) {
            $this->settings->set("maintenance_{$key}", $value);
        }

        $this->audit->log(AuditEventType::SETTINGS_UPDATED->value, 'maintenance_configured', null, 'Maintenance mode configured');

        return back()->with('success', 'Maintenance configuration saved.');
    }
}
