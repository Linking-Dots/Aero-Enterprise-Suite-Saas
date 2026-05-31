<?php

namespace Aero\Platform\Jobs;

use Aero\Notifications\Services\Mail\MailService;
use Aero\Notifications\Services\Sms\SmsService;
use Aero\Platform\Models\Plan;
use Aero\Platform\Models\Subscription;
use Aero\Platform\Models\Tenant;
use Aero\Platform\Services\Billing\SubscriptionBillingService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Plan 03 (aero-platform) Task 3 of foundation 10/10 push.
 *
 * REWRITTEN to close two production bugs:
 *   B-2: queried subscriptions.tenant_id (column gone — polymorphic billable
 *        means this returned zero rows, so past_due tenants NEVER auto-recovered)
 *   B-2: attemptPaymentCharge() was `rand(0, 100) > 60` — fake stub with
 *        40% random "success" rate. A real subscription that was past_due
 *        could randomly flip back to active without any payment occurring.
 *
 * Now uses Eloquent Subscription model + SubscriptionBillingService::retryPayment()
 * which integrates with Cashier/Stripe properly or throws when no gateway
 * is configured (instead of pretending success).
 */
class RetryFailedPaymentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maximum number of retry attempts.
     */
    protected int $maxRetries = 3;

    public function __construct()
    {
        $this->onQueue('billing'); // Axis C C3
    }

    public function handle(): void
    {
        Log::info('RetryFailedPaymentsJob: Starting failed payment retry processing');

        $failedPayments = Subscription::query()
            ->where('status', 'past_due')
            ->where('retry_count', '<', $this->maxRetries)
            ->where(function ($q) {
                $q->whereNull('next_retry_at')
                  ->orWhere('next_retry_at', '<=', now());
            })
            ->where('billable_type', Tenant::class)
            ->get();

        Log::info("RetryFailedPaymentsJob: Found {$failedPayments->count()} failed payments to retry");

        foreach ($failedPayments as $subscription) {
            try {
                $this->retryPayment($subscription);
            } catch (\Throwable $e) {
                Log::error("RetryFailedPaymentsJob: Failed to retry payment for subscription {$subscription->id}", [
                    'error' => $e->getMessage(),
                    'subscription_id' => $subscription->id,
                ]);
            }
        }

        Log::info('RetryFailedPaymentsJob: Completed failed payment retry processing');
    }

    /**
     * Retry payment for a specific subscription.
     */
    protected function retryPayment(Subscription $subscription): void
    {
        $retryCount = (int) $subscription->retry_count + 1;
        $isLastAttempt = $retryCount >= $this->maxRetries;

        $tenant = $subscription->tenant; // polymorphic accessor
        $plan = $subscription->plan_id ? Plan::find($subscription->plan_id) : null;

        if (! $tenant || ! $plan) {
            Log::warning("RetryFailedPaymentsJob: Tenant or plan not found for subscription {$subscription->id}");
            return;
        }

        $paymentSuccessful = $this->attemptPaymentCharge($subscription);

        if ($paymentSuccessful) {
            $subscription->forceFill([
                'status' => 'active',
                'retry_count' => 0,
                'next_retry_at' => null,
            ])->save();

            $this->sendPaymentSuccessNotification($tenant, $subscription);

            Log::info("RetryFailedPaymentsJob: Payment retry successful for subscription {$subscription->id}");
        } else {
            $nextRetryDelay = $this->calculateNextRetryDelay($retryCount);

            $subscription->forceFill([
                'retry_count' => $retryCount,
                'next_retry_at' => $isLastAttempt ? null : now()->addSeconds($nextRetryDelay),
            ])->save();

            if ($isLastAttempt) {
                $this->handleFinalRetryFailure($tenant, $subscription, $plan);
            } else {
                $this->sendPaymentRetryNotification($tenant, $subscription, $retryCount);
            }

            Log::info("RetryFailedPaymentsJob: Payment retry failed for subscription {$subscription->id} (attempt {$retryCount}/{$this->maxRetries})");
        }
    }

    /**
     * Attempt to charge the payment method.
     *
     * Plan 03 T3: previously was `rand(0, 100) > 60` — a fake 40% random
     * success rate that could mark genuinely-past-due subscriptions active
     * without ANY payment occurring. Now delegates to SubscriptionBillingService
     * which uses Cashier/Stripe when configured, or throws when not.
     */
    protected function attemptPaymentCharge(Subscription $subscription): bool
    {
        try {
            /** @var SubscriptionBillingService $billing */
            $billing = app(SubscriptionBillingService::class);

            return $billing->retryPayment($subscription);
        } catch (\Throwable $e) {
            Log::error("RetryFailedPaymentsJob: SubscriptionBillingService::retryPayment threw for subscription {$subscription->id}", [
                'error' => $e->getMessage(),
                'class' => $e::class,
            ]);
            return false;
        }
    }

    /**
     * Calculate exponential backoff for next retry.
     */
    protected function calculateNextRetryDelay(int $retryCount): int
    {
        // Exponential backoff: 24 hours, 48 hours, 72 hours
        return 86400 * $retryCount;
    }

    /**
     * Handle final retry failure — suspend or downgrade.
     */
    protected function handleFinalRetryFailure(Tenant $tenant, Subscription $subscription, Plan $plan): void
    {
        $gracePeriod = 10; // days
        $gracePeriodEnd = now()->addDays($gracePeriod);

        $subscription->forceFill([
            'status' => 'suspended',
            'grace_period_ends_at' => $gracePeriodEnd,
        ])->save();

        $adminUser = $this->resolveAdminUserForTenant($tenant);

        if ($adminUser) {
            $variables = [
                'tenant_name' => $tenant->name,
                'amount' => '$'.number_format(($subscription->amount ?? 0) / 100, 2),
                'payment_method' => 'Card',
                'last_four' => $subscription->payment_method_last_four ?? '****',
                'attempt_date' => now()->format('M d, Y'),
                'grace_period' => $gracePeriod,
                'billing_url' => config('app.url').'/billing',
                'support_url' => config('app.url').'/support',
            ];

            if (! empty($adminUser->email)) {
                MailService::make()
                    ->template('notifications/payment-failed', $variables)
                    ->to($adminUser->email)
                    ->subject('Final Payment Attempt Failed - Action Required')
                    ->send();
            }

            if (! empty($adminUser->phone)) {
                SmsService::make()
                    ->template('payment_failed', [
                        'app_name' => config('app.name'),
                        'amount' => '$'.number_format(($subscription->amount ?? 0) / 100, 2),
                        'billing_url' => config('app.url').'/billing',
                    ])
                    ->send($adminUser->phone, '');
            }
        }

        Log::warning("RetryFailedPaymentsJob: Final retry failed for subscription {$subscription->id}, entering grace period");
    }

    /**
     * Send payment retry notification.
     */
    protected function sendPaymentRetryNotification(Tenant $tenant, Subscription $subscription, int $retryCount): void
    {
        $adminUser = $this->resolveAdminUserForTenant($tenant);

        if ($adminUser && ! empty($adminUser->email)) {
            $variables = [
                'tenant_name' => $tenant->name,
                'amount' => '$'.number_format(($subscription->amount ?? 0) / 100, 2),
                'payment_method' => 'Card',
                'last_four' => $subscription->payment_method_last_four ?? '****',
                'attempt_date' => now()->format('M d, Y'),
                'next_retry' => $subscription->next_retry_at ? Carbon::parse($subscription->next_retry_at)->format('M d, Y') : 'Soon',
                'grace_period' => 10,
                'billing_url' => config('app.url').'/billing',
                'support_url' => config('app.url').'/support',
            ];

            MailService::make()
                ->template('notifications/payment-failed', $variables)
                ->to($adminUser->email)
                ->subject("Payment Failed - Retry Attempt {$retryCount}")
                ->queue('notifications')
                ->send();
        }
    }

    /**
     * Send payment success notification.
     */
    protected function sendPaymentSuccessNotification(Tenant $tenant, Subscription $subscription): void
    {
        $adminUser = $this->resolveAdminUserForTenant($tenant);

        if ($adminUser && ! empty($adminUser->email)) {
            $variables = [
                'tenant_name' => $tenant->name,
                'status' => 'Active',
                'message' => 'Your payment has been processed successfully and your subscription is now active.',
                'show_cta' => true,
                'dashboard_url' => config('app.url').'/dashboard',
                'support_url' => config('app.url').'/support',
                'billing_url' => config('app.url').'/billing',
            ];

            MailService::make()
                ->template('notifications/subscription-status', $variables)
                ->to($adminUser->email)
                ->subject('Payment Successful - Subscription Renewed')
                ->queue('notifications')
                ->send();
        }
    }

    /**
     * Resolve the owner / admin user for a tenant. Tenant.owner_* columns are
     * the canonical pointer; tenant-DB user lookup would require initializing
     * tenancy which isn't appropriate inside this central-DB cron job.
     */
    protected function resolveAdminUserForTenant(Tenant $tenant): ?object
    {
        if (! empty($tenant->owner_email)) {
            return (object) [
                'email' => $tenant->owner_email,
                'phone' => $tenant->owner_phone ?? null,
                'name'  => $tenant->owner_name ?? $tenant->name,
            ];
        }
        return null;
    }
}
