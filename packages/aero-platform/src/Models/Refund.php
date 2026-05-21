<?php

declare(strict_types=1);

namespace Aero\Platform\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Refund extends CentralModel
{
    use LogsActivity;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_PROCESSED = 'processed';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'reference',
        'tenant_id',
        'invoice_id',
        'amount',
        'currency',
        'reason',
        'status',
        'gateway_refund_id',
        'requested_by',
        'approved_by',
        'processed_by',
        'approved_at',
        'processed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->useLogName('refund');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(LandlordUser::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(LandlordUser::class, 'approved_by');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(LandlordUser::class, 'processed_by');
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
