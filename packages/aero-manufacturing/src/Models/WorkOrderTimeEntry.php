<?php

namespace Aero\Manufacturing\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkOrderTimeEntry extends TenantModel
{
    use HasFactory;

    protected $table = 'manufacturing_work_order_time_entries';

    protected $fillable = [
        'work_order_id', 'work_order_operation_id', 'employee_id',
        'start_time', 'end_time', 'break_time_minutes', 'total_time_minutes',
        'quantity_produced', 'quantity_scrapped', 'time_type', 'notes',
    ];

    protected $casts = [
        'work_order_id' => 'integer',
        'work_order_operation_id' => 'integer',
        'employee_id' => 'integer',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'break_time_minutes' => 'integer',
        'total_time_minutes' => 'integer',
        'quantity_produced' => 'decimal:3',
        'quantity_scrapped' => 'decimal:3',
    ];

    const TYPE_SETUP = 'setup';

    const TYPE_RUN = 'run';

    const TYPE_CLEANUP = 'cleanup';

    const TYPE_REWORK = 'rework';

    const TYPE_WAIT = 'wait';

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function workOrderOperation()
    {
        return $this->belongsTo(WorkOrderOperation::class);
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function getProductiveTimeMinutesAttribute()
    {
        return $this->total_time_minutes - $this->break_time_minutes;
    }

    public function getEfficiencyRateAttribute()
    {
        return $this->quantity_produced > 0 && $this->productive_time_minutes > 0 ?
            $this->quantity_produced / ($this->productive_time_minutes / 60) : 0;
    }
}
