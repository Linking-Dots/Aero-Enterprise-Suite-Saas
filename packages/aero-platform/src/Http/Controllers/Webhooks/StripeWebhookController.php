<?php

namespace Aero\Platform\Http\Controllers\Webhooks;

use Aero\Platform\Models\Plan;
use Aero\Platform\Models\Subscription;
use Aero\Platform\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Stripe Webhook Controller
 *
 * Extends Laravel Cashier's webhook controller to add custom handling
 * for subscription events specific to our multi-tenant platform.
 *
 * @see https://laravel.com/docs/11.x/billing#handling-stripe-webhooks
 */
class StripeWebhookController extends CashierWebhookController
{
    /**
     * Handle customer subscription updated.
     *
     * This webhook fires when a subscription status changes, allowing us to:
     * - Sync subscription status (active, past_due, canceled, etc.)
     * - Update tenant status based on subscription
     * - Handle payment failures gracefully
     *
     * @param  array<string, mixed>  $payload
     */
    protected function handleCustomerSubscriptionUpdated(array $payload): Response
    {
        // Let Cashier handle the base subscription update
        $response = parent::handleCustomerSubscriptionUpdated($payload);

        $data = $payload['data']['object'];
        $stripeSubscriptionId = $data['id'];
        $stripeStatus = $data['status'];
        $stripeCustomerId = $data['customer'];

        // Find the tenant by Stripe customer ID
        $tenant = Tenant::where('stripe_id', $stripeCustomerId)->first();

        if (! $tenant) {
            Log::warning('Stripe webhook: Tenant not found for customer', [
                'stripe_customer_id' => $stripeCustomerId,
                'stripe_subscription_id' => $stripeSubscriptionId,
            ]);

            return $response;
        }

        // Find our local subscription record
        $subscription = Subscription::where('stripe_id', $stripeSubscriptionId)->first();

        if ($subscription) {
            $this->syncSubscriptionStatus($subscription, $data);
        }

        // Update tenant status based on subscription state
        $this->updateTenantStatus($tenant, $stripeStatus);

        Log::info('Stripe webhook: Subscription updated', [
            'tenant_id' => $tenant->id,
            'stripe_status' => $stripeStatus,
            'subscription_id' => $subscription?->id,
        ]);

        return $response;
    }

    /**
     * Handle customer subscription created.
     *
     * Fired when a new subscription is created (after successful checkout).
     *
     * @param  array<string, mixed>  $payload
     */
    protected function handleCustomerSubscriptionCreated(array $payload): Response
    {
        $response = parent::handleCustomerSubscriptionCreated($payload);

        $data = $payload['data']['object'];
        $metadata = $data['metadata'] ?? [];
        $stripeCustomerId = $data['customer'];

        // Find tenant from metadata or Stripe customer ID
        $tenantId = $metadata['tenant_id'] ?? null;
        $tenant = $tenantId
            ? Tenant::find($tenantId)
            : Tenant::where('stripe_id', $stripeCustomerId)->first();

        if ($tenant) {
            // Handle plan subscription
            if (isset($metadata['plan_id'])) {
                $plan = Plan::find($metadata['plan_id']);

                if ($plan) {
                    $tenant->update([
                        'plan_id' => $plan->id,
                        'subscription_plan' => $metadata['billing_cycle'] ?? 'monthly',
                        'status' => Tenant::STATUS_ACTIVE,
                    ]);

                    Log::info('Stripe webhook: Subscription created, tenant activated', [
                        'tenant_id' => $tenant->id,
                        'plan_id' => $plan->id,
                    ]);
                }
            }

            // Handle module add-on subscription
            if (($metadata['type'] ?? null) === 'module_addon' && isset($metadata['module_id'])) {
                $module = \Aero\Platform\Models\Module::find($metadata['module_id']);

                if ($module) {
                    $tenant->modules()->syncWithoutDetaching([
                        $module->id => [
                            'is_active' => true,
                            'subscribed_at' => now(),
                            'unsubscribed_at' => null,
                        ],
                    ]);

                    Log::info('Stripe webhook: Module add-on subscribed', [
                        'tenant_id' => $tenant->id,
                        'module_id' => $module->id,
                        'module_code' => $module->code,
                    ]);
                }
            }
        }

        return $response;
    }

