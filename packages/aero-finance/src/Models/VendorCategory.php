<?php

namespace Aero\Finance\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VendorCategory extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'finance_vendor_categories';

    protected $fillable = [
        'name', 'description', 'default_payment_terms', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function vendors()
    {
        return $this->hasMany(Vendor::class);
    }
}
