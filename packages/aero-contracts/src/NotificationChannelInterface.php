<?php

declare(strict_types=1);

namespace Aero\Contracts;

interface NotificationChannelInterface
{
    public function send(object $notifiable, object $notification): void;
    public function channelName(): string;
}
