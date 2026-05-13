<?php

namespace Aero\Manufacturing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkCalendar extends TenantModel
{
    use HasFactory;

    protected $table = 'manufacturing_work_calendars';

    protected $fillable = [
        'name', 'description', 'monday_hours', 'tuesday_hours', 'wednesday_hours',
        'thursday_hours', 'friday_hours', 'saturday_hours', 'sunday_hours',
        'is_active',
    ];

    protected $casts = [
        'monday_hours' => 'decimal:2',
        'tuesday_hours' => 'decimal:2',
        'wednesday_hours' => 'decimal:2',
        'thursday_hours' => 'decimal:2',
        'friday_hours' => 'decimal:2',
        'saturday_hours' => 'decimal:2',
        'sunday_hours' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function workCenters()
    {
        return $this->hasMany(WorkCenter::class, 'calendar_id');
    }

    public function getTotalWeeklyHoursAttribute()
    {
        return $this->monday_hours + $this->tuesday_hours + $this->wednesday_hours +
               $this->thursday_hours + $this->friday_hours + $this->saturday_hours + $this->sunday_hours;
    }
}
