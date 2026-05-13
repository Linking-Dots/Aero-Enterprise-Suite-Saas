<?php

declare(strict_types=1);

namespace Aero\Platform\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Affiliate Model
 *
 * Represents referral partners who earn commissions for bringing new tenants.
 */
class Affiliate extends CentralModel
{
    use HasFactory;
    use SoftDeletes;

    protected $connection = 'central';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_REJECTED = 'rejected';

    public const COMMISSION_PERCENTAGE = 'percentage';

    public const COMMISSION_FIXED = 'fixed';

    public const PAYOUT_BANK_TRANSFER = 'bank_transfer';

    public const PAYOUT_PAYPAL = 'paypal';

    public const PAYOUT_STRIPE = 'stripe';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'company_name',
        'website',
        'referral_code',
        'status',
        'commission_rate',
        'commission_type',
        'fixed_commission',
        'cookie_days',
        'payout_method',
        'payout_details',
        'minimum_payout',
        'total_earnings',
        'pending_earnings',
        'paid_earnings',
        'total_referrals',
        'successful_referrals',
        'metadata',
        'notes',
        'approved_at',
        'last_referral_at',
    ];

    protected $casts = [
        'payout_details' => 'array',
        'metadata' => 'array',
        'commission_rate' => 'decimal:2',
        'fixed_commission' => 'decimal:2',
        'minimum_payout' => 'decimal:2',
        'total_earnings' => 'decimal:2',
        'pending_earnings' => 'decimal:2',
        'paid_earnings' => 'decimal:2',
        'cookie_days' => 'integer',
        'total_referrals' => 'integer',
        'successful_referrals' => 'integer',
        'approved_at' => 'datetime',
        'last_referral_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => self::STATUS_PENDING,
        'commission_type' => self::COMMISSION_PERCENTAGE,
        'commission_rate' => 10.00,
        'cookie_days' => 30,
        'minimum_payout' => 50.00,
        'total_earnings' => 0,
        'pending_earnings' => 0,
        'paid_earnings' => 0,
        'total_referrals' => 0,
        'successful_referrals' => 0,
        'payout_details' => '[]',
        'metadata' => '[]',
    ];

    protected $hidden = [
        'payout_details',
    ];

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::creating(function (self $affiliate) {
            if (empty($affiliate->referral_code)) {
                $affiliate->referral_code = self::generateUniqueReferralCode();
            }
        });
    }

    /**
     * Generate a unique referral code.
     */
    public static function generateUniqueReferralCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (self::where('referral_code', $code)->exists());

        return $code;
    }

    /**
     * Get all referrals.
     */
    public function referrals(): HasMany
    {
        return $this->hasMany(AffiliateReferral::class);
    }

    /**
     * Get all payouts.
     */
    public function payouts(): HasMany
    {
        return $this->hasMany(AffiliatePayout::class);
    }

    /**
     * Scope for approved affiliates.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope for pending affiliates.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for active affiliates (approved, not suspended).
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Check if affiliate is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if affiliate is suspended.
     */
    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    /**
     * Approve the affiliate.
     */
    public function approve(): bool
    {
        return $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_at' => now(),
        ]);
    }

    /**
     * Reject the affiliate.
     */
    public function reject(?string $reason = null): bool
    {
        $metadata = $this->metadata ?? [];
        $metadata['rejection_reason'] = $reason;

        return $this->update([
            'status' => self::STATUS_REJECTED,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Suspend the affiliate.
     */
    public function suspend(?string $reason = null): bool
    {
        $metadata = $this->metadata ?? [];
        $metadata['suspension_reason'] = $reason;

        return $this->update([
            'status' => self::STATUS_SUSPENDED,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Reactivate a suspended affiliate.
     */
    public function reactivate(): bool
    {
        return $this->update([
            'status' => self::STATUS_APPROVED,
        ]);
    }

    /**
     * Calculate commission for a transaction amount.
     */
    public function calculateCommission(float $transactionAmount): float
    {
        if ($this->commission_type === self::COMMISSION_FIXED) {
            return $this->fixed_commission ?? 0;
        }

        return round($transactionAmount * ($this->commission_rate / 100), 2);
    }

    /**
     * Record a new referral.
     */
    public function recordReferral(array $data): AffiliateReferral
    {
        $this->increment('total_referrals');
        $this->update(['last_referral_at' => now()]);

        return $this->referrals()->create($data);
    }

    /**
     * Add pending earnings.
     */
    public function addPendingEarnings(float $amount): bool
    {
        return $this->update([
            'pending_earnings' => $this->pending_earnings + $amount,
            'total_earnings' => $this->total_earnings + $amount,
        ]);
    }

    /**
     * Check if eligible for payout.
     */
    public function isEligibleForPayout(): bool
    {
        return $this->isApproved()
            && $this->pending_earnings >= $this->minimum_payout
            && ! empty($this->payout_method)
            && ! empty($this->payout_details);
    }

    /**
     * Get referral URL.
     */
    public function getReferralUrl(): string
    {
        return url("/?ref={$this->referral_code}");
    }

    /**
     * Find affiliate by referral code.
     */
    public static function findByCode(string $code): ?self
    {
        return static::where('referral_code', strtoupper($code))
            ->approved()
            ->first();
    }

    /**
     * Get status options.
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_SUSPENDED => 'Suspended',
            self::STATUS_REJECTED => 'Rejected',
        ];
    }

    /**
     * Get payout method options.
     */
    public static function getPayoutMethodOptions(): array
    {
        return [
            self::PAYOUT_BANK_TRANSFER => 'Bank Transfer',
            self::PAYOUT_PAYPAL => 'PayPal',
            self::PAYOUT_STRIPE => 'Stripe',
        ];
    }
}
