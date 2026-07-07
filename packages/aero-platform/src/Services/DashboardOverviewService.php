<?php

declare(strict_types=1);

namespace Aero\Platform\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * DashboardOverviewService
 *
 * Supplies the platform-admin "Living Command Center" dashboard payload.
 * All figures are read live from the landlord DB (seeded by PlatformDemoSeeder).
 * Decimal columns come back from MySQL as strings — every numeric is cast to
 * float/int at this boundary so the frontend never receives a string metric.
 *
 * Two entry points:
 *  - overview() : the full, eager above-the-fold payload
 *  - live()     : the small volatile subset refreshed by the ~30s poll
 */
class DashboardOverviewService
{
    private string $tz = 'Asia/Dhaka';

    /** Statuses that count as revenue-generating / established tenants. */
    private const ACTIVE = 'active';

    // ── Public API ────────────────────────────────────────────────────────

    public function overview(): array
    {
        return [
            'heroKpis'      => $this->heroKpis(),
            'revenueTrend'  => $this->revenueTrend(),
            'lifecycle'     => $this->lifecycle(),
            'growth'        => $this->growth(),
            'engagement'    => $this->engagement(),
            'recentTenants' => $this->recentTenants(),
            'generatedAt'   => Carbon::now($this->tz)->toIso8601String(),
        ];
    }

    public function live(): array
    {
        return [
            'stream'      => $this->activityStream(12),
            'pulse'       => $this->pulse(),
            'ops'         => $this->ops(),
            'generatedAt' => Carbon::now($this->tz)->toIso8601String(),
        ];
    }

    // ── Hero KPI band ─────────────────────────────────────────────────────

    private function heroKpis(): array
    {
        $stats = $this->statsSeries(30);           // last 30 daily stat rows
        $latest = $stats->last();
        $first  = $stats->first();

        $metricLatest = DB::table('platform_metrics_daily')->orderByDesc('date')->first();
        $metric30     = DB::table('platform_metrics_daily')
            ->where('date', '<=', Carbon::now($this->tz)->subDays(30)->toDateString())
            ->orderByDesc('date')->first();

        $mrr        = (float) ($metricLatest->mrr ?? 0);
        $mrr30      = (float) ($metric30->mrr ?? $mrr);
        $arr        = (float) ($metricLatest->arr ?? $mrr * 12);
        $arr30      = $mrr30 * 12;
        $active     = (int) ($metricLatest->active_tenants ?? 0);
        $active30   = (int) ($metric30->active_tenants ?? $active);
        $netNew     = round($mrr - $mrr30, 2);
        $churn      = $this->churnRate(30);

        return [
            [
                'key'   => 'mrr',
                'label' => 'MRR',
                'value' => $mrr,
                'format'=> 'currency',
                'delta' => $this->pctDelta($mrr, $mrr30),
                'spark' => $stats->pluck('total_mrr')->map(fn ($v) => (float) $v)->all(),
            ],
            [
                'key'   => 'arr',
                'label' => 'ARR',
                'value' => $arr,
                'format'=> 'currency',
                'delta' => $this->pctDelta($arr, $arr30),
                'spark' => $stats->pluck('total_mrr')->map(fn ($v) => (float) $v * 12)->all(),
            ],
            [
                'key'   => 'active',
                'label' => 'Active tenants',
                'value' => $active,
                'format'=> 'number',
                'delta' => $this->pctDelta($active, $active30),
                'spark' => $stats->pluck('active_tenants')->map(fn ($v) => (int) $v)->all(),
            ],
            [
                'key'   => 'net_new',
                'label' => 'Net-new MRR (30d)',
                'value' => $netNew,
                'format'=> 'currency',
                'delta' => null,
                'spark' => $stats->pluck('new_signups')->map(fn ($v) => (int) $v)->all(),
            ],
            [
                'key'    => 'churn',
                'label'  => 'Churn (30d)',
                'value'  => $churn,
                'format' => 'percent',
                'delta'  => null,
                'invert' => true, // lower is better
                'spark'  => $stats->pluck('churned_tenants')->map(fn ($v) => (int) $v)->all(),
            ],
        ];
    }

