<?php

namespace Aero\Manufacturing\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkOrderMaterial extends TenantModel
{
    use HasFactory;

    protected $table = 'manufacturing_work_order_materials';

    protected $fillable = [
        'work_order_id', 'product_id', 'planned_quantity', 'consumed_quantity',
        'unit_cost', 'warehouse_id', 'lot_number', 'serial_number',
        'consumed_by', 'consumed_at', 'notes',
    ];

    protected $casts = [
        'work_order_id' => 'integer',
        'product_id' => 'integer',
        'planned_quantity' => 'decimal:6',
        'consumed_quantity' => 'decimal:6',
        'unit_cost' => 'decimal:4',
        'warehouse_id' => 'integer',
        'consumed_by' => 'integer',
        'consumed_at' => 'datetime',
    ];

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function consumedBy()
    {
        return $this->belongsTo(User::class, 'consumed_by');
    }

    public function getVarianceQuantityAttribute()
    {
        return $this->consumed_quantity - $this->planned_quantity;
    }

    public function getTotalCostAttribute()
    {
        return $this->consumed_quantity * $this->unit_cost;
    }
}
