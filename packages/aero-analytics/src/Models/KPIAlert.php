<?php

namespace Aero\Analytics\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KPIAlert extends TenantModel
{
    use HasFactory;

    protected $table = 'analytics_kpi_alerts';

    protected $fillable = [
        'kpi_id', 'alert_type', 'threshold_value', 'current_value',
        'triggered_at', 'resolved_at', 'status', 'message',
        'notified_users', 'created_by',
    ];

    protected $casts = [
        'kpi_id' => 'integer',
        'threshold_value' => 'decimal:6',
        'current_value' => 'decimal:6',
        'triggered_at' => 'datetime',
        'resolved_at' => 'datetime',
        'notified_users' => 'json',
        'created_by' => 'integer',
    ];

    const TYPE_WARNING = 'warning';

    const TYPE_CRITICAL = 'critical';

    const TYPE_INFO = 'info';

    const STATUS_ACTIVE = 'active';

    const STATUS_RESOLVED = 'resolved';

    const STATUS_DISMISSED = 'dismissed';

    public function kpi()
    {
        return $this->belongsTo(KPI::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getDurationAttribute()
    {
        if ($this->resolved_at) {
            return $this->triggered_at->diffInMinutes($this->resolved_at);
        }

        return $this->triggered_at->diffInMinutes(now());
    }
}
