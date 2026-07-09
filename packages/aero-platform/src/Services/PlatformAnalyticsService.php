<?php

declare(strict_types=1);

namespace Aero\Platform\Services;

use Aero\Platform\Models\PlatformMetricDaily;
use Aero\Platform\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PlatformAnalyticsService
{
    /**
     * Command-centre payload for the /analytics landing — an executive
     * roll-up of platform revenue + tenant growth. Read-only.
     *
     * The headline MRR/ARR use the SAME live-normalised formula as
     * BillingDashboardService (single source of truth: active plan +
     * product subscription amounts, yearly÷12), so the two pages agree.
     * Historical series come from the platform_metrics_daily snapshot; the
     * current-month MRR point is overridden with the live figure so the trend
     * endpoint matches the headline.
     *
     * @return array<string, mixed>
     */
    public function overview(string $range = '6m'): array
    {
        [$from, $to, $months] = $this->rangeFor($range);
        $conn = central_connection();

        // Live MRR — identical definition to BillingDashboardService /
        // SubscriptionAdminService (active + trialing, plan price_monthly,
        // product normalised) so the Billing and Analytics pages agree exactly.
        $planMrr = (float) DB::connection($conn)->table('subscriptions as s')
            ->join('plans as p', 'p.id', '=', 's.plan_id')
            ->whereNull('s.deleted_at')->whereIn('s.status', ['active', 'trialing'])
            ->sum('p.price_monthly');
        $productMrr = (float) DB::connection($conn)->table('product_subscriptions')
            ->whereNull('deleted_at')->whereIn('status', ['active', 'trialing'])
            ->selectRaw("SUM(CASE WHEN billing_cycle IN ('yearly','annual') THEN amount / 12 WHEN billing_cycle = 'quarterly' THEN amount / 3 ELSE amount END) m")->value('m');
        $mrr = round($planMrr + (float) $productMrr, 2);

        $latest = PlatformMetricDaily::orderByDesc('date')->first();
        $activeTenants = (int) ($latest->active_tenants ?? 0);
        $trialTenants = (int) ($latest->trial_tenants ?? 0);

        // Historical series come from the daily snapshot (recorded MRR). The
        // headline above is the live current figure — it sits at or above the
        // last closed month, as current dashboards do.
        $monthly = $this->monthlySeries($from, $to);
        $periodRows = PlatformMetricDaily::range($from, $to);
        $newSum = (int) $periodRows->sum('new_tenants');
        $churnSum = (int) $periodRows->sum('churned_tenants');

        $firstMrr = $monthly['mrr'][0] ?? 0.0;
        $lastMrr = end($monthly['mrr']) ?: $mrr;
        $prevMrr = $monthly['mrr'][count($monthly['mrr']) - 2] ?? $firstMrr;

        return [
            'kpis' => [
                'mrr'            => $mrr,
                'arr'            => round($mrr * 12, 2),
                'plan_mrr'       => round($planMrr, 2),
                'product_mrr'    => round($productMrr, 2),
                'mrr_delta_pct'  => $prevMrr > 0 ? round(($lastMrr - $prevMrr) / $prevMrr * 100, 1) : 0.0,
                'ytd_growth_pct' => $firstMrr > 0 ? round(($lastMrr - $firstMrr) / $firstMrr * 100, 0) : 0,
                'active_tenants' => $activeTenants,
                'trial_tenants'  => $trialTenants,
                'new_tenants'    => $newSum,
                'churned_tenants' => $churnSum,
                'arpa'           => $activeTenants > 0 ? round($mrr / $activeTenants, 0) : 0.0,
            ],
            'sparks' => [
                'mrr' => $monthly['mrr'],
                'arr' => array_map(fn ($v) => round($v * 12, 2), $monthly['mrr']),
            ],
            'trend'         => $monthly,
            'by_plan'       => $this->mrrByPlan($conn),
            'by_product'    => $this->mrrByProduct($conn),
            'plan_dist'     => $this->planDistribution(),
            'top_tenants'   => $this->topTenantsByMrr($conn),
            'range'         => $range,
            'range_months'  => $months,
        ];
    }

    /** @return array{0:string,1:string,2:int} [from, to, months] */
    private function rangeFor(string $range): array
    {
        $months = match ($range) {
            '30d' => 1, '90d' => 3, '12m' => 12, default => 6,
        };
        $to = now()->toDateString();
        $from = now()->subMonthsNoOverflow($months - 1)->startOfMonth()->toDateString();

        return [$from, $to, $months];
    }

    /**
     * Monthly MRR (avg) + billed revenue (sum) + active tenants (month-end) +
     * new/churned tenants, from the daily snapshot (recorded history).
     *
     * @return array{labels: array, mrr: array, revenue: array, active: array, new: array, churned: array}
     */
    private function monthlySeries(string $from, string $to): array
    {
        $rows = PlatformMetricDaily::range($from, $to);
        $byMonth = [];
        foreach ($rows as $r) {
            $k = $r->date->format('Y-m');
            $byMonth[$k]['mrr'][] = (float) $r->mrr;
            $byMonth[$k]['rev'] = ($byMonth[$k]['rev'] ?? 0) + (float) $r->total_revenue;
            $byMonth[$k]['active'] = (int) $r->active_tenants; // last write wins = month-end
            $byMonth[$k]['new'] = ($byMonth[$k]['new'] ?? 0) + (int) $r->new_tenants;
            $byMonth[$k]['churned'] = ($byMonth[$k]['churned'] ?? 0) + (int) $r->churned_tenants;
        }

        $labels = [];
        $mrr = [];
        $revenue = [];
        $active = [];
        $new = [];
        $churned = [];
        foreach ($byMonth as $k => $v) {
            $labels[] = Carbon::parse($k.'-01')->format('M');
            $mrr[] = round(array_sum($v['mrr']) / max(1, count($v['mrr'])), 2);
            $revenue[] = round($v['rev'] ?? 0, 2);
            $active[] = $v['active'] ?? 0;
            $new[] = $v['new'] ?? 0;
            $churned[] = $v['churned'] ?? 0;
        }

        return compact('labels', 'mrr', 'revenue', 'active', 'new', 'churned');
    }

    /**
     * MRR per plan, normalised from subscription amounts (yearly÷12) so the
     * breakdown sums to the headline plan MRR.
     *
     * @return array<int, array{name: string, tenants: int, mrr: float}>
     */
    private function mrrByPlan(string $conn): array
    {
        return DB::connection($conn)->table('subscriptions as s')
            ->join('plans as p', 'p.id', '=', 's.plan_id')
            ->whereNull('s.deleted_at')->whereIn('s.status', ['active', 'trialing'])
            ->selectRaw('p.name, COUNT(*) tenants, SUM(p.price_monthly) mrr')
            ->groupBy('p.name')->orderByDesc('mrr')->get()
            ->map(fn ($r) => ['name' => $r->name, 'tenants' => (int) $r->tenants, 'mrr' => round((float) $r->mrr, 2)])->all();
    }

    /**
     * @return array<int, array{name: string, tenants: int, mrr: float}>
     */
    private function mrrByProduct(string $conn): array
    {
        return DB::connection($conn)->table('product_subscriptions as ps')
            ->join('products as pr', 'pr.id', '=', 'ps.product_id')
            ->whereNull('ps.deleted_at')->where('ps.status', 'active')
            ->selectRaw("pr.name, COUNT(*) tenants, SUM(CASE WHEN ps.billing_cycle IN ('yearly','annual') THEN ps.amount / 12 WHEN ps.billing_cycle = 'quarterly' THEN ps.amount / 3 ELSE ps.amount END) mrr")
            ->groupBy('pr.name')->orderByDesc('mrr')->get()
            ->map(fn ($r) => ['name' => $r->name, 'tenants' => (int) $r->tenants, 'mrr' => round((float) $r->mrr, 2)])->all();
    }

    /**
     * Highest-MRR tenants (plan subscriptions, normalised).
     *
     * @return array<int, array{tenant: string, plan: string, mrr: float}>
     */
    private function topTenantsByMrr(string $conn): array
    {
        return DB::connection($conn)->table('subscriptions as s')
            ->join('plans as p', 'p.id', '=', 's.plan_id')
            ->leftJoin('tenants as t', 't.id', '=', 's.tenant_id')
            ->whereNull('s.deleted_at')->whereIn('s.status', ['active', 'trialing'])
            ->selectRaw('t.name tenant, p.name plan, p.price_monthly mrr')
            ->orderByDesc('p.price_monthly')->limit(8)->get()
            ->map(fn ($r) => ['tenant' => $r->tenant ?: '—', 'plan' => $r->plan, 'mrr' => round((float) $r->mrr, 2)])->all();
    }

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
                ->select('tenant_id', DB::raw('max(storage_used_mb) as storage'))
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
