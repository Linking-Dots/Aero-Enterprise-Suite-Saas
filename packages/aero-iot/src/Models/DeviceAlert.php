<?php

namespace Aero\IoT\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeviceAlert extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'iot_device_alerts';

    protected $fillable = [
        'device_id', 'alert_type', 'severity', 'title', 'message',
        'alert_data', 'triggered_at', 'acknowledged_at', 'resolved_at',
        'acknowledged_by', 'resolved_by', 'status', 'notification_sent',
        'escalation_level', 'auto_resolve',
    ];

    protected $casts = [
        'device_id' => 'integer',
        'alert_data' => 'json',
        'triggered_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime',
        'acknowledged_by' => 'integer',
        'resolved_by' => 'integer',
        'notification_sent' => 'boolean',
        'escalation_level' => 'integer',
        'auto_resolve' => 'boolean',
    ];

    const TYPE_DEVICE_OFFLINE = 'device_offline';

    const TYPE_LOW_BATTERY = 'low_battery';

    const TYPE_SENSOR_FAULT = 'sensor_fault';

    const TYPE_CONNECTIVITY = 'connectivity';

    const TYPE_TEMPERATURE = 'temperature';

    const TYPE_THRESHOLD = 'threshold';

    const TYPE_MAINTENANCE = 'maintenance';

    const TYPE_SECURITY = 'security';

    const TYPE_PERFORMANCE = 'performance';

    const TYPE_ANOMALY = 'anomaly';

    const SEVERITY_LOW = 'low';

    const SEVERITY_MEDIUM = 'medium';

    const SEVERITY_HIGH = 'high';

    const SEVERITY_CRITICAL = 'critical';

    const STATUS_ACTIVE = 'active';

    const STATUS_ACKNOWLEDGED = 'acknowledged';

    const STATUS_RESOLVED = 'resolved';

    const STATUS_SUPPRESSED = 'suppressed';

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function acknowledgedBy()
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by');
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
        return $this->status === self::STATUS_RESOLVED;
    }

    public function isCritical()
    {
        return $this->severity === self::SEVERITY_CRITICAL;
    }

    public function isHigh()
    {
        return $this->severity === self::SEVERITY_HIGH;
    }

    public function getDurationAttribute()
    {
        $endTime = $this->resolved_at ?: now();

        return $this->triggered_at->diffInMinutes($endTime);
    }

    public function getAgeAttribute()
    {
        return $this->triggered_at->diffForHumans();
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

    public function getStatusColorAttribute()
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'danger',
            self::STATUS_ACKNOWLEDGED => 'warning',
            self::STATUS_RESOLVED => 'success',
            self::STATUS_SUPPRESSED => 'default',
            default => 'default'
        };
    }

    public function acknowledge(User $user, $notes = null)
    {
        $this->update([
            'status' => self::STATUS_ACKNOWLEDGED,
            'acknowledged_at' => now(),
            'acknowledged_by' => $user->id,
            'alert_data' => array_merge($this->alert_data ?: [], [
                'acknowledgment_notes' => $notes,
                'acknowledged_at' => now()->toISOString(),
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
                'resolved_at' => now()->toISOString(),
            ]),
        ]);
    }

    public function escalate()
    {
        $this->increment('escalation_level');
        $this->update([
            'alert_data' => array_merge($this->alert_data ?: [], [
                'escalated_at' => now()->toISOString(),
                'escalation_level' => $this->escalation_level,
            ]),
        ]);
    }

    public function suppress($duration = null)
    {
        $suppressedUntil = $duration ? now()->addMinutes($duration) : null;

        $this->update([
            'status' => self::STATUS_SUPPRESSED,
            'alert_data' => array_merge($this->alert_data ?: [], [
                'suppressed_at' => now()->toISOString(),
                'suppressed_until' => $suppressedUntil?->toISOString(),
            ]),
        ]);
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

    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('triggered_at', '>=', now()->subHours($hours));
    }

    public function scopeByDevice($query, $deviceId)
    {
        return $query->where('device_id', $deviceId);
    }
}
