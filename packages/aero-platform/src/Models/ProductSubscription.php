<?php

declare(strict_types=1);

namespace Aero\Platform\Models;

use Aero\Platform\Contracts\BillableSubscription as BillableSubscriptionContract;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductSubscription extends CentralModel implements BillableSubscriptionContract
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'product_id', 'billing_cycle', 'amount', 'discount_amount',
        'currency', 'status', 'trial_starts_at', 'trial_ends_at',
        'starts_at', 'ends_at', 'cancelled_at', 'cancellation_reason',
        'payment_method', 'external_subscription_id', 'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'trial_starts_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active'
            && ($this->ends_at === null || $this->ends_at->isFuture());
    }

    public function isTrialing(): bool
    {
        return $this->status === 'trialing'
            && $this->trial_ends_at !== null
            && $this->trial_ends_at->isFuture();
    }

    public function hasAccess(): bool
    {
        return $this->isActive() || $this->isTrialing();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()));
    }

    public function scopeHasAccess(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where('status', 'active')
                ->where(fn (Builder $sub) => $sub->whereNull('ends_at')->orWhere('ends_at', '>', now()));
        })->orWhere(function (Builder $q) {
            $q->where('status', 'trialing')
                ->where('trial_ends_at', '>', now());
        });
    }

    // =========================================================================
    // BillableSubscription interface implementation
    // =========================================================================

    public function billableType(): string
    {
        return 'product';
    }

    public function getTenantId(): string
    {
        return (string) $this->tenant_id;
    }

    public function getStatus(): string
    {
        return (string) $this->status;
    }

    public function getTrialEndsAt(): ?Carbon
    {
        return $this->trial_ends_at instanceof Carbon ? $this->trial_ends_at : null;
    }
}
