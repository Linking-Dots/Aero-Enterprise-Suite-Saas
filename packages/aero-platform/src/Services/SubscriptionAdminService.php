<?php

declare(strict_types=1);

namespace Aero\Platform\Services;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Platform\Mail\Subscription\PaymentReminderMail;
use Aero\Platform\Models\Plan;
use Aero\Platform\Models\ProductSubscription;
use Aero\Platform\Models\Subscription;
use Aero\Platform\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;

/**
 * Admin-only subscription management service.
 *
 * NOTE: This service performs direct DB updates on subscription records.
 * For Stripe-managed subscriptions, changes here will DIVERGE from Stripe
 * until the next webhook sync. A Stripe webhook sync job should be scheduled
 * for production to reconcile state. TODO: implement webhook reconciliation.
 */
class SubscriptionAdminService
{
    public function __construct(
        private readonly AuditServiceInterface $audit,
        private readonly SubscriptionAnalyticsService $analytics,
        private readonly SubscriptionEventRecorder $events
    ) {}

    /** Monthly-rate MRR for a plan subscription at a given cycle. */
    private function planMrr(?Plan $plan, string $cycle): float
    {
        if ($plan === null) {
            return 0.0;
        }

        return $cycle === 'yearly' ? round((float) $plan->price_annual / 12, 2) : (float) $plan->price_monthly;
    }

    /**
     * Unified command-centre payload: normalised plan + product subscriptions
     * (with tenant names resolved), health stats, and MRR. Guard-free query
     * builder so it's context-agnostic.
     *
     * @return array{stats: array, plan_subscriptions: array, product_subscriptions: array}
     */
    public function overview(): array
    {
        $planRows = DB::table('subscriptions as s')
            ->leftJoin('plans as p', 'p.id', '=', 's.plan_id')
            ->leftJoin('tenants as t', 't.id', '=', 's.tenant_id')
            ->whereNull('s.deleted_at')
            ->orderByDesc('s.created_at')
            ->limit(300)
            ->get(['s.id', 't.name as tenant', 's.tenant_id', 'p.name as plan', 'p.id as plan_id',
                's.status', 's.amount', 'p.price_monthly', 's.trial_ends_at', 's.created_at',
                's.billing_cycle', 's.currency', 's.quantity', 's.cancelled_at',
                's.current_period_end', 's.ends_at', 's.next_billing_date'])
            ->map(function ($r) {
                $renews = $r->next_billing_date ?? $r->current_period_end ?? $r->ends_at ?? $r->trial_ends_at;

                return [
                    'id'              => (string) $r->id,
                    'kind'            => 'plan',
                    'tenant'          => $r->tenant ?: '—',
                    'label'           => $r->plan ?: 'No plan',
                    'plan_id'         => $r->plan_id,
                    'status'          => $r->status,
                    // Normalise to the plan's MONTHLY rate for a consistent MRR
                    // (subscriptions.amount can hold the yearly charge for annual plans).
                    'amount'          => (float) ($r->price_monthly ?? 0),
                    'charge'          => (float) ($r->amount ?? 0),
                    'billing_cycle'   => $r->billing_cycle,
                    'currency'        => $r->currency ?: 'USD',
                    'quantity'        => $r->quantity,
                    'trial_ends'      => $r->trial_ends_at,
                    'since'           => $r->created_at,
                    'cancelled_at'    => $r->cancelled_at,
                    'renews_at'       => $renews,
                    'days_to_renewal' => $renews !== null ? (int) now()->startOfDay()->diffInDays(Carbon::parse($renews)->startOfDay(), false) : null,
                ];
            })->all();

        $productRows = DB::table('product_subscriptions as ps')
            ->leftJoin('products as pr', 'pr.id', '=', 'ps.product_id')
            ->leftJoin('tenants as t', 't.id', '=', 'ps.tenant_id')
            ->whereNull('ps.deleted_at')
            ->orderByDesc('ps.created_at')
            ->limit(300)
            ->get(['ps.id', 't.name as tenant', 'ps.tenant_id', 'pr.name as product',
                'ps.status', 'ps.amount', 'ps.trial_ends_at', 'ps.created_at',
                'ps.billing_cycle', 'ps.currency', 'ps.cancelled_at', 'ps.ends_at'])
            ->map(function ($r) {
                $monthly = ($r->billing_cycle === 'yearly') ? round((float) ($r->amount ?? 0) / 12, 2) : (float) ($r->amount ?? 0);
                $renews = $r->ends_at ?? $r->trial_ends_at;

                return [
                    'id'              => (string) $r->id,
                    'kind'            => 'product',
                    'tenant'          => $r->tenant ?: '—',
                    'label'           => $r->product ?: 'Product',
                    'plan_id'         => null,
                    'status'          => $r->status,
                    'amount'          => $monthly,
                    'charge'          => (float) ($r->amount ?? 0),
                    'billing_cycle'   => $r->billing_cycle,
                    'currency'        => $r->currency ?: 'USD',
                    'quantity'        => null,
                    'trial_ends'      => $r->trial_ends_at,
                    'since'           => $r->created_at,
                    'cancelled_at'    => $r->cancelled_at,
                    'renews_at'       => $renews,
                    'days_to_renewal' => $renews !== null ? (int) now()->startOfDay()->diffInDays(Carbon::parse($renews)->startOfDay(), false) : null,
                ];
            })->all();

        $analytics = $this->analytics->analytics();

        return [
            'stats'                 => array_merge($this->stats($planRows, $productRows), $analytics['extras']),
            'plan_subscriptions'    => $planRows,
            'product_subscriptions' => $productRows,
            'sparks'                => $analytics['sparks'],
            'mrr_trend'             => $analytics['mrr_trend'],
            'mrr_movement'          => $analytics['mrr_movement'],
            'cohorts'               => $analytics['cohorts'],
            'queues'                => $analytics['queues'],
        ];
    }

