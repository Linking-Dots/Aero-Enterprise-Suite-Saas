<?php

declare(strict_types=1);

namespace Aero\Platform\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * P-7 — In-app broadcast to all or specific tenants.
 */
class TenantBroadcast extends CentralModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'tenant_broadcasts';

    protected $fillable = [
        'title',
        'body',
        'target',
        'target_tenant_ids',
        'published_at',
        'dismissed_count',
        'created_by',
    ];

    protected $casts = [
        'target_tenant_ids' => 'array',
        'dismissed_count' => 'integer',
        'published_at' => 'datetime',
        'created_by' => 'integer',
    ];

    public function isPublished(): bool
    {
        return $this->published_at !== null;
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getAuditLabel(): string
    {
        return "Broadcast: {$this->title}";
    }
}
