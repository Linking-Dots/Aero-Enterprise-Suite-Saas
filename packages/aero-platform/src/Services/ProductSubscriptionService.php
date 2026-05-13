<?php

namespace Aero\Platform\Services;

use Aero\Platform\Models\Product;
use Aero\Platform\Models\ProductSubscription;
use Illuminate\Support\Str;

class ProductSubscriptionService
{
    public function __construct(
        private readonly ProductAccessService $accessService
    ) {}

    /**
     * Create a new product subscription for a tenant (called after successful payment).
     */
    public function subscribe(
        string  $tenantId,
        string  $productCode,
        string  $billingCycle,
        ?string $externalSubscriptionId = null,
        ?int    $trialDays              = null
    ): ProductSubscription {
        $product  = Product::active()->where('code', $productCode)->firstOrFail();
        $amount   = $billingCycle === 'yearly' ? $product->yearly_price : $product->monthly_price;
        $startsAt = now();
        $status   = $trialDays ? 'trialing' : 'active';

        $subscription = ProductSubscription::create([
            'id'                       => (string) Str::uuid(),
            'tenant_id'                => $tenantId,
            'product_id'               => $product->id,
            'billing_cycle'            => $billingCycle,
            'amount'                   => $amount,
            'currency'                 => $product->currency,
            'status'                   => $status,
            'starts_at'                => $startsAt,
            'ends_at'                  => null,
            'trial_starts_at'          => $trialDays ? $startsAt : null,
            'trial_ends_at'            => $trialDays ? now()->addDays($trialDays) : null,
            'external_subscription_id' => $externalSubscriptionId,
        ]);

        $this->accessService->flushCache($tenantId);

        return $subscription;
    }

    /**
     * Cancel a subscription at period end.
     */
    public function cancel(string $subscriptionId, string $reason = ''): ProductSubscription
    {
        $subscription = ProductSubscription::findOrFail($subscriptionId);
        $subscription->update([
            'status'              => 'cancelled',
            'cancelled_at'        => now(),
            'cancellation_reason' => $reason,
        ]);

        $this->accessService->flushCache($subscription->tenant_id);

        return $subscription->fresh();
    }

    /**
     * Mark subscription as expired (called by renewal cron on payment failure).
     */
    public function expire(string $subscriptionId): ProductSubscription
    {
        $subscription = ProductSubscription::findOrFail($subscriptionId);
        $subscription->update([
            'status'  => 'expired',
            'ends_at' => now(),
        ]);

        $this->accessService->flushCache($subscription->tenant_id);

        return $subscription->fresh();
    }

    /**
     * Flush the product-access cache for a tenant.
     *
     * Called by webhook handlers after a Stripe event mutates ProductSubscription
     * rows so the tenant sees updated module access immediately.
     */
    public function flushTenantCache(string $tenantId): void
    {
        $this->accessService->flushCache($tenantId);
    }
}
