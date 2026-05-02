<?php

declare(strict_types=1);

namespace Aero\Notifications\Services\Pipeline\Adapters;

use Aero\Notifications\Services\Notification\NotificationLoggingService;
use Illuminate\Notifications\Notification;

abstract class AbstractChannelAdapter
{
    public function __construct(
        protected NotificationLoggingService $logger,
    ) {}

    /**
     * Send the notification via this channel.
     *
     * @return array{success: bool, message: string}
     */
    abstract public function send(object $notifiable, Notification $notification): array;

    protected function logAttempt(object $notifiable, Notification $notification, string $channel): int
    {
        return $this->logger->log(
            $notifiable,
            get_class($notification),
            $notification->eventType ?? get_class($notification),
            $channel,
            'pending'
        );
    }

    protected function logSuccess(int $logId): void
    {
        $this->logger->markSent($logId);
    }

    protected function logFailure(int $logId, string $reason): void
    {
        $this->logger->markFailed($logId, $reason);
    }
}
