<?php

namespace Aero\Platform\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class NotificationLog extends CentralModel
{
    use MassPrunable;

    protected $fillable = [
        'user_id',
        'notifiable_type',
        'notifiable_id',
        'channel',
        'notification_type',
        'recipient',
        'subject',
        'content',
        'status',
        'error_message',
        'metadata',
        'attempts',
        'max_attempts',
        'last_attempt_at',
        'sent_at',
        'delivered_at',
        'read_at',
        'failed_at',
    ];

    /**
     * Get the prunable model query for logs older than 30 days.
     */
    public function prunable(): Builder
    {
        return static::where('created_at', '<=', now()->subDays(30));
    }

    protected $casts = [
        'metadata' => 'array',
        'last_attempt_at' => 'datetime',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    /**
     * Status constants.
     */
    public const STATUS_PENDING = 'pending';

    public const STATUS_SENDING = 'sending';

    public const STATUS_SENT = 'sent';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_FAILED = 'failed';

    public const STATUS_RETRYING = 'retrying';

    /**
     * Channel constants.
     */
    public const CHANNEL_EMAIL = 'email';

    public const CHANNEL_SMS = 'sms';

    public const CHANNEL_PUSH = 'push';

    public const CHANNEL_DATABASE = 'database';

    public const CHANNEL_SLACK = 'slack';

    /**
     * Get the user that owns the notification log.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the notifiable entity.
     */
    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope for pending notifications.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for failed notifications.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope for retryable notifications.
     */
    public function scopeRetryable($query)
    {
        return $query->where('status', self::STATUS_FAILED)
            ->whereColumn('attempts', '<', 'max_attempts');
    }

    /**
     * Scope by channel.
     */
    public function scopeByChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    /**
     * Check if notification can be retried.
     */
    public function canRetry(): bool
    {
        return $this->status === self::STATUS_FAILED
            && $this->attempts < $this->max_attempts;
    }

    /**
     * Increment attempt count.
     */
    public function incrementAttempt(): void
    {
        $this->increment('attempts');
        $this->update([
            'last_attempt_at' => now(),
            'status' => self::STATUS_RETRYING,
        ]);
    }

    /**
     * Mark as sent.
     */
    public function markAsSent(): void
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
        ]);
    }

    /**
     * Mark as delivered.
     */
    public function markAsDelivered(): void
    {
        $this->update([
            'status' => self::STATUS_DELIVERED,
            'delivered_at' => now(),
        ]);
    }

    /**
     * Mark as failed.
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
            'failed_at' => now(),
        ]);
    }

    /**
     * Create a log entry for a notification.
     */
    public static function logNotification(
        string $channel,
        string $notificationType,
        string $recipient,
        ?int $userId = null,
        ?string $subject = null,
        ?string $content = null,
        array $metadata = [],
        int $maxAttempts = 3
    ): self {
        return self::create([
            'user_id' => $userId,
            'channel' => $channel,
            'notification_type' => $notificationType,
            'recipient' => $recipient,
            'subject' => $subject,
            'content' => $content,
            'status' => self::STATUS_PENDING,
            'metadata' => $metadata,
            'attempts' => 0,
            'max_attempts' => $maxAttempts,
        ]);
    }
}
