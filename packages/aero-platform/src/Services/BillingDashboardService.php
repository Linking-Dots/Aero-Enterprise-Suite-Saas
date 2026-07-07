<?php

declare(strict_types=1);

namespace Aero\Platform\Services;

use Aero\Platform\Models\Subscription;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Billing "Revenue Command Center" data. Thin, read-only aggregation over the
 * live billing tables — recurring revenue (MRR/ARR, normalized), subscription
 * lifecycle, invoice collections, dunning (overdue), payment-gateway health, and
 * a recent-transactions feed.
 *
 * All money is monthly-normalized (yearly amount ÷ 12) so MRR agrees with the
 * Plans + Tenants pages — the legacy controller summed raw `amount`, which
 * over-counts annual subscriptions. All decimals are cast to float at the
 * boundary (MySQL returns decimal strings).
 */
class BillingDashboardService
{
    private const ACTIVE = 'active';

    /** @return array<string, mixed> */
    public function overview(): array
    {
        return [
            'heroes'         => $this->heroes(),
            'revenueTrend'   => $this->revenueTrend(),
            'lifecycle'      => $this->lifecycle(),
            'invoices'       => $this->invoiceHealth(),
            'needsAttention' => $this->needsAttention(),
            'gateways'       => $this->gateways(),
            'recent'         => $this->recentTransactions(),
        ];
    }

    /**
     * Fast-changing figures for the ~30s live poll (overdue + recent shift most).
     *
     * @return array<string, mixed>
     */
    public function live(): array
    {
        return [
            'invoices'       => $this->invoiceHealth(),
            'needsAttention' => $this->needsAttention(),
            'recent'         => $this->recentTransactions(),
        ];
    }

    /** @return array<string, mixed> */
    private function heroes(): array
    {
        $mrr = $this->normalizedMrr();
        $mrrPrev = $this->normalizedMrrAsOf(now()->subMonth());

        $subs = DB::table('subscriptions')->whereNull('deleted_at')
            ->selectRaw("SUM(status = 'active') a, SUM(status = 'trialing') t, SUM(status = 'cancelled') c")
            ->first();

        $overdue = DB::table('invoices')->whereNull('deleted_at')->where('status', 'overdue')
            ->selectRaw('COUNT(*) c, SUM(total) t')->first();
        $openCount = (int) DB::table('invoices')->whereNull('deleted_at')->where('status', 'issued')->count();

        $delta = $mrrPrev > 0 ? round((($mrr - $mrrPrev) / $mrrPrev) * 100, 1) : 0.0;

        return [
            'mrr'            => $mrr,
            'arr'            => round($mrr * 12, 2),
            'mrrDeltaPct'    => $delta,
            'activeSubs'     => (int) ($subs->a ?? 0),
            'trialingSubs'   => (int) ($subs->t ?? 0),
            'cancelledSubs'  => (int) ($subs->c ?? 0),
            'overdueCount'   => (int) ($overdue->c ?? 0),
            'overdueAmount'  => round((float) ($overdue->t ?? 0), 2),
            'openCount'      => $openCount,
        ];
    }

    /** Monthly-normalized MRR from active plan + product subscriptions. */
    private function normalizedMrr(): float
    {
        $plan = (float) DB::table('subscriptions')->whereNull('deleted_at')
            ->where('status', self::ACTIVE)
            ->selectRaw("SUM(CASE WHEN billing_cycle = 'yearly' THEN amount / 12 ELSE amount END) m")
            ->value('m');

        $product = (float) DB::table('product_subscriptions')->whereNull('deleted_at')
            ->where('status', self::ACTIVE)
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->selectRaw("SUM(CASE WHEN billing_cycle = 'yearly' THEN amount / 12 ELSE amount END) m")
            ->value('m');

        return round($plan + $product, 2);
    }

    /** Prior-period MRR baseline from platform_metrics_daily (for the delta). */
    private function normalizedMrrAsOf(Carbon $date): float
    {
        $row = DB::table('platform_metrics_daily')
            ->where('date', '<=', $date->toDateString())
            ->orderByDesc('date')->first();

        return $row ? round((float) $row->mrr, 2) : 0.0;
    }

    /**
     * Last 6 calendar months of MRR (total + product split) from the daily metrics
     * snapshot — the month's latest row per month.
     *
     * @return array<int, array{month: string, mrr: float, product: float}>
     */
    private function revenueTrend(): array
    {
        $since = now()->subMonths(5)->startOfMonth();

        $rows = DB::table('platform_metrics_daily')
            ->where('date', '>=', $since->toDateString())
            ->orderBy('date')
            ->get(['date', 'mrr', 'product_mrr']);

        $byMonth = [];
        foreach ($rows as $r) {
            $key = Carbon::parse($r->date)->format('Y-m');
            $byMonth[$key] = [
                'month'   => Carbon::parse($r->date)->format('M'),
                'mrr'     => round((float) $r->mrr, 2),
                'product' => round((float) $r->product_mrr, 2),
            ];
        }

        return array_values($byMonth);
    }

