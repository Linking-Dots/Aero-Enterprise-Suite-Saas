<?php

declare(strict_types=1);

namespace Aero\Platform\Services;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Platform\Models\ProductSubscription;
use Aero\Platform\Models\Subscription;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

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
        private readonly AuditServiceInterface $audit
    ) {}

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
                's.status', 's.amount', 'p.price_monthly', 's.trial_ends_at', 's.created_at'])
            ->map(fn ($r) => [
                'id'          => (string) $r->id,
                'kind'        => 'plan',
                'tenant'      => $r->tenant ?: '—',
                'label'       => $r->plan ?: 'No plan',
                'plan_id'     => $r->plan_id,
                'status'      => $r->status,
                // Normalise to the plan's MONTHLY rate for a consistent MRR
                // (subscriptions.amount can hold the yearly charge for annual plans).
                'amount'      => (float) ($r->price_monthly ?? 0),
                'trial_ends'  => $r->trial_ends_at,
                'since'       => $r->created_at,
            ])->all();

        $productRows = DB::table('product_subscriptions as ps')
            ->leftJoin('products as pr', 'pr.id', '=', 'ps.product_id')
            ->leftJoin('tenants as t', 't.id', '=', 'ps.tenant_id')
            ->whereNull('ps.deleted_at')
            ->orderByDesc('ps.created_at')
            ->limit(300)
            ->get(['ps.id', 't.name as tenant', 'ps.tenant_id', 'pr.name as product',
                'ps.status', 'ps.amount', 'ps.trial_ends_at', 'ps.created_at'])
            ->map(fn ($r) => [
                'id'          => (string) $r->id,
                'kind'        => 'product',
                'tenant'      => $r->tenant ?: '—',
                'label'       => $r->product ?: 'Product',
                'plan_id'     => null,
                'status'      => $r->status,
                'amount'      => (float) ($r->amount ?? 0),
                'trial_ends'  => $r->trial_ends_at,
                'since'       => $r->created_at,
            ])->all();

        return [
            'stats'                 => $this->stats($planRows, $productRows),
            'plan_subscriptions'    => $planRows,
            'product_subscriptions' => $productRows,
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
     * Sets status to cancelled and records reason.
     * Also cascade-cancels all active ProductSubscriptions for the tenant.
     */
    public function cancel(Subscription $subscription, string $reason): Subscription
    {
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

            DB::table('subscriptions')
                ->where('id', $subscription->id)
                ->update([
                    'plan_id' => $newPlanId,
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

            return $subscription;
        });
    }
}
