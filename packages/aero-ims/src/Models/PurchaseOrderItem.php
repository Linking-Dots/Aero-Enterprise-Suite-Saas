<?php

namespace Aero\Ims\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends TenantModel
{
    use HasFactory;

    protected $table = 'ims_purchase_order_items';

    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'quantity_ordered',
        'quantity_received',
        'unit_price',
        'total_price',
        'notes',
    ];

    protected $casts = [
        'purchase_order_id' => 'integer',
        'product_id' => 'integer',
        'quantity_ordered' => 'integer',
        'quantity_received' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the purchase order this item belongs to
     */
    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * Get the product this item refers to
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get pending quantity (ordered - received)
     */
    public function getPendingQuantityAttribute()
    {
        return $this->quantity_ordered - $this->quantity_received;
    }

    /**
     * Check if item is fully received
     */
    public function getIsFullyReceivedAttribute()
    {
        return $this->quantity_received >= $this->quantity_ordered;
    }

    /**
     * Check if item is partially received
     */
    public function getIsPartiallyReceivedAttribute()
    {
        return $this->quantity_received > 0 && $this->quantity_received < $this->quantity_ordered;
    }

    /**
     * Scope for fully received items
     */
    public function scopeFullyReceived($query)
    {
        return $query->whereColumn('quantity_received', '>=', 'quantity_ordered');
    }

    /**
     * Scope for pending items
     */
    public function scopePending($query)
    {
        return $query->whereColumn('quantity_received', '<', 'quantity_ordered');
    }
}
