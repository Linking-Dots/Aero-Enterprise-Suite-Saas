<?php

namespace Aero\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Backup extends TenantModel
{
    use HasFactory;

    protected $fillable = [
        'backup_id',
        'name',
        'type',
        'size',
        'status',
        'tenant_id',
        'storage_path',
        'storage_driver',
        'encryption_status',
        'created_at',
        'completed_at',
        'expires_at',
        'error_message',
        'metadata',
    ];

    protected $casts = [
        'size' => 'integer',
        'encryption_status' => 'boolean',
        'created_at' => 'datetime',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the tenant that owns the backup.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the configuration for this backup.
     */
    public function configuration()
    {
        return $this->belongsTo(BackupConfiguration::class, 'configuration_id');
    }

    /**
     * Scope a query to only include successful backups.
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope a query to only include failed backups.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope a query to only include pending backups.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include backups for a specific tenant.
     */
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Check if the backup is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Get the human-readable size.
     */
    public function getHumanReadableSize(): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $size = $this->size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2).' '.$units[$unit];
    }
}
