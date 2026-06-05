<?php

namespace Aero\Platform\Models;

use Aero\Contracts\Models\CentralModel;
use Aero\Platform\Contracts\BillableSubscription as BillableSubscriptionContract;
use Aero\Platform\Observers\SubscriptionImmutableObserver;
use Carbon\Carbon;
use Database\Factories\SubscriptionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Cashier\Subscription as CashierSubscription;

/**
 * Unified Subscription Model
 *
 * Extends Laravel Cashier's Subscription to serve as the single source of truth
 * for both Stripe-managed and lifecycle-managed subscription data.
 *
 * Cashier handles: stripe_id, stripe_status, stripe_price, billing via Stripe API.
 * Lifecycle service handles: upgrades, downgrades, grace periods, pending changes.
 *
 * @property string $id UUID primary key
 * @property string|null $tenant_id Legacy foreign key (kept for backward compat)
 * @property string $billable_type Cashier polymorphic type
 * @property string $billable_id Cashier polymorphic id
 * @property string $plan_id Foreign key to plans table
 * @property string $amount Charged amount (DECIMAL for precision)
 * @property string $status Subscription status: active, cancelled, past_due
 * @property Carbon $starts_at Subscription start date
 * @property Carbon|null $ends_at Subscription end date
 * @property string|null $stripe_id Stripe subscription ID (from Cashier)
 */
class Subscription extends CashierSubscription implements BillableSubscriptionContract
{
    /** @use HasFactory<SubscriptionFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * Subscription status constants.
     */
    public const STATUS_ACTIVE = 'active';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_PAST_DUE = 'past_due';

    public const STATUS_TRIALING = 'trialing';

    public const STATUS_EXPIRED = 'expired';

    /**
     * Boot the model — register immutability observer.
     */
    protected static function booted(): void
    {
        parent::booted();
        static::observe(SubscriptionImmutableObserver::class);

        // Subscriptions are central-owned. Cashier's base model uses the default
        // connection, which in tenant context is the tenant DB (no subscriptions
        // table). Pin writes to the central connection like CentralModel does.
        static::creating(fn (self $m) => $m->setConnection(CentralModel::centralConnectionName()));
        static::saving(fn (self $m) => $m->setConnection(CentralModel::centralConnectionName()));
    }

    /**
     * Resolve the central connection so tenant-context reads/writes hit the
     * central DB (mirrors CentralModel; Cashier's base class can't extend it).
     */
    public function getConnectionName(): ?string
    {
        return CentralModel::centralConnectionName();
    }

    /**
     * Override Cashier incrementing because we use UUID primary keys.
     */
    public $incrementing = false;

    /**
     * UUID key type.
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * Includes both Cashier fields and custom lifecycle fields.
     *
     * @var array<string>
     */
    protected $fillable = [
        // Cashier / Stripe fields
        'billable_type',
        'billable_id',
        'type',
        'name',
        'stripe_id',
        'stripe_status',
        'stripe_price',
        'quantity',
        'trial_ends_at',
        // Legacy / custom fields
        'tenant_id',
        'plan_id',
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
        'metadata',
        'next_billing_date',
        // Lifecycle fields
        'upgraded_from_plan_id',
        'upgraded_at',
        'downgraded_from_plan_id',
        'pending_plan_id',
        'downgraded_at',
        'downgrade_scheduled_at',
        'grace_period_ends_at',
        'current_period_start',
        // P-2 additions
        'current_period_end',
        'cancel_reason',
        'stripe_subscription_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * Merges Cashier base casts with our custom lifecycle casts.
     */
    protected $casts = [
        // Cashier base casts
        'quantity' => 'integer',
        'trial_ends_at' => 'datetime',
        'ends_at' => 'datetime',
        // Custom casts
        'amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'trial_starts_at' => 'datetime',
        'starts_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'metadata' => 'array',
        'next_billing_date' => 'datetime',
        // Lifecycle field casts
        'upgraded_at' => 'datetime',
        'downgraded_at' => 'datetime',
        'downgrade_scheduled_at' => 'datetime',
        'grace_period_ends_at' => 'datetime',
        'current_period_start' => 'datetime',
        // P-2 additions
        'current_period_end' => 'datetime',
    ];

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * Get the tenant that owns this subscription.
     *
     * Uses the polymorphic billable relation so this works seamlessly
     * with Laravel Cashier while still supporting our custom columns.
     */
    public function getTenantAttribute(): ?Tenant
    {
        if ($this->billable_type === Tenant::class) {
            return Tenant::find($this->billable_id);
        }

        return null;
    }

    /**
     * Cashier polymorphic billable relation alias.
     */
    public function owner(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'billable_type', 'billable_id');
    }

