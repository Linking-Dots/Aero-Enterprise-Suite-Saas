<?php

namespace Aero\Core\Console\Commands;

use Aero\Core\Services\BackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RunBackup extends Command
{
    protected $signature = 'backup:run {--type=full : Backup type (full, database, files)} {--tenant-id= : Tenant ID for tenant-specific backup}';
    protected $description = 'Run a backup operation';

    protected $backupService;

    public function __construct(BackupService $backupService)
    {
        parent::__construct();
        $this->backupService = $backupService;
    }

    public function handle()
    {
        $this->info('Starting backup operation...');

        $type = $this->option('type');
        $tenantId = $this->option('tenant-id');

        try {
            $backup = $this->backupService->createBackup(
                name: 'scheduled-' . now()->format('Y-m-d-H-i-s'),
                type: $type,
                tenantId: $tenantId ? (int) $tenantId : null
            );

            $this->info("Backup created successfully: {$backup->backup_id}");
            $this->info("Status: {$backup->status}");
            $this->info("Size: {$backup->getHumanReadableSize()}");
            
            return 0;
        } catch (\Exception $e) {
            $this->error("Backup failed: {$e->getMessage()}");
            Log::error("Scheduled backup failed", ['error' => $e->getMessage()]);
            
            return 1;
        }
    }
}
