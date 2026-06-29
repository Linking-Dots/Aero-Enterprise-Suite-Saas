<?php

declare(strict_types=1);

namespace Aero\Platform\Console\Commands;

use Aero\Platform\Events\ProductSubscriptionChanged;
use Aero\Platform\Models\ProductSubscription;
use Aero\Platform\Models\Subscription;
use Aero\Platform\Models\SubscriptionModule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Expire trial subscriptions whose trial_ends_at has passed.
 *
 * Handles non-Stripe tenants (SSLCommerz, manual billing) who don't receive
 * Stripe webhook events for trial expiration. Covers plan trials (subscriptions),
 * the canonical product trials (product_subscriptions), and the legacy module
 * trials (subscription_modules) still present from older data.
 */
class ExpireTrialSubscriptions extends Command
{
    protected $signature = 'subscriptions:expire-trials';

    protected $description = 'Expire plan and module trial subscriptions that have passed their trial end date';

    public function handle(): int
    {
        $this->info('Processing expired trials...');

        $planTrials = $this->expirePlanTrials();
        $productTrials = $this->expireProductTrials();
        $moduleTrials = $this->expireModuleTrials();

        $this->info("Plan trials expired: {$planTrials}");
        $this->info("Product trials expired: {$productTrials}");
        $this->info("Module trials expired (legacy): {$moduleTrials}");

        return self::SUCCESS;
    }

    /**
     * Expire product subscription trials (product_subscriptions table) — the canonical
     * source of module access. Fires ProductSubscriptionChanged('cancelled') so the
     * tenant module catalog re-syncs and role access for the lapsed module is suspended.
     */
    protected function expireProductTrials(): int
    {
        $expired = 0;

        $trials = ProductSubscription::where('status', 'trialing')
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<=', now())
            ->get();

        foreach ($trials as $productSub) {
            try {
                // Skip externally-managed (Stripe etc.) subscriptions — the provider's
                // webhook drives their lifecycle.
                if ($productSub->external_subscription_id) {
                    continue;
                }

                $productSub->update([
                    'status' => 'expired',
                    'ends_at' => $productSub->trial_ends_at,
                ]);

                event(new ProductSubscriptionChanged($productSub, 'cancelled'));

                Log::info('Product trial subscription expired', [
                    'product_subscription_id' => $productSub->id,
                    'product_id' => $productSub->product_id,
                    'tenant_id' => $productSub->tenant_id,
                ]);

                $expired++;
            } catch (\Exception $e) {
                Log::error('Failed to expire product trial subscription', [
                    'product_subscription_id' => $productSub->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $expired;
    }

    /**
     * Expire plan subscription trials (subscriptions table).
     */
    protected function expirePlanTrials(): int
    {
        $expired = 0;

        $trials = Subscription::where('status', Subscription::STATUS_TRIALING)
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<=', now())
            ->get();

        foreach ($trials as $subscription) {
            try {
                // If Stripe manages this subscription, skip — Stripe will send the webhook
                if ($subscription->stripe_id) {
                    continue;
                }

                $subscription->update([
                    'status' => Subscription::STATUS_EXPIRED,
                    'ends_at' => $subscription->trial_ends_at,
                ]);

                Log::info('Trial subscription expired', [
                    'subscription_id' => $subscription->id,
                    'billable_id' => $subscription->billable_id,
                    'trial_ends_at' => $subscription->trial_ends_at,
                ]);

                $expired++;
            } catch (\Exception $e) {
                Log::error('Failed to expire trial subscription', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $expired;
    }

    /**
     * Expire module subscription trials (subscription_modules table).
     */
    protected function expireModuleTrials(): int
    {
        $expired = 0;

        $moduleTrials = SubscriptionModule::where('status', SubscriptionModule::STATUS_TRIALING)
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<=', now())
            ->get();

        foreach ($moduleTrials as $moduleSub) {
            try {
                // Skip Stripe-managed module subscriptions
                if ($moduleSub->external_subscription_id) {
                    continue;
                }

                $moduleSub->update([
                    'status' => SubscriptionModule::STATUS_EXPIRED,
                    'ends_at' => $moduleSub->trial_ends_at,
                ]);

                Log::info('Module trial subscription expired', [
                    'subscription_module_id' => $moduleSub->id,
                    'module_code' => $moduleSub->module_code,
                    'billable_id' => $moduleSub->billable_id,
                ]);

                $expired++;
            } catch (\Exception $e) {
                Log::error('Failed to expire module trial subscription', [
                    'subscription_module_id' => $moduleSub->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $expired;
    }
}
