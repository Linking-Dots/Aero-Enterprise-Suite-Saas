<?php

declare(strict_types=1);

namespace Aero\Notifications\Services\Pipeline;

use Aero\Notifications\Services\Mail\MailService;
use Aero\Notifications\Services\Notification\NotificationLoggingService;
use Aero\Notifications\Services\Notification\NotificationPreferenceService;
use Aero\Notifications\Services\Push\FcmNotificationService;
use Aero\Notifications\Services\Sms\SmsService;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Notifications\Channels\BroadcastChannel;
use Illuminate\Notifications\Channels\DatabaseChannel;
use Illuminate\Notifications\Channels\MailChannel;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Unified Notification Pipeline
 *
 * Single entry point for all notifications. Dispatches to channel adapters,
 * applies user preferences, quiet hours, retry logic, and unified logging.
 */
class NotificationPipeline
{
    protected array $adapters = [];

    public function __construct(
        protected NotificationPreferenceService $preferences,
        protected NotificationLoggingService $logger,
        protected MailService $mail,
        protected SmsService $sms,
        protected ?FcmNotificationService $push = null,
        protected ?BroadcastManager $broadcast = null,
    ) {
        $this->registerAdapters();
    }

    protected function registerAdapters(): void
    {
        $this->adapters = [
            'mail' => new Adapters\MailChannelAdapter($this->mail, $this->logger),
            'sms' => new Adapters\SmsChannelAdapter($this->sms, $this->logger),
            'database' => new Adapters\DatabaseChannelAdapter($this->logger),
            'broadcast' => new Adapters\BroadcastChannelAdapter($this->broadcast, $this->logger),
        ];

        if ($this->push) {
            $this->adapters['push'] = new Adapters\PushChannelAdapter($this->push, $this->logger);
        }
    }

    /**
     * Dispatch a notification through all enabled channels.
     *
     * @param object $notifiable The entity receiving the notification (User, etc.)
     * @param Notification $notification The Laravel notification instance
     * @return array<string, array{success: bool, message: string}> Results per channel
     */
    public function dispatch(object $notifiable, Notification $notification): array
    {
        $eventType = $notification->eventType ?? get_class($notification);
        $channels = $this->resolveChannels($notifiable, $notification, $eventType);

        if (empty($channels)) {
            Log::debug('NotificationPipeline: no channels resolved', [
                'notifiable' => get_class($notifiable),
                'notification' => $eventType,
            ]);

            return [];
        }

        $results = [];
        foreach ($channels as $channel) {
            $results[$channel] = $this->sendViaChannel($notifiable, $notification, $channel, $eventType);
        }

        return $results;
    }

    /**
     * Dispatch only to specific channels, bypassing preferences.
     */
    public function dispatchToChannels(object $notifiable, Notification $notification, array $channels): array
    {
        $eventType = $notification->eventType ?? get_class($notification);
        $results = [];
        foreach ($channels as $channel) {
            $results[$channel] = $this->sendViaChannel($notifiable, $notification, $channel, $eventType);
        }

        return $results;
    }

    protected function resolveChannels(object $notifiable, Notification $notification, string $eventType): array
    {
        // 1. Notification declares its channels via via()
        $declared = $notification->via($notifiable);

        // 2. Filter by user preferences
        $enabled = [];
        foreach ($declared as $channel) {
            if ($this->preferences->shouldNotify($notifiable, $eventType, $channel)) {
                $enabled[] = $channel;
            }
        }

        // 3. Filter by quiet hours (skip non-urgent channels)
        if ($this->preferences->isInQuietHours($notifiable) && ! $this->isUrgent($eventType)) {
            $enabled = array_diff($enabled, ['mail', 'sms', 'push']);
        }

        // 4. Filter by global channel config
        $config = config('aero.notifications.channels', []);
        $enabled = array_filter($enabled, fn ($c) => ($config[$c]['enabled'] ?? true));

        return array_values($enabled);
    }

    protected function sendViaChannel(object $notifiable, Notification $notification, string $channel, string $eventType): array
    {
        $adapter = $this->adapters[$channel] ?? null;
        if (! $adapter) {
            return ['success' => false, 'message' => "No adapter registered for channel: {$channel}"];
        }

        try {
            $result = $adapter->send($notifiable, $notification);
            $result['channel'] = $channel;
            $result['event_type'] = $eventType;

            return $result;
        } catch (\Throwable $e) {
            Log::error("NotificationPipeline: {$channel} delivery failed", [
                'error' => $e->getMessage(),
                'notification' => $eventType,
                'notifiable' => get_class($notifiable),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'channel' => $channel,
                'event_type' => $eventType,
            ];
        }
    }

    protected function isUrgent(string $eventType): bool
    {
        return in_array($eventType, [
            'security_alert',
            'login_new_device',
            'escalation',
            'account_suspended',
        ], true);
    }
}
