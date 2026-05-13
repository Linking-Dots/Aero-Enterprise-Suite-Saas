<?php

namespace Aero\Platform\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionAuditLog extends CentralModel
{
    protected $table = 'subscription_audit_logs';

    public $timestamps = false;

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    protected $fillable = [
        'tenant_id',
        'subscription_id',
        'event_type',
        'description',
        'old_values',
        'new_values',
        'performed_by_user_id',
        'ip_address',
        'user_agent',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
