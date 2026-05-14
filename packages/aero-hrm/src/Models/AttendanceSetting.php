<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceSetting extends TenantModel
{
    use HasFactory;

    protected $fillable = [
        'shift_start_time',
        'shift_end_time',
        'break_duration_minutes',
        'late_arrival_threshold_minutes',
        'early_leave_threshold_minutes',
        'half_day_threshold_hours',
        'full_day_hours',
        'enable_ip_restriction',
        'allowed_ip_addresses',
        'enable_geolocation',
        'office_latitude',
        'office_longitude',
        'geofence_radius_meters',
        'require_selfie',
    ];

    protected $casts = [
        'shift_start_time' => 'datetime:H:i',
        'shift_end_time' => 'datetime:H:i',
        'break_duration_minutes' => 'integer',
        'late_arrival_threshold_minutes' => 'integer',
        'early_leave_threshold_minutes' => 'integer',
        'half_day_threshold_hours' => 'decimal:2',
        'full_day_hours' => 'decimal:2',
        'enable_ip_restriction' => 'boolean',
        'allowed_ip_addresses' => 'array',
        'enable_geolocation' => 'boolean',
        'office_latitude' => 'decimal:7',
        'office_longitude' => 'decimal:7',
        'geofence_radius_meters' => 'integer',
        'require_selfie' => 'boolean',
    ];
}
