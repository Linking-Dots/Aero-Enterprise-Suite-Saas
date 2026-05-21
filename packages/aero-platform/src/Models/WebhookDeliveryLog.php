<?php

declare(strict_types=1);

namespace Aero\Platform\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * P-7 — Delivery log for an outbound webhook attempt.
 */
class WebhookDeliveryLog extends CentralModel
{
    use HasFactory;

    protected $table = 'platform_webhook_delivery_logs';

    protected $fillable = [
        'endpoint_id',
        'event_type',
        'payload',
        'response_status',
        'response_body',
        'delivered_at',
        'next_retry_at',
    ];

    protected $casts = [
        'endpoint_id' => 'integer',
        'payload' => 'array',
        'response_status' => 'integer',
        'delivered_at' => 'datetime',
        'next_retry_at' => 'datetime',
    ];

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(WebhookEndpoint::class, 'endpoint_id');
    }
}
