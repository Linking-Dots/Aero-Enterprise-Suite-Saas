<?php

namespace Aero\Analytics\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KPI extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'analytics_kpis';

    protected $fillable = [
        'name', 'description', 'kpi_type', 'category', 'module',
        'data_source_id', 'calculation_config', 'target_value',
        'warning_threshold', 'critical_threshold', 'unit_of_measure',
        'frequency', 'is_active', 'created_by', 'owner_id',
    ];

    protected $casts = [
        'data_source_id' => 'integer',
        'calculation_config' => 'json',
        'target_value' => 'decimal:4',
        'warning_threshold' => 'decimal:4',
        'critical_threshold' => 'decimal:4',
        'is_active' => 'boolean',
        'created_by' => 'integer',
        'owner_id' => 'integer',
    ];

    const TYPE_COUNT = 'count';

    const TYPE_SUM = 'sum';

    const TYPE_AVERAGE = 'average';

    const TYPE_PERCENTAGE = 'percentage';

    const TYPE_RATIO = 'ratio';

    const TYPE_GROWTH_RATE = 'growth_rate';

    const FREQUENCY_REAL_TIME = 'real_time';

    const FREQUENCY_HOURLY = 'hourly';

    const FREQUENCY_DAILY = 'daily';

    const FREQUENCY_WEEKLY = 'weekly';

    const FREQUENCY_MONTHLY = 'monthly';

    const FREQUENCY_QUARTERLY = 'quarterly';

    const FREQUENCY_YEARLY = 'yearly';

    public function dataSource()
    {
        return $this->belongsTo(DataSource::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function values()
    {
        return $this->hasMany(KPIValue::class);
    }

    public function alerts()
    {
        return $this->hasMany(KPIAlert::class);
    }

    public function getCurrentValue()
    {
        return $this->values()->latest()->first();
    }

    public function getStatus($currentValue = null)
    {
        $currentValue = $currentValue ?: $this->getCurrentValue()?->value;

        if ($currentValue === null) {
            return 'unknown';
        }

        if ($this->critical_threshold && $currentValue <= $this->critical_threshold) {
            return 'critical';
        }

        if ($this->warning_threshold && $currentValue <= $this->warning_threshold) {
            return 'warning';
        }

        return 'good';
    }
}
