<?php

namespace Aero\Commerce\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends TenantModel
{
    use HasFactory;

    protected $table = 'commerce_order_items';

    protected $fillable = [
        'order_id', 'product_id', 'product_variant_id', 'sku', 'name',
        'quantity', 'unit_price', 'discount_amount', 'tax_amount',
        'line_total', 'options', 'vendor_id',
    ];

    protected $casts = [
        'order_id' => 'integer',
        'product_id' => 'integer',
        'product_variant_id' => 'integer',
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
        'options' => 'json',
        'vendor_id' => 'integer',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function getSubtotalAttribute()
    {
        return $this->quantity * $this->unit_price;
    }
}
