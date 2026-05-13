<?php

declare(strict_types=1);

namespace Aero\Platform\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Affiliate Referral Model
 *
 * Tracks individual referrals from affiliates.
 */
class AffiliateReferral extends CentralModel
{
    use HasFactory;

    protected $connection = 'central';

    public const STATUS_CLICKED = 'clicked';

    public const STATUS_REGISTERED = 'registered';

    public const STATUS_CONVERTED = 'converted';

    public const STATUS_REFUNDED = 'refunded';

    public const COMMISSION_PENDING = 'pending';

    public const COMMISSION_APPROVED = 'approved';

    public const COMMISSION_PAID = 'paid';

    public const COMMISSION_CANCELLED = 'cancelled';

    protected $fillable = [
        'affiliate_id',
        'visitor_id',
        'ip_address',
        'user_agent',
        'referrer_url',
        'landing_page',
        'utm_data',
        'status',
        'tenant_id',
        'tenant_email',
        'transaction_amount',
        'commission_amount',
        'commission_status',
        'registered_at',
        'converted_at',
        'commission_paid_at',
    ];

    protected $casts = [
        'utm_data' => 'array',
        'transaction_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'registered_at' => 'datetime',
        'converted_at' => 'datetime',
        'commission_paid_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => self::STATUS_CLICKED,
        'commission_status' => self::COMMISSION_PENDING,
        'utm_data' => '[]',
    ];

    /**
     * Get the affiliate.
     */
    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    /**
     * Get the tenant.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scope for converted referrals.
     */
    public function scopeConverted($query)
    {
        return $query->where('status', self::STATUS_CONVERTED);
    }

    /**
     * Scope for pending commissions.
     */
    public function scopePendingCommission($query)
    {
        return $query->where('commission_status', self::COMMISSION_PENDING);
    }

    /**
     * Mark as registered.
     */
    public function markAsRegistered(string $email): bool
    {
        return $this->update([
            'status' => self::STATUS_REGISTERED,
            'tenant_email' => $email,
            'registered_at' => now(),
        ]);
    }

    /**
     * Mark as converted (subscription paid).
     */
    public function markAsConverted(int $tenantId, float $transactionAmount): bool
    {
        $affiliate = $this->affiliate;
        $commissionAmount = $affiliate->calculateCommission($transactionAmount);

        $updated = $this->update([
            'status' => self::STATUS_CONVERTED,
            'tenant_id' => $tenantId,
            'transaction_amount' => $transactionAmount,
            'commission_amount' => $commissionAmount,
            'converted_at' => now(),
        ]);

        if ($updated) {
            $affiliate->increment('successful_referrals');
            $affiliate->addPendingEarnings($commissionAmount);
        }

        return $updated;
    }

    /**
     * Approve commission.
     */
    public function approveCommission(): bool
    {
        return $this->update([
            'commission_status' => self::COMMISSION_APPROVED,
        ]);
    }

    /**
     * Mark commission as paid.
     */
    public function markCommissionPaid(): bool
    {
        return $this->update([
            'commission_status' => self::COMMISSION_PAID,
            'commission_paid_at' => now(),
        ]);
    }

    /**
     * Cancel commission (e.g., refund).
     */
    public function cancelCommission(): bool
    {
        $updated = $this->update([
            'status' => self::STATUS_REFUNDED,
            'commission_status' => self::COMMISSION_CANCELLED,
        ]);

        if ($updated && $this->commission_amount) {
            $this->affiliate->decrement('pending_earnings', $this->commission_amount);
            $this->affiliate->decrement('total_earnings', $this->commission_amount);
        }

        return $updated;
    }
}
