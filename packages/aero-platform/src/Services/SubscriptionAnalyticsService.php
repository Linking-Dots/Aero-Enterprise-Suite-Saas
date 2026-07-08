<?php

declare(strict_types=1);

namespace Aero\Platform\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Read-only analytics for the Subscriptions command centre.
 *
 * Everything is reconstructed from real lifecycle timestamps (created_at /
 * cancelled_at / trial_ends_at) — a subscription counts as "alive" in month M
 * when it was created on or before the end of M and not yet cancelled by then.
 * MRR is normalised to the monthly rate (plan price_monthly for plan rows;
 * product amount, divided by 12 for yearly cycles).
 *
 * mrr_movement expansion/contraction are read from the subscription_events
 * ledger (priced plan-change / cycle-change deltas); new/churn stay
 * reconstructed from created_at/cancelled_at so history predating the ledger
 * still charts. There is no double-count: ledger expansion/contraction rows
 * are plan/cycle changes only, never creates or cancellations.
 *
 * Known approximation (documented, not fabricated):
 * - historical trial counts use trial_ends_at (a sub is "in trial" in month M
 *   while created_at <= EOM < trial_ends_at) — matches how trials actually run.
 */
class SubscriptionAnalyticsService
{
    /** @var array<int, object>|null */
    private ?array $planRows = null;

    /** @var array<int, object>|null */
    private ?array $productRows = null;

    /**
     * Full analytics payload for the command centre.
     *
     * @return array{sparks: array, mrr_trend: array, mrr_movement: array, cohorts: array, queues: array, extras: array}
     */
    public function analytics(): array
    {
        $now = CarbonImmutable::now();

        return [
            'sparks'       => $this->sparks($now),
            'mrr_trend'    => $this->mrrTrend($now),
            'mrr_movement' => $this->mrrMovement($now),
            'cohorts'      => $this->cohorts($now),
            'queues'       => $this->queues($now),
            'extras'       => $this->extras($now),
        ];
    }

    /* ------------------------------------------------------------------
     | row loading (2 bounded queries, then pure-PHP aggregation)
     * ----------------------------------------------------------------- */

    /** @return array<int, object> */
    private function plans(): array
    {
        return $this->planRows ??= DB::table('subscriptions as s')
            ->leftJoin('plans as p', 'p.id', '=', 's.plan_id')
            ->leftJoin('tenants as t', 't.id', '=', 's.tenant_id')
            ->whereNull('s.deleted_at')
            ->get([
                's.id', 's.status', 's.amount', 's.billing_cycle', 's.currency',
                's.created_at', 's.cancelled_at', 's.trial_ends_at',
                's.current_period_end', 's.ends_at', 's.next_billing_date', 's.grace_period_ends_at',
                'p.price_monthly', 'p.name as label', 't.name as tenant',
            ])->all();
    }

    /** @return array<int, object> */
    private function products(): array
    {
        return $this->productRows ??= DB::table('product_subscriptions as ps')
            ->leftJoin('products as pr', 'pr.id', '=', 'ps.product_id')
            ->leftJoin('tenants as t', 't.id', '=', 'ps.tenant_id')
            ->whereNull('ps.deleted_at')
            ->get([
                'ps.id', 'ps.status', 'ps.amount', 'ps.billing_cycle',
                'ps.created_at', 'ps.cancelled_at', 'ps.trial_ends_at',
                'pr.name as label', 't.name as tenant',
            ])->all();
    }

    private function monthlyRate(object $r, bool $isPlan): float
    {
        if ($isPlan) {
            return (float) ($r->price_monthly ?? 0);
        }
        $amount = (float) ($r->amount ?? 0);

        return ($r->billing_cycle ?? null) === 'yearly' ? round($amount / 12, 2) : $amount;
    }

    private function aliveAt(object $r, CarbonImmutable $eom): bool
    {
        if ($r->created_at === null || $r->created_at > $eom->toDateTimeString()) {
            return false;
        }

        return $r->cancelled_at === null || $r->cancelled_at > $eom->toDateTimeString();
    }

    /** MRR of all rows alive at end-of-month, split plan/product. */
    private function mrrAt(CarbonImmutable $eom): array
    {
        $plan = 0.0;
        $product = 0.0;
        foreach ($this->plans() as $r) {
            if ($this->aliveAt($r, $eom)) {
                $plan += $this->monthlyRate($r, true);
            }
        }
        foreach ($this->products() as $r) {
            if ($this->aliveAt($r, $eom)) {
                $product += $this->monthlyRate($r, false);
            }
        }

        return [round($plan, 2), round($product, 2)];
    }

    /* ------------------------------------------------------------------
     | series
     * ----------------------------------------------------------------- */

