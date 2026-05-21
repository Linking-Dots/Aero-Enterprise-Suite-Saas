<?php

declare(strict_types=1);

namespace Aero\Platform\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CouponRedemption extends CentralModel
{
    protected $fillable = [
        'coupon_id',
        'tenant_id',
        'subscribable_type',
        'subscribable_id',
        'discount_applied',
        'redeemed_at',
    ];

    protected $casts = [
        'discount_applied' => 'decimal:2',
        'redeemed_at' => 'datetime',
    ];

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * Polymorphic target: Subscription | ProductSubscription.
     */
    public function subscribable(): MorphTo
    {
        return $this->morphTo();
    }
}
