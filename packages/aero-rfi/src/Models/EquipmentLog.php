<?php

namespace Aero\Rfi\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EquipmentLog extends TenantModel
{
    use SoftDeletes;

    protected $fillable = [
        'daily_work_id',
        'work_layer_id',
        'equipment_type',
        'equipment_id',
        'model',
        'operator_name',
        'start_time',
        'end_time',
        'working_hours',
        'idle_hours',
        'breakdown_hours',
        'start_chainage_m',
        'end_chainage_m',
        'work_location',
        'fuel_consumed_liters',
        'fuel_type',
        'odometer_reading',
        'maintenance_status',
        'breakdown_details',
        'remarks',
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i:s',
        'end_time' => 'datetime:H:i:s',
        'working_hours' => 'decimal:2',
        'idle_hours' => 'decimal:2',
        'breakdown_hours' => 'decimal:2',
        'start_chainage_m' => 'decimal:3',
        'end_chainage_m' => 'decimal:3',
        'fuel_consumed_liters' => 'decimal:2',
        'odometer_reading' => 'decimal:2',
    ];

    public function rfi(): BelongsTo
    {
        return $this->belongsTo(Rfi::class);
    }

    public function workLayer(): BelongsTo
    {
        return $this->belongsTo(WorkLayer::class);
    }

    /**
     * Get equipment utilization percentage.
     */
    public function getUtilizationPercentageAttribute(): float
    {
        $totalHours = $this->working_hours + $this->idle_hours + $this->breakdown_hours;

        if ($totalHours == 0) {
            return 0;
        }

        return ($this->working_hours / $totalHours) * 100;
    }

    /**
     * Get fuel efficiency (km per liter).
     */
    public function getFuelEfficiencyAttribute(): ?float
    {
        if (! $this->fuel_consumed_liters || $this->fuel_consumed_liters == 0) {
            return null;
        }

        // Calculate based on chainage distance if available
        if ($this->start_chainage_m && $this->end_chainage_m) {
            $distanceKm = abs($this->end_chainage_m - $this->start_chainage_m) / 1000;

            return $distanceKm / $this->fuel_consumed_liters;
        }

        return null;
    }
}
