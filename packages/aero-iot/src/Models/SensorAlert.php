<?php

namespace Aero\IoT\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SensorAlert extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'iot_sensor_alerts';

    protected $fillable = [
        'sensor_id', 'sensor_data_id', 'alert_type', 'severity', 'message',
        'threshold_value', 'actual_value', 'alert_data', 'triggered_at',
        'acknowledged_at', 'resolved_at', 'acknowledged_by', 'resolved_by',
        'status', 'auto_resolve_duration',
    ];

    protected $casts = [
        'sensor_id' => 'integer',
        'sensor_data_id' => 'integer',
        'threshold_value' => 'decimal:6',
        'actual_value' => 'decimal:6',
        'alert_data' => 'json',
        'triggered_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime',
        'acknowledged_by' => 'integer',
        'resolved_by' => 'integer',
        'auto_resolve_duration' => 'integer',
    ];

    const TYPE_THRESHOLD_EXCEEDED = 'threshold_exceeded';

    const TYPE_OUT_OF_RANGE = 'out_of_range';

    const TYPE_RAPID_CHANGE = 'rapid_change';

    const TYPE_NO_DATA = 'no_data';

    const TYPE_CALIBRATION_NEEDED = 'calibration_needed';

    const TYPE_QUALITY_LOW = 'quality_low';

    const TYPE_ANOMALY_DETECTED = 'anomaly_detected';

    const SEVERITY_LOW = 'low';

    const SEVERITY_MEDIUM = 'medium';

    const SEVERITY_HIGH = 'high';

    const SEVERITY_CRITICAL = 'critical';

    const STATUS_ACTIVE = 'active';

    const STATUS_ACKNOWLEDGED = 'acknowledged';

    const STATUS_RESOLVED = 'resolved';

    const STATUS_AUTO_RESOLVED = 'auto_resolved';

    public function sensor()
    {
        return $this->belongsTo(Sensor::class);
    }

    public function sensorData()
    {
        return $this->belongsTo(SensorData::class);
    }

    public function acknowledgedBy()
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function device()
    {
        return $this->sensor->device();
    }

    public function isActive()
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isAcknowledged()
    {
        return $this->status === self::STATUS_ACKNOWLEDGED;
    }

    public function isResolved()
    {
        return in_array($this->status, [self::STATUS_RESOLVED, self::STATUS_AUTO_RESOLVED]);
    }

    public function isCritical()
    {
        return $this->severity === self::SEVERITY_CRITICAL;
    }

    public function getDurationAttribute()
    {
        $endTime = $this->resolved_at ?: now();

        return $this->triggered_at->diffInMinutes($endTime);
    }

    public function getSeverityColorAttribute()
    {
        return match ($this->severity) {
            self::SEVERITY_LOW => 'success',
            self::SEVERITY_MEDIUM => 'warning',
            self::SEVERITY_HIGH => 'danger',
            self::SEVERITY_CRITICAL => 'error',
            default => 'default'
        };
    }

    public function getVariancePercentageAttribute()
    {
        if (! $this->threshold_value || $this->threshold_value == 0) {
            return null;
        }

        $variance = abs($this->actual_value - $this->threshold_value);

        return round(($variance / abs($this->threshold_value)) * 100, 2);
    }

    public function acknowledge(User $user, $notes = null)
    {
        $this->update([
            'status' => self::STATUS_ACKNOWLEDGED,
            'acknowledged_at' => now(),
            'acknowledged_by' => $user->id,
            'alert_data' => array_merge($this->alert_data ?: [], [
                'acknowledgment_notes' => $notes,
            ]),
        ]);
    }

    public function resolve(User $user, $notes = null)
    {
        $this->update([
            'status' => self::STATUS_RESOLVED,
            'resolved_at' => now(),
            'resolved_by' => $user->id,
            'alert_data' => array_merge($this->alert_data ?: [], [
                'resolution_notes' => $notes,
            ]),
        ]);
    }

    public function autoResolve()
    {
        $this->update([
            'status' => self::STATUS_AUTO_RESOLVED,
            'resolved_at' => now(),
        ]);
    }

    public function shouldAutoResolve()
    {
        if (! $this->auto_resolve_duration || $this->isResolved()) {
            return false;
        }

        return $this->triggered_at->addMinutes($this->auto_resolve_duration) <= now();
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeUnacknowledged($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeUnresolved($query)
    {
        return $query->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_ACKNOWLEDGED]);
    }

    public function scopeBySeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', self::SEVERITY_CRITICAL);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('alert_type', $type);
    }

    public function scopeBySensor($query, $sensorId)
    {
        return $query->where('sensor_id', $sensorId);
    }

    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('triggered_at', '>=', now()->subHours($hours));
    }

    public function scopeAutoResolvable($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->whereNotNull('auto_resolve_duration')
            ->whereRaw('DATE_ADD(triggered_at, INTERVAL auto_resolve_duration MINUTE) <= NOW()');
    }
}
