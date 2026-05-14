<?php

namespace Aero\Analytics\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlertNotification extends TenantModel
{
    use HasFactory;

    protected $table = 'analytics_alert_notifications';

    protected $fillable = [
        'alert_rule_id', 'notification_type', 'recipient', 'subject',
        'message', 'status', 'sent_at', 'delivered_at', 'error_message',
        'retry_count', 'data_context',
    ];

    protected $casts = [
        'alert_rule_id' => 'integer',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'retry_count' => 'integer',
        'data_context' => 'json',
    ];

    const TYPE_EMAIL = 'email';

    const TYPE_SMS = 'sms';

    const TYPE_SLACK = 'slack';

    const TYPE_TEAMS = 'teams';

    const TYPE_WEBHOOK = 'webhook';

    const TYPE_PUSH = 'push';

    const STATUS_PENDING = 'pending';

    const STATUS_SENT = 'sent';

    const STATUS_DELIVERED = 'delivered';

    const STATUS_FAILED = 'failed';

    const STATUS_BOUNCED = 'bounced';

    public function alertRule()
    {
        return $this->belongsTo(AlertRule::class);
    }

    public function isSuccessful()
    {
        return in_array($this->status, [self::STATUS_SENT, self::STATUS_DELIVERED]);
    }
}
