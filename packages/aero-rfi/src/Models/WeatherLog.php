<?php

namespace Aero\Rfi\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeatherLog extends TenantModel
{
    protected $fillable = [
        'daily_work_id',
        'observation_date',
        'observation_time',
        'condition',
        'temperature_celsius',
        'humidity_percent',
        'rainfall_mm',
        'wind_condition',
        'wind_speed_kmh',
        'work_impact',
        'hours_lost',
        'impact_description',
        'affected_activities',
        'photo_path',
        'remarks',
    ];

    protected $casts = [
        'observation_date' => 'date',
        'observation_time' => 'datetime:H:i:s',
        'temperature_celsius' => 'decimal:2',
        'humidity_percent' => 'decimal:2',
        'rainfall_mm' => 'decimal:2',
        'wind_speed_kmh' => 'decimal:2',
        'hours_lost' => 'decimal:2',
        'affected_activities' => 'array',
    ];

    public function rfi(): BelongsTo
    {
        return $this->belongsTo(Rfi::class);
    }

    /**
     * Check if weather caused work stoppage.
     */
    public function getCausedStoppageAttribute(): bool
    {
        return $this->work_impact === 'work_stopped';
    }

    /**
     * Get human-readable impact level.
     */
    public function getImpactLevelTextAttribute(): string
    {
        return match ($this->work_impact) {
            'no_impact' => 'No Impact',
            'minor_delay' => 'Minor Delay',
            'major_delay' => 'Major Delay',
            'work_stopped' => 'Work Stopped',
            default => 'Unknown',
        };
    }

    /**
     * Check if conditions were suitable for work.
     */
    public function getSuitableForWorkAttribute(): bool
    {
        // Define criteria for workable conditions
        if ($this->rainfall_mm > 10) {
            return false; // Heavy rain
        }

        if ($this->wind_speed_kmh && $this->wind_speed_kmh > 40) {
            return false; // Strong winds
        }

        if ($this->temperature_celsius && ($this->temperature_celsius > 40 || $this->temperature_celsius < 5)) {
            return false; // Extreme temperatures
        }

        return true;
    }
}
