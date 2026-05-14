<?php

namespace Aero\Commerce\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VendorPayout extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'commerce_vendor_payouts';

    protected $fillable = [
        'vendor_id', 'payout_period_start', 'payout_period_end', 'total_commission',
        'platform_fees', 'payout_amount', 'currency', 'payment_method',
        'payment_reference', 'status', 'processed_by', 'processed_at', 'notes',
    ];

    protected $casts = [
        'vendor_id' => 'integer',
        'payout_period_start' => 'date',
        'payout_period_end' => 'date',
        'total_commission' => 'decimal:2',
        'platform_fees' => 'decimal:2',
        'payout_amount' => 'decimal:2',
        'processed_by' => 'integer',
        'processed_at' => 'datetime',
    ];

    const STATUS_DRAFT = 'draft';

    const STATUS_PENDING = 'pending';

    const STATUS_PROCESSING = 'processing';

    const STATUS_COMPLETED = 'completed';

    const STATUS_FAILED = 'failed';

    const STATUS_CANCELLED = 'cancelled';

    const METHOD_BANK_TRANSFER = 'bank_transfer';

    const METHOD_PAYPAL = 'paypal';

    const METHOD_CHECK = 'check';

    const METHOD_WIRE = 'wire';

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function commissions()
    {
        return $this->hasMany(VendorCommission::class);
    }

    public function getNetPayoutAttribute()
    {
        return $this->total_commission - $this->platform_fees;
    }

    public function getPlatformFeeRateAttribute()
    {
        if ($this->total_commission > 0) {
            return round(($this->platform_fees / $this->total_commission) * 100, 2);
        }

        return 0;
    }

    public function isCompleted()
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function canBeProcessed()
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_PENDING]);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('payout_period_start', [$startDate, $endDate])
            ->orWhereBetween('payout_period_end', [$startDate, $endDate]);
    }
}