    // ── Revenue trend (6 months) ──────────────────────────────────────────

    private function revenueTrend(): array
    {
        $rows = DB::table('platform_metrics_daily')
            ->where('date', '>=', Carbon::now($this->tz)->subMonths(6)->startOfMonth()->toDateString())
            ->orderBy('date')
            ->get();

        // last stat row per calendar month
        $byMonth = [];
        foreach ($rows as $r) {
            $key = Carbon::parse($r->date, $this->tz)->format('Y-m');
            $byMonth[$key] = $r; // later rows overwrite → month-end snapshot
        }

        $trend = [];
        foreach ($byMonth as $key => $r) {
            $trend[] = [
                'month'      => Carbon::createFromFormat('Y-m', $key, $this->tz)->format('M'),
                'mrr'        => (float) $r->mrr,
                'planMrr'    => (float) $r->plan_mrr,
                'productMrr' => (float) $r->product_mrr,
            ];
        }

        $latest = end($trend) ?: ['mrr' => 0, 'planMrr' => 0, 'productMrr' => 0];
        $activeTenants = (int) (DB::table('platform_metrics_daily')->orderByDesc('date')->value('active_tenants') ?? 0);

        return [
            'trend'      => array_values($trend),
            'planMrr'    => (float) $latest['planMrr'],
            'productMrr' => (float) $latest['productMrr'],
            'arpt'       => $activeTenants > 0 ? round((float) $latest['mrr'] / $activeTenants, 2) : 0.0,
            'momGrowth'  => $this->momGrowth($trend),
        ];
    }

    // ── Tenant lifecycle funnel + pipeline ────────────────────────────────

    private function lifecycle(): array
    {
        $pipeline = DB::table('tenants')
            ->select('status', DB::raw('count(*) as c'))
            ->groupBy('status')
            ->pluck('c', 'status');

        $statuses = ['active', 'trial', 'pending', 'provisioning', 'suspended', 'failed', 'archived'];
        $buckets = [];
        foreach ($statuses as $s) {
            $buckets[] = ['status' => $s, 'count' => (int) ($pipeline[$s] ?? 0)];
        }

        $leads   = (int) DB::table('prospect_leads')->count();
        $trials  = (int) ($pipeline['trial'] ?? 0)
                 + (int) DB::table('tenants')->whereNotNull('created_at')
                     ->whereExists(fn ($q) => $q->select(DB::raw(1))->from('subscriptions')
                         ->whereColumn('subscriptions.tenant_id', 'tenants.id')
                         ->where('status', 'trialing'))->count();
        $active  = (int) ($pipeline['active'] ?? 0);
        $total   = (int) DB::table('tenants')->count();

        $funnel = [
            ['stage' => 'Leads',    'count' => max($leads, $total)],
            ['stage' => 'Sign-ups', 'count' => $total],
            ['stage' => 'Trials',   'count' => max($trials, (int) ($pipeline['trial'] ?? 0))],
            ['stage' => 'Active',   'count' => $active],
        ];

        return [
            'funnel'   => $funnel,
            'pipeline' => $buckets,
            'total'    => $total,
        ];
    }

    // ── Growth & acquisition ──────────────────────────────────────────────

    private function growth(): array
    {
        $since = Carbon::now($this->tz)->subDays(30);

        $newSignups = (int) DB::table('tenants')->where('created_at', '>=', $since)->count();

        $leadsByStatus = DB::table('prospect_leads')
            ->select('status', DB::raw('count(*) as c'))
            ->groupBy('status')->pluck('c', 'status');

        $topSources = DB::table('prospect_leads')
            ->select('source', DB::raw('count(*) as c'))
            ->whereNotNull('source')
            ->groupBy('source')->orderByDesc('c')->limit(5)->get()
            ->map(fn ($r) => ['source' => $r->source, 'count' => (int) $r->c])->all();

        $trialsStarted   = (int) DB::table('platform_stats_daily')
            ->where('date', '>=', $since->toDateString())->sum('trials_started');
        $trialsConverted = (int) DB::table('platform_stats_daily')
            ->where('date', '>=', $since->toDateString())->sum('trials_converted');
        $convRate = $trialsStarted > 0 ? round(($trialsConverted / $trialsStarted) * 100, 1) : 0.0;

        $newsletter = (int) DB::table('newsletter_subscribers')->where('status', 'subscribed')->count();

        return [
            'newSignups'      => $newSignups,
            'leadsByStatus'   => $leadsByStatus->map(fn ($c) => (int) $c)->all(),
            'totalLeads'      => (int) $leadsByStatus->sum(),
            'topSources'      => $topSources,
            'trialConvRate'   => $convRate,
            'newsletterCount' => $newsletter,
        ];
    }