    private function sparks(CarbonImmutable $now): array
    {
        $mrr = [];
        $active = [];
        $trials = [];
        $churnPct = [];

        for ($i = 7; $i >= 0; $i--) {
            $eom = $now->subMonthsNoOverflow($i)->endOfMonth();
            $som = $now->subMonthsNoOverflow($i)->startOfMonth();
            [$p, $pr] = $this->mrrAt($eom);
            $mrr[] = round($p + $pr, 2);

            $a = 0;
            $t = 0;
            foreach ([[$this->plans(), true], [$this->products(), false]] as [$rows, $isPlan]) {
                foreach ($rows as $r) {
                    if (! $this->aliveAt($r, $eom)) {
                        continue;
                    }
                    $a++;
                    if ($r->trial_ends_at !== null && $r->trial_ends_at > $eom->toDateTimeString()) {
                        $t++;
                    }
                }
            }
            $active[] = $a;
            $trials[] = $t;

            // churn % = MRR cancelled during the month / MRR at month start
            [$sp, $spr] = $this->mrrAt($som->subDay());
            $opening = $sp + $spr;
            $churned = 0.0;
            foreach ([[$this->plans(), true], [$this->products(), false]] as [$rows, $isPlan]) {
                foreach ($rows as $r) {
                    if ($r->cancelled_at !== null && $r->cancelled_at >= $som->toDateTimeString() && $r->cancelled_at <= $eom->toDateTimeString()) {
                        $churned += $this->monthlyRate($r, $isPlan);
                    }
                }
            }
            $churnPct[] = $opening > 0 ? round($churned / $opening * 100, 1) : 0.0;
        }

        return ['mrr' => $mrr, 'active' => $active, 'trials' => $trials, 'churn_pct' => $churnPct];
    }

    private function mrrTrend(CarbonImmutable $now): array
    {
        $labels = [];
        $plan = [];
        $product = [];
        for ($i = 11; $i >= 0; $i--) {
            $m = $now->subMonthsNoOverflow($i);
            $labels[] = $m->format('M');
            [$p, $pr] = $this->mrrAt($m->endOfMonth());
            $plan[] = $p;
            $product[] = $pr;
        }

        return ['labels' => $labels, 'plan' => $plan, 'product' => $product];
    }

    private function mrrMovement(CarbonImmutable $now): array
    {
        // Ledger expansion/contraction, aggregated per YYYY-MM in one query.
        $ledger = [];
        if (\Illuminate\Support\Facades\Schema::connection(central_connection())->hasTable('subscription_events')) {
            $since = $now->subMonthsNoOverflow(5)->startOfMonth();
            $rows = DB::table('subscription_events')
                ->whereIn('movement', ['expansion', 'contraction'])
                ->where('occurred_at', '>=', $since->toDateTimeString())
                ->get(['movement', 'mrr_delta', 'occurred_at']);
            foreach ($rows as $row) {
                $key = CarbonImmutable::parse($row->occurred_at)->format('Y-m');
                $ledger[$key]['expansion'] = ($ledger[$key]['expansion'] ?? 0) + ($row->movement === 'expansion' ? (float) $row->mrr_delta : 0);
                $ledger[$key]['contraction'] = ($ledger[$key]['contraction'] ?? 0) + ($row->movement === 'contraction' ? abs((float) $row->mrr_delta) : 0);
            }
        }

        $labels = [];
        $new = [];
        $churn = [];
        $expansion = [];
        $contraction = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = $now->subMonthsNoOverflow($i);
            $som = $m->startOfMonth()->toDateTimeString();
            $eom = $m->endOfMonth()->toDateTimeString();
            $labels[] = $m->format('M');

            $n = 0.0;
            $c = 0.0;
            foreach ([[$this->plans(), true], [$this->products(), false]] as [$rows, $isPlan]) {
                foreach ($rows as $r) {
                    if ($r->created_at !== null && $r->created_at >= $som && $r->created_at <= $eom) {
                        $n += $this->monthlyRate($r, $isPlan);
                    }
                    if ($r->cancelled_at !== null && $r->cancelled_at >= $som && $r->cancelled_at <= $eom) {
                        $c += $this->monthlyRate($r, $isPlan);
                    }
                }
            }
            $key = $m->format('Y-m');
            $new[] = round($n, 2);
            $churn[] = round($c, 2);
            $expansion[] = round($ledger[$key]['expansion'] ?? 0, 2);
            $contraction[] = round($ledger[$key]['contraction'] ?? 0, 2);
        }

