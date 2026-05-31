<?php

declare(strict_types=1);

namespace Aero\Platform\Services\Billing;

use Aero\Platform\Models\Subscription;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Plan 03 (aero-platform) Task 2 + Task 3 — real renewal/retry charging.
 *
 * Replaces the `rand(0, 100) > 10` fake-success stub that was in
 * ProcessSubscriptionRenewalsJob and the `// TODO Integrate with payment
 * gateway` stub in RetryFailedPaymentsJob.
 *
 * This service is the single place that knows how to actually charge a
 * renewal or retry a past-due payment. It wraps Cashier (Stripe) when the
 * subscription is Stripe-managed, and falls back to throwing a clear
 * exception when the operator hasn't configured a gateway — which is far
 * safer than the previous random-success stub that silently marked
 * subscriptions as renewed without any real charge.
 *
 * Operators with non-Stripe gateways (SSLCommerz, PayPal, etc.) override
 * this service in their host AppServiceProvider:
 *
 *   $this->app->bind(SubscriptionBillingService::class, MyCustomService::class);
 */
class SubscriptionBillingService
{
    /**
     * Attempt to charge for a renewal.
     *
     * Returns true on success, false on a clean payment failure (e.g.,
     * card declined). Throws on misconfiguration so the job's exception
     * handler records the gap explicitly — never silently pretend success.
     */
    public function chargeRenewal(Subscription $subscription): bool
    {
        $tenant = $subscription->tenant;
        if (! $tenant) {
            Log::warning('SubscriptionBillingService::chargeRenewal — no tenant for subscription', [
                'subscription_id' => $subscription->id,
                'billable_type'   => $subscription->billable_type,
                'billable_id'     => $subscription->billable_id,
            ]);
            return false;
        }

        // Stripe path via Cashier — when subscription has a stripe_id, this
        // is a Cashier-managed subscription. Stripe handles the actual
        // renewal charge automatically on its end; we only need to verify
        // the charge succeeded (webhooks update stripe_status). Reaching
        // this method means we're being called out-of-band — log it.
        if (! empty($subscription->stripe_id)) {
            return $this->verifyStripeRenewal($subscription);
        }

        // Custom (non-Cashier) gateway path — operator must override
        throw new BillingGatewayNotConfiguredException(
            'No payment gateway is wired for subscription #'.$subscription->id.'. '.
            'Override SubscriptionBillingService::chargeRenewal in your host AppServiceProvider '.
            'to integrate your gateway, or move the subscription to Cashier (set stripe_id).'
        );
    }

    /**
     * Retry a past-due payment.
     *
     * For Stripe-managed subscriptions, calls Cashier's invoice retry path.
     * For others, throws unless overridden.
     */
    public function retryPayment(Subscription $subscription): bool
    {
        $tenant = $subscription->tenant;
        if (! $tenant) {
            return false;
        }

        if (! empty($subscription->stripe_id) && ! empty($subscription->latest_invoice_id)) {
            return $this->retryStripePayment($subscription);
        }

        throw new BillingGatewayNotConfiguredException(
            'Cannot retry payment for subscription #'.$subscription->id.': '.
            'no Stripe invoice id and no custom gateway override registered.'
        );
    }

    /**
     * For Cashier-managed renewals, Stripe's own scheduler attempts the
     * charge on next_billing_date. This method confirms the latest invoice
     * paid status; returns true when paid, false otherwise.
     *
     * Pulling this out as its own method so operators can stub or override
     * for testing.
     */
    protected function verifyStripeRenewal(Subscription $subscription): bool
    {
        // Cashier exposes Stripe SDK via Cashier::stripe(). Wrapped in try
        // so a misconfigured key doesn't crash the cron — we log and let
        // the job mark the subscription past_due.
        try {
            if (! class_exists(\Laravel\Cashier\Cashier::class)) {
                throw new BillingGatewayNotConfiguredException('Laravel Cashier is not installed.');
            }

            /** @var \Stripe\StripeClient $stripe */
            $stripe = \Laravel\Cashier\Cashier::stripe();
            $stripeSub = $stripe->subscriptions->retrieve($subscription->stripe_id);

            // 'active' means the latest invoice was paid. 'past_due' / 'unpaid' mean charge failed.
            return in_array($stripeSub->status, ['active', 'trialing'], true);
        } catch (Throwable $e) {
            Log::error('SubscriptionBillingService::verifyStripeRenewal failed', [
                'subscription_id' => $subscription->id,
                'stripe_id'       => $subscription->stripe_id,
                'error'           => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Force-retry the latest Stripe invoice's payment intent.
     */
    protected function retryStripePayment(Subscription $subscription): bool
    {
        try {
            if (! class_exists(\Laravel\Cashier\Cashier::class)) {
                throw new BillingGatewayNotConfiguredException('Laravel Cashier is not installed.');
            }

            /** @var \Stripe\StripeClient $stripe */
            $stripe = \Laravel\Cashier\Cashier::stripe();
            $invoice = $stripe->invoices->retrieve($subscription->latest_invoice_id, [
                'expand' => ['payment_intent'],
            ]);

            // If already paid, treat as success
            if ($invoice->status === 'paid') {
                return true;
            }

            // Trigger Stripe to retry the payment intent
            $paymentIntent = $invoice->payment_intent;
            if ($paymentIntent && $paymentIntent->id) {
                $stripe->paymentIntents->confirm($paymentIntent->id);

                // Re-retrieve invoice to check status after confirm
                $invoice = $stripe->invoices->retrieve($subscription->latest_invoice_id);
                return $invoice->status === 'paid';
            }

            return false;
        } catch (Throwable $e) {
            Log::error('SubscriptionBillingService::retryStripePayment failed', [
                'subscription_id' => $subscription->id,
                'stripe_id'       => $subscription->stripe_id,
                'error'           => $e->getMessage(),
            ]);
            return false;
        }
    }
}
