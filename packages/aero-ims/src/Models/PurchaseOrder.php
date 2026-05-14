<?php

namespace Aero\Ims\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'ims_purchase_orders';

    protected $fillable = [
        'order_number',
        'supplier_id',
        'warehouse_id',
        'order_date',
        'expected_date',
        'received_date',
        'status',
        'subtotal',
        'tax_amount',
        'shipping_cost',
        'total_amount',
        'notes',
        'requested_by',
        'approved_by',
        'received_by',
    ];

    protected $casts = [
        'supplier_id' => 'integer',
        'warehouse_id' => 'integer',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'requested_by' => 'integer',
        'approved_by' => 'integer',
        'received_by' => 'integer',
        'order_date' => 'date',
        'expected_date' => 'date',
        'received_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    const STATUS_DRAFT = 'draft';

    const STATUS_PENDING = 'pending';

    const STATUS_APPROVED = 'approved';

    const STATUS_ORDERED = 'ordered';

    const STATUS_PARTIALLY_RECEIVED = 'partially_received';

    const STATUS_RECEIVED = 'received';

    const STATUS_CANCELLED = 'cancelled';

    /**
     * Get the supplier for this purchase order
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the warehouse for this purchase order
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the user who requested this order
     */
    public function requester()
    {
        return $this->belongsTo(\Aero\Core\Models\User::class, 'requested_by');
    }

    /**
     * Get the user who approved this order
     */
    public function approver()
    {
        return $this->belongsTo(\Aero\Core\Models\User::class, 'approved_by');
    }

    /**
     * Get the user who received this order
     */
    public function receiver()
    {
        return $this->belongsTo(\Aero\Core\Models\User::class, 'received_by');
    }

    /**
     * Get the purchase order items
     */
    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    /**
     * Scope for pending orders
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for approved orders
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope for received orders
     */
    public function scopeReceived($query)
    {
        return $query->where('status', self::STATUS_RECEIVED);
    }

    /**
     * Get total items count
     */
    public function getItemsCountAttribute()
    {
        return $this->items()->count();
    }
}
