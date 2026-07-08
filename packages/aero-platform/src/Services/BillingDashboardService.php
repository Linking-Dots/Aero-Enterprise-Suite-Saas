<?php

declare(strict_types=1);

namespace Aero\Platform\Services;

use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Billing "Command Center" hub data for the /billing landing page — the
 * executive roll-up of the whole billing vertical.
 *
 * This service COMPOSES the two shipped command-centre aggregators rather than
 * re-deriving anything:
 *   - SubscriptionAdminService::overview()  → recurring revenue, lifecycle,
 *     MRR movement/trend, churn sparks and the renewals/trials/dunning queues.
 *   - InvoiceAdminService::overview()        → collections, AR aging, billed-vs-
 *     collected trend and the invoice list (overdue queue + payment-method mix).
 *
 * On top of that it adds a real, merged "recent billing activity" feed
 * (subscription lifecycle events + refunds + settled invoices, with the central
 * audit trail merged in when present) and a payment-gateway roster + settled
 * method mix. Everything is read-only and money is monthly-normalised by the
 * upstream services, so figures agree with the Subscriptions/Invoices pages.
 */
class BillingDashboardService
{
    public function __construct(
        private readonly SubscriptionAdminService $subscriptions,
        private readonly InvoiceAdminService $invoices,
    ) {}

    /** @return array<string, mixed> */
    public function overview(): array
    {
        $sub = $this->subscriptions->overview();
        $inv = $this->invoices->overview();

        $s = $sub['stats'];
        $i = $inv['stats'];
        $subSparks = $sub['sparks'];
        $invSparks = $inv['sparks'];

        $churnPct = ! empty($subSparks['churn_pct']) ? (float) end($subSparks['churn_pct']) : 0.0;

        return [
            'kpis' => [
                'mrr'            => $s['mrr'],
                'mrr_delta_pct'  => $s['mrr_delta_pct'] ?? 0.0,
                'arr'            => $s['arr'] ?? round($s['mrr'] * 12, 2),
                'plan_mrr'       => $s['plan_mrr'],
                'product_mrr'    => $s['product_mrr'],
                'active'         => $s['active'],
                'trialing'       => $s['trialing'],
                'trials_ending_7d' => $s['trials_ending_7d'] ?? 0,
                'collected'      => $i['collected'],
                'paid'           => $i['paid'],
                'outstanding'    => $i['outstanding'],
                'open'           => $i['open'],
                'overdue'        => $i['overdue'],
                'overdue_amt'    => $i['overdue_amt'],
                'paid_rate'      => $i['paid_rate'],
                'avg_days_to_pay' => $i['avg_days_to_pay'],
                'dunning_amount' => $s['dunning_amount'] ?? 0.0,
                'dunning_count'  => $s['dunning_count'] ?? 0,
                'churn_pct'      => $churnPct,
            ],
            'sparks' => [
                'mrr'         => $subSparks['mrr'] ?? [],
                'arr'         => array_map(static fn ($v) => round((float) $v * 12, 2), $subSparks['mrr'] ?? []),
                'active'      => $subSparks['active'] ?? [],
                'trials'      => $subSparks['trials'] ?? [],
                'churn'       => $subSparks['churn_pct'] ?? [],
                'collected'   => $invSparks['collected'] ?? [],
                'outstanding' => $invSparks['outstanding'] ?? [],
                'overdue'     => $invSparks['overdue'] ?? [],
                'paid_rate'   => $invSparks['paid_rate'] ?? [],
            ],
            'movement' => $sub['mrr_movement'],
            'trend'    => $inv['trend'],
            'health'   => [
                'active'     => $s['active'],
                'trialing'   => $s['trialing'],
                'past_due'   => $s['past_due'],
                'incomplete' => $s['incomplete'],
                'cancelled'  => $s['cancelled'],
                'total'      => $s['total'],
                'active_pct' => $s['total'] > 0 ? (int) round($s['active'] / $s['total'] * 100) : 0,
            ],
            'queues' => [
                'renewals' => $sub['queues']['renewals'],
                'trials'   => $sub['queues']['trials'],
                'dunning'  => $sub['queues']['dunning'],
                'overdue'  => $this->overdueInvoices($inv['invoices']),
            ],
            'activity'  => $this->recentActivity(),
            'gateways'  => $this->gateways(),
            'methodMix' => $this->methodMix($inv['invoices']),
        ];
    }

