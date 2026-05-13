<?php

namespace Aero\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class BackupConfiguration extends TenantModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'storage_driver',
        'schedule_frequency',
        'retention_days',
        'encryption_enabled',
        'notification_email',
        'included_files',
        'excluded_files',
        'backup_type',
        'active',
        'last_backup_at',
    ];

    protected $casts = [
        'encryption_enabled' => 'boolean',
        'active' => 'boolean',
        'included_files' => 'array',
        'excluded_files' => 'array',
        'last_backup_at' => 'datetime',
    ];

    /**
     * Get the backups for this configuration.
     */
    public function backups()
    {
        return $this->hasMany(Backup::class, 'configuration_id');
    }

    /**
     * Scope a query to only include active configurations.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Get the default configuration.
     */
    public static function getDefault()
    {
        return self::firstOrCreate(
            ['name' => 'default'],
            [
                'storage_driver' => 'local',
                'schedule_frequency' => 'daily',
                'retention_days' => 30,
                'encryption_enabled' => false,
                'backup_type' => 'full',
                'active' => true,
            ]
        );
    }
}
