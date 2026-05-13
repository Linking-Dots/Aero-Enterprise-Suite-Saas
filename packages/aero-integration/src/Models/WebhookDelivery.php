<?php

namespace Aero\Integration\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookDelivery extends TenantModel
{
    use HasFactory;

    protected $table = 'integration_webhook_deliveries';

    protected $fillable = [
        'webhook_endpoint_id', 'event_type', 'payload', 'request_headers',
        'response_status', 'response_headers', 'response_body', 'delivered_at',
        'attempt_number', 'duration_ms', 'error_message',
    ];

    protected $casts = [
        'webhook_endpoint_id' => 'integer',
        'payload' => 'json',
        'request_headers' => 'json',
        'response_status' => 'integer',
        'response_headers' => 'json',
        'delivered_at' => 'datetime',
        'attempt_number' => 'integer',
        'duration_ms' => 'integer',
    ];

    public function webhookEndpoint()
    {
        return $this->belongsTo(WebhookEndpoint::class);
    }

    public function isSuccessful()
    {
        return $this->response_status >= 200 && $this->response_status < 300;
    }

    public function isFailed()
    {
        return ! $this->isSuccessful();
    }

    public function shouldRetry()
    {
        return $this->isFailed() &&
               $this->attempt_number < ($this->webhookEndpoint->retry_attempts ?? 3) &&
               $this->response_status >= 500; // Only retry server errors
    }

    public function scopeSuccessful($query)
    {
        return $query->whereBetween('response_status', [200, 299]);
    }

    public function scopeFailed($query)
    {
        return $query->where(function ($q) {
            $q->where('response_status', '<', 200)
                ->orWhere('response_status', '>=', 300);
        });
    }

    public function scopeForEvent($query, $eventType)
    {
        return $query->where('event_type', $eventType);
    }
}
