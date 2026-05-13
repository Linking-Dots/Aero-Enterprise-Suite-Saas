<?php

namespace Aero\FieldService\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Technician extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'field_service_technicians';

    protected $fillable = [
        'employee_number', 'user_id', 'first_name', 'last_name', 'email',
        'phone', 'mobile_phone', 'hire_date', 'status', 'skill_level',
        'hourly_rate', 'territory_id', 'home_address', 'emergency_contact',
        'certifications', 'specializations', 'vehicle_info', 'gps_tracking_enabled',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'hire_date' => 'date',
        'hourly_rate' => 'decimal:2',
        'territory_id' => 'integer',
        'home_address' => 'json',
        'emergency_contact' => 'json',
        'certifications' => 'json',
        'specializations' => 'json',
        'vehicle_info' => 'json',
        'gps_tracking_enabled' => 'boolean',
    ];

    const STATUS_ACTIVE = 'active';

    const STATUS_INACTIVE = 'inactive';

    const STATUS_ON_LEAVE = 'on_leave';

    const STATUS_TERMINATED = 'terminated';

    const SKILL_JUNIOR = 'junior';

    const SKILL_INTERMEDIATE = 'intermediate';

    const SKILL_SENIOR = 'senior';

    const SKILL_EXPERT = 'expert';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function territory()
    {
        return $this->belongsTo(ServiceTerritory::class, 'territory_id');
    }

    public function workOrders()
    {
        return $this->hasMany(ServiceWorkOrder::class, 'assigned_technician_id');
    }

    public function timeEntries()
    {
        return $this->hasMany(ServiceTimeEntry::class);
    }

    public function availabilities()
    {
        return $this->hasMany(TechnicianAvailability::class);
    }

    public function skills()
    {
        return $this->belongsToMany(TechnicianSkill::class, 'field_service_technician_skills')
            ->withPivot('proficiency_level', 'certification_date');
    }

    public function getFullNameAttribute()
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function getCurrentLocationAttribute()
    {
        return $this->gpsLocations()->latest()->first();
    }

    public function gpsLocations()
    {
        return $this->hasMany(TechnicianGpsLocation::class);
    }

    public function isAvailable($date = null, $timeFrom = null, $timeTo = null)
    {
        $date = $date ?: now()->toDateString();

        return $this->status === self::STATUS_ACTIVE &&
               $this->availabilities()
                   ->where('date', $date)
                   ->where('is_available', true)
                   ->exists();
    }

    public function getWorkloadForPeriod($startDate, $endDate)
    {
        return $this->workOrders()
            ->whereBetween('scheduled_date', [$startDate, $endDate])
            ->whereNotIn('status', [ServiceWorkOrder::STATUS_CANCELLED, ServiceWorkOrder::STATUS_COMPLETED])
            ->count();
    }
}
