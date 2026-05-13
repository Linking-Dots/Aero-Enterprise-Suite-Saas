<?php

namespace Aero\Analytics\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportSubscription extends TenantModel
{
    use HasFactory;

    protected $table = 'analytics_report_subscriptions';

    protected $fillable = [
        'report_id', 'report_schedule_id', 'user_id', 'delivery_method',
        'delivery_address', 'is_active', 'subscribed_at', 'unsubscribed_at',
    ];

    protected $casts = [
        'report_id' => 'integer',
        'report_schedule_id' => 'integer',
        'user_id' => 'integer',
        'is_active' => 'boolean',
        'subscribed_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
    ];

    const DELIVERY_EMAIL = 'email';

    const DELIVERY_SLACK = 'slack';

    const DELIVERY_TEAMS = 'teams';

    const DELIVERY_SMS = 'sms';

    public function report()
    {
        return $this->belongsTo(Report::class);
    }

    public function reportSchedule()
    {
        return $this->belongsTo(ReportSchedule::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
