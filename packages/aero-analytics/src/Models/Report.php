<?php

namespace Aero\Analytics\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Report extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'analytics_reports';

    protected $fillable = [
        'name', 'description', 'report_type', 'category', 'module',
        'data_source_id', 'query_config', 'format_config', 'parameters',
        'is_public', 'is_scheduled', 'schedule_config', 'created_by',
    ];

    protected $casts = [
        'data_source_id' => 'integer',
        'query_config' => 'json',
        'format_config' => 'json',
        'parameters' => 'json',
        'is_public' => 'boolean',
        'is_scheduled' => 'boolean',
        'schedule_config' => 'json',
        'created_by' => 'integer',
    ];

    const TYPE_TABULAR = 'tabular';

    const TYPE_SUMMARY = 'summary';

    const TYPE_CHART = 'chart';

    const TYPE_CROSSTAB = 'crosstab';

    const TYPE_SUBREPORT = 'subreport';

    const MODULE_FINANCE = 'finance';

    const MODULE_HR = 'hr';

    const MODULE_CRM = 'crm';

    const MODULE_MANUFACTURING = 'manufacturing';

    const MODULE_PROJECT = 'project';

    const MODULE_INVENTORY = 'inventory';

    public function dataSource()
    {
        return $this->belongsTo(DataSource::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function executions()
    {
        return $this->hasMany(ReportExecution::class);
    }

    public function schedules()
    {
        return $this->hasMany(ReportSchedule::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(ReportSubscription::class);
    }
}
