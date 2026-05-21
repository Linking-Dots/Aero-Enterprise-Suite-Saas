<?php

declare(strict_types=1);

namespace Aero\Platform\Services\Finance;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Platform\Contracts\BillableSubscription;
use Aero\Platform\Models\Plan;
use Aero\Platform\Models\PlatformSetting;
use Aero\Platform\Models\Product;
use Aero\Platform\Models\ProductSubscription;
use Aero\Platform\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SubscriptionLifecycleService
{
    public function __construct(
        private readonly AuditServiceInterface $audit
    ) {}

    /**
     * UNION of trialing plan subscriptions and trialing product subscriptions.
     * Returns a plain paginator-compatible array with a `billable_type` discriminator.
     *
     * @return array{data: array<int, array<string, mixed>>, total: int}
     */
    public function listTrials(array $filters = []): array
    {
        $planTrials = Subscription::query()
            ->where('status', 'trialing')
            ->when(isset($filters['billable_type']) && $filters['billable_type'] === 'plan', fn ($q) => $q)
            ->when(isset($filters['billable_type']) && $filters['billable_type'] === 'product', fn ($q) => $q->whereRaw('1=0'))
            ->get()
            ->map(fn (Subscription $s) => array_merge($s->toArray(), ['billable_type_label' => 'plan']));

        $productTrials = ProductSubscription::query()
            ->where('status', 'trialing')
            ->when(isset($filters['billable_type']) && $filters['billable_type'] === 'product', fn ($q) => $q)
            ->when(isset($filters['billable_type']) && $filters['billable_type'] === 'plan', fn ($q) => $q->whereRaw('1=0'))
            ->get()
            ->map(fn (ProductSubscription $s) => array_merge($s->toArray(), ['billable_type_label' => 'product']));

        $merged = $planTrials->merge($productTrials)->sortBy('trial_ends_at')->values()->all();

        return ['data' => $merged, 'total' => count($merged)];
    }

    public function extendTrial(BillableSubscription $subscription, int $days, int $actorId): BillableSubscription
    {
        return DB::transaction(function () use ($subscription, $days): BillableSubscription {
            $current = $subscription->getTrialEndsAt() ?? now();
            $newEnd = $current->copy()->addDays($days);

            $subscription->fill(['trial_ends_at' => $newEnd]);
            $subscription->save();

            $this->audit->log(
                event: AuditEventType::SUBSCRIPTION_TRIAL_EXTENDED->value,
                action: 'trial_extended',
                subject: null,
                description: "Trial extended by {$days} days for {$subscription->billableType()} #{$subscription->getKey()}.",
                metadata: [
                    'billable_type' => $subscription->billableType(),
                    'subscription_id' => $subscription->getKey(),
                    'days' => $days,
                    'new_trial_ends_at' => $newEnd->toISOString(),
                ],
            );

            return $subscription->fresh();
        });
    }

    /**
     * Convert a trialing subscription to active.
     * Fails if subscription is already active.
     *
     * @param  int  $targetId  Plan ID (for plan subscriptions) or Product ID (for product subscriptions)
     */
    public function convertTrial(BillableSubscription $subscription, int $targetId, int $actorId): BillableSubscription
    {
        return DB::transaction(function () use ($subscription, $targetId): BillableSubscription {
            if ($subscription->getStatus() === 'active') {
                throw new InvalidArgumentException('Subscription is already active and cannot be trial-converted.');
            }

            $subscription->fill([
                'status' => 'active',
                'trial_ends_at' => null,
                'starts_at' => now(),
            ]);
            $subscription->save();

            $this->audit->log(
                event: AuditEventType::SUBSCRIPTION_TRIAL_CONVERTED->value,
                action: 'trial_converted',
                subject: null,
                description: "Trial converted for {$subscription->billableType()} #{$subscription->getKey()}.",
                metadata: [
                    'billable_type' => $subscription->billableType(),
                    'subscription_id' => $subscription->getKey(),
                    'target_id' => $targetId,
                ],
            );

            return $subscription->fresh();
        });
    }

    /**
     * Preview proration for a plan change.
     * Returns estimated credit and charge amounts.
     */
    public function previewProration(BillableSubscription $subscription, int $newTargetId): array
    {
        // Stub: real proration math uses billing cycle, days remaining, and new price.
        $newPrice = 0.0;
        if ($subscription->billableType() === 'plan') {
            $newPrice = (float) (Plan::find($newTargetId)?->monthly_price ?? 0);
        } else {
            $newPrice = (float) (Product::find($newTargetId)?->price ?? 0);
        }

        return [
            'credit_amount' => 0.0,
            'charge_amount' => $newPrice,
            'net_amount' => $newPrice,
            'description' => 'Proration preview (stub).',
        ];
    }

    /**
     * Execute a plan / product change on a subscription.
     * ARCH NOTE: changing a plan subscription does NOT cascade to ProductSubscription rows.
     */
    public function executeChange(BillableSubscription $subscription, int $newTargetId, int $actorId): BillableSubscription
    {
        return DB::transaction(function () use ($subscription, $newTargetId): BillableSubscription {
            if ($subscription->billableType() === 'plan') {
                /** @var Subscription $planSub */
                $planSub = $subscription;
                $planSub->fill(['plan_id' => $newTargetId]);
                $planSub->save();
                // ProductSubscription rows are intentionally untouched.
            } else {
                /** @var ProductSubscription $productSub */
                $productSub = $subscription;
                $productSub->fill(['product_id' => $newTargetId]);
                $productSub->save();
            }

            $this->audit->log(
                event: AuditEventType::SUBSCRIPTION_PLAN_CHANGED->value,
                action: 'plan_changed',
                subject: null,
                description: "Subscription changed to target #{$newTargetId} for {$subscription->billableType()} #{$subscription->getKey()}.",
                metadata: [
                    'billable_type' => $subscription->billableType(),
                    'subscription_id' => $subscription->getKey(),
                    'new_target_id' => $newTargetId,
                ],
            );

            return $subscription->fresh();
        });
    }

    public function pause(BillableSubscription $subscription, ?Carbon $resumeAt, int $actorId): BillableSubscription
    {
        return DB::transaction(function () use ($subscription, $resumeAt): BillableSubscription {
            $updates = ['status' => 'paused'];
            if ($resumeAt !== null) {
                $updates['ends_at'] = $resumeAt;
            }
            $subscription->fill($updates);
            $subscription->save();

            $this->audit->log(
                event: AuditEventType::SUBSCRIPTION_PAUSED->value,
                action: 'paused',
                subject: null,
                description: "Subscription paused for {$subscription->billableType()} #{$subscription->getKey()}.",
                metadata: [
                    'billable_type' => $subscription->billableType(),
                    'subscription_id' => $subscription->getKey(),
                    'resume_at' => $resumeAt?->toISOString(),
                ],
            );

            return $subscription->fresh();
        });
    }

    public function resume(BillableSubscription $subscription, int $actorId): BillableSubscription
    {
        return DB::transaction(function () use ($subscription): BillableSubscription {
            $subscription->fill(['status' => 'active', 'ends_at' => null]);
            $subscription->save();

            $this->audit->log(
                event: AuditEventType::SUBSCRIPTION_RESUMED->value,
                action: 'resumed',
                subject: null,
                description: "Subscription resumed for {$subscription->billableType()} #{$subscription->getKey()}.",
                metadata: [
                    'billable_type' => $subscription->billableType(),
                    'subscription_id' => $subscription->getKey(),
                ],
            );

            return $subscription->fresh();
        });
    }

    /**
     * List cancellations for BOTH plan and product subscriptions.
     */
    public function listCancellations(array $filters = []): array
    {
        $planCancellations = Subscription::query()
            ->where('status', 'cancelled')
            ->when(isset($filters['billable_type']) && $filters['billable_type'] === 'plan', fn ($q) => $q)
            ->when(isset($filters['billable_type']) && $filters['billable_type'] === 'product', fn ($q) => $q->whereRaw('1=0'))
            ->latest('cancelled_at')
            ->get()
            ->map(fn (Subscription $s) => array_merge($s->toArray(), ['billable_type_label' => 'plan']));

        $productCancellations = ProductSubscription::query()
            ->where('status', 'cancelled')
            ->when(isset($filters['billable_type']) && $filters['billable_type'] === 'product', fn ($q) => $q)
            ->when(isset($filters['billable_type']) && $filters['billable_type'] === 'plan', fn ($q) => $q->whereRaw('1=0'))
            ->latest('cancelled_at')
            ->get()
            ->map(fn (ProductSubscription $s) => array_merge($s->toArray(), ['billable_type_label' => 'product']));

        return $planCancellations->merge($productCancellations)->values()->all();
    }

    /**
     * Update cancellation flow configuration.
     * Config is keyed by billable type so plan and product cancellations can have distinct flows.
     *
     * Expected shape: ['plan' => [...survey/offers...], 'product' => [...]]
     */
    public function updateCancellationFlow(array $config, int $actorId): array
    {
        return DB::transaction(function () use ($config): array {
            $setting = PlatformSetting::firstOrCreate([]);
            $setting->setValue('cancellation_flow', $config);
            $setting->save();

            $this->audit->log(
                event: AuditEventType::SUBSCRIPTION_CANCELLATION_FLOW_UPDATED->value,
                action: 'updated',
                subject: $setting,
                description: 'Cancellation flow configuration updated.',
                after: $config,
            );

            return $config;
        });
    }
}
