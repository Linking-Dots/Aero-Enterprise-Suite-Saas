<?php

namespace Aero\Ims\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'ims_suppliers';

    protected $fillable = [
        'name',
        'code',
        'contact_person',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'tax_id',
        'payment_terms',
        'credit_limit',
        'status',
        'rating',
        'notes',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'rating' => 'decimal:1',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the products supplied by this supplier
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get the purchase orders for this supplier
     */
    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    /**
     * Scope for active suppliers
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Get total orders value for this supplier
     */
    public function getTotalOrdersValueAttribute()
    {
        return $this->purchaseOrders()->sum('total_amount');
    }

    /**
     * Get total orders count for this supplier
     */
    public function getTotalOrdersCountAttribute()
    {
        return $this->purchaseOrders()->count();
    }
}