    /**
     * Handle customer subscription trial will end.
     *
     * Fired three days before a subscription trial ends.
     * Notifies the tenant admin to take action before the trial expires.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function handleCustomerSubscriptionTrialWillEnd(array $payload): Response
    {
        $data = $payload['data']['object'];
        $stripeCustomerId = $data['customer'] ?? null;
        $stripeSubscriptionId = $data['id'] ?? null;
        $trialEnd = $data['trial_end'] ?? null;

        if (! $stripeCustomerId || ! $stripeSubscriptionId) {
            Log::warning('Stripe webhook: trial_will_end missing customer or subscription ID');

            return $this->successMethod();
        }

        $tenant = Tenant::where('stripe_id', $stripeCustomerId)->first();

        if ($tenant) {
            $trialEndDate = $trialEnd ? Carbon::createFromTimestamp($trialEnd) : now()->addDays(3);

            $tenant->data['trial_will_end_notified_at'] = now()->toIso8601String();
            $tenant->data['trial_ends_at'] = $trialEndDate->toIso8601String();
            $tenant->save();

            // Find the subscription to log the event
            $subscription = Subscription::where('stripe_id', $stripeSubscriptionId)->first();

            Log::info('Stripe webhook: Trial will end soon', [
                'tenant_id' => $tenant->id,
                'subscription_id' => $subscription?->id,
                'stripe_subscription_id' => $stripeSubscriptionId,
                'trial_ends_at' => $trialEndDate->toDateTimeString(),
            ]);

            // Dispatch notification job to tenant admin email
            \Aero\Platform\Jobs\Notifications\SendTrialEndingNotification::dispatch($tenant, $trialEndDate);
        } else {
            Log::warning('Stripe webhook: trial_will_end tenant not found', [
                'stripe_customer_id' => $stripeCustomerId,
                'stripe_subscription_id' => $stripeSubscriptionId,
            ]);
        }

        return $this->successMethod();
    }

    /**
     * Handle customer subscription deleted.
     *
     * Fired when a subscription is fully cancelled (end of billing period).
     *
     * @param  array<string, mixed>  $payload
     */
    protected function handleCustomerSubscriptionDeleted(array $payload): Response
    {
        $response = parent::handleCustomerSubscriptionDeleted($payload);

        $data = $payload['data']['object'];
        $stripeCustomerId = $data['customer'];

        $tenant = Tenant::where('stripe_id', $stripeCustomerId)->first();

        if ($tenant) {
            // Optionally downgrade or suspend tenant
            // You might want to keep them active for a grace period
            $tenant->update([
                'status' => Tenant::STATUS_SUSPENDED,
            ]);

            $tenant->data['subscription_cancelled_at'] = now()->toIso8601String();
            $tenant->save();

            Log::info('Stripe webhook: Subscription deleted, tenant suspended', [
                'tenant_id' => $tenant->id,
            ]);
        }

        return $response;
    }

    /**
     * Handle invoice payment failed.
     *
     * Fired when a payment attempt fails (e.g., expired card).
     *
     * @param  array<string, mixed>  $payload
     */
    protected function handleInvoicePaymentFailed(array $payload): Response
    {
        $data = $payload['data']['object'];
        $stripeCustomerId = $data['customer'];
        $attemptCount = $data['attempt_count'] ?? 1;

        $tenant = Tenant::where('stripe_id', $stripeCustomerId)->first();

        if ($tenant) {
            // Store payment failure info
            $tenant->data['last_payment_failed_at'] = now()->toIso8601String();
            $tenant->data['payment_failure_count'] = $attemptCount;
            $tenant->save();

            // After multiple failures, consider suspending
            if ($attemptCount >= 3) {
                $tenant->update(['status' => Tenant::STATUS_SUSPENDED]);

                Log::warning('Stripe webhook: Multiple payment failures, tenant suspended', [
                    'tenant_id' => $tenant->id,
                    'attempt_count' => $attemptCount,
                ]);
            }

            // TODO: Send notification to tenant about payment failure
        }

        return $this->successMethod();
    }

