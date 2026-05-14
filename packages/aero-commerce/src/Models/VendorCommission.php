<?php

namespace Aero\Commerce\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorCommission extends TenantModel
{
    use HasFactory;

    protected $table = 'commerce_vendor_commissions';

    protected $fillable = [
        'vendor_id', 'order_id', 'order_item_id', 'commission_rate', 'commission_amount',
        'commission_type', 'status', 'processed_at', 'notes',
    ];

    protected $casts = [
        'vendor_id' => 'integer',
        'order_id' => 'integer',
        'order_item_id' => 'integer',
        'commission_rate' => 'decimal:4',
        'commission_amount' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    const TYPE_PERCENTAGE = 'percentage';

    const TYPE_FIXED_AMOUNT = 'fixed_amount';

    const TYPE_TIERED = 'tiered';

    const STATUS_PENDING = 'pending';

    const STATUS_APPROVED = 'approved';

    const STATUS_PAID = 'paid';

    const STATUS_DISPUTED = 'disputed';

    const STATUS_CANCELLED = 'cancelled';

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function payout()
    {
        return $this->belongsTo(VendorPayout::class, 'vendor_payout_id');
    }

    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved()
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isPaid()
    {
        return $this->status === self::STATUS_PAID;
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }
}
