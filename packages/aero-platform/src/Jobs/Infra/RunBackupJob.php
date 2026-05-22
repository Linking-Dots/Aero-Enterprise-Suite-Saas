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
 * Executes a backup for a given BackupRecord.
 *
 * In v1 this is a stub. Production implementation should
 * snapshot the tenant DB and upload to the configured storage backend.
 */
class RunBackupJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 120;

    public function __construct(private readonly int $recordId) {}

    public function handle(): void
    {
        $record = BackupRecord::find($this->recordId);

        if (! $record) {
            Log::warning("RunBackupJob: record {$this->recordId} not found");

            return;
        }

        // In production: stream tenant DB dump to storage backend here.
        $storagePath = "backups/{$record->tenant_id}/".now()->format('Y/m/d')."/{$record->id}.sql.gz";

        $record->update([
            'status' => 'completed',
            'storage_path' => $storagePath,
            'completed_at' => now(),
        ]);
    }
}
