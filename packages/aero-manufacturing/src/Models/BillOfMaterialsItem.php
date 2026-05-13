<?php

namespace Aero\Manufacturing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillOfMaterialsItem extends TenantModel
{
    use HasFactory;

    protected $table = 'manufacturing_bom_items';

    protected $fillable = [
        'bom_id', 'component_id', 'quantity', 'unit_of_measure',
        'unit_cost', 'scrap_factor', 'operation_sequence', 'notes',
        'is_critical', 'lead_time_days',
    ];

    protected $casts = [
        'bom_id' => 'integer',
        'component_id' => 'integer',
        'quantity' => 'decimal:6',
        'unit_cost' => 'decimal:4',
        'scrap_factor' => 'decimal:4',
        'operation_sequence' => 'integer',
        'is_critical' => 'boolean',
        'lead_time_days' => 'integer',
    ];

    public function billOfMaterials()
    {
        return $this->belongsTo(BillOfMaterials::class, 'bom_id');
    }

    public function component()
    {
        return $this->belongsTo(Product::class, 'component_id');
    }

    public function getExtendedCostAttribute()
    {
        return $this->quantity * $this->unit_cost;
    }

    public function getNetQuantityAttribute()
    {
        return $this->quantity * (1 + $this->scrap_factor);
    }
}