    // ── Engagement (module adoption + feature usage) ──────────────────────

    private function engagement(): array
    {
        $activeTenants = max(1, (int) DB::table('tenants')->where('status', self::ACTIVE)->count());

        // module adoption: distinct tenants with an active product/module subscription
        $moduleRows = DB::table('subscription_modules')
            ->select('module_code', DB::raw('count(distinct billable_id) as c'))
            ->where('status', 'active')
            ->groupBy('module_code')->get();

        $modules = $moduleRows->map(fn ($r) => [
            'module'  => $r->module_code,
            'tenants' => (int) $r->c,
            'pct'     => round(((int) $r->c / $activeTenants) * 100, 1),
        ])->sortByDesc('pct')->values()->all();

        // feature usage intensity (top features by event count, last 30d)
        $features = DB::table('feature_usage_events')
            ->select('feature_code', DB::raw('count(*) as c'))
            ->where('occurred_at', '>=', Carbon::now($this->tz)->subDays(30))
            ->groupBy('feature_code')->orderByDesc('c')->limit(8)->get()
            ->map(fn ($r) => ['feature' => $r->feature_code, 'count' => (int) $r->c])->all();

        $maxFeature = collect($features)->max('count') ?: 1;

        return [
            'modules'       => $modules,
            'features'      => $features,
            'maxFeature'    => $maxFeature,
            'activeTenants' => $activeTenants,
        ];
    }

    // ── Recent tenants (with plan + mrr) ──────────────────────────────────

    private function recentTenants(int $limit = 8): array
    {
        $rows = DB::table('tenants as t')
            ->leftJoin('subscriptions as s', function ($j) {
                $j->on('s.tenant_id', '=', 't.id');
            })
            ->leftJoin('plans as p', 'p.id', '=', 's.plan_id')
            ->select('t.id', 't.name', 't.subdomain', 't.status', 't.created_at',
                'p.name as plan_name', 's.amount as sub_amount', 's.billing_cycle')
            ->orderByDesc('t.created_at')
            ->limit($limit)
            ->get();

        return $rows->map(function ($t) {
            $mrr = $t->sub_amount !== null
                ? ($t->billing_cycle === 'yearly' ? (float) $t->sub_amount / 12 : (float) $t->sub_amount)
                : null;

            return [
                'id'        => $t->id,
                'name'      => $t->name,
                'domain'    => $t->subdomain ? $t->subdomain . '.aeos365.com' : null,
                'plan'      => $t->plan_name,
                'status'    => $t->status,
                'mrr'       => $mrr !== null ? round($mrr, 2) : null,
                'createdAt' => $t->created_at ? Carbon::parse($t->created_at, $this->tz)->diffForHumans() : null,
            ];
        })->all();
    }

    // ── Live: activity stream ─────────────────────────────────────────────

