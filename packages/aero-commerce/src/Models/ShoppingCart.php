<?php

namespace Aero\Commerce\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShoppingCart extends TenantModel
{
    use HasFactory;

    protected $table = 'commerce_shopping_carts';

    protected $fillable = [
        'session_id', 'user_id', 'customer_id', 'currency',
        'status', 'abandoned_at', 'converted_at', 'order_id',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'customer_id' => 'integer',
        'abandoned_at' => 'datetime',
        'converted_at' => 'datetime',
        'order_id' => 'integer',
    ];

    const STATUS_ACTIVE = 'active';

    const STATUS_ABANDONED = 'abandoned';

    const STATUS_CONVERTED = 'converted';

    const STATUS_EXPIRED = 'expired';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(CartItem::class, 'cart_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function getTotalItemsAttribute()
    {
        return $this->items->sum('quantity');
    }

    public function getSubtotalAttribute()
    {
        return $this->items->sum(fn ($item) => $item->quantity * $item->price);
    }

    public function getTaxAmountAttribute()
    {
        return $this->items->sum('tax_amount');
    }

    public function getTotalAttribute()
    {
        return $this->subtotal + $this->tax_amount;
    }

    public function isEmpty()
    {
        return $this->items->isEmpty();
    }

    public function isAbandoned()
    {
        return $this->status === self::STATUS_ABANDONED ||
               ($this->status === self::STATUS_ACTIVE && $this->updated_at < now()->subHours(24));
    }
}
