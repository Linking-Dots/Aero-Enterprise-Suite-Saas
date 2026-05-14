<?php

namespace Aero\Finance\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'finance_customers';

    protected $fillable = [
        'customer_code', 'name', 'email', 'phone', 'website',
        'billing_address', 'shipping_address', 'tax_number',
        'credit_limit', 'payment_terms', 'currency',
        'customer_group_id', 'sales_rep_id', 'is_active', 'notes',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'customer_group_id' => 'integer',
        'sales_rep_id' => 'integer',
        'is_active' => 'boolean',
    ];

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function customerGroup()
    {
        return $this->belongsTo(CustomerGroup::class);
    }

    public function salesRep()
    {
        return $this->belongsTo(User::class, 'sales_rep_id');
    }

    public function getTotalOutstandingAttribute()
    {
        return $this->invoices()->where('status', '!=', 'paid')->sum('balance_amount');
    }
}
