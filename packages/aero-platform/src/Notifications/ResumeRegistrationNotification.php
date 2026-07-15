<?php

declare(strict_types=1);

namespace Aero\Platform\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

/**
 * Sends a magic link email to resume an incomplete registration.
 */
class ResumeRegistrationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $token,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $resumeUrl = URL::route('platform.register.resume', ['token' => $this->token]);

        // Brand name through the white-label chain (platform brand → Meridian),
        // never a hardcoded product name.
        try {
            $siteName = app(\Aero\Notifications\Contracts\BrandingResolver::class)->resolve()['company_name']
                ?? config('app.name', 'aeos365');
        } catch (\Throwable) {
            $siteName = config('app.name', 'aeos365');
        }

        return (new MailMessage)
            ->subject('Continue Your '.$siteName.' Registration')
            ->greeting('Hello!')
            ->line('You started creating your workspace on '.$siteName.' and saved your progress.')
            ->line('Click the button below to pick up right where you left off.')
            ->action('Resume Registration', $resumeUrl)
            ->line('This link will expire in 7 days for your security.')
            ->line('If you didn\'t request this link, you can safely ignore this email.')
            ->salutation('Best regards,<br>The '.$siteName.' Team');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'resume_registration',
            'token' => $this->token,
        ];
    }
}
