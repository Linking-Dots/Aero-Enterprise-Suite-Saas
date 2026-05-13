<?php

namespace Aero\Platform\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookLog extends CentralModel
{
    use HasFactory;

    protected $table = 'integrations_webhook_logs';

    protected $fillable = [
        'webhook_id', 'payload', 'response', 'status_code', 'success', 'error_message',
    ];

    protected $casts = [
        'webhook_id' => 'integer',
        'payload' => 'array',
        'response' => 'array',
        'status_code' => 'integer',
        'success' => 'boolean',
    ];

    public function webhook()
    {
        return $this->belongsTo(Webhook::class);
    }
}
