<?php

declare(strict_types=1);

namespace Aero\Platform\Jobs\Infra;

use Aero\Platform\Models\Infra\BackupRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Restores a backup for a target tenant.
 *
 * In v1 this is a stub. Production implementation should
 * download from storage and restore into the tenant DB connection.
 */
class RestoreBackupJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 2;

    public int $backoff = 300;

    public function __construct(
        private readonly int $backupRecordId,
        private readonly string $targetTenantId,
    ) {}

    public function handle(): void
    {
        $record = BackupRecord::find($this->backupRecordId);

        if (! $record) {
            Log::warning("RestoreBackupJob: backup record {$this->backupRecordId} not found");

            return;
        }

        Log::info("RestoreBackupJob: restoring backup {$this->backupRecordId} to tenant {$this->targetTenantId} from {$record->storage_path}");

        // In production: stream backup from storage and restore to tenant DB here.
    }
}
