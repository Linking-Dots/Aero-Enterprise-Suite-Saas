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

class ProcessSubscriptionRenewalsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('ProcessSubscriptionRenewalsJob: Starting renewal processing');

        // Get subscriptions renewing in next 7, 3, and 1 days
        $this->sendRenewalReminders(7);
        $this->sendRenewalReminders(3);
        $this->sendRenewalReminders(1);

        // Process subscriptions renewing today
        $this->processRenewalsToday();

        Log::info('ProcessSubscriptionRenewalsJob: Completed renewal processing');
    }

    /**
     * Send renewal reminders for subscriptions expiring in X days
     */
    protected function sendRenewalReminders(int $days): void
    {
        $targetDate = now()->addDays($days)->toDateString();

        $subscriptions = DB::table('subscriptions')
            ->where('status', 'active')
            ->where('next_billing_date', 'like', $targetDate.'%')
            ->whereNull('deleted_at')
            ->get();

        Log::info("ProcessSubscriptionRenewalsJob: Found {$subscriptions->count()} subscriptions renewing in {$days} day(s)");

        foreach ($subscriptions as $subscription) {
            try {
                $this->sendRenewalReminder($subscription, $days);
            } catch (\Exception $e) {
                Log::error("ProcessSubscriptionRenewalsJob: Failed to send reminder for subscription {$subscription->id}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Send renewal reminder to tenant
     *
     * @param  object  $subscription
     */
    protected function sendRenewalReminder($subscription, int $days): void
    {
        $tenant = DB::table('tenants')->find($subscription->tenant_id);
        $plan = DB::table('plans')->find($subscription->plan_id);

        if (! $tenant || ! $plan) {
            return;
        }

        $adminUser = DB::table('users')
            ->where('tenant_id', $tenant->id)
            ->where('is_owner', true)
            ->first();

        if (! $adminUser) {
            return;
        }

        $amount = '$'.number_format($subscription->amount / 100, 2);
        $nextBillingDate = Carbon::parse($subscription->next_billing_date)->format('F j, Y');

        // Send email reminder only for 7 and 3 day reminders
        if ($adminUser->email && in_array($days, [7, 3])) {
            $variables = [
                'tenant_name' => $tenant->name,
                'status' => 'Active',
                'message' => "Your subscription will renew in {$days} ".($days == 1 ? 'day' : 'days').". We'll charge {$amount} to your payment method on file.",
                'billing_cycle' => ucfirst($subscription->billing_cycle ?? 'monthly'),
                'next_billing_date' => $nextBillingDate,
                'show_cta' => true,
                'dashboard_url' => config('app.url').'/dashboard',
                'billing_url' => config('app.url').'/billing',
                'support_url' => config('app.url').'/support',
            ];

            MailService::make()
                ->template('notifications/subscription-status', $variables)
                ->to($adminUser->email)
                ->subject("Subscription Renewal in {$days} ".($days == 1 ? 'Day' : 'Days'))
                ->queue('notifications')
                ->send();
        }

        // Send SMS for 1 day reminder only
        if ($adminUser->phone && $days == 1) {
            SmsService::make()
                ->template('subscription_renewed', [
                    'app_name' => config('app.name'),
                    'next_billing_date' => $nextBillingDate,
                ])
                ->queue('sms')
                ->send($adminUser->phone, '');
        }

        Log::info("ProcessSubscriptionRenewalsJob: Renewal reminder sent for subscription {$subscription->id} ({$days} days)");
    }

    /**
     * Process subscriptions renewing today
     */
    protected function processRenewalsToday(): void
    {
        $today = now()->toDateString();

        $subscriptions = DB::table('subscriptions')
            ->where('status', 'active')
            ->where('next_billing_date', 'like', $today.'%')
            ->whereNull('deleted_at')
            ->get();

        Log::info("ProcessSubscriptionRenewalsJob: Found {$subscriptions->count()} subscriptions renewing today");

        foreach ($subscriptions as $subscription) {
            try {
                $this->processRenewal($subscription);
            } catch (\Exception $e) {
                Log::error("ProcessSubscriptionRenewalsJob: Failed to process renewal for subscription {$subscription->id}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Process a single subscription renewal
     *
     * @param  object  $subscription
     */
    protected function processRenewal($subscription): void
    {
        // TODO: Integrate with payment gateway to charge customer
        // For now, we'll simulate the renewal

        $paymentSuccessful = $this->attemptRenewalCharge($subscription);

        if ($paymentSuccessful) {
            // Calculate next billing date
            $currentBillingDate = Carbon::parse($subscription->next_billing_date);
            $nextBillingDate = $subscription->billing_cycle === 'yearly'
                ? $currentBillingDate->addYear()
                : $currentBillingDate->addMonth();

            // Update subscription
            DB::table('subscriptions')
                ->where('id', $subscription->id)
                ->update([
                    'current_period_start' => now(),
                    'current_period_end' => $nextBillingDate,
                    'next_billing_date' => $nextBillingDate,
                    'retry_count' => 0,
                    'updated_at' => now(),
                ]);

            // Send success notification
            $this->sendRenewalSuccessNotification($subscription);

            Log::info("ProcessSubscriptionRenewalsJob: Renewal successful for subscription {$subscription->id}");
        } else {
            // Mark as past due and trigger retry logic
            DB::table('subscriptions')
                ->where('id', $subscription->id)
                ->update([
                    'status' => 'past_due',
                    'retry_count' => 1,
                    'next_retry_at' => now()->addDay(),
                    'updated_at' => now(),
                ]);

            // Send payment failure notification
            $this->sendRenewalFailureNotification($subscription);

            Log::warning("ProcessSubscriptionRenewalsJob: Renewal failed for subscription {$subscription->id}");
        }
    }

    /**
     * Attempt to charge for renewal
     *
     * @param  object  $subscription
     */
    protected function attemptRenewalCharge($subscription): bool
    {
        // TODO: Integrate with payment gateway
        // Placeholder implementation
        return rand(0, 100) > 10; // 90% success rate for demo
    }

    /**
     * Send renewal success notification
     *
     * @param  object  $subscription
     */
    protected function sendRenewalSuccessNotification($subscription): void
    {
        $tenant = DB::table('tenants')->find($subscription->tenant_id);
        $adminUser = DB::table('users')
            ->where('tenant_id', $subscription->tenant_id)
            ->where('is_owner', true)
            ->first();

        if ($adminUser && $adminUser->email) {
            $variables = [
                'tenant_name' => $tenant->name ?? 'there',
                'status' => 'Active',
                'message' => 'Your subscription has been renewed successfully. Thank you for your continued business!',
                'next_billing_date' => Carbon::parse($subscription->next_billing_date)->format('F j, Y'),
                'billing_cycle' => ucfirst($subscription->billing_cycle ?? 'monthly'),
                'show_cta' => true,
                'dashboard_url' => config('app.url').'/dashboard',
                'billing_url' => config('app.url').'/billing',
            ];

            MailService::make()
                ->template('notifications/subscription-status', $variables)
                ->to($adminUser->email)
                ->subject('Subscription Renewed Successfully')
                ->queue('notifications')
                ->send();
        }
    }

    /**
     * Send renewal failure notification
     *
     * @param  object  $subscription
     */
    protected function sendRenewalFailureNotification($subscription): void
    {
        $tenant = DB::table('tenants')->find($subscription->tenant_id);
        $adminUser = DB::table('users')
            ->where('tenant_id', $subscription->tenant_id)
            ->where('is_owner', true)
            ->first();

        if ($adminUser && $adminUser->email) {
            $variables = [
                'tenant_name' => $tenant->name ?? 'there',
                'amount' => '$'.number_format($subscription->amount / 100, 2),
                'payment_method' => 'Card',
                'last_four' => $subscription->payment_method_last_four ?? '****',
                'attempt_date' => now()->format('M d, Y'),
                'next_retry' => now()->addDay()->format('M d, Y'),
                'grace_period' => 10,
                'billing_url' => config('app.url').'/billing',
                'support_url' => config('app.url').'/support',
            ];

            MailService::make()
                ->template('notifications/payment-failed', $variables)
                ->to($adminUser->email)
                ->subject('Subscription Renewal Failed - Action Required')
                ->send();
        }
    }
}
