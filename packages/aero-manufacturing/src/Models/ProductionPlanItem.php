<?php

namespace Aero\Manufacturing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionPlanItem extends TenantModel
{
    use HasFactory;

    protected $table = 'manufacturing_production_plan_items';

    protected $fillable = [
        'production_plan_id', 'product_id', 'planned_quantity', 'actual_quantity',
        'planned_start_date', 'planned_end_date', 'actual_start_date', 'actual_end_date',
        'work_order_id', 'priority', 'notes',
    ];

    protected $casts = [
        'production_plan_id' => 'integer',
        'product_id' => 'integer',
        'planned_quantity' => 'decimal:3',
        'actual_quantity' => 'decimal:3',
        'planned_start_date' => 'date',
        'planned_end_date' => 'date',
        'actual_start_date' => 'date',
        'actual_end_date' => 'date',
        'work_order_id' => 'integer',
    ];

    const PRIORITY_LOW = 'low';

    const PRIORITY_NORMAL = 'normal';

    const PRIORITY_HIGH = 'high';

    const PRIORITY_URGENT = 'urgent';

    public function productionPlan()
    {
        return $this->belongsTo(ProductionPlan::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function getVarianceQuantityAttribute()
    {
        return $this->actual_quantity - $this->planned_quantity;
    }

    public function getCompletionPercentageAttribute()
    {
        return $this->planned_quantity > 0 ? ($this->actual_quantity / $this->planned_quantity) * 100 : 0;
    }
}
