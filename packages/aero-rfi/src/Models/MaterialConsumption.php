<?php

namespace Aero\Rfi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaterialConsumption extends TenantModel
{
    use SoftDeletes;

    protected $fillable = [
        'daily_work_id',
        'work_layer_id',
        'material_name',
        'material_code',
        'specification',
        'unit',
        'quantity_used',
        'unit_cost',
        'start_chainage_m',
        'end_chainage_m',
        'supplier_name',
        'batch_number',
        'delivery_date',
        'test_certificate_ref',
        'quality_test_results',
        'quality_status',
        'wastage_quantity',
        'wastage_reason',
        'remarks',
    ];

    protected $casts = [
        'quantity_used' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'start_chainage_m' => 'decimal:3',
        'end_chainage_m' => 'decimal:3',
        'wastage_quantity' => 'decimal:3',
        'quality_test_results' => 'array',
        'delivery_date' => 'date',
    ];

    public function rfi(): BelongsTo
    {
        return $this->belongsTo(Rfi::class);
    }

    public function workLayer(): BelongsTo
    {
        return $this->belongsTo(WorkLayer::class);
    }

    /**
     * Get total cost for this material consumption.
     */
    public function getTotalCostAttribute(): float
    {
        return (float) $this->quantity_used * (float) $this->unit_cost;
    }

    /**
     * Get wastage percentage.
     */
    public function getWastagePercentageAttribute(): float
    {
        if ($this->quantity_used == 0) {
            return 0;
        }

        return ($this->wastage_quantity / $this->quantity_used) * 100;
    }
}
