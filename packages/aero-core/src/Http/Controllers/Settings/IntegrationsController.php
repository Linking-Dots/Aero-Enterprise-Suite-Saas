<?php

namespace Aero\Core\Http\Controllers\Settings;

use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Core\Services\Audit\AuditService;
use Aero\Core\Services\SystemSettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IntegrationsController extends Controller
{
    public function __construct(
        private SystemSettingService $settings,
        private AuditService $audit,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Core/Settings/Index', [
            'section' => 'integrations',
            'summary' => \Aero\Core\Services\SettingsSummary::build(),
            'integrations' => [
                'slack' => [
                    'enabled' => (bool) $this->settings->get('integration_slack_enabled', false),
                    'webhook_url' => $this->settings->get('integration_slack_webhook', ''),
                    'channel' => $this->settings->get('integration_slack_channel', '#general'),
                ],
                'google_workspace' => [
                    'enabled' => (bool) $this->settings->get('integration_gws_enabled', false),
                    'client_id' => $this->settings->get('integration_gws_client_id', ''),
                    'domain' => $this->settings->get('integration_gws_domain', ''),
                ],
                'microsoft_365' => [
                    'enabled' => (bool) $this->settings->get('integration_m365_enabled', false),
                    'tenant_id' => $this->settings->get('integration_m365_tenant_id', ''),
                    'client_id' => $this->settings->get('integration_m365_client_id', ''),
                ],
                'zapier' => [
                    'enabled' => (bool) $this->settings->get('integration_zapier_enabled', false),
                    'api_key' => $this->settings->get('integration_zapier_key', ''),
                ],
            ],
        ]);
    }

    public function update(Request $request, string $integration): RedirectResponse
    {
        $allowed = ['slack', 'google_workspace', 'microsoft_365', 'zapier'];
        abort_if(! in_array($integration, $allowed), 404);

        $data = $request->validate([
            'enabled' => ['boolean'],
            'webhook_url' => ['nullable', 'url'],
            'channel' => ['nullable', 'string'],
            'client_id' => ['nullable', 'string'],
            'domain' => ['nullable', 'string'],
            'tenant_id' => ['nullable', 'string'],
            'api_key' => ['nullable', 'string'],
        ]);

        $prefix = 'integration_'.str_replace('_', '_', $integration).'_';
        foreach ($data as $key => $value) {
            if ($value !== null) {
                $this->settings->set($prefix.str_replace('_', '_', $key), $value);
            }
        }

        $this->audit->log(AuditEventType::SETTINGS_UPDATED->value, 'integration_updated', null, "Integration updated: {$integration}");

        return back()->with('success', ucwords(str_replace('_', ' ', $integration)).' integration saved.');
    }
}
