<?php

namespace Aero\Manufacturing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RouteOperation extends TenantModel
{
    use HasFactory;

    protected $table = 'manufacturing_route_operations';

    protected $fillable = [
        'route_id', 'work_center_id', 'sequence', 'operation_name',
        'description', 'setup_time_minutes', 'run_time_per_unit_minutes',
        'cost_per_hour', 'move_time_minutes', 'wait_time_minutes',
        'is_outside_operation', 'vendor_id',
    ];

    protected $casts = [
        'route_id' => 'integer',
        'work_center_id' => 'integer',
        'sequence' => 'integer',
        'setup_time_minutes' => 'integer',
        'run_time_per_unit_minutes' => 'decimal:3',
        'cost_per_hour' => 'decimal:2',
        'move_time_minutes' => 'integer',
        'wait_time_minutes' => 'integer',
        'is_outside_operation' => 'boolean',
        'vendor_id' => 'integer',
    ];

    public function route()
    {
        return $this->belongsTo(Route::class);
    }

    public function workCenter()
    {
        return $this->belongsTo(WorkCenter::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function workOrderOperations()
    {
        return $this->hasMany(WorkOrderOperation::class);
    }

    public function getCostPerUnitAttribute()
    {
        $setupCost = ($this->setup_time_minutes / 60) * $this->cost_per_hour;
        $runCost = ($this->run_time_per_unit_minutes / 60) * $this->cost_per_hour;

        return $setupCost + $runCost;
    }

    public function getTotalTimeMinutesAttribute()
    {
        return $this->setup_time_minutes + $this->run_time_per_unit_minutes +
               $this->move_time_minutes + $this->wait_time_minutes;
    }
}
