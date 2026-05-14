<?php

namespace Aero\Manufacturing\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkOrderOperation extends TenantModel
{
    use HasFactory;

    protected $table = 'manufacturing_work_order_operations';

    protected $fillable = [
        'work_order_id', 'route_operation_id', 'work_center_id', 'sequence',
        'operation_name', 'status', 'planned_start_date', 'planned_end_date',
        'actual_start_date', 'actual_end_date', 'setup_time_minutes',
        'run_time_minutes', 'quantity_completed', 'quantity_scrapped',
        'assigned_to', 'notes',
    ];

    protected $casts = [
        'work_order_id' => 'integer',
        'route_operation_id' => 'integer',
        'work_center_id' => 'integer',
        'sequence' => 'integer',
        'planned_start_date' => 'datetime',
        'planned_end_date' => 'datetime',
        'actual_start_date' => 'datetime',
        'actual_end_date' => 'datetime',
        'setup_time_minutes' => 'integer',
        'run_time_minutes' => 'integer',
        'quantity_completed' => 'decimal:3',
        'quantity_scrapped' => 'decimal:3',
        'assigned_to' => 'integer',
    ];

    const STATUS_PLANNED = 'planned';

    const STATUS_RELEASED = 'released';

    const STATUS_IN_PROGRESS = 'in_progress';

    const STATUS_COMPLETED = 'completed';

    const STATUS_ON_HOLD = 'on_hold';

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function routeOperation()
    {
        return $this->belongsTo(RouteOperation::class);
    }

    public function workCenter()
    {
        return $this->belongsTo(WorkCenter::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function timeEntries()
    {
        return $this->hasMany(WorkOrderTimeEntry::class);
    }

    public function getCompletionPercentageAttribute()
    {
        $totalQuantity = $this->workOrder->quantity_to_produce;

        return $totalQuantity > 0 ? ($this->quantity_completed / $totalQuantity) * 100 : 0;
    }
}
