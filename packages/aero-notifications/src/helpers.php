<?php

declare(strict_types=1);

if (! function_exists('aero_notify')) {
    function aero_notify(object $notifiable, \Illuminate\Notifications\Notification $notification): array {
        return app(\Aero\Notifications\Services\Pipeline\NotificationPipeline::class)->dispatch($notifiable, $notification);
    }
}

if (! function_exists('aero_notify_channels')) {
    function aero_notify_channels(object $notifiable, \Illuminate\Notifications\Notification $notification, array $channels): array {
        return app(\Aero\Notifications\Services\Pipeline\NotificationPipeline::class)->dispatchToChannels($notifiable, $notification, $channels);
    }
}
