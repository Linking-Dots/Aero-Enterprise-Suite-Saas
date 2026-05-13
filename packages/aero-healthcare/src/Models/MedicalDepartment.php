<?php

namespace Aero\Healthcare\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MedicalDepartment extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'healthcare_departments';

    protected $fillable = [
        'name', 'code', 'description', 'head_provider_id', 'location',
        'phone', 'email', 'operating_hours', 'services_offered',
        'bed_capacity', 'current_occupancy', 'is_active', 'created_by',
    ];

    protected $casts = [
        'head_provider_id' => 'integer',
        'operating_hours' => 'json',
        'services_offered' => 'json',
        'bed_capacity' => 'integer',
        'current_occupancy' => 'integer',
        'is_active' => 'boolean',
        'created_by' => 'integer',
    ];

    public function headProvider()
    {
        return $this->belongsTo(HealthcareProvider::class, 'head_provider_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function providers()
    {
        return $this->hasMany(HealthcareProvider::class, 'department_id');
    }

    public function getOccupancyRateAttribute()
    {
        if ($this->bed_capacity === 0) {
            return 0;
        }

        return round(($this->current_occupancy / $this->bed_capacity) * 100, 1);
    }

    public function getAvailableBedsAttribute()
    {
        return max(0, $this->bed_capacity - $this->current_occupancy);
    }

    public function getTotalProvidersAttribute()
    {
        return $this->providers()->where('status', HealthcareProvider::STATUS_ACTIVE)->count();
    }

    public function isOperatingNow()
    {
        if (! $this->operating_hours) {
            return false;
        }

        $now = now();
        $dayOfWeek = strtolower($now->format('l'));
        $currentTime = $now->format('H:i');

        $todayHours = $this->operating_hours[$dayOfWeek] ?? null;

        if (! $todayHours || ! $todayHours['open']) {
            return false;
        }

        return $currentTime >= $todayHours['start'] && $currentTime <= $todayHours['end'];
    }

    public function hasAvailableBeds()
    {
        return $this->available_beds > 0;
    }

    public function offersService($service)
    {
        return in_array($service, $this->services_offered ?? []);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeWithAvailableBeds($query)
    {
        return $query->whereRaw('current_occupancy < bed_capacity');
    }
}