    /** @return array<string, mixed> */
    private function lifecycle(): array
    {
        $active    = (int) DB::table('subscriptions')->whereNull('deleted_at')->where('status', self::ACTIVE)->count();
        $trialing  = (int) DB::table('subscriptions')->whereNull('deleted_at')->where('status', 'trialing')->count();
        $cancelled = (int) DB::table('subscriptions')->whereNull('deleted_at')->where('status', 'cancelled')->count();

        // 30-day churn from the daily metrics: churned tenants over the average
        // active base. Null when the metrics window is empty.
        $m = DB::table('platform_metrics_daily')
            ->where('date', '>=', now()->subDays(30)->toDateString())
            ->selectRaw('SUM(churned_tenants) churned, AVG(active_tenants) active_avg')->first();
        $churn = ($m && (float) $m->active_avg > 0)
            ? round(((int) $m->churned / (float) $m->active_avg) * 100, 1)
            : null;

        return [
            'active'    => $active,
            'trialing'  => $trialing,
            'cancelled' => $cancelled,
            'total'     => $active + $trialing + $cancelled,
            'churnPct'  => $churn,
        ];
    }

    /** @return array<string, mixed> */
    private function invoiceHealth(): array
    {
        $rows = DB::table('invoices')->whereNull('deleted_at')
            ->selectRaw('status, COUNT(*) c, SUM(total) t')
            ->groupBy('status')->get()->keyBy('status');

        $pick = fn (string $s) => [
            'count'  => (int) ($rows[$s]->c ?? 0),
            'amount' => round((float) ($rows[$s]->t ?? 0), 2),
        ];

        $paid    = $pick('paid');
        $open    = $pick('issued');
        $overdue = $pick('overdue');

        $totalCount = $paid['count'] + $open['count'] + $overdue['count'];
        $collectedPct = $totalCount > 0 ? round(($paid['count'] / $totalCount) * 100, 0) : 0;

        return [
            'paid'         => $paid,
            'open'         => $open,
            'overdue'      => $overdue,
            'total'        => $totalCount,
            'collectedPct' => $collectedPct,
        ];
    }

    /**
     * Overdue invoices, oldest due-date first — the dunning worklist. Tenant name
     * comes via subscription (uuid → uuid), the only reliable link (the polymorphic
     * billable_id is a bigint that can't join the uuid tenant in seeded data).
     *
     * @return array<int, array<string, mixed>>
     */
    private function needsAttention(): array
    {
        return DB::table('invoices as i')
            ->leftJoin('subscriptions as s', 's.id', '=', 'i.subscription_id')
            ->leftJoin('tenants as t', 't.id', '=', 's.tenant_id')
            ->whereNull('i.deleted_at')
            ->where('i.status', 'overdue')
            ->orderBy('i.due_date')
            ->limit(6)
            ->get(['i.invoice_number', 'i.total', 'i.due_date', 't.name as tenant'])
            ->map(fn ($r) => [
                'invoice'     => $r->invoice_number,
                'tenant'      => $r->tenant ?? '—',
                'amount'      => round((float) $r->total, 2),
                'daysOverdue' => $r->due_date ? (int) abs(now()->diffInDays(Carbon::parse($r->due_date))) : null,
            ])->all();
    }

    /**
     * Configured payment gateways with live/disabled state.
     *
     * @return array<int, array<string, mixed>>
     */
    private function gateways(): array
    {
        return DB::table('payment_gateways')
            ->orderByDesc('is_default')->orderBy('label')
            ->get(['code', 'label', 'is_enabled', 'is_default'])
            ->map(fn ($g) => [
                'code'      => $g->code,
                'label'     => $g->label,
                'enabled'   => (bool) $g->is_enabled,
                'isDefault' => (bool) $g->is_default,
            ])->all();
    }

    /**
     * Latest invoices for the transactions feed.
     *
     * @return array<int, array<string, mixed>>
     */
    private function recentTransactions(): array
    {
        return DB::table('invoices as i')
            ->leftJoin('subscriptions as s', 's.id', '=', 'i.subscription_id')
            ->leftJoin('tenants as t', 't.id', '=', 's.tenant_id')
            ->whereNull('i.deleted_at')
            ->orderByDesc('i.created_at')
            ->limit(8)
            ->get(['i.invoice_number', 'i.total', 'i.status', 'i.created_at', 't.name as tenant'])
            ->map(fn ($r) => [
                'invoice' => $r->invoice_number,
                'tenant'  => $r->tenant ?? '—',
                'amount'  => round((float) $r->total, 2),
                'status'  => $r->status,
                'date'    => $r->created_at ? Carbon::parse($r->created_at)->format('M j') : null,
            ])->all();
    }
}
