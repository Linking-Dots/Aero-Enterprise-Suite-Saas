<?php

namespace Aero\FieldService\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TechnicianAvailability extends TenantModel
{
    use HasFactory;

    protected $table = 'field_service_technician_availability';

    protected $fillable = [
        'technician_id', 'date', 'start_time', 'end_time', 'is_available',
        'availability_type', 'reason', 'notes',
    ];

    protected $casts = [
        'technician_id' => 'integer',
        'date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'is_available' => 'boolean',
    ];

    const TYPE_REGULAR = 'regular';

    const TYPE_OVERTIME = 'overtime';

    const TYPE_ON_CALL = 'on_call';

    const TYPE_VACATION = 'vacation';

    const TYPE_SICK_LEAVE = 'sick_leave';

    const TYPE_TRAINING = 'training';

    public function technician()
    {
        return $this->belongsTo(Technician::class);
    }

    public function getHoursAttribute()
    {
        if ($this->start_time && $this->end_time) {
            return $this->start_time->diffInHours($this->end_time);
        }

        return 0;
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    public function scopeForDate($query, $date)
    {
        return $query->where('date', $date);
    }
}