    /**
     * @param  array<int, array>  $plan
     * @param  array<int, array>  $product
     * @return array<string, int|float>
     */
    private function stats(array $plan, array $product): array
    {
        $all = array_merge($plan, $product);
        $count = fn (string $status) => count(array_filter($all, fn ($r) => $r['status'] === $status));
        $mrr = fn (array $rows) => array_sum(array_map(
            fn ($r) => in_array($r['status'], ['active', 'trialing'], true) ? $r['amount'] : 0,
            $rows
        ));

        return [
            'active'       => $count('active'),
            'trialing'     => $count('trialing'),
            'past_due'     => $count('past_due'),
            'incomplete'   => $count('incomplete'),
            'cancelled'    => $count('cancelled'),
            'total'        => count($all),
            'plan_total'   => count($plan),
            'product_total' => count($product),
            'plan_mrr'     => round($mrr($plan), 2),
            'product_mrr'  => round($mrr($product), 2),
            'mrr'          => round($mrr($plan) + $mrr($product), 2),
        ];
    }

    /**
     * Paginated list of subscriptions with tenant + plan eager-loaded.
     *
     * @return LengthAwarePaginator<Subscription>
     */
    public function list(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return Subscription::query()
            ->with(['plan:id,name,slug', 'owner'])
            ->when(
                isset($filters['status']),
                fn ($q) => $q->where('status', $filters['status'])
            )
            ->when(
                isset($filters['plan_id']),
                fn ($q) => $q->where('plan_id', $filters['plan_id'])
            )
            ->when(isset($filters['search']), function ($q) use ($filters) {
                $q->where('tenant_id', 'like', '%'.$filters['search'].'%');
            })
            ->latest('created_at')
            ->paginate($perPage);
    }

