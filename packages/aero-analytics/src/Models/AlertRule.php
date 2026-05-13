<?php

namespace Aero\Analytics\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AlertRule extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'analytics_alert_rules';

    protected $fillable = [
        'name', 'description', 'rule_type', 'data_source_id', 'condition_config',
        'notification_config', 'frequency', 'is_active', 'created_by',
        'last_triggered_at', 'trigger_count',
    ];

    protected $casts = [
        'data_source_id' => 'integer',
        'condition_config' => 'json',
        'notification_config' => 'json',
        'is_active' => 'boolean',
        'created_by' => 'integer',
        'last_triggered_at' => 'datetime',
        'trigger_count' => 'integer',
    ];

    const TYPE_THRESHOLD = 'threshold';

    const TYPE_ANOMALY = 'anomaly';

    const TYPE_TREND = 'trend';

    const TYPE_COMPARISON = 'comparison';

    const FREQUENCY_REAL_TIME = 'real_time';

    const FREQUENCY_EVERY_MINUTE = 'every_minute';

    const FREQUENCY_EVERY_5_MINUTES = 'every_5_minutes';

    const FREQUENCY_EVERY_15_MINUTES = 'every_15_minutes';

    const FREQUENCY_HOURLY = 'hourly';

    const FREQUENCY_DAILY = 'daily';

    public function dataSource()
    {
        return $this->belongsTo(DataSource::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function notifications()
    {
        return $this->hasMany(AlertNotification::class);
    }
}
