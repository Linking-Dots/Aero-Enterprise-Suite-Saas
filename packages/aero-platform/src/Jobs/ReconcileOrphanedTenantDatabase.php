<?php

declare(strict_types=1);

namespace Aero\Platform\Jobs;

use Aero\Platform\Support\TenantDatabaseDropGuard;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Reconcile an orphaned tenant database (Axis A A4+A11).
 *
 * Teardown (GDPR forget / retention purge) deletes the central tenant rows in a
 * committed transaction BEFORE dropping the database (DDL can't participate in
 * the transaction). If the drop then fails, the rows are gone but the database
 * lingers — an orphan. This job retries the guarded drop so an orphan is cleaned
 * up rather than silently leaked; if it still cannot drop, it alerts loudly for
 * manual intervention.
 */
class ReconcileOrphanedTenantDatabase implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 5;

    /** @var array<int,int> */
    public array $backoff = [60, 300, 900, 3600, 7200];

    public function __construct(public readonly string $databaseName)
    {
        $this->onQueue('maintenance'); // Axis C C3
    }

    public function handle(): void
    {
        // Re-assert safety: never let a reconciliation drop the wrong database.
        TenantDatabaseDropGuard::assertSafe($this->databaseName);

        DB::statement("DROP DATABASE IF EXISTS `{$this->databaseName}`");

        Log::info('Reconciled orphaned tenant database', [
            'database' => $this->databaseName,
        ]);
    }

    public function failed(?Throwable $e): void
    {
        Log::critical(
            'Orphaned tenant database could NOT be reconciled after teardown — manual cleanup required.',
            [
                'database' => $this->databaseName,
                'error' => $e?->getMessage(),
            ]
        );
    }
}
