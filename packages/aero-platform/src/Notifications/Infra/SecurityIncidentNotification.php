<?php

declare(strict_types=1);

namespace Aero\Platform\Notifications\Infra;

use Aero\Platform\Models\Infra\SecurityIncident;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SecurityIncidentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly SecurityIncident $incident) {}

    /** @return array<string> */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("[{$this->incident->severity}] Security Incident: {$this->incident->title}")
            ->greeting('Important Security Notice')
            ->line('We are contacting you regarding a security incident affecting your account.')
            ->line("**Title:** {$this->incident->title}")
            ->line("**Severity:** {$this->incident->severity}")
            ->line("**Description:** {$this->incident->description}")
            ->line('Our security team is actively working to resolve this issue.')
            ->line('You will receive updates as the situation progresses.')
            ->salutation('The Platform Security Team');
    }
}
