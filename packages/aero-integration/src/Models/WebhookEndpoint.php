<?php

namespace Aero\Integration\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WebhookEndpoint extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'integration_webhook_endpoints';

    protected $fillable = [
        'name', 'url', 'method', 'events', 'headers', 'secret', 'timeout',
        'retry_attempts', 'is_active', 'last_triggered_at', 'success_count',
        'failure_count', 'created_by', 'description',
    ];

    protected $casts = [
        'events' => 'json',
        'headers' => 'json',
        'timeout' => 'integer',
        'retry_attempts' => 'integer',
        'is_active' => 'boolean',
        'last_triggered_at' => 'datetime',
        'success_count' => 'integer',
        'failure_count' => 'integer',
        'created_by' => 'integer',
    ];

    const METHOD_POST = 'POST';

    const METHOD_PUT = 'PUT';

    const METHOD_PATCH = 'PATCH';

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deliveries()
    {
        return $this->hasMany(WebhookDelivery::class);
    }

    public function subscriptions()
    {
        return $this->belongsToMany(IntegrationEndpoint::class, 'integration_webhook_subscriptions')
            ->withPivot('event_types', 'is_active');
    }

    public function getSuccessRateAttribute()
    {
        $total = $this->success_count + $this->failure_count;
        if ($total === 0) {
            return 0;
        }

        return round(($this->success_count / $total) * 100, 2);
    }

    public function isHealthy()
    {
        return $this->success_rate >= 95; // 95% success rate threshold
    }

    public function supportsEvent($event)
    {
        return in_array($event, $this->events ?? []);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForEvent($query, $event)
    {
        return $query->whereJsonContains('events', $event);
    }
}
