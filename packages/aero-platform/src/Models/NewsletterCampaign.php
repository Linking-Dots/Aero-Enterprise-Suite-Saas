<?php

declare(strict_types=1);

namespace Aero\Platform\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Newsletter Campaign Model
 *
 * A broadcast composed and sent to a subscriber audience/segment. Outbound mail
 * is simulated at the platform layer (recipients snapshotted, engagement metrics
 * generated) — the record and its analytics are real.
 */
class NewsletterCampaign extends CentralModel
{
    use HasFactory;
    use SoftDeletes;

    protected $connection = 'central';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_SENDING = 'sending';

    public const STATUS_SENT = 'sent';

    public const STATUS_CANCELLED = 'cancelled';

    public const AUDIENCE_ALL = 'all_confirmed';

    public const AUDIENCE_SOURCE = 'source';

    protected $fillable = [
        'name',
        'subject',
        'preheader',
        'from_name',
        'from_email',
        'body',
        'status',
        'audience_type',
        'audience_source',
        'recipients_count',
        'sent_count',
        'open_count',
        'click_count',
        'bounce_count',
        'unsubscribe_count',
        'metadata',
        'scheduled_at',
        'sent_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'recipients_count' => 'integer',
        'sent_count' => 'integer',
        'open_count' => 'integer',
        'click_count' => 'integer',
        'bounce_count' => 'integer',
        'unsubscribe_count' => 'integer',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => self::STATUS_DRAFT,
        'audience_type' => self::AUDIENCE_ALL,
        'recipients_count' => 0,
        'sent_count' => 0,
        'open_count' => 0,
        'click_count' => 0,
        'bounce_count' => 0,
        'unsubscribe_count' => 0,
    ];

    public function scopeSent($query)
    {
        return $query->where('status', self::STATUS_SENT);
    }

    public function getOpenRateAttribute(): float
    {
        return $this->sent_count > 0 ? round($this->open_count / $this->sent_count * 100, 1) : 0.0;
    }

    public function getClickRateAttribute(): float
    {
        return $this->sent_count > 0 ? round($this->click_count / $this->sent_count * 100, 1) : 0.0;
    }

    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_SCHEDULED => 'Scheduled',
            self::STATUS_SENDING => 'Sending',
            self::STATUS_SENT => 'Sent',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }
}
