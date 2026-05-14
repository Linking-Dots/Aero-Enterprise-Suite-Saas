<?php

namespace Aero\Analytics\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportSchedule extends TenantModel
{
    use HasFactory;

    protected $table = 'analytics_report_schedules';

    protected $fillable = [
        'report_id', 'name', 'description', 'frequency', 'schedule_config',
        'output_format', 'delivery_method', 'delivery_config', 'parameters',
        'is_active', 'created_by', 'last_run_at', 'next_run_at',
    ];

    protected $casts = [
        'report_id' => 'integer',
        'schedule_config' => 'json',
        'delivery_config' => 'json',
        'parameters' => 'json',
        'is_active' => 'boolean',
        'created_by' => 'integer',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
    ];

    const FREQUENCY_HOURLY = 'hourly';

    const FREQUENCY_DAILY = 'daily';

    const FREQUENCY_WEEKLY = 'weekly';

    const FREQUENCY_MONTHLY = 'monthly';

    const FREQUENCY_QUARTERLY = 'quarterly';

    const FREQUENCY_YEARLY = 'yearly';

    const DELIVERY_EMAIL = 'email';

    const DELIVERY_FTP = 'ftp';

    const DELIVERY_SFTP = 'sftp';

    const DELIVERY_API = 'api';

    const DELIVERY_STORAGE = 'storage';

    public function report()
    {
        return $this->belongsTo(Report::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function subscriptions()
    {
        return $this->hasMany(ReportSubscription::class);
    }

    public function isDue()
    {
        return $this->is_active && $this->next_run_at && $this->next_run_at <= now();
    }
}
