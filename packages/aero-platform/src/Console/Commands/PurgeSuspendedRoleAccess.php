<?php

declare(strict_types=1);

namespace Aero\Platform\Console\Commands;

use Aero\HRMAC\Models\RoleModuleAccess;
use Aero\Platform\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Hard-deletes suspended role_module_access rows after the grace period expires.
 *
 * Audit D17 — soft-suspend role grants on product unsubscribe.
 *
 * When a product subscription is cancelled, SuspendUnsubscribedRoleAccess marks
 * the affected role grants as status='suspended' with suspended_at=now(). If the
 * customer does NOT re-subscribe within the grace window (default 30 days), this
 * command permanently removes those rows so they cannot be restored without manual
 * RBAC reconfiguration.
 *
 * Schedule: daily via AeroPlatformServiceProvider.
 */
class PurgeSuspendedRoleAccess extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hrmac:purge-suspended-grants
                            {--days=30 : Grace period in days — rows suspended longer than this are hard-deleted}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hard-delete suspended role_module_access rows that have exceeded the grace period (default 30 days) across all tenants';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');

        if ($days < 1) {
            $this->error('--days must be at least 1.');

            return self::FAILURE;
        }

        $cutoff = now()->subDays($days);
        $tenants = Tenant::all();

        if ($tenants->isEmpty()) {
            $this->info('No tenants found — nothing to purge.');

            return self::SUCCESS;
        }

        $this->info("Purging suspended role grants older than {$days} days (cutoff: {$cutoff->toDateTimeString()}) across {$tenants->count()} tenant(s)...");

        $totalPurged = 0;
        $failed = 0;

        foreach ($tenants as $tenant) {
            try {
                tenancy()->initialize($tenant);

                $count = RoleModuleAccess::query()
                    ->where('status', RoleModuleAccess::STATUS_SUSPENDED)
                    ->where('suspended_at', '<', $cutoff)
                    ->delete();

                if ($count > 0) {
                    $this->line("  Tenant {$tenant->id}: purged {$count} suspended grant(s).");

                    Log::info('PurgeSuspendedRoleAccess: purged grants', [
                        'tenant_id' => $tenant->id,
                        'rows_deleted' => $count,
                        'grace_days' => $days,
                    ]);
                }

                $totalPurged += $count;
            } catch (Throwable $e) {
                $failed++;

                $this->error("  Tenant {$tenant->id}: FAILED — {$e->getMessage()}");

                Log::error('PurgeSuspendedRoleAccess failed for tenant', [
                    'tenant_id' => $tenant->id,
                    'exception' => $e->getMessage(),
                ]);
            } finally {
                if (tenancy()->initialized) {
                    tenancy()->end();
                }
            }
        }

        $this->newLine();
        $this->info("Done. Total purged: {$totalPurged}. Tenants with errors: {$failed}.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