        return [
            'labels'      => $labels,
            'new'         => $new,
            'expansion'   => $expansion,
            'contraction' => $contraction,
            'churn'       => $churn,
        ];
    }

    private function cohorts(CarbonImmutable $now): array
    {
        $out = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = $now->subMonthsNoOverflow($i);
            $som = $m->startOfMonth()->toDateTimeString();
            $eom = $m->endOfMonth()->toDateTimeString();

            $cohort = [];
            foreach ([[$this->plans(), true], [$this->products(), false]] as [$rows]) {
                foreach ($rows as $r) {
                    if ($r->created_at !== null && $r->created_at >= $som && $r->created_at <= $eom) {
                        $cohort[] = $r;
                    }
                }
            }
            $size = count($cohort);
            $values = [];
            for ($k = 0; $k <= 5; $k++) {
                $checkpoint = $m->addMonthsNoOverflow($k)->endOfMonth();
                if ($size === 0 || $checkpoint->isFuture()) {
                    $values[] = null;

                    continue;
                }
                $retained = count(array_filter(
                    $cohort,
                    fn ($r) => $r->cancelled_at === null || $r->cancelled_at > $checkpoint->toDateTimeString()
                ));
                $values[] = (int) round($retained / $size * 100);
            }
            $out[] = ['label' => $m->format('M'), 'values' => $values];
        }

        return $out;
    }

    /* ------------------------------------------------------------------
     | action queues
     * ----------------------------------------------------------------- */

    private function queues(CarbonImmutable $now): array
    {
        $renewals = [];
        foreach ($this->plans() as $r) {
            if ($r->status !== 'active') {
                continue;
            }
            $renewsAt = $r->next_billing_date ?? $r->current_period_end ?? $r->ends_at;
            if ($renewsAt === null || $renewsAt < $now->toDateTimeString() || $renewsAt > $now->addDays(30)->toDateTimeString()) {
                continue;
            }
            $renewals[] = [
                'id'            => (string) $r->id,
                'kind'          => 'plan',
                'tenant'        => $r->tenant ?: '—',
                'label'         => $r->label ?: 'No plan',
                'billing_cycle' => $r->billing_cycle,
                'amount'        => (float) ($r->amount ?? 0),
                'renews_at'     => $renewsAt,
            ];
        }
        usort($renewals, fn ($a, $b) => strcmp($a['renews_at'], $b['renews_at']));

        $trials = [];
        foreach ([[$this->plans(), 'plan'], [$this->products(), 'product']] as [$rows, $kind]) {
            foreach ($rows as $r) {
                if ($r->status !== 'trialing' || $r->trial_ends_at === null) {
                    continue;
                }
                $trials[] = [
                    'id'            => (string) $r->id,
                    'kind'          => $kind,
                    'tenant'        => $r->tenant ?: '—',
                    'label'         => $r->label ?: '—',
                    'trial_ends_at' => $r->trial_ends_at,
                    'days_left'     => max(0, (int) $now->diffInDays(CarbonImmutable::parse($r->trial_ends_at), false)),
                ];
            }
        }
        usort($trials, fn ($a, $b) => strcmp($a['trial_ends_at'], $b['trial_ends_at']));

        $dunning = [];
        foreach ([[$this->plans(), 'plan', true], [$this->products(), 'product', false]] as [$rows, $kind, $isPlan]) {
            foreach ($rows as $r) {
                if ($r->status !== 'past_due') {
                    continue;
                }
                $dueRef = $isPlan ? ($r->current_period_end ?? $r->ends_at ?? $r->next_billing_date) : null;
                $dunning[] = [
                    'id'            => (string) $r->id,
                    'kind'          => $kind,
                    'tenant'        => $r->tenant ?: '—',
                    'label'         => $r->label ?: '—',
                    'amount'        => (float) ($r->amount ?? 0),
                    'days_overdue'  => $dueRef !== null ? max(0, (int) CarbonImmutable::parse($dueRef)->diffInDays($now, false)) : 0,
                    'grace_ends_at' => $isPlan ? ($r->grace_period_ends_at ?? null) : null,
                ];
            }
        }
        usort($dunning, fn ($a, $b) => $b['days_overdue'] <=> $a['days_overdue']);

        return [
            'renewals' => array_slice($renewals, 0, 8),
            'trials'   => array_slice($trials, 0, 8),
            'dunning'  => array_slice($dunning, 0, 8),
        ];
    }

    /* ------------------------------------------------------------------
     | stat extras
     * ----------------------------------------------------------------- */

    private function extras(CarbonImmutable $now): array
    {
        [$p, $pr] = $this->mrrAt($now->endOfMonth());
        $mrrNow = $p + $pr;
        [$pp, $ppr] = $this->mrrAt($now->subMonthsNoOverflow(1)->endOfMonth());
        $mrrPrev = $pp + $ppr;

        $dunningCount = 0;
        $dunningAmount = 0.0;
        $trialsEnding = 0;
        $expired = 0;
        foreach ([[$this->plans(), true], [$this->products(), false]] as [$rows, $isPlan]) {
            foreach ($rows as $r) {
                if ($r->status === 'past_due') {
                    $dunningCount++;
                    $dunningAmount += $this->monthlyRate($r, $isPlan);
                }
                if ($r->status === 'expired') {
                    $expired++;
                }
                if ($r->status === 'trialing' && $r->trial_ends_at !== null && $r->trial_ends_at <= $now->addDays(7)->toDateTimeString()) {
                    $trialsEnding++;
                }
            }
        }

        return [
            'arr'              => round($mrrNow * 12, 2),
            'mrr_delta_pct'    => $mrrPrev > 0 ? round(($mrrNow - $mrrPrev) / $mrrPrev * 100, 1) : 0.0,
            'dunning_count'    => $dunningCount,
            'dunning_amount'   => round($dunningAmount, 2),
            'trials_ending_7d' => $trialsEnding,
            'expired'          => $expired,
        ];
    }
}
