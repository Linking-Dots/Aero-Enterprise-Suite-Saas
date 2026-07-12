<?php

declare(strict_types=1);

namespace Aero\Platform\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Central per-tenant AI usage summary (one row per tenant per billing month),
 * written by the aeon:rollup job and read by the platform AI console.
 */
class AeonTenantUsage extends CentralModel
{
    protected $connection = 'central';

    protected $table = 'aeon_tenant_usage';

    protected $fillable = [
        'tenant_id', 'period', 'enabled', 'message_limit', 'model', 'plan_name',
        'messages_used', 'feedback_up', 'feedback_down', 'last_used_at', 'synced_at',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'message_limit' => 'integer',
        'messages_used' => 'integer',
        'feedback_up' => 'integer',
        'feedback_down' => 'integer',
        'last_used_at' => 'datetime',
        'synced_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
