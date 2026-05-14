<?php

namespace Aero\Manufacturing\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkOrder extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'manufacturing_work_orders';

    protected $fillable = [
        'work_order_number', 'product_id', 'bom_id', 'quantity_to_produce',
        'quantity_produced', 'quantity_scrapped', 'priority', 'status',
        'planned_start_date', 'planned_end_date', 'actual_start_date', 'actual_end_date',
        'route_id', 'cost_center_id', 'notes', 'created_by', 'assigned_to',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'bom_id' => 'integer',
        'quantity_to_produce' => 'decimal:3',
        'quantity_produced' => 'decimal:3',
        'quantity_scrapped' => 'decimal:3',
        'planned_start_date' => 'datetime',
        'planned_end_date' => 'datetime',
        'actual_start_date' => 'datetime',
        'actual_end_date' => 'datetime',
        'route_id' => 'integer',
        'cost_center_id' => 'integer',
        'created_by' => 'integer',
        'assigned_to' => 'integer',
    ];

    const PRIORITY_LOW = 'low';

    const PRIORITY_NORMAL = 'normal';

    const PRIORITY_HIGH = 'high';

    const PRIORITY_URGENT = 'urgent';

    const STATUS_PLANNED = 'planned';

    const STATUS_RELEASED = 'released';

    const STATUS_IN_PROGRESS = 'in_progress';

    const STATUS_COMPLETED = 'completed';

    const STATUS_CANCELLED = 'cancelled';

    const STATUS_ON_HOLD = 'on_hold';

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function billOfMaterials()
    {
        return $this->belongsTo(BillOfMaterials::class, 'bom_id');
    }

    public function route()
    {
        return $this->belongsTo(Route::class);
    }

    public function costCenter()
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function operations()
    {
        return $this->hasMany(WorkOrderOperation::class);
    }

    public function materials()
    {
        return $this->hasMany(WorkOrderMaterial::class);
    }

    public function timeEntries()
    {
        return $this->hasMany(WorkOrderTimeEntry::class);
    }

    public function getCompletionPercentageAttribute()
    {
        return $this->quantity_to_produce > 0 ?
            ($this->quantity_produced / $this->quantity_to_produce) * 100 : 0;
    }
}
