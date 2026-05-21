<?php

declare(strict_types=1);

namespace Aero\Platform\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * P-7 — Scheduled maintenance window.
 */
class MaintenanceWindow extends CentralModel
{
    use HasFactory;

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'maintenance_windows';

    protected $fillable = [
        'title',
        'message',
        'starts_at',
        'ends_at',
        'status',
        'affected_tenants',
        'affected_tenant_ids',
        'cancelled_at',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'affected_tenant_ids' => 'array',
    ];

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function getAuditLabel(): string
    {
        return "Maintenance Window: {$this->title}";
    }
}
