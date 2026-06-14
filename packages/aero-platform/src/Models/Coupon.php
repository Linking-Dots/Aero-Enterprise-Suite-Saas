<?php

declare(strict_types=1);

namespace Aero\Platform\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Coupon extends CentralModel
{
    use LogsActivity;

    public const TYPE_PERCENT = 'percent';

    public const TYPE_FIXED = 'fixed';

    public const DURATION_ONCE = 'once';

    public const DURATION_REPEATING = 'repeating';

    public const DURATION_FOREVER = 'forever';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'code',
        'name',
        'type',
        'value',
        'currency',
        'duration',
        'duration_months',
        'max_redemptions',
        'redemption_count',
        'expires_at',
        'status',
        'campaign_id',
        'created_by',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'expires_at' => 'datetime',
        'redemption_count' => 'integer',
        'max_redemptions' => 'integer',
        'duration_months' => 'integer',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->useLogName('coupon');
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(CouponCampaign::class, 'campaign_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function hasReachedMaxRedemptions(): bool
    {
        return $this->max_redemptions !== null
            && $this->redemption_count >= $this->max_redemptions;
    }
}
