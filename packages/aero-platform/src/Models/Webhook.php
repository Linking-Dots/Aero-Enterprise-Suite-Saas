<?php

namespace Aero\Platform\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Webhook extends CentralModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'integrations_webhooks';

    protected $fillable = [
        'connector_id', 'name', 'url', 'event', 'method', 'headers', 'secret',
        'is_active', 'last_triggered_at', 'success_count', 'failure_count',
    ];

    protected $casts = [
        'connector_id' => 'integer',
        'headers' => 'array',
        'is_active' => 'boolean',
        'last_triggered_at' => 'datetime',
        'success_count' => 'integer',
        'failure_count' => 'integer',
    ];

    public function connector()
    {
        return $this->belongsTo(Connector::class);
    }

    public function logs()
    {
        return $this->hasMany(WebhookLog::class);
    }
}
