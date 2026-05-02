<?php

declare(strict_types=1);

namespace Aero\Notifications\Services\Pipeline\Adapters;

use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Notifications\Notification;

class BroadcastChannelAdapter extends AbstractChannelAdapter
{
    public function __construct(
        protected ?BroadcastManager $broadcast,
        protected \Aero\Notifications\Services\Notification\NotificationLoggingService $logger,
    ) {
        parent::__construct($logger);
    }

    public function send(object $notifiable, Notification $notification): array
    {
        $logId = $this->logAttempt($notifiable, $notification, 'broadcast');

        try {
            if (! method_exists($notification, 'toBroadcast')) {
                // Fallback: use toArray() for broadcast payload
                $payload = $notification->toArray($notifiable);
            } else {
                $payload = $notification->toBroadcast($notifiable);
            }

            if (! $this->broadcast) {
                throw new \RuntimeException('Broadcast manager not available');
            }

            $channel = 'App.Models.User.'.$notifiable->id;
            $this->broadcast->event(new \Aero\Notifications\Events\BroadcastNotification(
                $channel,
                $payload,
                $notification->eventType ?? get_class($notification)
            ));

            $this->logSuccess($logId);

            return ['success' => true, 'message' => 'Broadcast dispatched'];
        } catch (\Throwable $e) {
            $this->logFailure($logId, $e->getMessage());

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
