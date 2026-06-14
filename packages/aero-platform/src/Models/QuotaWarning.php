<?php

namespace Aero\Platform\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotaWarning extends CentralModel
{
    protected $fillable = [
        'tenant_id',
        'quota_type',
        'percentage',
        'threshold_type', // 'warning', 'critical', 'block'
        'first_warned_at',
        'last_warned_at',
        'warning_count',
        'is_dismissed',
        'dismissed_at',
        'dismissed_by_user_id',
    ];

    protected $casts = [
        'percentage' => 'float',
        'warning_count' => 'integer',
        'is_dismissed' => 'boolean',
        'first_warned_at' => 'datetime',
        'last_warned_at' => 'datetime',
        'dismissed_at' => 'datetime',
    ];

    /**
     * Get the tenant that owns the warning.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the user who dismissed the warning.
     */
    public function dismissedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dismissed_by_user_id');
    }

    /**
     * Record a new warning or update existing.
     */
    public static function recordWarning(
        string $tenantId,
        string $quotaType,
        float $percentage,
        string $thresholdType
    ): self {
        $warning = static::firstOrNew([
            'tenant_id' => $tenantId,
            'quota_type' => $quotaType,
            'is_dismissed' => false,
        ]);

        if (! $warning->exists) {
            $warning->first_warned_at = now();
            $warning->warning_count = 0;
        }

        $warning->percentage = $percentage;
        $warning->threshold_type = $thresholdType;
        $warning->last_warned_at = now();
        $warning->warning_count++;
        $warning->save();

        return $warning;
    }

    /**
     * Dismiss the warning.
     */
    public function dismiss(?int $userId = null): self
    {
        $this->update([
            'is_dismissed' => true,
            'dismissed_at' => now(),
            'dismissed_by_user_id' => $userId,
        ]);

        return $this;
    }

    /**
     * Check if warning is in grace period.
     */
    public function isInGracePeriod(int $gracePeriodDays = 10): bool
    {
        if (! $this->first_warned_at) {
            return false;
        }

        return $this->first_warned_at->diffInDays(now()) < $gracePeriodDays;
    }

    /**
     * Scope to get active (not dismissed) warnings.
     */
    public function scopeActive($query)
    {
        return $query->where('is_dismissed', false);
    }

    /**
     * Scope to get warnings by tenant.
     */
    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope to get warnings by quota type.
     */
    public function scopeForQuotaType($query, string $quotaType)
    {
        return $query->where('quota_type', $quotaType);
    }
}
