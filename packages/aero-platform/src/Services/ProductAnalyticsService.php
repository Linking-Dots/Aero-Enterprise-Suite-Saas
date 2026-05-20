<?php

declare(strict_types=1);

namespace Aero\Platform\Services;

use Aero\Platform\Models\FeatureUsageEvent;
use Aero\Platform\Models\FunnelDefinition;
use Aero\Platform\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProductAnalyticsService
{
    public function featureUsage(int $days = 30): array
    {
        $since = now()->subDays($days);

        $rows = FeatureUsageEvent::query()
            ->where('occurred_at', '>=', $since)
            ->select(
                'feature_code',
                DB::raw('count(*) as events'),
                DB::raw('count(distinct tenant_id) as tenants')
            )
            ->groupBy('feature_code')->get();

        $activeTenants = max(1, Tenant::where('status', Tenant::STATUS_ACTIVE)->count());

        return $rows->map(fn ($r) => [
            'feature_code' => $r->feature_code,
            'events' => (int) $r->events,
            'tenants' => (int) $r->tenants,
            'adoption_pct' => round(($r->tenants / $activeTenants) * 100, 2),
        ])->sortByDesc('adoption_pct')->values()->all();
    }

    public function cohortRetention(int $months = 6): array
    {
        $cohorts = DB::connection('central')->table('tenants')
            ->select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as cohort"),
                DB::raw('count(*) as size')
            )
            ->where('created_at', '>=', now()->subMonths($months))
            ->groupBy('cohort')->orderBy('cohort')->get();

        $matrix = [];
        foreach ($cohorts as $c) {
            $row = ['cohort' => $c->cohort, 'size' => (int) $c->size, 'months' => []];
            for ($m = 0; $m < $months; $m++) {
                $end = Carbon::parse($c->cohort.'-01')->endOfMonth()->addMonths($m);
                $retained = DB::connection('central')->table('tenants')
                    ->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$c->cohort])
                    ->where('status', Tenant::STATUS_ACTIVE)
                    ->where(function ($q) use ($end) {
                        $q->whereNull('deleted_at')->orWhere('deleted_at', '>', $end);
                    })
                    ->count();
                $row['months'][] = [
                    'month' => $m,
                    'retained' => $retained,
                    'pct' => $c->size > 0 ? round(($retained / $c->size) * 100, 2) : 0,
                ];
            }
            $matrix[] = $row;
        }

        return $matrix;
    }

    public function funnelAnalysis(FunnelDefinition $funnel, int $days = 30): array
    {
        $since = now()->subDays($days);
        $steps = [];
        $prevTenants = null;

        foreach ($funnel->steps as $idx => $step) {
            $event = $step['event'];
            $tenants = FeatureUsageEvent::where('feature_code', $event)
                ->where('occurred_at', '>=', $since)
                ->distinct('tenant_id')->count('tenant_id');

            $conversion = $prevTenants ? round(($tenants / max(1, $prevTenants)) * 100, 2) : 100.0;
            $steps[] = [
                'order' => $idx,
                'label' => $step['label'] ?? $event,
                'event' => $event,
                'tenants' => $tenants,
                'conversion_pct' => $conversion,
            ];
            $prevTenants = $tenants;
        }

        return ['funnel' => $funnel->name, 'steps' => $steps];
    }

    public function adoptionMetrics(): array
    {
        $activeTenants = max(1, Tenant::where('status', Tenant::STATUS_ACTIVE)->count());
        $usingAny30d = FeatureUsageEvent::where('occurred_at', '>=', now()->subDays(30))
            ->distinct('tenant_id')->count('tenant_id');

        return [
            'active_tenants' => $activeTenants,
            'dau_30d' => $usingAny30d,
            'adoption_pct' => round(($usingAny30d / $activeTenants) * 100, 2),
            'top_features' => $this->featureUsage(30),
        ];
    }
}
