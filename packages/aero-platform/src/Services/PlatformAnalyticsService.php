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

        // ARCH NOTE: Product MRR is independent — sum product_subscriptions.price_monthly per product.
        $byProduct = DB::connection('central')->table('product_subscriptions')
            ->join('products', 'product_subscriptions.product_id', '=', 'products.id')
            ->where('product_subscriptions.status', 'active')
            ->select('products.name', DB::raw('count(*) as tenants'), DB::raw('sum(product_subscriptions.price_monthly) as mrr'))
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
            'plan_distribution' => Tenant::query()
                ->select('plan_id', DB::raw('count(*) as count'))
                ->groupBy('plan_id')->with('plan:id,name')->get()->toArray(),
            'retention' => $this->retentionMatrix(),
        ];
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
