<?php

declare(strict_types=1);

namespace Aero\Notifications\Notifications;

use Aero\Notifications\Services\Pipeline\NotificationPipeline;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

abstract class BaseAeroNotification extends Notification
{
    use Queueable;

    public string $eventType;
    public array $context;
    public bool $ignorePreferences = false;
    public bool $forceChannels = false;
    protected array $forcedChannels = [];

    public function __construct(string $eventType, array $context = [])
    {
        $this->eventType = $eventType;
        $this->context = $context;
    }

    public function via(object $notifiable): array
    {
        if ($this->forceChannels && !empty($this->forcedChannels)) return $this->forcedChannels;
        return ['database', 'mail', 'broadcast'];
    }

    public function withChannels(array $channels): static { $this->forceChannels = true; $this->forcedChannels = $channels; return $this; }
    public function withoutPreferences(): static { $this->ignorePreferences = true; return $this; }

    public function toArray(object $notifiable): array { return ['event_type' => $this->eventType, 'context' => $this->context, 'message' => $this->getMessage()]; }
    public function toBroadcast(object $notifiable): array { return ['event_type' => $this->eventType, 'context' => $this->context, 'message' => $this->getMessage()]; }
    abstract public function getMessage(): string;
}