    /**
     * Get the plan associated with this subscription.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Get all platform invoices generated for this subscription.
     *
     * Named billingInvoices to avoid collision with Cashier's
     * invoices() method which queries Stripe invoice objects.
     */
    public function billingInvoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Scope to filter active subscriptions.
     *
     * A subscription is considered active when:
     * 1. Status is 'active'
     * 2. ends_at is in the future (or null for lifetime subscriptions)
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
     * Scope to filter subscriptions currently in trial.
     */
    public function scopeTrialing($query)
    {
        return $query->where('status', self::STATUS_TRIALING)
            ->where('trial_ends_at', '>', now());
    }

    /**
     * Scope to filter cancelled subscriptions.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    /**
     * Scope to filter past due subscriptions.
     */
    public function scopePastDue($query)
    {
        return $query->where('status', self::STATUS_PAST_DUE);
    }

    /**
     * Scope to filter expired subscriptions.
     */
    public function scopeExpired($query)
    {
        return $query->where('ends_at', '<', now());
    }

    /**
     * Scope to filter by billing cycle.
     */
    public function scopeBillingCycle(Builder $query, string $cycle): Builder
    {
        return $query->where('billing_cycle', $cycle);
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Check if the subscription is currently active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && ($this->ends_at === null || $this->ends_at->isFuture());
    }

    /**
     * Check if the subscription is currently in trial.
     */
    public function isTrialing(): bool
    {
        return $this->status === self::STATUS_TRIALING
            && $this->trial_ends_at?->isFuture();
    }

    /**
     * Check if the subscription has expired.
     */
    public function isExpired(): bool
    {
        return $this->ends_at?->isPast() ?? false;
    }

    /**
     * Check if the subscription is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Check if the subscription is past due on payment.
     */
    public function isPastDue(): bool
    {
        return $this->status === self::STATUS_PAST_DUE;
    }

    /**
     * Get the number of days remaining in the subscription.
     */
    public function daysRemaining(): int
    {
        if ($this->ends_at === null) {
            return PHP_INT_MAX; // Lifetime subscription
        }

        return max(0, now()->diffInDays($this->ends_at, false));
    }

    /**
     * Cancel the subscription.
     */
    public function cancel(?string $reason = null): bool
    {
        return $this->update([
            'status' => self::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);
    }

    // =========================================================================
    // BillableSubscription interface implementation
    // =========================================================================

    public function billableType(): string
    {
        return 'plan';
    }

    public function getTenantId(): string
    {
        return (string) $this->billable_id;
    }

    public function getStatus(): string
    {
        return (string) $this->status;
    }

    public function getTrialEndsAt(): ?Carbon
    {
        return $this->trial_ends_at instanceof Carbon ? $this->trial_ends_at : null;
    }

    /**
     * Renew the subscription for another billing cycle.
     *
     * Updates amount from the current plan price, preserves the billing
     * cycle, and advances next_billing_date alongside ends_at.
     */
    public function renew(): bool
    {
        $plan = $this->plan;
        $cycle = $this->billing_cycle ?? 'monthly';
        $duration = $cycle === 'yearly' ? 12 : ($plan->duration_in_months ?? 1);

        $effectivePrice = $cycle === 'yearly'
            ? ($plan->yearly_price ?? $plan->monthly_price)
            : $plan->monthly_price;

        $newEnd = now()->addMonths($duration);

        return $this->update([
            'status' => self::STATUS_ACTIVE,
            'starts_at' => now(),
            'ends_at' => $newEnd,
            'next_billing_date' => $newEnd,
            'amount' => $effectivePrice,
            'billing_cycle' => $cycle,
            'cancelled_at' => null,
            'cancellation_reason' => null,
        ]);
    }
}
