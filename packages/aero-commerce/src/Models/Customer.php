<?php

namespace Aero\Commerce\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'commerce_customers';

    protected $fillable = [
        'customer_number', 'user_id', 'first_name', 'last_name', 'email',
        'phone', 'date_of_birth', 'gender', 'customer_group_id',
        'is_active', 'email_verified_at', 'phone_verified_at',
        'last_login_at', 'registration_source', 'notes',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'date_of_birth' => 'date',
        'customer_group_id' => 'integer',
        'is_active' => 'boolean',
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
    ];

    const GENDER_MALE = 'male';

    const GENDER_FEMALE = 'female';

    const GENDER_OTHER = 'other';

    const GENDER_PREFER_NOT_TO_SAY = 'prefer_not_to_say';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customerGroup()
    {
        return $this->belongsTo(CustomerGroup::class);
    }

    public function addresses()
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function carts()
    {
        return $this->hasMany(ShoppingCart::class);
    }

    public function wishlistItems()
    {
        return $this->hasMany(WishlistItem::class);
    }

    public function reviews()
    {
        return $this->hasMany(ProductReview::class);
    }

    public function getFullNameAttribute()
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function getTotalOrdersAttribute()
    {
        return $this->orders()->count();
    }

    public function getTotalSpentAttribute()
    {
        return $this->orders()
            ->whereIn('order_status', [Order::ORDER_STATUS_DELIVERED, Order::ORDER_STATUS_SHIPPED])
            ->sum('total_amount');
    }

    public function getDefaultBillingAddressAttribute()
    {
        return $this->addresses()->where('type', CustomerAddress::TYPE_BILLING)
            ->where('is_default', true)->first();
    }

    public function getDefaultShippingAddressAttribute()
    {
        return $this->addresses()->where('type', CustomerAddress::TYPE_SHIPPING)
            ->where('is_default', true)->first();
    }
}
