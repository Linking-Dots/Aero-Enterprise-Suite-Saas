<?php

namespace Aero\Commerce\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerWishlist extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'commerce_customer_wishlists';

    protected $fillable = [
        'customer_id', 'name', 'description', 'is_public', 'is_default',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'is_public' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(WishlistItem::class, 'wishlist_id');
    }

    public function getTotalItemsAttribute()
    {
        return $this->items()->count();
    }

    public function getTotalValueAttribute()
    {
        return $this->items()->join('commerce_products', 'commerce_wishlist_items.product_id', '=', 'commerce_products.id')
            ->sum('commerce_products.price');
    }

    public function hasProduct($productId)
    {
        return $this->items()->where('product_id', $productId)->exists();
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}
