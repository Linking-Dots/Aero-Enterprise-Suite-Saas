<?php

namespace Aero\Platform\Models;

use Carbon\Carbon;
use Database\Factories\SubscriptionModuleFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Subscription Module (Add-on)
 *
 * Represents an individual module subscription separate from the base plan.
 * Each row is a single module add-on linked to a tenant's polymorphic billable
 * record. Module subscriptions can have independent billing cycles, trial
 * periods, and cancellation status from the base plan subscription.
 *
 * @deprecated Use ProductSubscription instead. SubscriptionModule will be removed in a future release.
 *
 * @property string $id UUID primary key
 * @property string $billable_type
 * @property string $billable_id
 * @property string $module_code Reference to module_pricing.module_code
 * @property string $billing_cycle monthly|yearly
 * @property string $amount Decimal price
 * @property string $status active, cancelled, past_due, trialing, paused, expired
 * @property Carbon|null $trial_ends_at
 * @property Carbon|null $ends_at
 */
class SubscriptionModule extends CentralModel
{
    use HasFactory, HasUuids, SoftDeletes;

    /** @use HasFactory<SubscriptionModuleFactory> */
    protected $table = 'subscription_modules';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'billable_type',
        'billable_id',
        'module_code',
        'billing_cycle',
        'amount',
        'discount_amount',
        'currency',
        'status',
        'trial_starts_at',
        'trial_ends_at',
        'starts_at',
        'ends_at',
        'cancelled_at',
        'cancellation_reason',
        'payment_method',
        'external_subscription_id',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'metadata' => 'array',
        'trial_starts_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /**
     * Subscription status constants.
     */
    public const STATUS_ACTIVE = 'active';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_PAST_DUE = 'past_due';

    public const STATUS_TRIALING = 'trialing';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_PAUSED = 'paused';

    /**
     * The tenant (or other billable entity) that owns this module subscription.
     */
    public function billable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Convenience accessor for the tenant when billable_type is Tenant.
     */
    public function getTenantAttribute(): ?Tenant
    {
        if ($this->billable_type === Tenant::class) {
            return Tenant::find($this->billable_id);
        }

        return null;
    }

    /**
     * Scope: only active module subscriptions.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where(function ($q) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>', now());
            });
    }

    /**
     * Scope: currently in trial.
     */
    public function scopeTrialing($query)
    {
        return $query->where('status', self::STATUS_TRIALING)
            ->where('trial_ends_at', '>', now());
    }

    /**
     * Scope: cancelled.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    /**
     * Scope: expired.
     */
    public function scopeExpired($query)
    {
        return $query->where('ends_at', '<', now());
    }
}