    /**
     * Cancel a subscription — admin override.
     *
     * mode 'immediate' (default): status → cancelled now, cascade-cancels the
     * tenant's active ProductSubscriptions.
     * mode 'period_end': access continues — ends_at is pinned to the current
     * period end and the reason recorded; the lifecycle scheduler completes
     * the cancellation when the period lapses. Falls back to immediate when
     * no current period end exists.
     */
    public function cancel(Subscription $subscription, string $reason, string $mode = 'immediate'): Subscription
    {
        if ($mode === 'period_end') {
            $periodEnd = $subscription->current_period_end ?? $subscription->next_billing_date;
            if ($periodEnd !== null) {
                return DB::transaction(function () use ($subscription, $reason, $periodEnd) {
                    DB::table('subscriptions')
                        ->where('id', $subscription->id)
                        ->update([
                            'ends_at' => $periodEnd,
                            'cancel_reason' => $reason,
                            'updated_at' => now(),
                        ]);

                    $subscription->refresh();

                    $this->audit->log(
                        'subscription.cancel_scheduled',
                        'cancel_scheduled',
                        $subscription,
                        "Subscription [{$subscription->id}] scheduled to cancel at period end.",
                        [],
                        ['ends_at' => (string) $periodEnd, 'cancel_reason' => $reason]
                    );

                    return $subscription;
                });
            }
            // No period boundary on record — fall through to immediate.
        }

        return DB::transaction(function () use ($subscription, $reason) {
            // Bypass SubscriptionImmutableObserver by using DB::table directly
            // so the admin can force-cancel without triggering the immutability check
            // on billing_cycle/plan_id.
            DB::table('subscriptions')
                ->where('id', $subscription->id)
                ->update([
                    'status' => Subscription::STATUS_CANCELLED,
                    'cancelled_at' => now(),
                    'cancel_reason' => $reason,
                    'updated_at' => now(),
                ]);

            $subscription->refresh();

            // Cascade-cancel all active ProductSubscriptions for this tenant
            if ($subscription->tenant_id) {
                ProductSubscription::where('tenant_id', $subscription->tenant_id)
                    ->whereIn('status', ['active', 'trialing'])
                    ->update([
                        'status' => 'cancelled',
                        'cancelled_at' => now(),
                        'cancellation_reason' => "Plan subscription cancelled: {$reason}",
                    ]);

                $this->audit->log(
                    AuditEventType::PRODUCT_SUBSCRIPTIONS_CANCELLED->value,
                    'cancelled',
                    $subscription,
                    "All product subscriptions cancelled for tenant {$subscription->tenant_id}"
                );
            }

            $this->audit->log(
                AuditEventType::SUBSCRIPTION_CANCELLED->value,
                'cancelled',
                $subscription,
                "Subscription [{$subscription->id}] cancelled by admin.",
                ['status' => Subscription::STATUS_ACTIVE],
                ['status' => Subscription::STATUS_CANCELLED, 'cancel_reason' => $reason]
            );

            $lostMrr = $this->planMrr(Plan::find($subscription->plan_id), (string) $subscription->billing_cycle);
            $this->events->record((string) $subscription->id, 'plan', $subscription->tenant_id, 'cancelled', $lostMrr, 0.0, $subscription->currency ?? 'USD');

            return $subscription;
        });
    }

    /**
     * Change the plan on a subscription — admin override.
     */
    public function changePlan(Subscription $subscription, string $newPlanId): Subscription
    {
        return DB::transaction(function () use ($subscription, $newPlanId) {
            $oldPlanId = $subscription->plan_id;
            $cycle = (string) $subscription->billing_cycle;
            $oldMrr = $this->planMrr(Plan::find($oldPlanId), $cycle);
            $newMrr = $this->planMrr(Plan::find($newPlanId), $cycle);

            DB::table('subscriptions')
                ->where('id', $subscription->id)
                ->update([
                    'plan_id' => $newPlanId,
                    'amount' => $cycle === 'yearly'
                        ? (float) (Plan::find($newPlanId)?->price_annual ?? $subscription->amount)
                        : $newMrr,
                    'updated_at' => now(),
                ]);

            $subscription->refresh();

            $this->audit->log(
                AuditEventType::SUBSCRIPTION_UPGRADED->value,
                'plan_changed',
                $subscription,
                "Subscription [{$subscription->id}] plan changed by admin.",
                ['plan_id' => $oldPlanId],
                ['plan_id' => $newPlanId]
            );

            $eventType = $newMrr >= $oldMrr ? 'upgraded' : 'downgraded';
            $this->events->record((string) $subscription->id, 'plan', $subscription->tenant_id, $eventType, $oldMrr, $newMrr, $subscription->currency ?? 'USD');

            return $subscription;
        });
    }

    /**
     * Upgrade a subscription to a new plan — alias for changePlan().
     *
     * Product subscriptions are independent of plan — they are preserved on plan upgrade.
     */
    public function upgrade(Subscription $sub, int $newPlanId, int $actorId): Subscription
    {
        // Product subscriptions are independent of plan — they are preserved on plan upgrade.
        return $this->changePlan($sub, (string) $newPlanId);
    }

