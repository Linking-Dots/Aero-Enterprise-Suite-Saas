<?php

declare(strict_types=1);

namespace Aero\Notifications\Services\Pipeline\Adapters;

use Aero\Notifications\Services\Sms\SmsService;
use Illuminate\Notifications\Notification;

class SmsChannelAdapter extends AbstractChannelAdapter
{
    public function __construct(
        protected SmsService $sms,
        protected \Aero\Notifications\Services\Notification\NotificationLoggingService $logger,
    ) {
        parent::__construct($logger);
    }

    public function send(object $notifiable, Notification $notification): array
    {
        $logId = $this->logAttempt($notifiable, $notification, 'sms');

        try {
            if (! method_exists($notification, 'toSms')) {
                throw new \RuntimeException('Notification does not implement toSms()');
            }

            $message = $notification->toSms($notifiable);
            $to = method_exists($notifiable, 'routeNotificationForSms')
                ? $notifiable->routeNotificationForSms($notification)
                : ($notifiable->phone ?? $notifiable->phone_number ?? null);

            if (! $to) {
                throw new \RuntimeException('No phone number for notifiable');
            }

            $result = $this->sms->send($to, $message);

            if ($result['success']) {
                $this->logSuccess($logId);
            } else {
                $this->logFailure($logId, $result['message'] ?? 'SMS failed');
            }

            return $result;
        } catch (\Throwable $e) {
            $this->logFailure($logId, $e->getMessage());

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
