<?php

namespace Aero\Pos\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerGroup extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'pos_customer_groups';

    protected $fillable = [
        'name',
        'description',
        'discount_percentage',
        'is_active',
    ];

    protected $casts = [
        'discount_percentage' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get the customers for the group
     */
    public function customers()
    {
        return $this->hasMany(Customer::class, 'customer_group_id');
    }

    /**
     * Scope for active groups
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
