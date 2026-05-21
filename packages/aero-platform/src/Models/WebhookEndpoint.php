<?php

declare(strict_types=1);

namespace Aero\Platform\Models;

use Aero\Core\Encryption\EncryptedField;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * P-7 — Outbound Webhook Endpoint (platform level).
 */
class WebhookEndpoint extends CentralModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'platform_webhook_endpoints';

    protected $fillable = [
        'url',
        'description',
        'events',
        'secret',
        'is_active',
        'failure_count',
        'last_triggered_at',
    ];

    protected $casts = [
        'events' => 'array',
        'is_active' => 'boolean',
        'failure_count' => 'integer',
        'last_triggered_at' => 'datetime',
        'secret' => EncryptedField::class,
    ];

    public function deliveryLogs(): HasMany
    {
        return $this->hasMany(WebhookDeliveryLog::class, 'endpoint_id');
    }

    public function getAuditLabel(): string
    {
        return "Webhook Endpoint: {$this->url}";
    }
}
