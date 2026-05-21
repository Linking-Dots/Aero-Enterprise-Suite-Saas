<?php

declare(strict_types=1);

namespace Aero\Platform\Services\Integrations;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Platform\Models\WebhookDeliveryLog;
use Aero\Platform\Models\WebhookEndpoint;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * P-7 — Webhook Endpoint Service (platform level).
 */
class WebhookService
{
    public function __construct(private readonly AuditServiceInterface $audit) {}

    public function list(array $filters = []): LengthAwarePaginator
    {
        return WebhookEndpoint::query()
            ->when($filters['q'] ?? null, fn ($q, $v) => $q->where('url', 'like', "%{$v}%"))
            ->when(isset($filters['active']), fn ($q) => $q->where('is_active', (bool) $filters['active']))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();
    }

    public function create(array $data): WebhookEndpoint
    {
        return DB::transaction(function () use ($data): WebhookEndpoint {
            $endpoint = WebhookEndpoint::create([
                'url' => $data['url'],
                'description' => $data['description'] ?? null,
                'events' => $data['events'],
                'secret' => $data['secret'] ?? Str::random(32),
                'is_active' => $data['is_active'] ?? true,
            ]);

            $this->audit->log(
                event: AuditEventType::WEBHOOK_ENDPOINT_CREATED->value,
                action: 'create',
                subject: $endpoint,
                description: "Webhook endpoint created: {$endpoint->url}",
            );

            return $endpoint;
        });
    }

    public function update(int|string $id, array $data): WebhookEndpoint
    {
        return DB::transaction(function () use ($id, $data): WebhookEndpoint {
            $endpoint = WebhookEndpoint::findOrFail($id);

            $before = $endpoint->only(['url', 'description', 'events', 'is_active']);

            $endpoint->update(array_filter([
                'url' => $data['url'] ?? $endpoint->url,
                'description' => $data['description'] ?? $endpoint->description,
                'events' => $data['events'] ?? $endpoint->events,
                'is_active' => $data['is_active'] ?? $endpoint->is_active,
            ], fn ($v) => $v !== null));

            $this->audit->log(
                event: AuditEventType::WEBHOOK_ENDPOINT_UPDATED->value,
                action: 'update',
                subject: $endpoint,
                description: "Webhook endpoint updated: {$endpoint->url}",
                before: $before,
                after: $endpoint->fresh()->only(['url', 'description', 'events', 'is_active']),
            );

            return $endpoint->refresh();
        });
    }

    public function delete(int|string $id): void
    {
        DB::transaction(function () use ($id): void {
            $endpoint = WebhookEndpoint::findOrFail($id);

            $this->audit->log(
                event: AuditEventType::WEBHOOK_ENDPOINT_DELETED->value,
                action: 'delete',
                subject: $endpoint,
                description: "Webhook endpoint deleted: {$endpoint->url}",
            );

            $endpoint->delete();
        });
    }

    /**
     * Dispatch a sample payload to the endpoint and record a delivery log.
     */
    public function test(int|string $id): WebhookDeliveryLog
    {
        return DB::transaction(function () use ($id): WebhookDeliveryLog {
            $endpoint = WebhookEndpoint::findOrFail($id);

            $payload = [
                'event' => 'webhook.test',
                'timestamp' => now()->toIso8601String(),
                'data' => ['message' => 'This is a test webhook delivery from AEOS365.'],
            ];

            $responseStatus = 0;
            $responseBody = '';

            try {
                $response = Http::timeout(10)
                    ->withHeaders($this->buildSignatureHeaders($endpoint->secret ?? '', $payload))
                    ->post($endpoint->url, $payload);

                $responseStatus = $response->status();
                $responseBody = (string) $response->body();
            } catch (\Throwable $e) {
                $responseBody = $e->getMessage();
            }

            $log = WebhookDeliveryLog::create([
                'endpoint_id' => $endpoint->id,
                'event_type' => 'webhook.test',
                'payload' => $payload,
                'response_status' => $responseStatus,
                'response_body' => $responseBody,
                'delivered_at' => now(),
            ]);

            $endpoint->increment('failure_count', $responseStatus >= 400 || $responseStatus === 0 ? 1 : 0);
            $endpoint->update(['last_triggered_at' => now()]);

            $this->audit->log(
                event: AuditEventType::WEBHOOK_ENDPOINT_TESTED->value,
                action: 'test',
                subject: $endpoint,
                description: "Webhook endpoint tested: {$endpoint->url} — HTTP {$responseStatus}",
            );

            return $log;
        });
    }

    public function deliveryLogs(int|string $endpointId, array $filters = []): LengthAwarePaginator
    {
        return WebhookDeliveryLog::where('endpoint_id', $endpointId)
            ->when($filters['event_type'] ?? null, fn ($q, $v) => $q->where('event_type', $v))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();
    }

    /**
     * Re-deliver a previous delivery log payload.
     */
    public function replay(int|string $logId): WebhookDeliveryLog
    {
        return DB::transaction(function () use ($logId): WebhookDeliveryLog {
            $log = WebhookDeliveryLog::findOrFail($logId);
            $endpoint = WebhookEndpoint::findOrFail($log->endpoint_id);

            $responseStatus = 0;
            $responseBody = '';

            try {
                $payload = $log->payload ?? [];
                $response = Http::timeout(10)
                    ->withHeaders($this->buildSignatureHeaders($endpoint->secret ?? '', $payload))
                    ->post($endpoint->url, $payload);

                $responseStatus = $response->status();
                $responseBody = (string) $response->body();
            } catch (\Throwable $e) {
                $responseBody = $e->getMessage();
            }

            $newLog = WebhookDeliveryLog::create([
                'endpoint_id' => $endpoint->id,
                'event_type' => $log->event_type,
                'payload' => $log->payload,
                'response_status' => $responseStatus,
                'response_body' => $responseBody,
                'delivered_at' => now(),
            ]);

            $this->audit->log(
                event: AuditEventType::WEBHOOK_LOG_REPLAYED->value,
                action: 'replay',
                subject: $endpoint,
                description: "Webhook delivery log #{$log->id} replayed to {$endpoint->url} — HTTP {$responseStatus}",
            );

            return $newLog;
        });
    }

    /**
     * Rotate the signing secret for an endpoint.
     */
    public function rotateSecret(int|string $id): WebhookEndpoint
    {
        return DB::transaction(function () use ($id): WebhookEndpoint {
            $endpoint = WebhookEndpoint::findOrFail($id);

            $endpoint->update(['secret' => Str::random(32)]);

            $this->audit->log(
                event: AuditEventType::WEBHOOK_SECRET_ROTATED->value,
                action: 'rotate-secret',
                subject: $endpoint,
                description: "Webhook signing secret rotated for: {$endpoint->url}",
            );

            return $endpoint->refresh();
        });
    }

    public function eventCatalog(): array
    {
        return [
            'tenant.created',
            'tenant.updated',
            'tenant.suspended',
            'tenant.activated',
            'subscription.created',
            'subscription.cancelled',
            'subscription.upgraded',
            'invoice.generated',
            'invoice.paid',
            'feature_flag.toggled',
            'webhook.test',
        ];
    }

    private function buildSignatureHeaders(string $secret, array $payload): array
    {
        if (empty($secret)) {
            return [];
        }

        $timestamp = time();
        $body = json_encode($payload);
        $signature = hash_hmac('sha256', "{$timestamp}.{$body}", $secret);

        return [
            'X-Webhook-Timestamp' => (string) $timestamp,
            'X-Webhook-Signature' => "sha256={$signature}",
        ];
    }
}
