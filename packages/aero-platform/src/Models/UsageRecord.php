<?php

namespace Aero\Platform\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class UsageRecord extends CentralModel
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'tenant_id',
        'subscription_id',
        'metric_name',
        'metric_type',
        'quantity',
        'unit',
        'billing_period_start',
        'billing_period_end',
        'attributable_type',
        'attributable_id',
        'metadata',
        'reported_to_stripe',
        'stripe_usage_record_id',
        'reported_at',
    ];

    protected $casts = [
        'quantity' => 'decimal:6',
        'billing_period_start' => 'date',
        'billing_period_end' => 'date',
        'metadata' => 'array',
        'reported_to_stripe' => 'boolean',
        'reported_at' => 'datetime',
    ];

    /**
     * The tenant this usage belongs to.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * The subscription this usage is tied to.
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * The entity that generated this usage (user, module, etc).
     */
    public function attributable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope to filter by metric name.
     */
    public function scopeForMetric($query, string $metricName)
    {
        return $query->where('metric_name', $metricName);
    }

    /**
     * Scope to filter by tenant.
     */
    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope to filter by billing period.
     */
    public function scopeInPeriod($query, $start, $end)
    {
        return $query->where('billing_period_start', '>=', $start)
            ->where('billing_period_end', '<=', $end);
    }

    /**
     * Scope for records not yet reported to Stripe.
     */
    public function scopeUnreported($query)
    {
        return $query->where('reported_to_stripe', false);
    }

    /**
     * Get the current billing period dates.
     *
     * @return array{start: Carbon, end: Carbon}
     */
    public static function getCurrentBillingPeriod(): array
    {
        $now = now();

        return [
            'start' => $now->copy()->startOfMonth(),
            'end' => $now->copy()->endOfMonth(),
        ];
    }
}
