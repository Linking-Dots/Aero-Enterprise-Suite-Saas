<?php

namespace Aero\Pos\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'pos_transactions';

    protected $fillable = [
        'transaction_number',
        'customer_id',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'payment_method',
        'payment_status',
        'status',
        'notes',
        'processed_by',
        'processed_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    /**
     * Get the customer that owns the transaction
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Get the transaction items for the transaction
     */
    public function items()
    {
        return $this->hasMany(TransactionItem::class, 'transaction_id');
    }

    /**
     * Get the user who processed the transaction
     */
    public function processedBy()
    {
        return $this->belongsTo(\Aero\Core\Models\User::class, 'processed_by');
    }

    /**
     * Scope for completed transactions
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for pending transactions
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for today's transactions
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope for this month's transactions
     */
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);
    }
}
