<?php

declare(strict_types=1);

namespace Aero\Platform\Support;

use Aero\Platform\Jobs\ReconcileOrphanedTenantDatabase;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Ordered, atomic-as-possible tenant teardown (Axis A A4+A11).
 *
 * DROP DATABASE is DDL — MySQL implicitly commits it, so wrapping it in
 * DB::transaction() alongside row deletes is illusory: if a later step throws,
 * the dropped DB cannot be rolled back. Both GDPR forget and retention purge
 * previously dropped the DB FIRST inside a "transaction", risking either an
 * orphaned row (pointing at a dropped DB) or an orphaned DB.
 *
 * This sequencer enforces the only safe ordering:
 *   1. Guard the database name (shared TenantDatabaseDropGuard).
 *   2. Delete the central rows in a real transaction and COMMIT.
 *   3. Drop the database OUTSIDE any transaction.
 *   4. On drop failure, enqueue reconciliation — never leave a committed-row
 *      deletion paired with a live database and no cleanup path.
 *
 * The drop mechanism is supplied by the caller (raw DROP for dedicated mode,
 * Stancl tenants:delete for cPanel/managed mode) so this stays mode-agnostic.
 */
final class TenantTeardownSequencer
{
    /**
     * @param  string|null  $databaseName     Physical DB name, or null when there is none to drop.
     * @param  Closure():void  $deleteCentralRows  Deletes central tenant rows; runs in a committed transaction.
     * @param  Closure():void  $dropDatabase       Drops the tenant database; runs after the commit.
     * @param  array<string,mixed>  $context        Logging context.
     */
    public function teardown(
        ?string $databaseName,
        Closure $deleteCentralRows,
        Closure $dropDatabase,
        array $context = [],
    ): void {
        if ($databaseName !== null) {
            TenantDatabaseDropGuard::assertSafe($databaseName);
        }

        // 1. Central-row deletions commit FIRST, before any DDL.
        DB::transaction($deleteCentralRows);

        if ($databaseName === null) {
            return;
        }

        // 2. Drop the database outside the transaction. On failure, the rows are
        //    already gone — reconcile the now-orphaned database rather than leak it.
        try {
            $dropDatabase();
        } catch (Throwable $e) {
            Log::error('Tenant database drop failed after central rows were committed; enqueuing reconciliation.', array_merge($context, [
                'database' => $databaseName,
                'error' => $e->getMessage(),
            ]));

            ReconcileOrphanedTenantDatabase::dispatch($databaseName);

            throw $e;
        }
    }
}
