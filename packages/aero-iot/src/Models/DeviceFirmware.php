<?php

namespace Aero\IoT\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeviceFirmware extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'iot_device_firmware';

    protected $fillable = [
        'device_id', 'firmware_version', 'firmware_file', 'firmware_size',
        'checksum', 'release_notes', 'is_stable', 'min_hardware_version',
        'max_hardware_version', 'update_status', 'update_started_at',
        'update_completed_at', 'update_progress', 'error_message',
        'rollback_version', 'created_by',
    ];

    protected $casts = [
        'device_id' => 'integer',
        'firmware_size' => 'integer',
        'is_stable' => 'boolean',
        'update_started_at' => 'datetime',
        'update_completed_at' => 'datetime',
        'update_progress' => 'integer',
        'created_by' => 'integer',
    ];

    const STATUS_PENDING = 'pending';

    const STATUS_DOWNLOADING = 'downloading';

    const STATUS_DOWNLOADED = 'downloaded';

    const STATUS_INSTALLING = 'installing';

    const STATUS_COMPLETED = 'completed';

    const STATUS_FAILED = 'failed';

    const STATUS_ROLLED_BACK = 'rolled_back';

    const STATUS_CANCELLED = 'cancelled';

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isCompleted()
    {
        return $this->update_status === self::STATUS_COMPLETED;
    }

    public function isFailed()
    {
        return $this->update_status === self::STATUS_FAILED;
    }

    public function isInProgress()
    {
        return in_array($this->update_status, [
            self::STATUS_DOWNLOADING,
            self::STATUS_INSTALLING,
        ]);
    }

    public function getDurationAttribute()
    {
        if (! $this->update_started_at) {
            return null;
        }

        $endTime = $this->update_completed_at ?: now();

        return $this->update_started_at->diffInMinutes($endTime);
    }

    public function getStatusColorAttribute()
    {
        return match ($this->update_status) {
            self::STATUS_PENDING => 'default',
            self::STATUS_DOWNLOADING => 'primary',
            self::STATUS_DOWNLOADED => 'primary',
            self::STATUS_INSTALLING => 'warning',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_FAILED => 'danger',
            self::STATUS_ROLLED_BACK => 'warning',
            self::STATUS_CANCELLED => 'default',
            default => 'default'
        };
    }

    public function getFormattedSizeAttribute()
    {
        if (! $this->firmware_size) {
            return 'Unknown';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->firmware_size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2).' '.$units[$unit];
    }

    public function isCompatible($hardwareVersion = null)
    {
        $hwVersion = $hardwareVersion ?: $this->device->firmware_version;
        if (! $hwVersion) {
            return true;
        }

        if ($this->min_hardware_version && version_compare($hwVersion, $this->min_hardware_version, '<')) {
            return false;
        }

        if ($this->max_hardware_version && version_compare($hwVersion, $this->max_hardware_version, '>')) {
            return false;
        }

        return true;
    }

    public function verifyChecksum($fileContent)
    {
        if (! $this->checksum) {
            return true;
        }

        $calculatedChecksum = hash('sha256', $fileContent);

        return $calculatedChecksum === $this->checksum;
    }

    public function startUpdate()
    {
        $this->update([
            'update_status' => self::STATUS_DOWNLOADING,
            'update_started_at' => now(),
            'update_progress' => 0,
        ]);
    }

    public function updateProgress($progress, $status = null)
    {
        $updateData = ['update_progress' => $progress];

        if ($status) {
            $updateData['update_status'] = $status;
        }

        $this->update($updateData);
    }

    public function complete()
    {
        $this->update([
            'update_status' => self::STATUS_COMPLETED,
            'update_completed_at' => now(),
            'update_progress' => 100,
        ]);

        // Update device firmware version
        $this->device->update([
            'firmware_version' => $this->firmware_version,
        ]);
    }

    public function fail($errorMessage = null)
    {
        $this->update([
            'update_status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
        ]);
    }

    public function rollback()
    {
        if ($this->rollback_version) {
            $this->device->update([
                'firmware_version' => $this->rollback_version,
            ]);
        }

        $this->update([
            'update_status' => self::STATUS_ROLLED_BACK,
        ]);
    }

    public function cancel()
    {
        $this->update([
            'update_status' => self::STATUS_CANCELLED,
        ]);
    }

    public function scopeCompleted($query)
    {
        return $query->where('update_status', self::STATUS_COMPLETED);
    }

    public function scopeFailed($query)
    {
        return $query->where('update_status', self::STATUS_FAILED);
    }

    public function scopeInProgress($query)
    {
        return $query->whereIn('update_status', [
            self::STATUS_DOWNLOADING,
            self::STATUS_INSTALLING,
        ]);
    }

    public function scopeStable($query)
    {
        return $query->where('is_stable', true);
    }

    public function scopeByDevice($query, $deviceId)
    {
        return $query->where('device_id', $deviceId);
    }

    public function scopeByVersion($query, $version)
    {
        return $query->where('firmware_version', $version);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
