<?php

namespace Aero\IoT\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeviceCommand extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'iot_device_commands';

    protected $fillable = [
        'device_id', 'command_name', 'parameters', 'priority', 'timeout',
        'status', 'sent_at', 'acknowledged_at', 'completed_at', 'failed_at',
        'response', 'error_message', 'retry_count', 'max_retries',
        'created_by', 'scheduled_for',
    ];

    protected $casts = [
        'device_id' => 'integer',
        'parameters' => 'json',
        'priority' => 'integer',
        'timeout' => 'integer',
        'sent_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
        'response' => 'json',
        'retry_count' => 'integer',
        'max_retries' => 'integer',
        'created_by' => 'integer',
        'scheduled_for' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';

    const STATUS_SENT = 'sent';

    const STATUS_ACKNOWLEDGED = 'acknowledged';

    const STATUS_COMPLETED = 'completed';

    const STATUS_FAILED = 'failed';

    const STATUS_TIMEOUT = 'timeout';

    const STATUS_CANCELLED = 'cancelled';

    const PRIORITY_LOW = 1;

    const PRIORITY_NORMAL = 5;

    const PRIORITY_HIGH = 8;

    const PRIORITY_CRITICAL = 10;

    const COMMAND_RESTART = 'restart';

    const COMMAND_SHUTDOWN = 'shutdown';

    const COMMAND_UPDATE_CONFIG = 'update_config';

    const COMMAND_CALIBRATE = 'calibrate';

    const COMMAND_RESET = 'reset';

    const COMMAND_DIAGNOSTIC = 'diagnostic';

    const COMMAND_UPGRADE_FIRMWARE = 'upgrade_firmware';

    const COMMAND_SET_PARAMETER = 'set_parameter';

    const COMMAND_GET_STATUS = 'get_status';

    const COMMAND_TRIGGER_SENSOR = 'trigger_sensor';

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isSent()
    {
        return $this->status === self::STATUS_SENT;
    }

    public function isCompleted()
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isFailed()
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isTimedOut()
    {
        return $this->status === self::STATUS_TIMEOUT;
    }

    public function getDurationAttribute()
    {
        if (! $this->sent_at) {
            return null;
        }

        $endTime = $this->completed_at ?: $this->failed_at ?: now();

        return $this->sent_at->diffInSeconds($endTime);
    }

    public function isExpired()
    {
        if (! $this->sent_at || ! $this->timeout) {
            return false;
        }

        return $this->sent_at->addSeconds($this->timeout) < now();
    }

    public function canRetry()
    {
        return $this->retry_count < $this->max_retries && $this->isFailed();
    }

    public function getPriorityLabelAttribute()
    {
        return match ($this->priority) {
            self::PRIORITY_LOW => 'Low',
            self::PRIORITY_NORMAL => 'Normal',
            self::PRIORITY_HIGH => 'High',
            self::PRIORITY_CRITICAL => 'Critical',
            default => 'Unknown'
        };
    }

    public function getStatusColorAttribute()
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'default',
            self::STATUS_SENT => 'primary',
            self::STATUS_ACKNOWLEDGED => 'warning',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_FAILED => 'danger',
            self::STATUS_TIMEOUT => 'danger',
            self::STATUS_CANCELLED => 'default',
            default => 'default'
        };
    }

    public function send()
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
        ]);

        // Here you would integrate with your IoT platform/MQTT broker
        // to actually send the command to the device

        return $this;
    }

    public function acknowledge($response = null)
    {
        $this->update([
            'status' => self::STATUS_ACKNOWLEDGED,
            'acknowledged_at' => now(),
            'response' => $response,
        ]);
    }

    public function complete($response = null)
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
            'response' => $response,
        ]);
    }

    public function fail($errorMessage = null)
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'failed_at' => now(),
            'error_message' => $errorMessage,
        ]);
    }

    public function timeout()
    {
        $this->update([
            'status' => self::STATUS_TIMEOUT,
            'failed_at' => now(),
            'error_message' => 'Command timed out after '.$this->timeout.' seconds',
        ]);
    }

    public function cancel()
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
        ]);
    }

    public function retry()
    {
        if (! $this->canRetry()) {
            return false;
        }

        $this->increment('retry_count');
        $this->update([
            'status' => self::STATUS_PENDING,
            'sent_at' => null,
            'acknowledged_at' => null,
            'completed_at' => null,
            'failed_at' => null,
            'response' => null,
            'error_message' => null,
        ]);

        return true;
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeHighPriority($query)
    {
        return $query->where('priority', '>=', self::PRIORITY_HIGH);
    }

    public function scopeScheduled($query)
    {
        return $query->whereNotNull('scheduled_for');
    }

    public function scopeDue($query)
    {
        return $query->where('scheduled_for', '<=', now())
            ->where('status', self::STATUS_PENDING);
    }

    public function scopeExpired($query)
    {
        return $query->where('status', self::STATUS_SENT)
            ->whereNotNull('sent_at')
            ->whereNotNull('timeout')
            ->whereRaw('DATE_ADD(sent_at, INTERVAL timeout SECOND) < NOW()');
    }

    public function scopeByDevice($query, $deviceId)
    {
        return $query->where('device_id', $deviceId);
    }

    public function scopeByCommand($query, $commandName)
    {
        return $query->where('command_name', $commandName);
    }
}
