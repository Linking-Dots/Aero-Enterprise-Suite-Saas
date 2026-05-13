<?php

namespace Aero\Finance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'finance_vendors';

    protected $fillable = [
        'vendor_code', 'name', 'email', 'phone', 'website',
        'billing_address', 'shipping_address', 'tax_number',
        'payment_terms', 'currency', 'vendor_category_id',
        'contact_person', 'is_active', 'notes', 'bank_details',
    ];

    protected $casts = [
        'vendor_category_id' => 'integer',
        'is_active' => 'boolean',
        'bank_details' => 'json',
    ];

    public function bills()
    {
        return $this->hasMany(Bill::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function vendorCategory()
    {
        return $this->belongsTo(VendorCategory::class);
    }

    public function getTotalOutstandingAttribute()
    {
        return $this->bills()->where('status', '!=', 'paid')->sum('balance_amount');
    }
}
