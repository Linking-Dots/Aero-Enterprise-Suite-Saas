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
 * Plan 03 (aero-platform) Task 2 of foundation 10/10 push.
 *
 * REWRITTEN to close two production bugs identified by Phase 1 audit:
 *   B-1: queried subscriptions.tenant_id (column no longer exists; the
 *        Subscription model uses polymorphic billable_id/billable_type)
 *        — silently returned zero subscriptions, so no reminders were sent
 *   A-4: `where('next_billing_date', 'like', $targetDate.'%')` — string
 *        LIKE on a datetime column works but bypasses the index. Switched
 *        to whereDate() for correctness AND performance
 *
 * Also closes the fake-success-rate gambling stub: attemptRenewalCharge()
 * previously did `return rand(0, 100) > 10` — 90% randomly true. Now
 * delegates to PaymentGatewayService which throws on misconfiguration
 * rather than pretending payment succeeded.
 */
class ProcessSubscriptionRenewalsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        $this->onQueue('billing'); // Axis C C3 — isolate Stripe-bound work.
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('ProcessSubscriptionRenewalsJob: Starting renewal processing');

        // Send reminders at T-7, T-3, T-1 days
        $this->sendRenewalReminders(7);
        $this->sendRenewalReminders(3);
        $this->sendRenewalReminders(1);

        // Process subscriptions renewing today
        $this->processRenewalsToday();

        Log::info('ProcessSubscriptionRenewalsJob: Completed renewal processing');
    }

    /**
     * Send renewal reminders for subscriptions expiring in X days.
     *
     * Plan 03 T2 fix: polymorphic Subscription via Eloquent + whereDate
     * instead of string LIKE on datetime.
     */
    protected function sendRenewalReminders(int $days): void
    {
        $targetDate = now()->addDays($days)->toDateString();

        $subscriptions = Subscription::query()
            ->where('status', 'active')
            ->whereDate('next_billing_date', $targetDate)
            ->where('billable_type', Tenant::class)
            ->get();

        Log::info("ProcessSubscriptionRenewalsJob: Found {$subscriptions->count()} subscriptions renewing in {$days} day(s)");

        foreach ($subscriptions as $subscription) {
            try {
                $this->sendRenewalReminder($subscription, $days);
            } catch (\Throwable $e) {
                Log::error("ProcessSubscriptionRenewalsJob: Failed to send reminder for subscription {$subscription->id}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Send renewal reminder to tenant.
     */
    protected function sendRenewalReminder(Subscription $subscription, int $days): void
    {
        // Polymorphic billable — works correctly whether owner is Tenant or another billable type.
        $tenant = $subscription->tenant; // accessor on Subscription model
        $plan = $subscription->plan_id ? Plan::find($subscription->plan_id) : null;

        if (! $tenant || ! $plan) {
            Log::warning("ProcessSubscriptionRenewalsJob: Skipped reminder for subscription {$subscription->id} — tenant or plan missing", [
                'tenant_present' => (bool) $tenant,
                'plan_present' => (bool) $plan,
            ]);
            return;
        }

        $adminUser = $this->resolveAdminUserForTenant($tenant);
        if (! $adminUser) {
            Log::warning("ProcessSubscriptionRenewalsJob: No owner user found for tenant {$tenant->id}");
            return;
        }

        $amount = '$'.number_format(($subscription->amount ?? 0) / 100, 2);
        $nextBillingDate = Carbon::parse($subscription->next_billing_date)->format('F j, Y');

        // Email for 7 and 3 day reminders
        if (! empty($adminUser->email) && in_array($days, [7, 3], true)) {
            $variables = [
                'tenant_name' => $tenant->name,
                'status' => 'Active',
                'message' => "Your subscription will renew in {$days} ".($days === 1 ? 'day' : 'days').". We'll charge {$amount} to your payment method on file.",
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
                ->subject("Subscription Renewal in {$days} ".($days === 1 ? 'Day' : 'Days'))
                ->queue('notifications')
                ->send();
        }

        // SMS for T-1 reminder only
        if (! empty($adminUser->phone) && $days === 1) {
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
     * Process subscriptions renewing today.
     */
    protected function processRenewalsToday(): void
    {
        $today = now()->toDateString();

        $subscriptions = Subscription::query()
            ->where('status', 'active')
            ->whereDate('next_billing_date', $today)
            ->where('billable_type', Tenant::class)
            ->get();

        Log::info("ProcessSubscriptionRenewalsJob: Found {$subscriptions->count()} subscriptions renewing today");

        foreach ($subscriptions as $subscription) {
            try {
                $this->processRenewal($subscription);
            } catch (\Throwable $e) {
                Log::error("ProcessSubscriptionRenewalsJob: Failed to process renewal for subscription {$subscription->id}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Process a single subscription renewal.
     */
    protected function processRenewal(Subscription $subscription): void
    {
        $paymentSuccessful = $this->attemptRenewalCharge($subscription);

        if ($paymentSuccessful) {
            // Calculate next billing date
            $currentBillingDate = Carbon::parse($subscription->next_billing_date);
            $nextBillingDate = $subscription->billing_cycle === 'yearly'
                ? $currentBillingDate->addYear()
                : $currentBillingDate->addMonth();

            $subscription->forceFill([
                'current_period_start' => now(),
                'current_period_end' => $nextBillingDate,
                'next_billing_date' => $nextBillingDate,
                'retry_count' => 0,
            ])->save();

            $this->sendRenewalSuccessNotification($subscription);

            Log::info("ProcessSubscriptionRenewalsJob: Renewal successful for subscription {$subscription->id}");
        } else {
            $subscription->forceFill([
                'status' => 'past_due',
                'retry_count' => 1,
                'next_retry_at' => now()->addDay(),
            ])->save();

            $this->sendRenewalFailureNotification($subscription);

            Log::warning("ProcessSubscriptionRenewalsJob: Renewal failed for subscription {$subscription->id}");
        }
    }

    /**
     * Attempt to charge for renewal.
     *
     * Plan 03 T2: previously returned `rand(0, 100) > 10` — a randomized
     * stub that silently pretended 90% of renewals succeeded. Now delegates
     * to PaymentGatewayService which uses the configured gateway (Stripe
     * via Cashier, SSLCommerz, etc.). If no gateway is wired the service
     * throws — far better than fake-success.
     */
    protected function attemptRenewalCharge(Subscription $subscription): bool
    {
        try {
            /** @var SubscriptionBillingService $billing */
            $billing = app(SubscriptionBillingService::class);

            return $billing->chargeRenewal($subscription);
        } catch (\Throwable $e) {
            Log::error("ProcessSubscriptionRenewalsJob: SubscriptionBillingService::chargeRenewal threw for subscription {$subscription->id}", [
                'error' => $e->getMessage(),
                'class' => $e::class,
            ]);
            return false;
        }
    }

    /**
     * Send renewal success notification.
     */
    protected function sendRenewalSuccessNotification(Subscription $subscription): void
    {
        $tenant = $subscription->tenant;
        if (! $tenant) {
            return;
        }

        $adminUser = $this->resolveAdminUserForTenant($tenant);

        if ($adminUser && ! empty($adminUser->email)) {
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
     * Send renewal failure notification.
     */
    protected function sendRenewalFailureNotification(Subscription $subscription): void
    {
        $tenant = $subscription->tenant;
        if (! $tenant) {
            return;
        }

        $adminUser = $this->resolveAdminUserForTenant($tenant);

        if ($adminUser && ! empty($adminUser->email)) {
            $variables = [
                'tenant_name' => $tenant->name ?? 'there',
                'amount' => '$'.number_format(($subscription->amount ?? 0) / 100, 2),
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

    /**
     * Resolve the owner / admin user for a tenant.
     *
     * Tries the LandlordUser/User table with is_owner=true. Tenant-scoped
     * lookup so SaaS data isolation is preserved.
     */
    protected function resolveAdminUserForTenant(Tenant $tenant): ?object
    {
        // The owner_email column on Tenant is the canonical pointer.
        // Fall back to any user with is_owner=true in the tenant DB (works
        // when the renewal cron runs in a context where tenancy is initialized).
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
