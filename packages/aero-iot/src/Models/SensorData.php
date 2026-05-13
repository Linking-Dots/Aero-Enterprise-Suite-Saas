<?php

namespace Aero\IoT\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SensorData extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'iot_sensor_data';

    protected $fillable = [
        'sensor_id', 'value', 'unit', 'quality_indicator', 'timestamp',
        'metadata', 'is_anomaly', 'processing_status', 'batch_id',
    ];

    protected $casts = [
        'sensor_id' => 'integer',
        'value' => 'decimal:6',
        'quality_indicator' => 'decimal:3',
        'timestamp' => 'datetime',
        'metadata' => 'json',
        'is_anomaly' => 'boolean',
    ];

    const STATUS_RAW = 'raw';

    const STATUS_VALIDATED = 'validated';

    const STATUS_PROCESSED = 'processed';

    const STATUS_ARCHIVED = 'archived';

    const STATUS_ERROR = 'error';

    public function sensor()
    {
        return $this->belongsTo(Sensor::class);
    }

    public function device()
    {
        return $this->sensor->device();
    }

    public function alerts()
    {
        return $this->hasMany(SensorAlert::class);
    }

    public function getFormattedValueAttribute()
    {
        $unit = $this->unit ?: $this->sensor?->measurement_unit;
        if ($unit) {
            return number_format($this->value, 2).' '.$unit;
        }

        return number_format($this->value, 2);
    }

    public function getAgeAttribute()
    {
        return $this->timestamp->diffForHumans();
    }

    public function isRecent($minutes = 30)
    {
        return $this->timestamp >= now()->subMinutes($minutes);
    }

    public function hasGoodQuality($threshold = 0.8)
    {
        return $this->quality_indicator >= $threshold;
    }

    public function isOutOfRange()
    {
        $sensor = $this->sensor;
        if (! $sensor) {
            return false;
        }

        return ($sensor->min_value && $this->value < $sensor->min_value) ||
               ($sensor->max_value && $this->value > $sensor->max_value);
    }

    public function markAsAnomaly($reason = null)
    {
        $this->update([
            'is_anomaly' => true,
            'metadata' => array_merge($this->metadata ?: [], [
                'anomaly_detected_at' => now(),
                'anomaly_reason' => $reason,
            ]),
        ]);
    }

    public function scopeByRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('timestamp', [$startDate, $endDate]);
    }

    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('timestamp', '>=', now()->subHours($hours));
    }

    public function scopeByValue($query, $operator, $value)
    {
        return $query->where('value', $operator, $value);
    }

    public function scopeGoodQuality($query, $threshold = 0.8)
    {
        return $query->where('quality_indicator', '>=', $threshold);
    }

    public function scopeAnomalies($query)
    {
        return $query->where('is_anomaly', true);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('processing_status', $status);
    }

    public function scopeBySensor($query, $sensorId)
    {
        return $query->where('sensor_id', $sensorId);
    }

    public function scopeByBatch($query, $batchId)
    {
        return $query->where('batch_id', $batchId);
    }
}
