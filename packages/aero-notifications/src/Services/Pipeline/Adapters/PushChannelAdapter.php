<?php

declare(strict_types=1);

namespace Aero\Notifications\Services\Pipeline\Adapters;

use Aero\Notifications\Services\Push\FcmNotificationService;
use Illuminate\Notifications\Notification;

class PushChannelAdapter extends AbstractChannelAdapter
{
    public function __construct(
        protected FcmNotificationService $push,
        protected \Aero\Notifications\Services\Notification\NotificationLoggingService $logger,
    ) {
        parent::__construct($logger);
    }

    public function send(object $notifiable, Notification $notification): array
    {
        $logId = $this->logAttempt($notifiable, $notification, 'push');

        try {
            if (! method_exists($notification, 'toFcm')) {
                throw new \RuntimeException('Notification does not implement toFcm()');
            }

            $payload = $notification->toFcm($notifiable);
            $tokens = method_exists($notifiable, 'routeNotificationForFcm')
                ? $notifiable->routeNotificationForFcm($notification)
                : ($notifiable->fcm_tokens ?? $notifiable->device_tokens ?? []);

            if (empty($tokens)) {
                throw new \RuntimeException('No FCM tokens for notifiable');
            }

            if (is_string($tokens)) {
                $tokens = [$tokens];
            }

            if (count($tokens) === 1) {
                $success = $this->push->sendNotification($tokens[0], $payload['title'], $payload['body'], $payload['data'] ?? []);
            } else {
                $result = $this->push->sendMulticastNotification($tokens, $payload['title'], $payload['body'], $payload['data'] ?? []);
                $success = ($result['failed'] ?? 0) === 0;
            }

            if ($success) {
                $this->logSuccess($logId);

                return ['success' => true, 'message' => 'Push notification sent'];
            }

            $this->logFailure($logId, 'Push notification failed');

            return ['success' => false, 'message' => 'Push notification failed'];
        } catch (\Throwable $e) {
            $this->logFailure($logId, $e->getMessage());

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
