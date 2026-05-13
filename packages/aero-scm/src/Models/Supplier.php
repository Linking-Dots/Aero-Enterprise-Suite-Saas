<?php

namespace Aero\Scm\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'contact_person',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'zip',
        'country',
        'tax_id',
        'website',
        'payment_terms',
        'credit_limit',
        'status',
        'category_id',
        'rating',
        'notes',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'rating' => 'integer',
    ];

    // Relationships
    public function category()
    {
        return $this->belongsTo(SupplierCategory::class, 'category_id');
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function products()
    {
        return $this->hasMany(InventoryItem::class, 'manufacturer_id');
    }

    public function contacts()
    {
        return $this->hasMany(SupplierContact::class);
    }

    public function payments()
    {
        return $this->hasMany(Transaction::class, 'vendor_id');
    }

    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable');
    }
}
