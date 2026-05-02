<?php

declare(strict_types=1);

namespace Aero\Notifications\Services\Pipeline\Adapters;

use Aero\Notifications\Services\Mail\MailService;
use Illuminate\Notifications\Notification;

class MailChannelAdapter extends AbstractChannelAdapter
{
    public function __construct(
        protected MailService $mail,
        protected \Aero\Notifications\Services\Notification\NotificationLoggingService $logger,
    ) {
        parent::__construct($logger);
    }

    public function send(object $notifiable, Notification $notification): array
    {
        $logId = $this->logAttempt($notifiable, $notification, 'mail');

        try {
            $message = $notification->toMail($notifiable);
            $to = method_exists($notifiable, 'routeNotificationForMail')
                ? $notifiable->routeNotificationForMail($notification)
                : ($notifiable->email ?? null);

            if (! $to) {
                throw new \RuntimeException('No email address for notifiable');
            }

            $result = $this->mail
                ->to($to)
                ->subject($message->subject ?? 'Notification')
                ->html($message->render() ?? '')
                ->send();

            if ($result['success']) {
                $this->logSuccess($logId);
            } else {
                $this->logFailure($logId, $result['message']);
            }

            return $result;
        } catch (\Throwable $e) {
            $this->logFailure($logId, $e->getMessage());

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
