<?php

namespace Aero\Commerce\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItem extends TenantModel
{
    use HasFactory;

    protected $table = 'commerce_cart_items';

    protected $fillable = [
        'cart_id', 'product_id', 'product_variant_id', 'quantity',
        'price', 'discount_amount', 'tax_amount', 'options',
    ];

    protected $casts = [
        'cart_id' => 'integer',
        'product_id' => 'integer',
        'product_variant_id' => 'integer',
        'quantity' => 'integer',
        'price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'options' => 'json',
    ];

    public function cart()
    {
        return $this->belongsTo(ShoppingCart::class, 'cart_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function getSubtotalAttribute()
    {
        return $this->quantity * $this->price;
    }

    public function getLineTotalAttribute()
    {
        return $this->subtotal - $this->discount_amount + $this->tax_amount;
    }
}
