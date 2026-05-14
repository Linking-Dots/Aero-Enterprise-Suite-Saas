<?php

namespace Aero\IoT\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeviceMaintenance extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'iot_device_maintenance';

    protected $fillable = [
        'device_id', 'maintenance_type', 'title', 'description', 'priority',
        'scheduled_date', 'started_at', 'completed_at', 'estimated_duration',
        'actual_duration', 'status', 'performed_by', 'cost', 'parts_used',
        'notes', 'next_maintenance_date', 'maintenance_data',
    ];

    protected $casts = [
        'device_id' => 'integer',
        'scheduled_date' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'estimated_duration' => 'integer',
        'actual_duration' => 'integer',
        'performed_by' => 'integer',
        'cost' => 'decimal:2',
        'parts_used' => 'json',
        'next_maintenance_date' => 'datetime',
        'maintenance_data' => 'json',
    ];

    const TYPE_PREVENTIVE = 'preventive';

    const TYPE_CORRECTIVE = 'corrective';

    const TYPE_CALIBRATION = 'calibration';

    const TYPE_INSPECTION = 'inspection';

    const TYPE_CLEANING = 'cleaning';

    const TYPE_UPGRADE = 'upgrade';

    const TYPE_REPLACEMENT = 'replacement';

    const TYPE_EMERGENCY = 'emergency';

    const PRIORITY_LOW = 'low';

    const PRIORITY_MEDIUM = 'medium';

    const PRIORITY_HIGH = 'high';

    const PRIORITY_CRITICAL = 'critical';

    const STATUS_SCHEDULED = 'scheduled';

    const STATUS_IN_PROGRESS = 'in_progress';

    const STATUS_COMPLETED = 'completed';

    const STATUS_CANCELLED = 'cancelled';

    const STATUS_DELAYED = 'delayed';

    const STATUS_ON_HOLD = 'on_hold';

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function performer()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function isScheduled()
    {
        return $this->status === self::STATUS_SCHEDULED;
    }

    public function isInProgress()
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function isCompleted()
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isOverdue()
    {
        return $this->scheduled_date < now() && ! $this->isCompleted();
    }

    public function isDue($days = 7)
    {
        return $this->scheduled_date <= now()->addDays($days) && ! $this->isCompleted();
    }

    public function getPriorityColorAttribute()
    {
        return match ($this->priority) {
            self::PRIORITY_LOW => 'success',
            self::PRIORITY_MEDIUM => 'warning',
            self::PRIORITY_HIGH => 'danger',
            self::PRIORITY_CRITICAL => 'error',
            default => 'default'
        };
    }

    public function getStatusColorAttribute()
    {
        return match ($this->status) {
            self::STATUS_SCHEDULED => 'default',
            self::STATUS_IN_PROGRESS => 'primary',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_CANCELLED => 'danger',
            self::STATUS_DELAYED => 'warning',
            self::STATUS_ON_HOLD => 'warning',
            default => 'default'
        };
    }

    public function getDurationDifferenceAttribute()
    {
        if (! $this->estimated_duration || ! $this->actual_duration) {
            return null;
        }

        return $this->actual_duration - $this->estimated_duration;
    }

    public function getFormattedDurationAttribute()
    {
        if (! $this->actual_duration) {
            return null;
        }

        $hours = floor($this->actual_duration / 60);
        $minutes = $this->actual_duration % 60;

        return $hours > 0 ? "{$hours}h {$minutes}m" : "{$minutes}m";
    }

    public function getTotalCostAttribute()
    {
        $partsCost = 0;
        if (is_array($this->parts_used)) {
            $partsCost = collect($this->parts_used)
                ->sum(fn ($part) => ($part['quantity'] ?? 1) * ($part['unit_cost'] ?? 0));
        }

        return ($this->cost ?: 0) + $partsCost;
    }

    public function start(User $user)
    {
        $this->update([
            'status' => self::STATUS_IN_PROGRESS,
            'started_at' => now(),
            'performed_by' => $user->id,
        ]);
    }

    public function complete($notes = null, $nextMaintenanceDate = null)
    {
        $duration = null;
        if ($this->started_at) {
            $duration = now()->diffInMinutes($this->started_at);
        }

        $updateData = [
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
            'actual_duration' => $duration,
        ];

        if ($notes) {
            $updateData['notes'] = $notes;
        }

        if ($nextMaintenanceDate) {
            $updateData['next_maintenance_date'] = $nextMaintenanceDate;
        }

        $this->update($updateData);

        // Update device maintenance schedule if applicable
        if ($nextMaintenanceDate) {
            $schedule = $this->device->maintenance_schedule ?: [];
            $schedule[$this->maintenance_type] = $nextMaintenanceDate;
            $this->device->update(['maintenance_schedule' => $schedule]);
        }
    }

    public function cancel($reason = null)
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'notes' => $reason ? "Cancelled: {$reason}" : 'Cancelled',
        ]);
    }

    public function delay($newDate, $reason = null)
    {
        $this->update([
            'status' => self::STATUS_DELAYED,
            'scheduled_date' => $newDate,
            'notes' => $reason ? "Delayed: {$reason}" : 'Delayed',
        ]);
    }

    public function hold($reason = null)
    {
        $this->update([
            'status' => self::STATUS_ON_HOLD,
            'notes' => $reason ? "On Hold: {$reason}" : 'On Hold',
        ]);
    }

    public function addPart($partName, $quantity, $unitCost = null)
    {
        $parts = $this->parts_used ?: [];
        $parts[] = [
            'part_name' => $partName,
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'added_at' => now()->toISOString(),
        ];

        $this->update(['parts_used' => $parts]);
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    public function scopeOverdue($query)
    {
        return $query->where('scheduled_date', '<', now())
            ->whereNotIn('status', [self::STATUS_COMPLETED, self::STATUS_CANCELLED]);
    }

    public function scopeDue($query, $days = 7)
    {
        return $query->where('scheduled_date', '<=', now()->addDays($days))
            ->whereNotIn('status', [self::STATUS_COMPLETED, self::STATUS_CANCELLED]);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('maintenance_type', $type);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeByDevice($query, $deviceId)
    {
        return $query->where('device_id', $deviceId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('scheduled_date', [$startDate, $endDate]);
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('scheduled_date', [
            now()->startOfWeek(),
            now()->endOfWeek(),
        ]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereBetween('scheduled_date', [
            now()->startOfMonth(),
            now()->endOfMonth(),
        ]);
    }
}
