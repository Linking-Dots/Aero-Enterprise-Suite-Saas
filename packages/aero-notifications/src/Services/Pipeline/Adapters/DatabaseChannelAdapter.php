<?php

declare(strict_types=1);

namespace Aero\Notifications\Services\Pipeline\Adapters;

use Illuminate\Notifications\Channels\DatabaseChannel;
use Illuminate\Notifications\Notification;

class DatabaseChannelAdapter extends AbstractChannelAdapter
{
    public function __construct(
        protected \Aero\Notifications\Services\Notification\NotificationLoggingService $logger,
    ) {
        parent::__construct($logger);
    }

    public function send(object $notifiable, Notification $notification): array
    {
        $logId = $this->logAttempt($notifiable, $notification, 'database');

        try {
            $data = $notification->toArray($notifiable);

            $notifiable->notifications()->create([
                'id' => $notification->id,
                'type' => get_class($notification),
                'data' => $data,
                'read_at' => null,
            ]);

            $this->logSuccess($logId);

            return ['success' => true, 'message' => 'Stored in database'];
        } catch (\Throwable $e) {
            $this->logFailure($logId, $e->getMessage());

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
