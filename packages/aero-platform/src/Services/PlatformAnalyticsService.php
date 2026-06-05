<?php

declare(strict_types=1);

namespace Aero\Platform\Services;

use Aero\Platform\Models\PlatformMetricDaily;
use Aero\Platform\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PlatformAnalyticsService
{
    public function revenue(string $from, string $to, string $bucket = 'day'): array
    {
        $rows = PlatformMetricDaily::range($from, $to);

        $trend = $rows->groupBy(fn ($r) => $this->bucketKey($r->date, $bucket))
            ->map(fn ($group, $key) => [
                'period' => $key,
                'mrr' => (float) $group->avg('mrr'),
                'revenue' => (float) $group->sum('total_revenue'),
            ])->values()->all();

        // ARCH NOTE: Plan MRR (plan subscription revenue only).
        $byPlan = DB::connection('central')->table('subscriptions')
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->where('subscriptions.status', 'active')
            ->select('plans.name', DB::raw('count(*) as tenants'), DB::raw('sum(plans.price_monthly) as mrr'))
            ->groupBy('plans.name')->get()->toArray();

        // ARCH NOTE: Product MRR is independent. product_subscriptions stores a
        // single `amount` per `billing_cycle`; normalise to a monthly figure so it
        // is comparable to plan MRR (plans.price_monthly).
        $byProduct = DB::connection('central')->table('product_subscriptions')
            ->join('products', 'product_subscriptions.product_id', '=', 'products.id')
            ->where('product_subscriptions.status', 'active')
            ->select('products.name', DB::raw('count(*) as tenants'), DB::raw(
                "sum(case product_subscriptions.billing_cycle "
                ."when 'annual' then product_subscriptions.amount / 12 "
                ."when 'yearly' then product_subscriptions.amount / 12 "
                ."when 'quarterly' then product_subscriptions.amount / 3 "
                ."else product_subscriptions.amount end) as mrr"
            ))
            ->groupBy('products.name')->get()->toArray();

        return [
            'trend' => $trend,
            'by_plan' => $byPlan,
            'by_product' => $byProduct,
            'churn' => [
                'churned' => (int) $rows->sum('churned_tenants'),
                'new' => (int) $rows->sum('new_tenants'),
            ],
        ];
    }

    public function tenantAnalytics(string $from, string $to): array
    {
        $rows = PlatformMetricDaily::range($from, $to);

        return [
            'signup_trend' => $rows->map(fn ($r) => [
                'date' => $r->date->toDateString(),
                'new_tenants' => $r->new_tenants,
                'churned' => $r->churned_tenants,
            ])->all(),
            'plan_distribution' => $this->planDistribution(),
            'retention' => $this->retentionMatrix(),
        ];
    }

    /**
     * Build plan distribution by joining tenants → subscriptions (polymorphic)
     * → plans.
     *
     * Plan 03 T1 — closes the broken `tenants.plan_id` query identified in
     * the Phase 1 audit. The `plan_id` column on `tenants` was removed when
     * subscriptions became polymorphic via billable_id/billable_type, but
     * this method still SELECTed it — production threw "Unknown column 'plan_id'"
     * (MySQL strict) or returned a single NULL row (MySQL relaxed).
     *
     * Returns: [['plan_id' => int, 'plan_name' => string, 'count' => int], ...]
     */
    private function planDistribution(): array
    {
        return DB::connection('central')->table('subscriptions')
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->join('tenants', function ($j) {
                $j->on('tenants.id', '=', 'subscriptions.billable_id')
                  ->where('subscriptions.billable_type', '=', Tenant::class);
            })
            ->where('subscriptions.status', 'active')
            ->select(
                'plans.id as plan_id',
                'plans.name as plan_name',
                DB::raw('count(DISTINCT tenants.id) as count')
            )
            ->groupBy('plans.id', 'plans.name')
            ->orderBy('plans.id')
            ->get()
            ->map(fn ($r) => [
                'plan_id'   => (int) $r->plan_id,
                'plan_name' => (string) $r->plan_name,
                'count'     => (int) $r->count,
            ])
            ->all();
    }

    public function usageAnalytics(): array
    {
        return [
            'top_tenants_by_storage' => DB::connection('central')->table('tenant_stats')
                ->select('tenant_id', DB::raw('max(storage_mb) as storage'))
                ->groupBy('tenant_id')
                ->orderByDesc('storage')->limit(10)->get()->toArray(),
            'top_tenants_by_api' => DB::connection('central')->table('tenant_stats')
                ->select('tenant_id', DB::raw('sum(api_requests) as api'))
                ->where('date', '>=', now()->subDays(30)->toDateString())
                ->groupBy('tenant_id')
                ->orderByDesc('api')->limit(10)->get()->toArray(),
        ];
    }

    private function bucketKey(Carbon $date, string $bucket): string
    {
        return match ($bucket) {
            'week' => $date->copy()->startOfWeek()->toDateString(),
            'month' => $date->format('Y-m'),
            default => $date->toDateString(),
        };
    }

    private function retentionMatrix(): array
    {
        // Simplified cohort retention by signup month over 6 months
        $cohorts = DB::connection('central')->table('tenants')
            ->select(DB::raw("DATE_FORMAT(created_at, '%Y-%m') as cohort"), DB::raw('count(*) as size'))
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('cohort')->orderBy('cohort')->get();

        return $cohorts->map(fn ($c) => [
            'cohort' => $c->cohort,
            'size' => (int) $c->size,
        ])->all();
    }
}