    /**
     * Reactivate a cancelled or past-due subscription.
     */
    public function reactivate(Subscription $subscription): Subscription
    {
        return DB::transaction(function () use ($subscription) {
            DB::table('subscriptions')
                ->where('id', $subscription->id)
                ->update([
                    'status' => Subscription::STATUS_ACTIVE,
                    'cancelled_at' => null,
                    'cancel_reason' => null,
                    'updated_at' => now(),
                ]);

            $subscription->refresh();

            $this->audit->log(
                'subscription.reactivated',
                'reactivated',
                $subscription,
                "Subscription [{$subscription->id}] reactivated by admin."
            );

            $mrr = $this->planMrr(Plan::find($subscription->plan_id), (string) $subscription->billing_cycle);
            $this->events->record((string) $subscription->id, 'plan', $subscription->tenant_id, 'reactivated', 0.0, $mrr, $subscription->currency ?? 'USD');

            return $subscription;
        });
    }

    /* ==================================================================
     |  Command-centre domain operations
     * ================================================================== */

    /**
     * Guided create — attach a plan or product subscription to a tenant.
     *
     * @param array{tenant_id:string, kind:string, plan_id?:string, product_id?:string, billing_cycle:string, trial_days?:int} $data
     */
    public function store(array $data): Subscription|ProductSubscription
    {
        return DB::transaction(function () use ($data) {
            $trialDays = (int) ($data['trial_days'] ?? 0);
            $cycle = $data['billing_cycle'];
            $now = now();

            if ($data['kind'] === 'plan') {
                $plan = Plan::findOrFail($data['plan_id']);
                $amount = $cycle === 'yearly' ? (float) $plan->price_annual : (float) $plan->price_monthly;

                $sub = Subscription::create([
                    'tenant_id' => $data['tenant_id'],
                    'billable_type' => Tenant::class,
                    'billable_id' => $data['tenant_id'],
                    'plan_id' => $plan->id,
                    'billing_cycle' => $cycle,
                    'amount' => $amount,
                    'currency' => $plan->currency ?? 'USD',
                    'status' => $trialDays > 0 ? Subscription::STATUS_TRIALING : Subscription::STATUS_ACTIVE,
                    'trial_starts_at' => $trialDays > 0 ? $now : null,
                    'trial_ends_at' => $trialDays > 0 ? $now->copy()->addDays($trialDays) : null,
                    'starts_at' => $now,
                    'current_period_start' => $now,
                    'current_period_end' => $cycle === 'yearly' ? $now->copy()->addYear() : $now->copy()->addMonth(),
                    'next_billing_date' => $trialDays > 0
                        ? $now->copy()->addDays($trialDays)
                        : ($cycle === 'yearly' ? $now->copy()->addYear() : $now->copy()->addMonth()),
                ]);

                $this->audit->log(
                    'subscription.created',
                    'created',
                    $sub,
                    "Subscription [{$sub->id}] created by admin — plan {$plan->name}, {$cycle}."
                );

                // A trial books no MRR until it converts, so new-revenue ledger
                // entry is deferred to convertTrial; paid signups book now.
                if ($trialDays === 0) {
                    $this->events->record((string) $sub->id, 'plan', $sub->tenant_id, 'created', 0.0, $this->planMrr($plan, $cycle), $plan->currency ?? 'USD');
                }

                return $sub;
            }

            $product = DB::table('products')->where('id', $data['product_id'])->first();
            if ($product === null) {
                throw new InvalidArgumentException('Unknown product.');
            }
            $amount = $cycle === 'yearly' ? (float) $product->yearly_price : (float) $product->monthly_price;

            $sub = ProductSubscription::create([
                'tenant_id' => $data['tenant_id'],
                'product_id' => $product->id,
                'billing_cycle' => $cycle,
                'amount' => $amount,
                'currency' => 'USD',
                'status' => $trialDays > 0 ? 'trialing' : 'active',
                'trial_starts_at' => $trialDays > 0 ? $now : null,
                'trial_ends_at' => $trialDays > 0 ? $now->copy()->addDays($trialDays) : null,
                'starts_at' => $now,
            ]);

            $this->audit->log(
                'product_subscription.created',
                'created',
                $sub,
                "Product subscription [{$sub->id}] created by admin — {$product->name}, {$cycle}."
            );

            if ($trialDays === 0) {
                $monthly = $cycle === 'yearly' ? round($amount / 12, 2) : $amount;
                $this->events->record((string) $sub->id, 'product', $sub->tenant_id, 'created', 0.0, $monthly, 'USD');
            }

            return $sub;
        });
    }

