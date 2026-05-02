<?php

namespace Aero\Platform\Jobs\Notifications;

use Aero\Platform\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Send trial ending notification to tenant admin.
 *
 * Dispatched when Stripe webhook signals trial_will_end.
 */
class SendTrialEndingNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Tenant $tenant,
        public Carbon $trialEndsAt
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $adminEmail = $this->tenant->data['owner_email']
            ?? $this->tenant->data['admin_email']
            ?? null;

        if (! $adminEmail) {
            Log::warning('Trial ending notification: no admin email for tenant', [
                'tenant_id' => $this->tenant->id,
            ]);

            return;
        }

        Mail::to($adminEmail)->send(new \Aero\Platform\Mail\Subscription\TrialEndingMail($this->tenant, $this->trialEndsAt));

        Log::info('Trial ending notification dispatched', [
            'tenant_id' => $this->tenant->id,
            'admin_email' => $adminEmail,
            'trial_ends_at' => $this->trialEndsAt->toDateTimeString(),
        ]);
    }
}
