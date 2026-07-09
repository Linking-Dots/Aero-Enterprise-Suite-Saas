<?php

declare(strict_types=1);

namespace Aero\Platform\Services\Integrations;

use Aero\Platform\Models\PlatformApiKey;
use Aero\Platform\Models\WebhookDeliveryLog;
use Aero\Platform\Models\WebhookEndpoint;
use Carbon\Carbon;

/**
 * Read-only aggregation for the Integrations command centre — API keys,
 * outbound webhooks (with delivery health) and the connector catalogue on one
 * surface. Composes the existing key/webhook services; every figure is derived.
 */
class IntegrationsAdminService
{
    public function __construct(private readonly WebhookService $webhooks) {}

    /** @return array<string, mixed> */
    public function overview(): array
    {
        $keys = PlatformApiKey::with('createdBy:id,name')->latest('id')->get();
        $endpoints = WebhookEndpoint::latest('id')->get();

        $since = Carbon::now()->subHours(72);
        $logs = WebhookDeliveryLog::where('created_at', '>=', $since)->get();
        $delivered = $logs->where('response_status', 200)->count();
        $totalLogs = $logs->count();

        $activeKeys = $keys->where('is_active', true)->whereNull('revoked_at')->count();
        $failing = $endpoints->filter(fn ($e) => (int) $e->failure_count >= 10)->count();

        return [
            'kpis' => [
                'active_keys'      => $activeKeys,
                'total_keys'       => $keys->count(),
                'endpoints'        => $endpoints->count(),
                'active_endpoints' => $endpoints->where('is_active', true)->count(),
                'failing'          => $failing,
                'deliveries_72h'   => $totalLogs,
                'delivered'        => $delivered,
                'failed'           => $totalLogs - $delivered,
                'success_rate'     => $totalLogs > 0 ? (int) round($delivered / $totalLogs * 100) : 100,
                'events'           => count($this->webhooks->eventCatalog()),
            ],
            'api_keys'  => $keys->map(fn ($k) => [
                'id'          => $k->id,
                'name'        => $k->name,
                'key_prefix'  => $k->key_prefix,
                'scopes'      => is_array($k->scopes) ? $k->scopes : [],
                'last_used'   => optional($k->last_used_at)->toDateTimeString(),
                'created_by'  => $k->createdBy?->name,
                'is_active'   => (bool) $k->is_active && $k->revoked_at === null,
                'expires_at'  => optional($k->expires_at)->toDateTimeString(),
            ])->all(),
            'endpoints' => $endpoints->map(fn ($e) => [
                'id'            => $e->id,
                'url'           => $e->url,
                'description'   => $e->description,
                'events'        => is_array($e->events) ? $e->events : [],
                'is_active'     => (bool) $e->is_active,
                'failure_count' => (int) $e->failure_count,
                'last_triggered' => optional($e->last_triggered_at)->toDateTimeString(),
                'status'        => ! $e->is_active ? 'disabled' : ((int) $e->failure_count >= 10 ? 'failing' : 'active'),
            ])->all(),
            'recent_logs' => WebhookDeliveryLog::with('endpoint:id,url')
                ->latest('created_at')->limit(10)->get()
                ->map(fn ($l) => [
                    'id'       => $l->id,
                    'event'    => $l->event_type,
                    'host'     => $l->endpoint ? parse_url($l->endpoint->url, PHP_URL_HOST) : '—',
                    'status'   => (int) $l->response_status,
                    'ok'       => (int) $l->response_status === 200,
                    'at'       => optional($l->created_at)->toDateTimeString(),
                ])->all(),
            'events'     => $this->webhooks->eventCatalog(),
            'connectors' => $this->connectors(),
        ];
    }

    /**
     * Curated connector catalogue. Static domain knowledge — there is no
     * per-connector backend yet, so "connected" reflects a marketplace state,
     * not a live OAuth link. Presented honestly as a browse/connect surface.
     *
     * @return array<int, array<string, mixed>>
     */
    private function connectors(): array
    {
        return [
            ['key' => 'slack', 'name' => 'Slack', 'category' => 'Messaging', 'color' => '#4A154B', 'desc' => 'Post tenant & billing events to a Slack channel', 'connected' => true],
            ['key' => 'stripe', 'name' => 'Stripe', 'category' => 'Payments', 'color' => '#635BFF', 'desc' => 'Sync subscriptions & invoices with Stripe', 'connected' => true],
            ['key' => 'zapier', 'name' => 'Zapier', 'category' => 'Automation', 'color' => '#FF4A00', 'desc' => 'Trigger 6,000+ apps from platform events', 'connected' => true],
            ['key' => 'google', 'name' => 'Google Workspace', 'category' => 'Identity', 'color' => '#4285F4', 'desc' => 'SSO & directory sync for tenant admins', 'connected' => false],
            ['key' => 'teams', 'name' => 'Microsoft Teams', 'category' => 'Messaging', 'color' => '#6264A7', 'desc' => 'Alerts & approvals in Teams', 'connected' => false],
            ['key' => 'salesforce', 'name' => 'Salesforce', 'category' => 'CRM', 'color' => '#00A1E0', 'desc' => 'Push new tenants as leads to Salesforce', 'connected' => false],
            ['key' => 'github', 'name' => 'GitHub', 'category' => 'DevOps', 'color' => '#181717', 'desc' => 'Link deploys & incidents to releases', 'connected' => false],
            ['key' => 'hubspot', 'name' => 'HubSpot', 'category' => 'CRM', 'color' => '#FF7A59', 'desc' => 'Marketing automation & lifecycle sync', 'connected' => false],
        ];
    }
}