    /** Pause an active subscription (billing halted until resume). */
    public function pause(Subscription $subscription): Subscription
    {
        if ($subscription->status !== Subscription::STATUS_ACTIVE) {
            throw new InvalidArgumentException('Only active subscriptions can be paused.');
        }

        return $this->adminStatusChange($subscription, 'paused', 'subscription.paused', 'paused');
    }

    /** Resume a paused subscription. */
    public function resume(Subscription $subscription): Subscription
    {
        if ($subscription->status !== 'paused') {
            throw new InvalidArgumentException('Only paused subscriptions can be resumed.');
        }

        return $this->adminStatusChange($subscription, Subscription::STATUS_ACTIVE, 'subscription.resumed', 'resumed');
    }

    /** Extend a running trial by N days. */
    public function extendTrial(Subscription $subscription, int $days): Subscription
    {
        if ($subscription->status !== Subscription::STATUS_TRIALING || $subscription->trial_ends_at === null) {
            throw new InvalidArgumentException('Only trialing subscriptions can be extended.');
        }

        return DB::transaction(function () use ($subscription, $days) {
            $newEnd = Carbon::parse($subscription->trial_ends_at)->addDays($days);
            DB::table('subscriptions')->where('id', $subscription->id)->update([
                'trial_ends_at' => $newEnd,
                'next_billing_date' => $newEnd,
                'updated_at' => now(),
            ]);
            $subscription->refresh();

            $this->audit->log(
                'subscription.trial_extended',
                'trial_extended',
                $subscription,
                "Trial extended {$days} day(s) to {$newEnd->toDateString()}."
            );

            return $subscription;
        });
    }

    /** Convert a trial to a paid, active subscription now. */
    public function convertTrial(Subscription $subscription): Subscription
    {
        if ($subscription->status !== Subscription::STATUS_TRIALING) {
            throw new InvalidArgumentException('Only trialing subscriptions can be converted.');
        }

        return DB::transaction(function () use ($subscription) {
            $now = now();
            $end = $subscription->billing_cycle === 'yearly' ? $now->copy()->addYear() : $now->copy()->addMonth();
            DB::table('subscriptions')->where('id', $subscription->id)->update([
                'status' => Subscription::STATUS_ACTIVE,
                'trial_ends_at' => $now,
                'current_period_start' => $now,
                'current_period_end' => $end,
                'next_billing_date' => $end,
                'updated_at' => $now,
            ]);
            $subscription->refresh();

            $this->audit->log(
                'subscription.trial_converted',
                'trial_converted',
                $subscription,
                "Trial converted to active — first period ends {$end->toDateString()}."
            );

            // Trial start booked no MRR; conversion is where the revenue lands.
            $mrr = $this->planMrr(Plan::find($subscription->plan_id), (string) $subscription->billing_cycle);
            $this->events->record((string) $subscription->id, 'plan', $subscription->tenant_id, 'trial_converted', 0.0, $mrr, $subscription->currency ?? 'USD');

            return $subscription;
        });
    }

    /** Switch billing cycle (monthly ↔ yearly) and re-derive the charge amount from the plan. */
    public function changeCycle(Subscription $subscription, string $cycle): Subscription
    {
        return DB::transaction(function () use ($subscription, $cycle) {
            $plan = Plan::find($subscription->plan_id);
            $amount = $plan === null
                ? $subscription->amount
                : ($cycle === 'yearly' ? (float) $plan->price_annual : (float) $plan->price_monthly);
            $old = (string) $subscription->billing_cycle;
            $oldMrr = $this->planMrr($plan, $old);
            $newMrr = $this->planMrr($plan, $cycle);

            DB::table('subscriptions')->where('id', $subscription->id)->update([
                'billing_cycle' => $cycle,
                'amount' => $amount,
                'updated_at' => now(),
            ]);
            $subscription->refresh();

            $this->audit->log(
                'subscription.cycle_changed',
                'cycle_changed',
                $subscription,
                "Billing cycle changed by admin.",
                ['billing_cycle' => $old],
                ['billing_cycle' => $cycle, 'amount' => $amount]
            );

            // A yearly switch lowers the effective monthly rate (annual discount)
            // → contraction; monthly→yearly is neutral-to-expansion by the same
            // math. The ledger records whatever the priced delta actually is.
            $this->events->record((string) $subscription->id, 'plan', $subscription->tenant_id, 'cycle_changed', $oldMrr, $newMrr, $subscription->currency ?? 'USD');

            return $subscription;
        });
    }

