<?php

namespace Aero\Platform\Models;

use Aero\Core\Models\CentralModel;
use Aero\Platform\Database\Factories\PlanFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * EOS365 Plan Model
 *
 * Represents a subscription plan (e.g., 'Gold Monthly', 'Enterprise Yearly').
 * Uses DECIMAL for price to ensure financial precision.
 *
 * @property string $id UUID primary key
 * @property string $slug Unique identifier for code reference
 * @property string $name Display name
 * @property string $monthly_price Monthly price in decimal format
 * @property int $duration_in_months Billing cycle: 1=Monthly, 12=Yearly
 * @property int $max_users Maximum allowed users (0 = unlimited)
 * @property int $max_storage_gb Storage quota in GB
 */
class Plan extends CentralModel
{
    /** @use HasFactory<PlanFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): PlanFactory
    {
        return PlanFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'tier',
        'plan_type',
        'description',
        'monthly_price',
        'yearly_price',
        'setup_fee',
        'currency',
        'features',
        'limits',
        'trial_days',
        'grace_days',
        'downgrade_policy',
        'cancellation_policy',
        'sort_order',
        'is_active',
        'is_featured',
        'visibility',
        'duration_in_months',
        'supports_custom_duration',
        'max_users',
        'max_storage_gb',
        // Stripe integration
        'stripe_monthly_price_id',
        'stripe_yearly_price_id',
        'stripe_product_id',
        // P-2 additions
        'price_monthly',
        'price_annual',
        'status',
        'is_public',
        'stripe_price_id_monthly',
        'stripe_price_id_annual',
    ];

    /**
     * The attributes that should be cast.
     *
     * Note: Using decimal:2 for price columns ensures proper
     * financial calculations without floating point errors.
     */
    protected function casts(): array
    {
        return [
            'monthly_price' => 'decimal:2',
            'yearly_price' => 'decimal:2',
            'setup_fee' => 'decimal:2',
            'price_monthly' => 'decimal:2',
            'price_annual' => 'decimal:2',
            'is_public' => 'boolean',
            'features' => 'array',
            'limits' => 'array',
            'trial_days' => 'integer',
            'grace_days' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'supports_custom_duration' => 'boolean',
            'duration_in_months' => 'integer',
            'max_users' => 'integer',
            'max_storage_gb' => 'integer',
        ];
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * Get all subscriptions for this plan.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get all tenants subscribed to this plan.
     */
    public function tenants(): HasManyThrough
    {
        return $this->hasManyThrough(
            Tenant::class,
            Subscription::class,
            'plan_id',      // Foreign key on subscriptions table
            'id',           // Foreign key on tenants table
            'id',           // Local key on plans table
            'tenant_id'     // Local key on subscriptions table
        );
    }

    /**
     * Get canonical quota rows for this plan.
     */
    public function planQuotas(): HasMany
    {
        return $this->hasMany(PlanQuota::class)->orderBy('sort_order');
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Scope to filter only active plans.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter featured plans.
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope to order plans by sort_order.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Scope to filter monthly plans.
     */
    public function scopeMonthly(Builder $query): Builder
    {
        return $query->where('duration_in_months', 1);
    }

    /**
     * Scope to filter yearly plans.
     */
    public function scopeYearly(Builder $query): Builder
    {
        return $query->where('duration_in_months', 12);
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Check if users are unlimited for this plan.
     */
    public function hasUnlimitedUsers(): bool
    {
        return $this->max_users === 0;
    }

    /**
     * Get the effective price based on duration.
     */
    public function getEffectivePrice(): string
    {
        return $this->duration_in_months === 12
            ? $this->yearly_price
            : $this->monthly_price;
    }

    /**
     * Calculate monthly equivalent price for yearly plans.
     */
    public function getMonthlyEquivalent(): string
    {
        if ($this->duration_in_months === 12) {
            return number_format((float) $this->yearly_price / 12, 2);
        }

        return $this->monthly_price;
    }

    // =========================================================================
    // COMPUTED ATTRIBUTES (ACCESSORS)
    // =========================================================================

    /**
     * Get the count of active subscribers on this plan.
     */
    public function getSubscribersCountAttribute(): int
    {
        return $this->subscriptions()
            ->where('status', 'active')
            ->count();
    }

    /**
     * Get the count of all subscribers (including trial/cancelled).
     */
    public function getTotalSubscribersCountAttribute(): int
    {
        return $this->subscriptions()->count();
    }

    /**
     * Get the count of subscribers currently in trial period.
     */
    public function getTrialCountAttribute(): int
    {
        return $this->subscriptions()
            ->where('status', 'trialing')
            ->count();
    }

    /**
     * Get the count of cancelled subscriptions.
     */
    public function getCancelledCountAttribute(): int
    {
        return $this->subscriptions()
            ->where('status', 'cancelled')
            ->count();
    }

    /**
     * Get the Monthly Recurring Revenue (MRR) for this plan.
     * Calculated from all active subscriptions paying monthly.
     */
    public function getMrrAttribute(): string
    {
        $activeSubscriptions = $this->subscriptions()
            ->where('status', 'active')
            ->get();

        // Calculate MRR: monthly subscribers pay monthly_price, yearly pay yearly_price/12
        $mrr = 0;
        foreach ($activeSubscriptions as $subscription) {
            if ($subscription->billing_period === 'yearly') {
                $mrr += (float) $this->yearly_price / 12;
            } else {
                $mrr += (float) $this->monthly_price;
            }
        }

        return number_format($mrr, 2, '.', '');
    }

    /**
     * Get the Annual Recurring Revenue (ARR) for this plan.
     */
    public function getArrAttribute(): string
    {
        return number_format((float) $this->mrr * 12, 2, '.', '');
    }

    /**
     * Get the count of features included in this plan.
     */
    public function getFeaturesCountAttribute(): int
    {
        $features = $this->features;

        if (is_string($features)) {
            $features = json_decode($features, true);
        }

        return is_array($features) ? count($features) : 0;
    }

    /**
     * Plans carry no modules — plans and products are SEPARATE subscriptions,
     * and modules are carried by products (products.module_code). Kept so
     * legacy serializers keep a stable shape.
     */
    public function getModulesCountAttribute(): int
    {
        return 0;
    }

    /**
     * Get canonical quotas projected from normalized rows or legacy limits.
     *
     * @return array<int, array{key: string, value: mixed, unit: string|null, metadata: array<string, mixed>}>
     */
    public function getCanonicalQuotasAttribute(): array
    {
        $rows = $this->relationLoaded('planQuotas')
            ? $this->planQuotas
            : $this->planQuotas()->get();

        if ($rows->isNotEmpty()) {
            return $rows->map(function (PlanQuota $quota) {
                return [
                    'key' => (string) $quota->key,
                    'value' => $quota->value,
                    'unit' => $quota->unit,
                    'metadata' => is_array($quota->metadata) ? $quota->metadata : [],
                ];
            })->values()->all();
        }

        $limits = is_array($this->limits) ? $this->limits : [];

        if (! array_key_exists('max_users', $limits) && $this->max_users !== null) {
            $limits['max_users'] = $this->max_users;
        }
        if (! array_key_exists('max_storage_gb', $limits) && $this->max_storage_gb !== null) {
            $limits['max_storage_gb'] = $this->max_storage_gb;
        }

        $quotas = [];
        foreach ($limits as $key => $value) {
            if (! is_scalar($value) && $value !== null) {
                continue;
            }

            $quotas[] = [
                'key' => (string) $key,
                'value' => $value,
                'unit' => str_ends_with((string) $key, '_gb') ? 'gb' : 'count',
                'metadata' => [],
            ];
        }

        return $quotas;
    }

    /**
     * Get the formatted monthly price with currency.
     */
    public function getFormattedMonthlyPriceAttribute(): string
    {
        $currency = $this->currency ?? 'USD';
        $symbol = $this->getCurrencySymbol($currency);

        return $symbol.number_format((float) $this->monthly_price, 2);
    }

    /**
     * Get the formatted yearly price with currency.
     */
    public function getFormattedYearlyPriceAttribute(): string
    {
        $currency = $this->currency ?? 'USD';
        $symbol = $this->getCurrencySymbol($currency);

        return $symbol.number_format((float) $this->yearly_price, 2);
    }

    /**
     * Get the yearly savings compared to monthly billing.
     */
    public function getYearlySavingsAttribute(): string
    {
        $monthlyTotal = (float) $this->monthly_price * 12;
        $yearlyTotal = (float) $this->yearly_price;

        return number_format($monthlyTotal - $yearlyTotal, 2, '.', '');
    }

    /**
     * Get the yearly savings percentage.
     */
    public function getYearlySavingsPercentAttribute(): int
    {
        $monthlyTotal = (float) $this->monthly_price * 12;
        $yearlyTotal = (float) $this->yearly_price;

        if ($monthlyTotal === 0.0) {
            return 0;
        }

        return (int) round((($monthlyTotal - $yearlyTotal) / $monthlyTotal) * 100);
    }

    /**
     * Check if this plan is a free plan.
     */
    public function getIsFreeAttribute(): bool
    {
        return (float) $this->monthly_price === 0.0;
    }

    /**
     * Get currency symbol for a currency code.
     */
    protected function getCurrencySymbol(string $currency): string
    {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'BDT' => '৳',
            'JPY' => '¥',
            'INR' => '₹',
            'AUD' => 'A$',
            'CAD' => 'C$',
        ];

        return $symbols[$currency] ?? $currency.' ';
    }

    /**
     * Define the attributes that should be appended to model arrays/JSON.
     */
    protected $appends = [
        'subscribers_count',
        'features_count',
        'modules_count',
        'formatted_monthly_price',
        'formatted_yearly_price',
        'is_free',
    ];
}
