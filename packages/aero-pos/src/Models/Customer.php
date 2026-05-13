<?php

namespace Aero\Pos\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'pos_customers';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'date_of_birth',
        'gender',
        'loyalty_points',
        'customer_group_id',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'loyalty_points' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the transactions for the customer
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'customer_id');
    }

    /**
     * Get the customer group
     */
    public function customerGroup()
    {
        return $this->belongsTo(CustomerGroup::class, 'customer_group_id');
    }

    /**
     * Get total purchases amount
     */
    public function getTotalPurchasesAttribute()
    {
        return $this->transactions()->sum('total_amount');
    }

    /**
     * Get total transactions count
     */
    public function getTotalTransactionsAttribute()
    {
        return $this->transactions()->count();
    }

    /**
     * Get last purchase date
     */
    public function getLastPurchaseAttribute()
    {
        return $this->transactions()->latest()->value('created_at');
    }

    /**
     * Scope for active customers
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for customers with recent purchases
     */
    public function scopeRecentCustomers($query, $days = 30)
    {
        return $query->whereHas('transactions', function ($query) use ($days) {
            $query->where('created_at', '>=', now()->subDays($days));
        });
    }
}