    /**
     * Request a dunning charge retry. There is no card-charge pipeline for
     * lifecycle-managed subscriptions yet, so this records the request in the
     * audit trail for the dunning scheduler — it never fakes a payment result.
     */
    public function retryCharge(Subscription $subscription): void
    {
        $this->audit->log(
            'subscription.dunning.retry_requested',
            'retry_requested',
            $subscription,
            "Payment retry requested by admin for subscription [{$subscription->id}]."
        );
    }

    /** Queue a payment-reminder email to the tenant's billing address. */
    public function remind(Subscription $subscription): bool
    {
        $tenant = DB::table('tenants')->where('id', $subscription->tenant_id)->first(['name', 'email']);
        $plan = DB::table('plans')->where('id', $subscription->plan_id)->value('name');

        $sent = false;
        if ($tenant?->email) {
            Mail::to($tenant->email)->queue(new PaymentReminderMail(
                tenantName: $tenant->name ?? 'there',
                planName: $plan ?? 'your subscription',
                amount: (float) $subscription->amount,
                currency: $subscription->currency ?? 'USD',
            ));
            $sent = true;
        }

        $this->audit->log(
            'subscription.dunning.reminder_sent',
            'reminder_sent',
            $subscription,
            $sent
                ? "Payment reminder queued to {$tenant->email}."
                : "Payment reminder requested but no tenant email on record."
        );

        return $sent;
    }

    /**
     * Bulk cancel / remind. Each item is processed independently so one
     * failure cannot roll back the others.
     *
     * @param  array<int, array{kind:string, id:string}>  $ids
     * @return array{ok:int, failed:int}
     */
    public function bulk(array $ids, string $action, string $reason = ''): array
    {
        $ok = 0;
        $failed = 0;
        foreach ($ids as $item) {
            try {
                if (($item['kind'] ?? '') === 'product') {
                    if ($action === 'cancel') {
                        $ps = ProductSubscription::findOrFail($item['id']);
                        $ps->update([
                            'status' => 'cancelled',
                            'cancelled_at' => now(),
                            'cancellation_reason' => $reason ?: 'Bulk cancellation',
                        ]);
                        $ok++;
                    }

                    continue; // remind targets plan subscriptions only
                }

                $sub = Subscription::findOrFail($item['id']);
                match ($action) {
                    'cancel' => $this->cancel($sub, $reason ?: 'Bulk cancellation'),
                    'remind' => $this->remind($sub),
                    default => throw new InvalidArgumentException("Unknown bulk action [$action]."),
                };
                $ok++;
            } catch (\Throwable) {
                $failed++;
            }
        }

        return ['ok' => $ok, 'failed' => $failed];
    }

