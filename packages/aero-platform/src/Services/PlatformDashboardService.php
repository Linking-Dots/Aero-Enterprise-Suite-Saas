<?php

declare(strict_types=1);

namespace Aero\Platform\Services;

use Aero\Platform\Models\PlatformMetricDaily;
use Aero\Platform\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PlatformDashboardService
{
    public function stats(): array
    {
        return Cache::remember('platform:dashboard:stats', 60, function () {
            $today = Carbon::today()->toDateString();
            $latest = PlatformMetricDaily::latest('date')->first();
            $prev = PlatformMetricDaily::where('date', '<', $latest?->date ?? $today)
                ->orderByDesc('date')->first();

            $churnRate = $this->churnRate(30);

            // ARCH NOTE: MRR/ARR = plan_mrr + product_mrr per locked architecture.
            // Plan subscriptions and product subscriptions are independent revenue streams.
            return [
                'mrr' => (float) ($latest->mrr ?? 0),
                'mrr_growth' => $this->growth(
                    $latest !== null ? (float) $latest->mrr : null,
                    $prev !== null ? (float) $prev->mrr : null,
                ),
                'plan_mrr' => (float) ($latest->plan_mrr ?? 0),
                'product_mrr' => (float) ($latest->product_mrr ?? 0),
                'arr' => (float) ($latest->arr ?? 0),
                'plan_arr' => (float) ($latest->plan_arr ?? 0),
                'product_arr' => (float) ($latest->product_arr ?? 0),
                'active_tenants' => (int) ($latest->active_tenants ?? Tenant::where('status', Tenant::STATUS_ACTIVE)->count()),
                'trial_tenants' => (int) ($latest->trial_tenants ?? 0),
                'churned_tenants' => (int) ($latest->churned_tenants ?? 0),
                'churn_rate_pct' => $churnRate,
                'new_tenants_7d' => Tenant::where('created_at', '>=', now()->subDays(7))->count(),
                'total_revenue' => (float) ($latest->total_revenue ?? 0),
            ];
        });
    }

    public function recentTenants(int $limit = 10): array
    {
        return Tenant::query()
            ->select(['id', 'name', 'subdomain', 'status', 'created_at'])
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function systemHealth(): array
    {
        return [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'queue' => $this->checkQueue(),
            'storage' => $this->checkStorage(),
        ];
    }

    private function growth(?float $current, ?float $previous): float
    {
        if (! $previous || $previous == 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }

    private function churnRate(int $days): float
    {
        $start = now()->subDays($days)->toDateString();
        $churned = (int) PlatformMetricDaily::where('date', '>=', $start)->sum('churned_tenants');
        $active = max(1, (int) (PlatformMetricDaily::latest('date')->value('active_tenants') ?? 1));

        return round(($churned / $active) * 100, 2);
    }

    private function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::connection('central')->select('select 1');

            return ['status' => 'ok', 'latency_ms' => round((microtime(true) - $start) * 1000, 2)];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function checkCache(): array
    {
        try {
            Cache::put('platform:health:check', 1, 5);

            return ['status' => Cache::get('platform:health:check') === 1 ? 'ok' : 'error'];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function checkQueue(): array
    {
        try {
            $pending = DB::connection('central')->table('jobs')->count();
            $failed = DB::connection('central')->table('failed_jobs')->count();

            return ['status' => $failed > 50 ? 'warn' : 'ok', 'pending' => $pending, 'failed' => $failed];
        } catch (\Throwable $e) {
            return ['status' => 'unknown'];
        }
    }

    private function checkStorage(): array
    {
        $free = @disk_free_space(storage_path());
        $total = @disk_total_space(storage_path());
        if (! $free || ! $total) {
            return ['status' => 'unknown'];
        }
        $usedPct = round((($total - $free) / $total) * 100, 1);

        return [
            'status' => $usedPct > 90 ? 'warn' : 'ok',
            'used_pct' => $usedPct,
            'free_gb' => round($free / 1024 / 1024 / 1024, 2),
        ];
    }
}
