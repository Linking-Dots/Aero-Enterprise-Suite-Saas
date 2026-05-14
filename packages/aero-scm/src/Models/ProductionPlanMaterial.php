<?php

namespace Aero\Scm\Models;

use Aero\Contracts\Models\TenantModel;

use App\Models\Tenant\IMS\InventoryItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionPlanMaterial extends TenantModel
{
    use HasFactory;

    protected $fillable = [
        'production_plan_id',
        'inventory_item_id',
        'required_quantity',
        'allocated_quantity',
        'consumed_quantity',
    ];

    protected $casts = [
        'required_quantity' => 'decimal:2',
        'allocated_quantity' => 'decimal:2',
        'consumed_quantity' => 'decimal:2',
    ];

    /**
     * Get the production plan.
     */
    public function productionPlan()
    {
        return $this->belongsTo(ProductionPlan::class);
    }

    /**
     * Get the inventory item (material).
     */
    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }

    /**
     * Get remaining quantity needed.
     */
    public function getRemainingQuantityAttribute()
    {
        return $this->required_quantity - $this->allocated_quantity;
    }

    /**
     * Check if material is fully allocated.
     */
    public function isFullyAllocated()
    {
        return $this->allocated_quantity >= $this->required_quantity;
    }

    /**
     * Check if material is fully consumed.
     */
    public function isFullyConsumed()
    {
        return $this->consumed_quantity >= $this->required_quantity;
    }
}
