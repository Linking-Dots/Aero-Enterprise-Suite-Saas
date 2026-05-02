<?php

declare(strict_types=1);

namespace Aero\Notifications\Contracts;

interface PushNotificationService
{
    /**
     * Send a push notification to a single device token.
     */
    public function send(string $deviceToken, string $title, string $body, array $data = []): bool;

    /**
     * Send a push notification to multiple device tokens.
     */
    public function sendMulticast(array $deviceTokens, string $title, string $body, array $data = []): array;
}
