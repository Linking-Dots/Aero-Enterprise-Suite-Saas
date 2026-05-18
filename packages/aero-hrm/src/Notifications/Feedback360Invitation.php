<?php

declare(strict_types=1);

namespace Aero\HRM\Notifications;

use Aero\HRM\Models\Feedback360Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class Feedback360Invitation extends Notification
{
    public function __construct(public readonly Feedback360Request $request) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('You have been invited to provide 360° feedback')
            ->line("Please submit your feedback by {$this->request->due_on}.");
    }
}
