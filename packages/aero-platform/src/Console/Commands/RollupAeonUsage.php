<?php

declare(strict_types=1);

namespace Aero\Platform\Console\Commands;

use Aero\Platform\Models\AeonTenantUsage;
use Aero\Platform\Models\Tenant;
use Aero\Platform\Services\Quotas\QuotaEnforcementService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Roll up per-tenant AI usage + feedback into the central aeon_tenant_usage
 * summary for the fleet console. Runs IN each tenant's context (the only place
 * the tenant-scoped message counter + aeon_messages feedback are readable), then
 * writes the summary on the central connection. The console reads the summary
 * instead of fanning out per request.
 */
class RollupAeonUsage extends Command
{
    protected $signature = 'aeon:rollup';

    protected $description = "Roll up per-tenant AI usage + feedback into the central fleet summary";

    public function handle(QuotaEnforcementService $quotas): int
    {
        $period = now()->format('Y-m');
        $start = now()->startOfMonth();
        $tenants = Tenant::query()->whereNull('deleted_at')->get();

        $synced = 0;
        $failed = 0;

        foreach ($tenants as $tenant) {
            $used = 0;
            $up = 0;
            $down = 0;
            $last = null;

            // Tenant context: read the actual delivered messages + thumbs.
            try {
                tenancy()->initialize($tenant);
                if (Schema::hasTable('aeon_messages')) {
                    $used = (int) DB::table('aeon_messages')->where('role', 'assistant')->where('created_at', '>=', $start)->count();
                    $up = (int) DB::table('aeon_messages')->where('feedback', 1)->where('created_at', '>=', $start)->count();
                    $down = (int) DB::table('aeon_messages')->where('feedback', -1)->where('created_at', '>=', $start)->count();
                    $last = DB::table('aeon_messages')->max('created_at');
                }
            } catch (Throwable) {
                // tenant DB missing / not migrated — leave zeros
            } finally {
                if (tenancy()->initialized) {
                    tenancy()->end();
                }
            }

            // Central context: entitlement/limit/model + write the summary.
            try {
                // getAiSummary resolves the plan; plan-less tenants can throw —
                // fall back to a disabled summary so every tenant still gets a row.
                try {
                    $s = $quotas->getAiSummary($tenant);
                } catch (Throwable) {
                    $s = ['enabled' => false, 'limit' => 0, 'model' => 'flash'];
                }
                $planName = null;
                try {
                    $planName = optional($tenant->plan)->name;
                } catch (Throwable) {
                    // plan/subscription accessor unsafe for plan-less tenants
                }
                AeonTenantUsage::updateOrCreate(
                    ['tenant_id' => $tenant->id, 'period' => $period],
                    [
                        'enabled' => (bool) $s['enabled'],
                        'message_limit' => (int) $s['limit'],
                        'model' => (string) $s['model'],
                        'plan_name' => $planName,
                        'messages_used' => $used,
                        'feedback_up' => $up,
                        'feedback_down' => $down,
                        'last_used_at' => $last,
                        'synced_at' => now(),
                    ],
                );
                $synced++;
            } catch (Throwable $e) {
                $failed++;
                $this->warn("  {$tenant->id}: {$e->getMessage()}");
            }
        }

        \Illuminate\Support\Facades\Cache::forget('aeon:fleet_stats');
        $this->info("Rolled up {$synced} tenant(s) for {$period}. {$failed} failed.");

        return self::SUCCESS;
    }
}