    /**
     * Fast-changing subset for a live poll (queues + activity shift most).
     * The page's ~45s partial reload refreshes the full `overview`, so this is
     * only used by the JSON `stats` endpoint.
     *
     * @return array<string, mixed>
     */
    public function live(): array
    {
        $inv = $this->invoices->overview();

        return [
            'queues'   => ['overdue' => $this->overdueInvoices($inv['invoices'])],
            'activity' => $this->recentActivity(),
        ];
    }

    /**
     * Overdue invoices, oldest-first (largest days-overdue), for the collections
     * queue. `id` is carried so the row's quick actions can POST to the invoice
     * lifecycle endpoints (same ids the Invoices page uses).
     *
     * @param  array<int, array<string, mixed>>  $invoices
     * @return array<int, array<string, mixed>>
     */
    private function overdueInvoices(array $invoices): array
    {
        $overdue = array_values(array_filter($invoices, static fn ($r) => $r['status'] === 'overdue'));
        usort($overdue, static fn ($a, $b) => $b['days_overdue'] <=> $a['days_overdue']);

        return array_map(static fn ($r) => [
            'id'           => $r['id'],
            'tenant'       => $r['tenant'],
            'number'       => $r['number'],
            'amount'       => $r['total'],
            'days_overdue' => $r['days_overdue'],
        ], array_slice($overdue, 0, 6));
    }

    /**
     * Merged, real "recent billing activity" feed: subscription lifecycle events
     * (priced), refunds, and settled invoices — with the central platform audit
     * trail merged in when it holds billing rows. Sorted newest-first, capped.
     * Every read is guarded so a missing table can't 500 the hub.
     *
     * @return array<int, array<string, mixed>>
     */
    private function recentActivity(): array
    {
        $items = [];

        // 1) Subscription lifecycle ledger — priced movement per tenant.
        try {
            $events = DB::table('subscription_events as e')
                ->leftJoin('tenants as t', 't.id', '=', 'e.tenant_id')
                ->orderByDesc('e.occurred_at')
                ->limit(12)
                ->get(['e.event_type', 'e.movement', 'e.mrr_delta', 'e.currency', 't.name as tenant', 'e.occurred_at', 'e.actor_name']);
            foreach ($events as $e) {
                $delta = (float) $e->mrr_delta;
                $items[] = [
                    'kind'     => $this->activityKind((string) $e->movement, (string) $e->event_type),
                    'tenant'   => $e->tenant ?: '—',
                    'message'  => $this->eventMessage((string) $e->event_type, (string) $e->movement),
                    'amount'   => $delta !== 0.0 ? round($delta, 2) : null,
                    'currency' => $e->currency ?: 'USD',
                    'at'       => $e->occurred_at,
                    'note'     => $e->actor_name ?: null,
                ];
            }
        } catch (QueryException) {
            // subscription_events absent — skip
        }

        // 2) Refunds — signed negative movements.
        try {
            $refunds = DB::table('refunds as r')
                ->leftJoin('tenants as t', 't.id', '=', 'r.tenant_id')
                ->orderByDesc('r.created_at')
                ->limit(6)
                ->get(['r.reference', 'r.amount', 'r.currency', 'r.reason', 'r.status', 't.name as tenant', 'r.created_at']);
            foreach ($refunds as $r) {
                $items[] = [
                    'kind'     => 'refund',
                    'tenant'   => $r->tenant ?: '—',
                    'message'  => "refunded {$r->reference}".($r->reason ? " — {$r->reason}" : ''),
                    'amount'   => -round((float) $r->amount, 2),
                    'currency' => $r->currency ?: 'USD',
                    'at'       => $r->created_at,
                    'note'     => $r->status ?: null,
                ];
            }
        } catch (QueryException) {
            // refunds absent — skip
        }

        // 3) Settled invoices — real collections.
        $paid = DB::table('invoices as i')
            ->leftJoin('tenants as t', 't.id', '=', 'i.billable_id')
            ->whereNull('i.deleted_at')
            ->whereNotNull('i.paid_at')
            ->orderByDesc('i.paid_at')
            ->limit(6)
            ->get(['i.invoice_number', 'i.total', 'i.currency', 'i.payment_method', 't.name as tenant', 'i.paid_at']);
        foreach ($paid as $p) {
            $items[] = [
                'kind'     => 'pay',
                'tenant'   => $p->tenant ?: '—',
                'message'  => "paid invoice {$p->invoice_number}",
                'amount'   => round((float) $p->total, 2),
                'currency' => $p->currency ?: 'USD',
                'at'       => $p->paid_at,
                'note'     => $p->payment_method ?: null,
            ];
        }

        // 4) Central audit trail — merged when it carries billing subjects
        //    (empty on seeded data, but real admin actions land here in prod).
        try {
            [$conn, $table] = (is_saas_mode() && ! (function_exists('tenancy') && tenancy()->initialized))
                ? [central_connection(), 'platform_audit_logs']
                : [null, 'audit_logs'];

            $audits = DB::connection($conn)->table($table)
                ->where(fn ($q) => $q->where('subject_type', 'like', '%Invoice%')->orWhere('subject_type', 'like', '%Subscription%'))
                ->orderByDesc('created_at')
                ->limit(6)
                ->get(['action', 'description', 'actor_name', 'created_at']);
            foreach ($audits as $a) {
                $items[] = [
                    'kind'     => 'event',
                    'tenant'   => $a->actor_name ?: 'System',
                    'message'  => $a->description ?: ($a->action ?: 'billing action'),
                    'amount'   => null,
                    'currency' => null,
                    'at'       => $a->created_at,
                    'note'     => 'audit',
                ];
            }
        } catch (QueryException) {
            // audit table absent in this context — keep the composed feed
        }

        usort($items, static fn ($a, $b) => strcmp((string) $b['at'], (string) $a['at']));

        return array_slice($items, 0, 10);
    }

