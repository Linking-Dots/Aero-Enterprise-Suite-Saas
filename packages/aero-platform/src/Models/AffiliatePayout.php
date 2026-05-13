<?php

declare(strict_types=1);

namespace Aero\Platform\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Affiliate Payout Model
 *
 * Tracks commission payouts to affiliates.
 */
class AffiliatePayout extends CentralModel
{
    use HasFactory;

    protected $connection = 'central';

    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'affiliate_id',
        'amount',
        'currency',
        'status',
        'payout_method',
        'payout_details',
        'transaction_reference',
        'notes',
        'processed_at',
        'completed_at',
    ];

    protected $casts = [
        'payout_details' => 'array',
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => self::STATUS_PENDING,
        'currency' => 'USD',
        'payout_details' => '[]',
    ];

    protected $hidden = [
        'payout_details',
    ];

    /**
     * Get the affiliate.
     */
    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    /**
     * Scope for pending payouts.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for completed payouts.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Mark as processing.
     */
    public function markAsProcessing(): bool
    {
        return $this->update([
            'status' => self::STATUS_PROCESSING,
            'processed_at' => now(),
        ]);
    }

    /**
     * Mark as completed.
     */
    public function markAsCompleted(?string $transactionReference = null): bool
    {
        $updated = $this->update([
            'status' => self::STATUS_COMPLETED,
            'transaction_reference' => $transactionReference,
            'completed_at' => now(),
        ]);

        if ($updated) {
            $affiliate = $this->affiliate;
            $affiliate->update([
                'pending_earnings' => max(0, $affiliate->pending_earnings - $this->amount),
                'paid_earnings' => $affiliate->paid_earnings + $this->amount,
            ]);

            // Mark related referral commissions as paid
            AffiliateReferral::where('affiliate_id', $this->affiliate_id)
                ->where('commission_status', AffiliateReferral::COMMISSION_APPROVED)
                ->update([
                    'commission_status' => AffiliateReferral::COMMISSION_PAID,
                    'commission_paid_at' => now(),
                ]);
        }

        return $updated;
    }

    /**
     * Mark as failed.
     */
    public function markAsFailed(?string $reason = null): bool
    {
        return $this->update([
            'status' => self::STATUS_FAILED,
            'notes' => $reason,
        ]);
    }

    /**
     * Create a payout for an affiliate.
     */
    public static function createForAffiliate(Affiliate $affiliate, ?float $amount = null): self
    {
        $payoutAmount = $amount ?? $affiliate->pending_earnings;

        return static::create([
            'affiliate_id' => $affiliate->id,
            'amount' => $payoutAmount,
            'payout_method' => $affiliate->payout_method,
            'payout_details' => $affiliate->payout_details,
        ]);
    }
}
