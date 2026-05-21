<?php

declare(strict_types=1);

namespace Aero\Platform\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * P-7 — Targeted bulk email to tenants.
 *
 * target_filter supports:
 *   - plan_ids      → tenants with active Subscription to those plans
 *   - product_ids   → tenants with active ProductSubscription to those products
 *   - statuses      → tenant status filter
 * Both axes are independent and combined with AND logic.
 */
class TenantEmailBlast extends CentralModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'tenant_email_blasts';

    protected $fillable = [
        'subject',
        'body_html',
        'target_filter',
        'sent_count',
        'sent_at',
        'created_by',
    ];

    protected $casts = [
        'target_filter' => 'array',
        'sent_count' => 'integer',
        'sent_at' => 'datetime',
        'created_by' => 'integer',
    ];

    public function isSent(): bool
    {
        return $this->sent_at !== null;
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(LandlordUser::class, 'created_by');
    }

    public function getAuditLabel(): string
    {
        return "Email Blast: {$this->subject}";
    }
}
