<?php

declare(strict_types=1);

namespace Aero\Platform\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Webhook Event Model
 *
 * Records processed webhook events for idempotency.
 * Duplicate deliveries from Stripe or other providers are safely skipped
 * by checking this table before processing.
 */
class WebhookEvent extends CentralModel
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'provider',
        'event_id',
        'event_type',
        'status',
        'payload',
        'error',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'processed_at' => 'datetime',
    ];

    /**
     * Check if an event has already been processed.
     */
    public static function alreadyProcessed(string $provider, string $eventId): bool
    {
        return static::where('provider', $provider)
            ->where('event_id', $eventId)
            ->where('status', 'processed')
            ->exists();
    }

    /**
     * Record a successfully processed event.
     */
    public static function recordProcessed(string $provider, string $eventId, string $eventType, ?array $payload = null): static
    {
        return static::create([
            'provider' => $provider,
            'event_id' => $eventId,
            'event_type' => $eventType,
            'status' => 'processed',
            'payload' => $payload,
            'processed_at' => now(),
        ]);
    }

    /**
     * Record a failed event processing attempt.
     */
    public static function recordFailed(string $provider, string $eventId, string $eventType, string $error, ?array $payload = null): static
    {
        return static::create([
            'provider' => $provider,
            'event_id' => $eventId,
            'event_type' => $eventType,
            'status' => 'failed',
            'error' => $error,
            'payload' => $payload,
            'processed_at' => now(),
        ]);
    }
}
