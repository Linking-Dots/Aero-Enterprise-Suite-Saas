<?php

namespace Aero\Commerce\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coupon extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'commerce_coupons';

    protected $fillable = [
        'code', 'name', 'description', 'type', 'value', 'minimum_amount',
        'maximum_discount', 'usage_limit', 'usage_limit_per_customer',
        'used_count', 'valid_from', 'valid_until', 'applicable_products',
        'applicable_categories', 'is_active', 'created_by',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'minimum_amount' => 'decimal:2',
        'maximum_discount' => 'decimal:2',
        'usage_limit' => 'integer',
        'usage_limit_per_customer' => 'integer',
        'used_count' => 'integer',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'applicable_products' => 'json',
        'applicable_categories' => 'json',
        'is_active' => 'boolean',
        'created_by' => 'integer',
    ];

    const TYPE_PERCENTAGE = 'percentage';

    const TYPE_FIXED_AMOUNT = 'fixed_amount';

    const TYPE_FREE_SHIPPING = 'free_shipping';

    const TYPE_BUY_X_GET_Y = 'buy_x_get_y';

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function orderCoupons()
    {
        return $this->hasMany(OrderCoupon::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'commerce_coupon_products');
    }

    public function categories()
    {
        return $this->belongsToMany(ProductCategory::class, 'commerce_coupon_categories');
    }

    public function isValid($orderAmount = null, $customerId = null)
    {
        // Check if active
        if (! $this->is_active) {
            return false;
        }

        // Check date range
        $now = now();
        if ($this->valid_from && $now->isBefore($this->valid_from)) {
            return false;
        }
        if ($this->valid_until && $now->isAfter($this->valid_until)) {
            return false;
        }

        // Check usage limit
        if ($this->usage_limit && $this->used_count >= $this->usage_limit) {
            return false;
        }

        // Check minimum amount
        if ($orderAmount && $this->minimum_amount && $orderAmount < $this->minimum_amount) {
            return false;
        }

        // Check per-customer usage limit
        if ($customerId && $this->usage_limit_per_customer) {
            $customerUsage = $this->orderCoupons()
                ->whereHas('order', function ($query) use ($customerId) {
                    $query->where('customer_id', $customerId);
                })
                ->count();

            if ($customerUsage >= $this->usage_limit_per_customer) {
                return false;
            }
        }

        return true;
    }

    public function calculateDiscount($orderAmount)
    {
        switch ($this->type) {
            case self::TYPE_PERCENTAGE:
                $discount = ($orderAmount * $this->value) / 100;
                break;
            case self::TYPE_FIXED_AMOUNT:
                $discount = $this->value;
                break;
            default:
                $discount = 0;
        }

        // Apply maximum discount limit
        if ($this->maximum_discount && $discount > $this->maximum_discount) {
            $discount = $this->maximum_discount;
        }

        return min($discount, $orderAmount);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeValid($query)
    {
        $now = now();

        return $query->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('valid_from')
                    ->orWhere('valid_from', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', $now);
            });
    }
}
