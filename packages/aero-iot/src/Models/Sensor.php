<?php

namespace Aero\IoT\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sensor extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'iot_sensors';

    protected $fillable = [
        'device_id', 'sensor_name', 'sensor_type', 'measurement_unit',
        'min_value', 'max_value', 'accuracy', 'resolution', 'sampling_rate',
        'calibration_date', 'calibration_value', 'is_active', 'alert_thresholds',
        'configuration', 'created_by',
    ];

    protected $casts = [
        'device_id' => 'integer',
        'min_value' => 'decimal:4',
        'max_value' => 'decimal:4',
        'accuracy' => 'decimal:4',
        'resolution' => 'decimal:4',
        'sampling_rate' => 'integer',
        'calibration_date' => 'date',
        'calibration_value' => 'decimal:4',
        'is_active' => 'boolean',
        'alert_thresholds' => 'json',
        'configuration' => 'json',
        'created_by' => 'integer',
    ];

    const TYPE_TEMPERATURE = 'temperature';

    const TYPE_HUMIDITY = 'humidity';

    const TYPE_PRESSURE = 'pressure';

    const TYPE_ACCELEROMETER = 'accelerometer';

    const TYPE_GYROSCOPE = 'gyroscope';

    const TYPE_MAGNETOMETER = 'magnetometer';

    const TYPE_PROXIMITY = 'proximity';

    const TYPE_LIGHT = 'light';

    const TYPE_SOUND = 'sound';

    const TYPE_AIR_QUALITY = 'air_quality';

    const TYPE_MOTION = 'motion';

    const TYPE_VIBRATION = 'vibration';

    const TYPE_FLOW = 'flow';

    const TYPE_LEVEL = 'level';

    const TYPE_PH = 'ph';

    const TYPE_CONDUCTIVITY = 'conductivity';

    const TYPE_VOLTAGE = 'voltage';

    const TYPE_CURRENT = 'current';

    const TYPE_POWER = 'power';

    const TYPE_GPS = 'gps';

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sensorData()
    {
        return $this->hasMany(SensorData::class);
    }

    public function alerts()
    {
        return $this->hasMany(SensorAlert::class);
    }

    public function getLatestReadingAttribute()
    {
        return $this->sensorData()
            ->orderBy('timestamp', 'desc')
            ->first();
    }

    public function getAverageReadingAttribute($hours = 24)
    {
        return $this->sensorData()
            ->where('timestamp', '>=', now()->subHours($hours))
            ->avg('value');
    }

    public function getReadingTrendAttribute($hours = 24)
    {
        $readings = $this->sensorData()
            ->where('timestamp', '>=', now()->subHours($hours))
            ->orderBy('timestamp')
            ->pluck('value')
            ->toArray();

        if (count($readings) < 2) {
            return 'stable';
        }

        $firstHalf = array_slice($readings, 0, count($readings) / 2);
        $secondHalf = array_slice($readings, count($readings) / 2);

        $firstAvg = array_sum($firstHalf) / count($firstHalf);
        $secondAvg = array_sum($secondHalf) / count($secondHalf);

        $difference = $secondAvg - $firstAvg;
        $threshold = abs($firstAvg * 0.05); // 5% threshold

        if ($difference > $threshold) {
            return 'increasing';
        }
        if ($difference < -$threshold) {
            return 'decreasing';
        }

        return 'stable';
    }

    public function needsCalibration($months = 12)
    {
        if (! $this->calibration_date) {
            return true;
        }

        return $this->calibration_date->addMonths($months) <= now();
    }

    public function checkAlertThresholds($value)
    {
        if (! is_array($this->alert_thresholds)) {
            return [];
        }

        $alerts = [];

        foreach ($this->alert_thresholds as $threshold) {
            $condition = $threshold['condition'] ?? null;
            $thresholdValue = $threshold['value'] ?? null;
            $severity = $threshold['severity'] ?? 'medium';

            if (! $condition || ! $thresholdValue) {
                continue;
            }

            $triggered = match ($condition) {
                'greater_than' => $value > $thresholdValue,
                'less_than' => $value < $thresholdValue,
                'equal' => $value == $thresholdValue,
                'greater_equal' => $value >= $thresholdValue,
                'less_equal' => $value <= $thresholdValue,
                default => false
            };

            if ($triggered) {
                $alerts[] = [
                    'condition' => $condition,
                    'threshold_value' => $thresholdValue,
                    'actual_value' => $value,
                    'severity' => $severity,
                    'message' => $threshold['message'] ?? null,
                ];
            }
        }

        return $alerts;
    }

    public function recordReading($value, $timestamp = null, $metadata = null)
    {
        $timestamp = $timestamp ?: now();

        $sensorData = $this->sensorData()->create([
            'value' => $value,
            'timestamp' => $timestamp,
            'metadata' => $metadata,
        ]);

        // Check for alert thresholds
        $alerts = $this->checkAlertThresholds($value);
        foreach ($alerts as $alert) {
            $this->alerts()->create([
                'sensor_data_id' => $sensorData->id,
                'alert_type' => 'threshold_exceeded',
                'severity' => $alert['severity'],
                'message' => $alert['message'] ?? 'Threshold exceeded',
                'alert_data' => $alert,
                'triggered_at' => $timestamp,
            ]);
        }

        return $sensorData;
    }

    public function calibrate($calibrationValue, $notes = null)
    {
        $this->update([
            'calibration_date' => now(),
            'calibration_value' => $calibrationValue,
        ]);

        // Log calibration event
        $this->device->recordTelemetry('sensor_calibrated', $calibrationValue, $this->measurement_unit);

        return true;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('sensor_type', $type);
    }

    public function scopeNeedsCalibration($query, $months = 12)
    {
        return $query->where(function ($q) use ($months) {
            $q->whereNull('calibration_date')
                ->orWhere('calibration_date', '<=', now()->subMonths($months));
        });
    }

    public function scopeByDevice($query, $deviceId)
    {
        return $query->where('device_id', $deviceId);
    }
}
