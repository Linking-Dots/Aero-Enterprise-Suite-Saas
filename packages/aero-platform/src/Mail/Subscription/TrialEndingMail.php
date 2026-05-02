<?php

namespace Aero\Platform\Mail\Subscription;

use Aero\Platform\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Trial Ending Notification Email
 *
 * Sent to tenant administrators when a subscription trial is about to expire,
 * prompting them to add payment details to avoid service interruption.
 */
class TrialEndingMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Tenant $tenant,
        public Carbon $trialEndsAt
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your free trial ends soon — '.config('app.name'),
            tags: ['trial-ending', 'subscription', 'billing'],
            metadata: [
                'tenant_id' => $this->tenant->id,
                'trial_ends_at' => $this->trialEndsAt->toDateTimeString(),
                'type' => 'trial_ending',
            ]
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.subscription.trial-ending',
            with: [
                'tenantName' => $this->tenant->name,
                'trialEndsAt' => $this->trialEndsAt,
                'daysRemaining' => (int) now()->diffInDays($this->trialEndsAt, false),
                'billingUrl' => $this->resolveBillingUrl(),
                'supportUrl' => config('platform.support_url', route('support.index')),
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    /**
     * Resolve the tenant billing management URL.
     */
    private function resolveBillingUrl(): string
    {
        $domain = sprintf('%s.%s', $this->tenant->subdomain, config('platform.central_domain', 'localhost'));
        $scheme = config('app.debug') ? 'http' : 'https';

        return sprintf('%s://%s/billing', $scheme, $domain);
    }
}
