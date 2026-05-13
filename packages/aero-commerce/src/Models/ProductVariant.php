<?php

namespace Aero\Commerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'commerce_product_variants';

    protected $fillable = [
        'product_id', 'sku', 'name', 'price', 'sale_price', 'cost_price',
        'weight', 'dimensions', 'stock_quantity', 'stock_status',
        'is_active', 'image', 'attributes',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'weight' => 'decimal:3',
        'dimensions' => 'json',
        'stock_quantity' => 'integer',
        'is_active' => 'boolean',
        'attributes' => 'json',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getEffectivePriceAttribute()
    {
        return $this->sale_price ?: $this->price;
    }

    public function getDisplayNameAttribute()
    {
        return $this->product->name.' - '.$this->name;
    }

    public function isInStock()
    {
        return $this->stock_status === Product::STOCK_IN_STOCK && $this->stock_quantity > 0;
    }
}
