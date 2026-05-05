<?php

namespace Aero\Core\Services;

use Aero\Core\Models\Backup;
use Aero\Core\Models\BackupConfiguration;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Backup\BackupDestination\BackupDestination;
use Spatie\Backup\Tasks\Backup\BackupJob;
use Spatie\Backup\Tasks\Backup\Manifest;

class BackupService
{
    /**
     * Create a manual backup.
     */
    public function createBackup(string $name = null, string $type = 'full', int $tenantId = null): Backup
    {
        $config = BackupConfiguration::getDefault();
        
        $backup = Backup::create([
            'backup_id' => Str::uuid(),
            'name' => $name ?? 'backup-' . now()->format('Y-m-d-H-i-s'),
            'type' => $type,
            'status' => 'pending',
            'tenant_id' => $tenantId,
            'storage_driver' => $config->storage_driver,
            'encryption_status' => $config->encryption_enabled,
            'created_at' => now(),
            'expires_at' => now()->addDays($config->retention_days),
        ]);

        try {
            $backupPath = $this->executeBackup($backup, $config);
            
            $backup->update([
                'status' => 'completed',
                'storage_path' => $backupPath,
                'completed_at' => now(),
                'size' => Storage::disk($config->storage_driver)->size($backupPath),
            ]);

            $config->update(['last_backup_at' => now()]);

            Log::info("Backup created successfully", ['backup_id' => $backup->backup_id]);
            
            return $backup;
        } catch (\Exception $e) {
            $backup->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            Log::error("Backup failed", ['backup_id' => $backup->backup_id, 'error' => $e->getMessage()]);
            
            throw $e;
        }
    }

    /**
     * Execute the backup using spatie/laravel-backup.
     */
    protected function executeBackup(Backup $backup, BackupConfiguration $config): string
    {
        $backupJob = new BackupJob(
            config('backup.backup.destination.disks') ?? [$config->storage_driver],
            config('backup.backup.source.files') ?? [],
            config('backup.backup.source.databases') ?? [],
            config('backup.backup.destination.filename_prefix') ?? $backup->name,
            config('backup.backup.destination.password') ?? null,
            config('backup.backup.notifications') ?? [],
            $config->encryption_enabled,
        );

        $backupJob->run();

        $backupDestination = BackupDestination::create($config->storage_driver, config('backup.backup.name'));
        $backupPath = $backupDestination->newestBackup()->path();

        return $backupPath;
    }

    /**
     * List all backups.
     */
    public function listBackups(array $filters = [])
    {
        $query = Backup::query();

        if (isset($filters['tenant_id'])) {
            $query->forTenant($filters['tenant_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        return $query->latest('created_at')->paginate(20);
    }

    /**
     * Get a specific backup.
     */
    public function getBackup(string $backupId): Backup
    {
        return Backup::where('backup_id', $backupId)->firstOrFail();
    }

    /**
     * Delete a backup.
     */
    public function deleteBackup(string $backupId): bool
    {
        $backup = $this->getBackup($backupId);

        if ($backup->storage_path) {
            Storage::disk($backup->storage_driver)->delete($backup->storage_path);
        }

        return $backup->delete();
    }

    /**
     * Download a backup.
     */
    public function downloadBackup(string $backupId)
    {
        $backup = $this->getBackup($backupId);

        if (!Storage::disk($backup->storage_driver)->exists($backup->storage_path)) {
            throw new \Exception('Backup file not found');
        }

        return Storage::disk($backup->storage_driver)->download($backup->storage_path, $backup->name . '.zip');
    }

    /**
     * Apply retention policy - delete old backups.
     */
    public function applyRetentionPolicy(): int
    {
        $config = BackupConfiguration::getDefault();
        $deletedCount = 0;

        $expiredBackups = Backup::where('expires_at', '<', now())
            ->where('status', 'completed')
            ->get();

        foreach ($expiredBackups as $backup) {
            try {
                $this->deleteBackup($backup->backup_id);
                $deletedCount++;
            } catch (\Exception $e) {
                Log::error("Failed to delete expired backup", ['backup_id' => $backup->backup_id, 'error' => $e->getMessage()]);
            }
        }

        Log::info("Retention policy applied", ['deleted_count' => $deletedCount]);
        
        return $deletedCount;
    }

    /**
     * Get backup statistics.
     */
    public function getBackupStats(): array
    {
        $totalBackups = Backup::count();
        $successfulBackups = Backup::successful()->count();
        $failedBackups = Backup::failed()->count();
        $pendingBackups = Backup::pending()->count();
        
        $totalSize = Backup::successful()->sum('size');
        
        $lastBackup = Backup::successful()->latest('completed_at')->first();
        
        return [
            'total_backups' => $totalBackups,
            'successful_backups' => $successfulBackups,
            'failed_backups' => $failedBackups,
            'pending_backups' => $pendingBackups,
            'total_size' => $totalSize,
            'success_rate' => $totalBackups > 0 ? round(($successfulBackups / $totalBackups) * 100, 2) : 0,
            'last_backup_at' => $lastBackup?->completed_at,
        ];
    }
}