    /**
     * Handle invoice payment succeeded.
     *
     * Fired when a recurring payment is successful.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function handleInvoicePaymentSucceeded(array $payload): Response
    {
        $data = $payload['data']['object'];
        $stripeCustomerId = $data['customer'];

        $tenant = Tenant::where('stripe_id', $stripeCustomerId)->first();

        if ($tenant) {
            // Clear any payment failure flags
            unset($tenant->data['last_payment_failed_at']);
            unset($tenant->data['payment_failure_count']);
            $tenant->data['last_payment_at'] = now()->toIso8601String();
            $tenant->save();

            // Ensure tenant is active
            if ($tenant->status === Tenant::STATUS_SUSPENDED) {
                $tenant->update(['status' => Tenant::STATUS_ACTIVE]);
            }

            Log::info('Stripe webhook: Payment succeeded', [
                'tenant_id' => $tenant->id,
                'amount' => $data['amount_paid'] ?? 0,
            ]);
        }

        return $this->successMethod();
    }

    /**
     * Handle checkout session completed.
     *
     * Fired when a customer completes checkout and payment is confirmed.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function handleCheckoutSessionCompleted(array $payload): Response
    {
        $data = $payload['data']['object'];
        $metadata = $data['metadata'] ?? [];

        if (isset($metadata['tenant_id'])) {
            $tenant = Tenant::find($metadata['tenant_id']);

            if ($tenant) {
                // Associate Stripe customer if not already
                if (! $tenant->stripe_id && isset($data['customer'])) {
                    $tenant->update(['stripe_id' => $data['customer']]);
                }

                Log::info('Stripe webhook: Checkout completed', [
                    'tenant_id' => $tenant->id,
                    'session_id' => $data['id'],
                ]);
            }
        }

        return $this->successMethod();
    }

    /**
     * Sync our local subscription status with Stripe.
     *
     * @param  array<string, mixed>  $stripeData
     */
    protected function syncSubscriptionStatus(Subscription $subscription, array $stripeData): void
    {
        $stripeStatus = $stripeData['status'];

        // Map Stripe status to our status
        $statusMap = [
            'active' => Subscription::STATUS_ACTIVE,
            'past_due' => Subscription::STATUS_PAST_DUE,
            'canceled' => Subscription::STATUS_CANCELLED,
            'unpaid' => Subscription::STATUS_PAST_DUE,
            'trialing' => Subscription::STATUS_TRIALING,
            'incomplete' => Subscription::STATUS_PAST_DUE,
            'incomplete_expired' => Subscription::STATUS_EXPIRED,
            'paused' => Subscription::STATUS_CANCELLED,
        ];

        $newStatus = $statusMap[$stripeStatus] ?? $subscription->status;

        $subscription->update([
            'status' => $newStatus,
            'stripe_status' => $stripeStatus,
            'ends_at' => isset($stripeData['current_period_end'])
                ? Carbon::createFromTimestamp($stripeData['current_period_end'])
                : $subscription->ends_at,
        ]);
    }

    /**
     * Update tenant status based on subscription state.
     */
    protected function updateTenantStatus(Tenant $tenant, string $stripeStatus): void
    {
        $newTenantStatus = match ($stripeStatus) {
            'active', 'trialing' => Tenant::STATUS_ACTIVE,
            'past_due', 'unpaid' => Tenant::STATUS_ACTIVE, // Grace period - still active but flagged
            'canceled', 'incomplete_expired' => Tenant::STATUS_SUSPENDED,
            default => $tenant->status,
        };

        if ($tenant->status !== $newTenantStatus) {
            $tenant->update(['status' => $newTenantStatus]);
        }
    }
}
