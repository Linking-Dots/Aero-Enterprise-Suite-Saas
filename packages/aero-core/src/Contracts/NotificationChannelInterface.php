<?php

namespace Aero\Core\Contracts;

interface NotificationChannelInterface
{
    public function send(object $notifiable, object $notification): void;
    public function channelName(): string;
}
