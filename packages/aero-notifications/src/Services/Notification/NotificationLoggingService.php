<?php

declare(strict_types=1);

namespace Aero\Notifications\Services\Notification;

use Aero\Notifications\Models\NotificationLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class NotificationLoggingService
{
    public function log(object $notifiable, string $notificationClass, string $eventType, string $channel, string $status = 'pending', array $metadata = []): int
    {
        try {
            $log = NotificationLog::create([
                'notifiable_type' => get_class($notifiable),
                'notifiable_id' => $notifiable->id ?? null,
                'channel' => $channel,
                'notification_type' => $notificationClass,
                'event_type' => $eventType,
                'recipient' => $this->resolveRecipient($notifiable, $channel),
                'status' => $status,
                'metadata' => $metadata,
                'attempts' => 1,
                'max_attempts' => config('aero.notifications.retry.max_attempts', 3),
                'last_attempt_at' => now(),
                'sent_at' => $status === 'sent' ? now() : null,
            ]);
            return $log->id;
        } catch (\Throwable $e) {
            Log::error('Notification log failed', ['error' => $e->getMessage()]);
            return 0;
        }
    }

    public function markSent(int $logId): void
    {
        try { NotificationLog::where('id', $logId)->update(['status' => NotificationLog::STATUS_SENT, 'sent_at' => now(), 'delivered_at' => now()]); } catch (\Throwable $e) { Log::error('markSent failed', ['id' => $logId]); }
    }

    public function markDelivered(int $logId): void
    {
        try { NotificationLog::where('id', $logId)->update(['status' => NotificationLog::STATUS_DELIVERED, 'delivered_at' => now()]); } catch (\Throwable $e) { Log::error('markDelivered failed', ['id' => $logId]); }
    }

    public function markFailed(int $logId, string $reason): void
    {
        try { NotificationLog::where('id', $logId)->update(['status' => NotificationLog::STATUS_FAILED, 'error_message' => $reason, 'failed_at' => now()]); } catch (\Throwable $e) { Log::error('markFailed failed', ['id' => $logId]); }
    }

    public function markRead(int $logId): void
    {
        try { NotificationLog::where('id', $logId)->update(['status' => NotificationLog::STATUS_READ, 'read_at' => now()]); } catch (\Throwable $e) { Log::error('markRead failed', ['id' => $logId]); }
    }

    public function scheduleRetry(int $logId, Carbon $retryAt): void
    {
        try { NotificationLog::where('id', $logId)->increment('attempts'); NotificationLog::where('id', $logId)->update(['status' => NotificationLog::STATUS_PENDING, 'last_attempt_at' => now()]); } catch (\Throwable $e) { Log::error('scheduleRetry failed', ['id' => $logId]); }
    }

    public function getFailedForRetry(int $limit = 100): array
    {
        return NotificationLog::whereIn('status', [NotificationLog::STATUS_FAILED, NotificationLog::STATUS_PENDING])
            ->whereColumn('attempts', '<', 'max_attempts')
            ->where('last_attempt_at', '<=', now()->subMinutes(5))
            ->orderBy('last_attempt_at')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    protected function resolveRecipient(object $notifiable, string $channel): string
    {
        return match ($channel) {
            'mail' => $notifiable->email ?? '',
            'sms' => $notifiable->phone ?? $notifiable->phone_number ?? '',
            'push' => implode(',', $notifiable->fcm_tokens ?? $notifiable->device_tokens ?? []),
            default => (string) ($notifiable->id ?? ''),
        };
    }
}
