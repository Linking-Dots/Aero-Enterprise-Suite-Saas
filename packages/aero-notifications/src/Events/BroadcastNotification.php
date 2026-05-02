<?php

declare(strict_types=1);

namespace Aero\Notifications\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BroadcastNotification implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $channel,
        public array $payload,
        public string $eventType,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel($this->channel)];
    }

    public function broadcastAs(): string { return 'notification.received'; }
}
