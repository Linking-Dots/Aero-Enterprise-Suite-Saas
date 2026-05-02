<?php

declare(strict_types=1);

namespace Aero\Notifications\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

class NotificationLog extends Model
{
    use MassPrunable;

    protected $fillable = [
        'user_id','notifiable_type','notifiable_id','channel','notification_type','event_type',
        'recipient','subject','content','status','error_message','metadata','attempts',
        'max_attempts','last_attempt_at','sent_at','delivered_at','read_at','failed_at',
    ];

    protected $casts = [
        'metadata' => 'array','sent_at' => 'datetime','delivered_at' => 'datetime',
        'read_at' => 'datetime','failed_at' => 'datetime','last_attempt_at' => 'datetime',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT = 'sent';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_READ = 'read';
    public const STATUS_FAILED = 'failed';
    public const CHANNEL_EMAIL = 'email';
    public const CHANNEL_SMS = 'sms';
    public const CHANNEL_PUSH = 'push';
    public const CHANNEL_BROADCAST = 'broadcast';
    public const CHANNEL_DATABASE = 'database';

    public function prunable(): \Illuminate\Database\Eloquent\Builder
    {
        return static::where('created_at', '<', now()->subDays(30));
    }

    public function scopePending($query) { return $query->where('status', self::STATUS_PENDING); }
    public function scopeFailed($query) { return $query->where('status', self::STATUS_FAILED); }
    public function scopeSent($query) { return $query->where('status', self::STATUS_SENT); }
    public function scopeChannel($query, string $channel) { return $query->where('channel', $channel); }
    public function scopeRecent($query) { return $query->where('created_at', '>=', now()->subDays(7)); }

    public static function logNotification(array $data): self { return static::create(array_merge(['status' => self::STATUS_PENDING,'attempts' => 1,'max_attempts' => config('aero.notifications.retry.max_attempts', 3),'last_attempt_at' => now()], $data)); }
    public static function markSent(int $id): void { static::where('id', $id)->update(['status' => self::STATUS_SENT, 'sent_at' => now()]); }
    public static function markDelivered(int $id): void { static::where('id', $id)->update(['status' => self::STATUS_DELIVERED, 'delivered_at' => now()]); }
    public static function markFailed(int $id, string $reason): void { static::where('id', $id)->update(['status' => self::STATUS_FAILED, 'error_message' => $reason, 'failed_at' => now()]); }
    public static function markRead(int $id): void { static::where('id', $id)->update(['status' => self::STATUS_READ, 'read_at' => now()]); }
    public static function getPending(int $limit = 100): array { return static::pending()->limit($limit)->get()->toArray(); }
}