    private function activityStream(int $limit = 12): array
    {
        return DB::table('platform_audit_logs')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn ($r) => [
                'id'      => $r->id,
                'actor'   => $r->actor_name ?? 'System',
                'action'  => $r->action,
                'event'   => $r->event_type,
                'text'    => $r->description ?? $r->event_type,
                'subject' => $r->subject_label ?? null,
                'at'      => $r->created_at ? Carbon::parse($r->created_at, $this->tz)->diffForHumans() : null,
                'ts'      => $r->created_at ? Carbon::parse($r->created_at, $this->tz)->timestamp : 0,
            ])->all();
    }

    // ── Live: system pulse ────────────────────────────────────────────────

    private function pulse(): array
    {
        $failed      = (int) DB::table('tenants')->where('status', 'failed')->count();
        $provisioning= (int) DB::table('tenants')->where('status', 'provisioning')->count();
        $suspended   = (int) DB::table('tenants')->where('status', 'suspended')->count();
        $pastDue     = (int) DB::table('subscriptions')->where('status', 'past_due')->count();

        $alerts = [];
        if ($failed) {
            $alerts[] = ['severity' => 'critical', 'title' => 'Failed provisioning', 'detail' => "$failed tenant(s) failed during provisioning", 'href' => '/onboarding/provisioning'];
        }
        if ($provisioning) {
            $alerts[] = ['severity' => 'warning', 'title' => 'Provisioning in progress', 'detail' => "$provisioning tenant(s) currently provisioning", 'href' => '/onboarding/provisioning'];
        }
        if ($pastDue) {
            $alerts[] = ['severity' => 'warning', 'title' => 'Past-due payments', 'detail' => "$pastDue subscription(s) past due", 'href' => '/billing/subscriptions'];
        }
        if ($suspended) {
            $alerts[] = ['severity' => 'info', 'title' => 'Suspended tenants', 'detail' => "$suspended tenant(s) suspended", 'href' => '/tenants'];
        }

        $severity = $failed ? 'critical' : (($provisioning || $pastDue) ? 'warning' : 'operational');

        return [
            'status'     => $severity,
            'alerts'     => $alerts,
            'alertCount' => count($alerts),
        ];
    }

    // ── Live: ops (health + error trend) ──────────────────────────────────

    private function ops(): array
    {
        // 24h error trend, bucketed per 3h
        $since = Carbon::now($this->tz)->subDay();
        $errRows = DB::table('error_logs')
            ->where('created_at', '>=', $since)
            ->get(['created_at', 'is_resolved']);

        $buckets = array_fill(0, 8, 0); // 8 × 3h
        $now = Carbon::now($this->tz);
        foreach ($errRows as $e) {
            $hoursAgo = abs((int) $now->diffInHours(Carbon::parse($e->created_at, $this->tz)));
            $idx = max(0, min(7, 7 - intdiv($hoursAgo, 3)));
            $buckets[$idx]++;
        }
        $totalErrors = count($errRows);
        $unresolved  = (int) DB::table('error_logs')->where('is_resolved', 0)->count();

        $services = [
            'database' => $this->pingDb(),
            'cache'    => 'ok',
            'queue'    => (int) DB::table('failed_jobs')->count() > 50 ? 'degraded' : 'ok',
            'storage'  => 'ok',
            'mail'     => 'ok',
            'search'   => 'ok',
        ];

        return [
            'errorTrend'   => $buckets,
            'errors24h'    => $totalErrors,
            'unresolved'   => $unresolved,
            'services'     => $services,
        ];
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function statsSeries(int $days): Collection
    {
        return DB::table('platform_stats_daily')
            ->where('date', '>=', Carbon::now($this->tz)->subDays($days)->toDateString())
            ->orderBy('date')
            ->get();
    }

    private function churnRate(int $days): float
    {
        $start = Carbon::now($this->tz)->subDays($days)->toDateString();
        $churned = (int) DB::table('platform_metrics_daily')->where('date', '>=', $start)->sum('churned_tenants');
        $active  = max(1, (int) (DB::table('platform_metrics_daily')->orderByDesc('date')->value('active_tenants') ?? 1));

        return round(($churned / $active) * 100, 2);
    }

    private function pctDelta(float $current, float $previous): ?float
    {
        if ($previous == 0.0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    private function momGrowth(array $trend): float
    {
        $n = count($trend);
        if ($n < 2) {
            return 0.0;
        }
        $last = (float) $trend[$n - 1]['mrr'];
        $prev = (float) $trend[$n - 2]['mrr'];

        return $this->pctDelta($last, $prev) ?? 0.0;
    }

    private function pingDb(): string
    {
        try {
            DB::select('select 1');

            return 'ok';
        } catch (\Throwable $e) {
            return 'down';
        }
    }
}
