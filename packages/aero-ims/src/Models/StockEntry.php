<?php

namespace Aero\Ims\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockEntry extends TenantModel
{
    use HasFactory;

    protected $table = 'ims_stock_entries';

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'quantity',
        'reserved_quantity',
        'location',
        'batch_number',
        'expiry_date',
        'cost_price',
        'notes',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'warehouse_id' => 'integer',
        'quantity' => 'integer',
        'reserved_quantity' => 'integer',
        'cost_price' => 'decimal:2',
        'expiry_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the product this stock entry belongs to
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the warehouse this stock entry belongs to
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get available quantity (total - reserved)
     */
    public function getAvailableQuantityAttribute()
    {
        return $this->quantity - $this->reserved_quantity;
    }

    /**
     * Get total value of this stock entry
     */
    public function getTotalValueAttribute()
    {
        return $this->quantity * $this->cost_price;
    }

    /**
     * Scope for products with available stock
     */
    public function scopeAvailable($query)
    {
        return $query->where('quantity', '>', 0);
    }

    /**
     * Scope for expired stock entries
     */
    public function scopeExpired($query)
    {
        return $query->where('expiry_date', '<', now());
    }

    /**
     * Scope for stock entries expiring soon
     */
    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->where('expiry_date', '<=', now()->addDays($days))
            ->where('expiry_date', '>', now());
    }
}
