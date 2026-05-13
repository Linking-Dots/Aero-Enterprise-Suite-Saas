<?php

namespace Aero\Analytics\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DashboardView extends TenantModel
{
    use HasFactory;

    protected $table = 'analytics_dashboard_views';

    protected $fillable = [
        'dashboard_id', 'user_id', 'viewed_at', 'session_id',
        'ip_address', 'user_agent', 'duration_seconds', 'device_type',
    ];

    protected $casts = [
        'dashboard_id' => 'integer',
        'user_id' => 'integer',
        'viewed_at' => 'datetime',
        'duration_seconds' => 'integer',
    ];

    const DEVICE_DESKTOP = 'desktop';

    const DEVICE_MOBILE = 'mobile';

    const DEVICE_TABLET = 'tablet';

    public function dashboard()
    {
        return $this->belongsTo(Dashboard::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
