<?php

namespace Aero\Crm\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Model;

class DealProduct extends TenantModel
{
    protected $fillable = [
        'deal_id',
        'product_id',
        'name',
        'sku',
        'description',
        'unit_price',
        'quantity',
        'discount_percent',
        'discount_amount',
        'tax_percent',
        'total',
        'billing_type',
        'billing_cycles',
        'billing_start_date',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'quantity' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_percent' => 'decimal:2',
        'total' => 'decimal:2',
        'billing_cycles' => 'integer',
        'billing_start_date' => 'date',
    ];

    /**
     * Billing types
     */
    const BILLING_ONE_TIME = 'one_time';

    const BILLING_MONTHLY = 'monthly';

    const BILLING_QUARTERLY = 'quarterly';

    const BILLING_YEARLY = 'yearly';

    const BILLING_CUSTOM = 'custom';

    /**
     * Get the deal
     */
    public function deal()
    {
        return $this->belongsTo(Deal::class);
    }

    /**
     * Calculate total automatically
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($product) {
            $subtotal = $product->unit_price * $product->quantity;
            $discount = $product->discount_amount ?: ($subtotal * ($product->discount_percent / 100));
            $afterDiscount = $subtotal - $discount;
            $tax = $afterDiscount * ($product->tax_percent / 100);
            $product->total = $afterDiscount + $tax;
        });
    }
}
