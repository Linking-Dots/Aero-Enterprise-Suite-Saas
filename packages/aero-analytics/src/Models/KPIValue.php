<?php

namespace Aero\Analytics\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KPIValue extends TenantModel
{
    use HasFactory;

    protected $table = 'analytics_kpi_values';

    protected $fillable = [
        'kpi_id', 'value', 'target_value', 'period_start', 'period_end',
        'calculated_at', 'data_context', 'notes',
    ];

    protected $casts = [
        'kpi_id' => 'integer',
        'value' => 'decimal:6',
        'target_value' => 'decimal:6',
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'calculated_at' => 'datetime',
        'data_context' => 'json',
    ];

    public function kpi()
    {
        return $this->belongsTo(KPI::class);
    }

    public function getVarianceAttribute()
    {
        return $this->value - $this->target_value;
    }

    public function getVariancePercentageAttribute()
    {
        return $this->target_value > 0 ? (($this->value - $this->target_value) / $this->target_value) * 100 : 0;
    }

    public function getStatusAttribute()
    {
        return $this->kpi->getStatus($this->value);
    }
}
