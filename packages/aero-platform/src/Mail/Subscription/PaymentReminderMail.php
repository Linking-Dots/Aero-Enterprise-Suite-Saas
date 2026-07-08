<?php

declare(strict_types=1);

namespace Aero\Platform\Mail\Subscription;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Payment Reminder Email
 *
 * Queued to a tenant's billing address from the platform-admin dunning
 * queue when a subscription payment is past due. Content is self-contained
 * (htmlString) — the platform package registers no blade view namespace,
 * so a markdown view here would 500 at send time (see TrialEndingMail,
 * which has that latent defect).
 */
class PaymentReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $tenantName,
        public string $planName,
        public float $amount,
        public string $currency = 'USD',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Payment reminder — '.config('app.name'),
            tags: ['payment-reminder', 'dunning', 'billing'],
        );
    }

    public function content(): Content
    {
        $app = e(config('app.name'));
        $name = e($this->tenantName);
        $plan = e($this->planName);
        $amount = e($this->currency).' '.number_format($this->amount, 2);

        return new Content(htmlString: <<<HTML
            <div style="font-family:Arial,Helvetica,sans-serif;max-width:560px;margin:0 auto;color:#1f2937;line-height:1.6">
              <h2 style="font-size:18px;margin:24px 0 8px">Payment reminder</h2>
              <p>Hi {$name},</p>
              <p>The latest payment for your <strong>{$plan}</strong> subscription ({$amount}) could not be processed
                 and is now past due. To keep your workspace uninterrupted, please update your payment method or
                 settle the outstanding balance from your billing page.</p>
              <p>If you have already paid, you can ignore this reminder — it can take a short while for the payment
                 to be reflected.</p>
              <p style="color:#6b7280;font-size:13px;margin-top:24px">— The {$app} billing team</p>
            </div>
            HTML);
    }
}
