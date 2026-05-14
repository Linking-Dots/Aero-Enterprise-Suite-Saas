<?php

namespace Aero\Analytics\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dashboard extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'analytics_dashboards';

    protected $fillable = [
        'name', 'description', 'dashboard_type', 'layout_config',
        'is_public', 'is_default', 'refresh_interval', 'created_by',
        'category', 'tags', 'access_permissions',
    ];

    protected $casts = [
        'layout_config' => 'json',
        'is_public' => 'boolean',
        'is_default' => 'boolean',
        'refresh_interval' => 'integer',
        'created_by' => 'integer',
        'tags' => 'array',
        'access_permissions' => 'json',
    ];

    const TYPE_EXECUTIVE = 'executive';

    const TYPE_OPERATIONAL = 'operational';

    const TYPE_FINANCIAL = 'financial';

    const TYPE_SALES = 'sales';

    const TYPE_HR = 'hr';

    const TYPE_MANUFACTURING = 'manufacturing';

    const TYPE_CUSTOM = 'custom';

    const CATEGORY_STANDARD = 'standard';

    const CATEGORY_DEPARTMENT = 'department';

    const CATEGORY_ROLE_BASED = 'role_based';

    const CATEGORY_PERSONAL = 'personal';

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function widgets()
    {
        return $this->hasMany(DashboardWidget::class);
    }

    public function userAccess()
    {
        return $this->hasMany(DashboardUserAccess::class);
    }

    public function views()
    {
        return $this->hasMany(DashboardView::class);
    }
}
