<?php

declare(strict_types=1);

namespace Aero\Notifications\Services\Notification;

use Aero\Notifications\Models\UserNotificationPreference;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class NotificationPreferenceService
{
    public function shouldNotify(object $notifiable, string $eventType, string $channel): bool
    {
        $userId = $notifiable->id ?? null; if (! $userId) return true;
        try { return UserNotificationPreference::isEnabledForUser($userId, $eventType, $channel); } catch (\Throwable $e) { Log::warning('Preference check failed, defaulting to true', ['error' => $e->getMessage()]); return true; }
    }

    public function isInQuietHours(object $notifiable): bool
    {
        if (! config('aero.notifications.quiet_hours.enabled', false)) return false;
        $userId = $notifiable->id ?? null;
        $start = Carbon::createFromTimeString($this->getQuietStart($userId))->tz(config('app.timezone', 'UTC'));
        $end = Carbon::createFromTimeString($this->getQuietEnd($userId))->tz(config('app.timezone', 'UTC'));
        $now = now(config('app.timezone', 'UTC'));
        return $start->lessThanOrEqualTo($end) ? $now->between($start, $end) : ($now->greaterThanOrEqualTo($start) || $now->lessThanOrEqualTo($end));
    }

    public function getDigestFrequency(object $notifiable): string
    {
        $userId = $notifiable->id ?? null;
        if (! $userId) return config('aero.notifications.digest.default', 'immediate');
        try { return UserNotificationPreference::getDigestFrequency($userId) ?? config('aero.notifications.digest.default', 'immediate'); } catch (\Throwable $e) { return config('aero.notifications.digest.default', 'immediate'); }
    }

    public function getEnabledChannels(object $notifiable): array
    {
        $userId = $notifiable->id ?? null; if (! $userId) return ['mail','database'];
        try { $prefs = UserNotificationPreference::getForUser($userId); $enabled = []; foreach ($prefs as $p) { if ($p['enabled'] ?? false) $enabled[] = $p['channel']; } return array_unique($enabled) ?: ['mail','database']; } catch (\Throwable $e) { return ['mail','database']; }
    }

    public function getPreference(object $notifiable, string $eventType, string $channel): ?UserNotificationPreference
    {
        $userId = $notifiable->id ?? null; if (! $userId) return null;
        return UserNotificationPreference::where('user_id', $userId)->where('event_type', $eventType)->where('channel', $channel)->first();
    }

    public function setPreference(object $notifiable, string $eventType, string $channel, bool $enabled, array $options = []): void
    {
        $userId = $notifiable->id ?? null; if (! $userId) return;
        try { UserNotificationPreference::setForUser($userId, $eventType, $channel, $enabled, $options); } catch (\Throwable $e) { Log::error('Set preference failed', ['error' => $e->getMessage()]); }
    }

    public function getQuietStart(?int $userId): string { return $userId ? (UserNotificationPreference::getQuietHours($userId)['start'] ?? config('aero.notifications.quiet_hours.start', '22:00')) : config('aero.notifications.quiet_hours.start', '22:00'); }
    public function getQuietEnd(?int $userId): string { return $userId ? (UserNotificationPreference::getQuietHours($userId)['end'] ?? config('aero.notifications.quiet_hours.end', '08:00')) : config('aero.notifications.quiet_hours.end', '08:00'); }

    public function resetPreferences(int $userId): void
    {
        try { UserNotificationPreference::resetForUser($userId); } catch (\Throwable $e) { Log::error('Reset preferences failed', ['user' => $userId, 'error' => $e->getMessage()]); }
    }
}
