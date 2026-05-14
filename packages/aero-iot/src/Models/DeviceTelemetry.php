<?php

namespace Aero\IoT\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeviceTelemetry extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'iot_device_telemetry';

    protected $fillable = [
        'device_id', 'metric_name', 'metric_value', 'unit', 'data_type',
        'quality_score', 'timestamp', 'metadata', 'aggregation_period',
        'is_processed',
    ];

    protected $casts = [
        'device_id' => 'integer',
        'metric_value' => 'decimal:6',
        'quality_score' => 'decimal:3',
        'timestamp' => 'datetime',
        'metadata' => 'json',
        'is_processed' => 'boolean',
    ];

    const DATA_TYPE_NUMERIC = 'numeric';

    const DATA_TYPE_STRING = 'string';

    const DATA_TYPE_BOOLEAN = 'boolean';

    const DATA_TYPE_JSON = 'json';

    const DATA_TYPE_BINARY = 'binary';

    const PERIOD_REALTIME = 'realtime';

    const PERIOD_MINUTE = 'minute';

    const PERIOD_HOUR = 'hour';

    const PERIOD_DAY = 'day';

    const PERIOD_WEEK = 'week';

    const PERIOD_MONTH = 'month';

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function getFormattedValueAttribute()
    {
        if ($this->unit) {
            return number_format($this->metric_value, 2).' '.$this->unit;
        }

        return number_format($this->metric_value, 2);
    }

    public function getAgeAttribute()
    {
        return $this->timestamp->diffForHumans();
    }

    public function isStale($minutes = 30)
    {
        return $this->timestamp < now()->subMinutes($minutes);
    }

    public function hasGoodQuality($threshold = 0.8)
    {
        return $this->quality_score >= $threshold;
    }

    public function scopeByMetric($query, $metricName)
    {
        return $query->where('metric_name', $metricName);
    }

    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('timestamp', '>=', now()->subHours($hours));
    }

    public function scopeByDevice($query, $deviceId)
    {
        return $query->where('device_id', $deviceId);
    }

    public function scopeProcessed($query)
    {
        return $query->where('is_processed', true);
    }

    public function scopeUnprocessed($query)
    {
        return $query->where('is_processed', false);
    }

    public function scopeByPeriod($query, $period)
    {
        return $query->where('aggregation_period', $period);
    }

    public function scopeGoodQuality($query, $threshold = 0.8)
    {
        return $query->where('quality_score', '>=', $threshold);
    }
}