    /** Icon/colour class for an activity row from its ledger movement/type. */
    private function activityKind(string $movement, string $eventType): string
    {
        return match (true) {
            $movement === 'new' || $eventType === 'created'      => 'new',
            $movement === 'expansion'                            => 'up',
            $movement === 'contraction'                          => 'down',
            $movement === 'churn' || $eventType === 'cancelled'  => 'churn',
            default                                              => 'event',
        };
    }

    /** Human message for a subscription lifecycle event. */
    private function eventMessage(string $eventType, string $movement): string
    {
        return match ($eventType) {
            'created'         => 'started a new subscription',
            'cancelled'       => 'cancelled their subscription',
            'reactivated'     => 'reactivated their subscription',
            'trial_converted' => 'converted their trial to paid',
            'cycle_changed'   => 'changed billing cycle',
            'upgraded'        => 'upgraded their plan',
            'downgraded'      => 'downgraded their plan',
            default           => match ($movement) {
                'expansion'   => 'expanded their subscription',
                'contraction' => 'reduced their subscription',
                'churn'       => 'cancelled their subscription',
                default       => 'updated their subscription',
            },
        };
    }

    /**
     * Settled-payment method mix from paid invoices' payment_method — the honest
     * distribution of how collected revenue actually settled.
     *
     * @param  array<int, array<string, mixed>>  $invoices
     * @return array<int, array{method: string, count: int, amount: float, pct: int}>
     */
    private function methodMix(array $invoices): array
    {
        $mix = [];
        $totalPaid = 0;
        foreach ($invoices as $r) {
            if ($r['status'] !== 'paid') {
                continue;
            }
            $method = $r['method'] ?: 'other';
            $mix[$method]['count'] = ($mix[$method]['count'] ?? 0) + 1;
            $mix[$method]['amount'] = ($mix[$method]['amount'] ?? 0) + $r['amount_paid'];
            $totalPaid++;
        }

        $out = [];
        foreach ($mix as $method => $agg) {
            $out[] = [
                'method' => $method,
                'count'  => $agg['count'],
                'amount' => round($agg['amount'], 2),
                'pct'    => $totalPaid > 0 ? (int) round($agg['count'] / $totalPaid * 100) : 0,
            ];
        }
        usort($out, static fn ($a, $b) => $b['count'] <=> $a['count']);

        return $out;
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
            ->map(static fn ($g) => [
                'code'      => $g->code,
                'label'     => $g->label,
                'enabled'   => (bool) $g->is_enabled,
                'isDefault' => (bool) $g->is_default,
            ])->all();
    }
}
