<?php

namespace Aero\Finance\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerGroup extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'finance_customer_groups';

    protected $fillable = [
        'name', 'description', 'discount_percentage',
        'credit_limit', 'payment_terms', 'is_active',
    ];

    protected $casts = [
        'discount_percentage' => 'decimal:2',
        'credit_limit' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }
}
