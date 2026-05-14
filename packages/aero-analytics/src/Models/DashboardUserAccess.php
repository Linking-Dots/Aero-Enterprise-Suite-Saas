<?php

namespace Aero\Analytics\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DashboardUserAccess extends TenantModel
{
    use HasFactory;

    protected $table = 'analytics_dashboard_user_access';

    protected $fillable = [
        'dashboard_id', 'user_id', 'access_type', 'granted_by',
        'granted_at', 'expires_at', 'is_active',
    ];

    protected $casts = [
        'dashboard_id' => 'integer',
        'user_id' => 'integer',
        'granted_by' => 'integer',
        'granted_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    const ACCESS_VIEW = 'view';

    const ACCESS_EDIT = 'edit';

    const ACCESS_ADMIN = 'admin';

    public function dashboard()
    {
        return $this->belongsTo(Dashboard::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function grantedBy()
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    public function isExpired()
    {
        return $this->expires_at && $this->expires_at < now();
    }

    public function isValid()
    {
        return $this->is_active && ! $this->isExpired();
    }
}
