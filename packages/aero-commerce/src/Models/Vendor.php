<?php

namespace Aero\Commerce\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'commerce_vendors';

    protected $fillable = [
        'vendor_code', 'business_name', 'contact_name', 'email', 'phone',
        'website', 'description', 'address', 'city', 'state', 'postal_code',
        'country', 'tax_number', 'commission_rate', 'payment_terms',
        'status', 'user_id', 'approved_at', 'approved_by',
    ];

    protected $casts = [
        'commission_rate' => 'decimal:2',
        'user_id' => 'integer',
        'approved_at' => 'datetime',
        'approved_by' => 'integer',
    ];

    const STATUS_PENDING = 'pending';

    const STATUS_APPROVED = 'approved';

    const STATUS_SUSPENDED = 'suspended';

    const STATUS_REJECTED = 'rejected';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getTotalProductsAttribute()
    {
        return $this->products()->count();
    }

    public function getTotalSalesAttribute()
    {
        return $this->orderItems()->sum('line_total');
    }

    public function isApproved()
    {
        return $this->status === self::STATUS_APPROVED;
    }
}
