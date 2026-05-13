<?php

namespace Aero\Commerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderCoupon extends TenantModel
{
    use HasFactory;

    protected $table = 'commerce_order_coupons';

    protected $fillable = [
        'order_id', 'coupon_id', 'coupon_code', 'discount_amount', 'applied_at',
    ];

    protected $casts = [
        'order_id' => 'integer',
        'coupon_id' => 'integer',
        'discount_amount' => 'decimal:2',
        'applied_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function getDiscountPercentageAttribute()
    {
        if ($this->order && $this->order->subtotal > 0) {
            return round(($this->discount_amount / $this->order->subtotal) * 100, 2);
        }

        return 0;
    }
}
