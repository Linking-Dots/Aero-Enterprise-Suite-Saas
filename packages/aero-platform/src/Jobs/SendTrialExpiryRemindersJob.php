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

class SendTrialExpiryRemindersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of days before expiry to send reminders
     */
    protected array $reminderDays = [7, 3, 1];

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('SendTrialExpiryRemindersJob: Starting trial expiry reminder processing');

        foreach ($this->reminderDays as $days) {
            $this->sendRemindersForDays($days);
        }

        Log::info('SendTrialExpiryRemindersJob: Completed trial expiry reminder processing');
    }

    /**
     * Send reminders for tenants expiring in specific days
     */
    protected function sendRemindersForDays(int $days): void
    {
        $targetDate = now()->addDays($days)->toDateString();

        // Get tenants whose trial subscription expires on the target date
        $tenants = Tenant::query()
            ->whereHas('subscription', function ($q) use ($targetDate) {
                $q->where('status', Subscription::STATUS_TRIALING)
                    ->whereNotNull('trial_ends_at')
                    ->whereDate('trial_ends_at', $targetDate);
            })
            ->whereNull('deleted_at')
            ->get();

        Log::info("SendTrialExpiryRemindersJob: Found {$tenants->count()} tenants expiring in {$days} day(s)");

        foreach ($tenants as $tenant) {
            try {
                $this->sendReminderToTenant($tenant, $days);
            } catch (\Exception $e) {
                Log::error("SendTrialExpiryRemindersJob: Failed to send reminder to tenant {$tenant->id}", [
                    'error' => $e->getMessage(),
                    'tenant_id' => $tenant->id,
                ]);
            }
        }
    }

    /**
     * Send reminder notification to a specific tenant
     *
     * @param  object  $tenant
     */
    protected function sendReminderToTenant($tenant, int $days): void
    {
        $trialEnds = Carbon::parse($tenant->subscription('default')?->trial_ends_at);

        // Get tenant admin email and phone
        $adminUser = DB::table('users')
            ->where('tenant_id', $tenant->id)
            ->where('is_owner', true)
            ->first();

        if (! $adminUser) {
            Log::warning("SendTrialExpiryRemindersJob: No admin user found for tenant {$tenant->id}");

            return;
        }

        $variables = [
            'user_name' => $adminUser->name ?? 'there',
            'tenant_name' => $tenant->name,
            'days_remaining' => $days,
            'trial_ends' => $trialEnds->format('F j, Y'),
            'upgrade_url' => config('app.url').'/billing/upgrade',
            'support_url' => config('app.url').'/support',
            'discount' => '20',
            'show_pricing' => $days <= 3, // Show pricing for urgent reminders
        ];

        // Send email notification
        if ($adminUser->email) {
            MailService::make()
                ->template('notifications/trial-expiry', $variables)
                ->to($adminUser->email)
                ->subject("Your trial ends in {$days} ".($days == 1 ? 'day' : 'days'))
                ->queue('notifications')
                ->send();

            Log::info("SendTrialExpiryRemindersJob: Email sent to {$adminUser->email} for tenant {$tenant->id}");
        }

        // Send SMS notification
        if ($adminUser->phone) {
            SmsService::make()
                ->template('trial_expiry', [
                    'app_name' => config('app.name'),
                    'days' => $days,
                    'discount' => '20',
                    'upgrade_url' => config('app.url').'/upgrade',
                ])
                ->queue('sms')
                ->retry(3)
                ->send($adminUser->phone, '');

            Log::info("SendTrialExpiryRemindersJob: SMS sent to {$adminUser->phone} for tenant {$tenant->id}");
        }

        // Record reminder sent
        DB::table('notification_log')->insert([
            'tenant_id' => $tenant->id,
            'user_id' => $adminUser->id,
            'type' => 'trial_expiry',
            'channel' => 'email_sms',
            'metadata' => json_encode(['days_remaining' => $days]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
