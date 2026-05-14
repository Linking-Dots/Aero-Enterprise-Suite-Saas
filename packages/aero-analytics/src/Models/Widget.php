<?php

namespace Aero\Analytics\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Widget extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'analytics_widgets';

    protected $fillable = [
        'name', 'description', 'widget_type', 'data_source_id',
        'query_config', 'visualization_config', 'refresh_interval',
        'is_active', 'created_by', 'category',
    ];

    protected $casts = [
        'data_source_id' => 'integer',
        'query_config' => 'json',
        'visualization_config' => 'json',
        'refresh_interval' => 'integer',
        'is_active' => 'boolean',
        'created_by' => 'integer',
    ];

    const TYPE_CHART = 'chart';

    const TYPE_TABLE = 'table';

    const TYPE_KPI = 'kpi';

    const TYPE_GAUGE = 'gauge';

    const TYPE_MAP = 'map';

    const TYPE_TEXT = 'text';

    const TYPE_IFRAME = 'iframe';

    const CATEGORY_FINANCE = 'finance';

    const CATEGORY_SALES = 'sales';

    const CATEGORY_HR = 'hr';

    const CATEGORY_OPERATIONS = 'operations';

    const CATEGORY_MANUFACTURING = 'manufacturing';

    const CATEGORY_GENERAL = 'general';

    public function dataSource()
    {
        return $this->belongsTo(DataSource::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function dashboardWidgets()
    {
        return $this->hasMany(DashboardWidget::class);
    }
}