    /**
     * Drawer detail: recent invoices, audit activity, payment method, discount.
     *
     * @return array{invoices: array, activity: array, payment_method: ?array, discount_amount: float}
     */
    public function detail(string $kind, string $id): array
    {
        $invoices = [];
        $pm = null;
        $discount = 0.0;

        if ($kind === 'plan') {
            // select('*') + null-coalesced reads: the Cashier pm_* columns are
            // added by a guarded migration and can be absent on older schemas.
            $sub = DB::table('subscriptions')->where('id', $id)->first();
            if ($sub !== null) {
                $pmType = $sub->pm_type ?? null;
                $pm = $pmType ? ['type' => $pmType, 'last_four' => $sub->pm_last_four ?? null] : null;
                $discount = (float) ($sub->discount_amount ?? 0);
            }
            $invoices = DB::table('invoices')
                ->where('subscription_id', $id)
                ->orderByDesc('created_at')
                ->limit(8)
                ->get(['invoice_number', 'created_at', 'total', 'status'])
                ->map(fn ($i) => [
                    'number' => $i->invoice_number,
                    'date'   => $i->created_at,
                    'amount' => (float) $i->total,
                    'status' => $i->status,
                ])->all();
        } else {
            $discount = (float) (DB::table('product_subscriptions')->where('id', $id)->value('discount_amount') ?? 0);
        }

        // Activity merges two real sources: the domain-specific
        // subscription_audit_logs (lifecycle history, FK'd to the subscription)
        // and the generic platform_audit_logs where AuditService routes admin
        // actions in platform context. Both are central-owned; merged, sorted,
        // capped. Each read is guarded so a missing table can't 500 the drawer.
        $activity = [];
        try {
            if ($kind === 'plan') {
                $activity = DB::table('subscription_audit_logs')
                    ->where('subscription_id', $id)
                    ->orderByDesc('created_at')
                    ->limit(12)
                    ->get(['event_type', 'description', 'created_at'])
                    ->map(fn ($a) => [
                        'event'  => $a->event_type,
                        'action' => $a->event_type,
                        'detail' => $a->description,
                        'actor'  => null,
                        'at'     => $a->created_at,
                    ])->all();
            }
        } catch (\Illuminate\Database\QueryException) {
            // domain table absent — fall through to platform audits
        }

        try {
            $subjectType = $kind === 'plan' ? Subscription::class : ProductSubscription::class;
            [$conn, $table] = (is_saas_mode() && ! (function_exists('tenancy') && tenancy()->initialized))
                ? [central_connection(), 'platform_audit_logs']
                : [null, 'audit_logs'];

            $adminActs = DB::connection($conn)->table($table)
                ->where('subject_type', $subjectType)
                ->where('subject_id', $id)
                ->orderByDesc('created_at')
                ->limit(12)
                ->get(['event_type', 'action', 'description', 'actor_name', 'created_at'])
                ->map(fn ($a) => [
                    'event'  => $a->event_type,
                    'action' => $a->action,
                    'detail' => $a->description,
                    'actor'  => $a->actor_name,
                    'at'     => $a->created_at,
                ])->all();

            $activity = array_merge($activity, $adminActs);
            usort($activity, fn ($a, $b) => strcmp((string) $b['at'], (string) $a['at']));
            $activity = array_slice($activity, 0, 12);
        } catch (\Illuminate\Database\QueryException) {
            // platform audit table absent in this context — keep domain events
        }

        return [
            'invoices' => $invoices,
            'activity' => $activity,
            'payment_method' => $pm,
            'discount_amount' => $discount,
        ];
    }

    /**
     * All subscriptions (both kinds) flattened for CSV export.
     *
     * @return array<int, array<string, mixed>>
     */
    public function exportRows(): array
    {
        $overview = $this->overview();
        $rows = array_merge($overview['plan_subscriptions'], $overview['product_subscriptions']);

        return array_map(fn ($r) => [
            'kind'          => $r['kind'],
            'tenant'        => $r['tenant'],
            'label'         => $r['label'],
            'status'        => $r['status'],
            'billing_cycle' => $r['billing_cycle'] ?? '',
            'mrr'           => $r['amount'],
            'charge'        => $r['charge'],
            'currency'      => $r['currency'],
            'renews_at'     => $r['renews_at'] ?? '',
            'trial_ends_at' => $r['trial_ends'] ?? '',
            'started_at'    => $r['since'] ?? '',
            'cancelled_at'  => $r['cancelled_at'] ?? '',
        ], $rows);
    }

    /** Shared admin-override status flip with audit. */
    private function adminStatusChange(Subscription $subscription, string $to, string $event, string $action): Subscription
    {
        return DB::transaction(function () use ($subscription, $to, $event, $action) {
            $from = $subscription->status;
            DB::table('subscriptions')->where('id', $subscription->id)->update([
                'status' => $to,
                'updated_at' => now(),
            ]);
            $subscription->refresh();

            $this->audit->log(
                $event,
                $action,
                $subscription,
                "Subscription [{$subscription->id}] {$action} by admin.",
                ['status' => $from],
                ['status' => $to]
            );

            return $subscription;
        });
    }
}
