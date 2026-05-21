<?php

declare(strict_types=1);

namespace Aero\Platform\Services;

use Aero\Core\Services\Audit\AuditEventType;
use Aero\Core\Services\AuditService;
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
        private readonly AuditService $audit
    ) {}

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
                    $subscription,
                    "All product subscriptions cancelled for tenant {$subscription->tenant_id}"
                );
            }

            $this->audit->log(
                AuditEventType::SUBSCRIPTION_CANCELLED->value,
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
                $subscription,
                "Subscription [{$subscription->id}] reactivated by admin."
            );

            return $subscription;
        });
    }
}
