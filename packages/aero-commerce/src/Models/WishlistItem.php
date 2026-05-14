<?php

namespace Aero\Commerce\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WishlistItem extends TenantModel
{
    use HasFactory;

    protected $table = 'commerce_wishlist_items';

    protected $fillable = [
        'wishlist_id', 'product_id', 'product_variant_id', 'quantity',
        'added_price', 'priority', 'notes',
    ];

    protected $casts = [
        'wishlist_id' => 'integer',
        'product_id' => 'integer',
        'product_variant_id' => 'integer',
        'quantity' => 'integer',
        'added_price' => 'decimal:2',
        'priority' => 'integer',
    ];

    const PRIORITY_LOW = 1;

    const PRIORITY_MEDIUM = 2;

    const PRIORITY_HIGH = 3;

    public function wishlist()
    {
        return $this->belongsTo(CustomerWishlist::class, 'wishlist_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function getCurrentPriceAttribute()
    {
        if ($this->product_variant_id) {
            return $this->productVariant?->price ?? $this->product?->price;
        }

        return $this->product?->price;
    }

    public function getPriceChangeAttribute()
    {
        if (! $this->added_price || ! $this->current_price) {
            return 0;
        }

        return $this->current_price - $this->added_price;
    }

    public function getPriceChangePercentageAttribute()
    {
        if (! $this->added_price || $this->added_price == 0) {
            return 0;
        }

        return round((($this->current_price - $this->added_price) / $this->added_price) * 100, 2);
    }

    public function isOnSale()
    {
        return $this->current_price < $this->added_price;
    }

    public function isAvailable()
    {
        if ($this->product_variant_id) {
            return $this->productVariant?->is_active && $this->productVariant?->stock_quantity > 0;
        }

        return $this->product?->is_active && $this->product?->stock_quantity > 0;
    }

    public function scopeHighPriority($query)
    {
        return $query->where('priority', self::PRIORITY_HIGH);
    }

    public function scopeAvailable($query)
    {
        return $query->whereHas('product', function ($q) {
            $q->where('is_active', true)
                ->where('stock_quantity', '>', 0);
        });
    }
}
