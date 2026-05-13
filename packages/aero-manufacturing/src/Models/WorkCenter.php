<?php

namespace Aero\Manufacturing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkCenter extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'manufacturing_work_centers';

    protected $fillable = [
        'code', 'name', 'description', 'work_center_type',
        'capacity_hours_per_day', 'efficiency_percentage',
        'setup_time_minutes', 'cost_per_hour', 'location',
        'is_active', 'calendar_id',
    ];

    protected $casts = [
        'capacity_hours_per_day' => 'decimal:2',
        'efficiency_percentage' => 'decimal:2',
        'setup_time_minutes' => 'integer',
        'cost_per_hour' => 'decimal:2',
        'is_active' => 'boolean',
        'calendar_id' => 'integer',
    ];

    const TYPE_MACHINE = 'machine';

    const TYPE_WORKSTATION = 'workstation';

    const TYPE_ASSEMBLY_LINE = 'assembly_line';

    const TYPE_QUALITY_CONTROL = 'quality_control';

    public function operations()
    {
        return $this->hasMany(RouteOperation::class);
    }

    public function workOrderOperations()
    {
        return $this->hasMany(WorkOrderOperation::class);
    }

    public function calendar()
    {
        return $this->belongsTo(WorkCalendar::class, 'calendar_id');
    }

    public function getEffectiveCapacityAttribute()
    {
        return $this->capacity_hours_per_day * ($this->efficiency_percentage / 100);
    }
}
