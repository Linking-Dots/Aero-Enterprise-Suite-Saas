<?php

namespace Aero\Platform\Jobs;

use Aero\Notifications\Services\Mail\MailService;
use Aero\Notifications\Services\Sms\SmsService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RetryFailedPaymentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maximum number of retry attempts
     */
    protected int $maxRetries = 3;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('RetryFailedPaymentsJob: Starting failed payment retry processing');

        // Get subscriptions with failed payments that need retry
        $failedPayments = DB::table('subscriptions')
            ->where('status', 'past_due')
            ->where('retry_count', '<', $this->maxRetries)
            ->whereDate('next_retry_at', '<=', now())
            ->whereNull('deleted_at')
            ->get();

        Log::info("RetryFailedPaymentsJob: Found {$failedPayments->count()} failed payments to retry");

        foreach ($failedPayments as $subscription) {
            try {
                $this->retryPayment($subscription);
            } catch (\Exception $e) {
                Log::error("RetryFailedPaymentsJob: Failed to retry payment for subscription {$subscription->id}", [
                    'error' => $e->getMessage(),
                    'subscription_id' => $subscription->id,
                ]);
            }
        }

        Log::info('RetryFailedPaymentsJob: Completed failed payment retry processing');
    }

    /**
     * Retry payment for a specific subscription
     *
     * @param  object  $subscription
     */
    protected function retryPayment($subscription): void
    {
        $retryCount = $subscription->retry_count + 1;
        $isLastAttempt = $retryCount >= $this->maxRetries;

        // Get tenant and plan information
        $tenant = DB::table('tenants')->find($subscription->tenant_id);
        $plan = DB::table('plans')->find($subscription->plan_id);

        if (! $tenant || ! $plan) {
            Log::warning("RetryFailedPaymentsJob: Tenant or plan not found for subscription {$subscription->id}");

            return;
        }

        // TODO: Integrate with payment gateway (Stripe/Paddle) to retry charge
        // For now, we'll simulate the retry logic
        $paymentSuccessful = $this->attemptPaymentCharge($subscription);

        if ($paymentSuccessful) {
            // Payment successful - update subscription status
            DB::table('subscriptions')
                ->where('id', $subscription->id)
                ->update([
                    'status' => 'active',
                    'retry_count' => 0,
                    'next_retry_at' => null,
                    'updated_at' => now(),
                ]);

            $this->sendPaymentSuccessNotification($tenant, $subscription);

            Log::info("RetryFailedPaymentsJob: Payment retry successful for subscription {$subscription->id}");
        } else {
            // Payment failed - update retry info
            $nextRetryDelay = $this->calculateNextRetryDelay($retryCount);

            DB::table('subscriptions')
                ->where('id', $subscription->id)
                ->update([
                    'retry_count' => $retryCount,
                    'next_retry_at' => $isLastAttempt ? null : now()->addSeconds($nextRetryDelay),
                    'updated_at' => now(),
                ]);

            if ($isLastAttempt) {
                // Final retry failed - take action
                $this->handleFinalRetryFailure($tenant, $subscription, $plan);
            } else {
                // Send retry notification
                $this->sendPaymentRetryNotification($tenant, $subscription, $retryCount);
            }

            Log::info("RetryFailedPaymentsJob: Payment retry failed for subscription {$subscription->id} (attempt {$retryCount}/{$this->maxRetries})");
        }
    }

    /**
     * Attempt to charge the payment method
     *
     * @param  object  $subscription
     */
    protected function attemptPaymentCharge($subscription): bool
    {
        // TODO: Integrate with payment gateway
        // This is a placeholder that should be replaced with actual payment gateway integration

        // Simulate random success/failure for demonstration
        return rand(0, 100) > 60; // 40% success rate for demo
    }

    /**
     * Calculate exponential backoff for next retry
     *
     * @return int Seconds until next retry
     */
    protected function calculateNextRetryDelay(int $retryCount): int
    {
        // Exponential backoff: 24 hours, 48 hours, 72 hours
        return 86400 * $retryCount; // 1 day, 2 days, 3 days
    }

    /**
     * Handle final retry failure - suspend or downgrade
     *
     * @param  object  $tenant
     * @param  object  $subscription
     * @param  object  $plan
     */
    protected function handleFinalRetryFailure($tenant, $subscription, $plan): void
    {
        $gracePeriod = 10; // days from config
        $gracePeriodEnd = now()->addDays($gracePeriod);

        // Suspend subscription after grace period
        DB::table('subscriptions')
            ->where('id', $subscription->id)
            ->update([
                'status' => 'suspended',
                'grace_period_ends_at' => $gracePeriodEnd,
                'updated_at' => now(),
            ]);

        // Get admin user
        $adminUser = DB::table('users')
            ->where('tenant_id', $tenant->id)
            ->where('is_owner', true)
            ->first();

        if ($adminUser) {
            $variables = [
                'tenant_name' => $tenant->name,
                'amount' => '$'.number_format($subscription->amount / 100, 2),
                'payment_method' => 'Card',
                'last_four' => $subscription->payment_method_last_four ?? '****',
                'attempt_date' => now()->format('M d, Y'),
                'grace_period' => $gracePeriod,
                'billing_url' => config('app.url').'/billing',
                'support_url' => config('app.url').'/support',
            ];

            // Send final failure notification
            if ($adminUser->email) {
                MailService::make()
                    ->template('notifications/payment-failed', $variables)
                    ->to($adminUser->email)
                    ->subject('Final Payment Attempt Failed - Action Required')
                    ->send();
            }

            if ($adminUser->phone) {
                SmsService::make()
                    ->template('payment_failed', [
                        'app_name' => config('app.name'),
                        'amount' => '$'.number_format($subscription->amount / 100, 2),
                        'billing_url' => config('app.url').'/billing',
                    ])
                    ->send($adminUser->phone, '');
            }
        }

        Log::warning("RetryFailedPaymentsJob: Final retry failed for subscription {$subscription->id}, entering grace period");
    }

    /**
     * Send payment retry notification
     *
     * @param  object  $tenant
     * @param  object  $subscription
     */
    protected function sendPaymentRetryNotification($tenant, $subscription, int $retryCount): void
    {
        $adminUser = DB::table('users')
            ->where('tenant_id', $tenant->id)
            ->where('is_owner', true)
            ->first();

        if ($adminUser && $adminUser->email) {
            $variables = [
                'tenant_name' => $tenant->name,
                'amount' => '$'.number_format($subscription->amount / 100, 2),
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
     * Send payment success notification
     *
     * @param  object  $tenant
     * @param  object  $subscription
     */
    protected function sendPaymentSuccessNotification($tenant, $subscription): void
    {
        $adminUser = DB::table('users')
            ->where('tenant_id', $tenant->id)
            ->where('is_owner', true)
            ->first();

        if ($adminUser && $adminUser->email) {
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
}
