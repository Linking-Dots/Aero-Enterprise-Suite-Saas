<?php

namespace Aero\FieldService\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServicePart extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'field_service_parts';

    protected $fillable = [
        'part_number', 'part_name', 'description', 'category', 'manufacturer',
        'unit_cost', 'selling_price', 'unit_of_measure', 'stock_quantity',
        'minimum_stock_level', 'maximum_stock_level', 'location', 'status',
        'weight', 'dimensions', 'warranty_period_months',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'minimum_stock_level' => 'integer',
        'maximum_stock_level' => 'integer',
        'weight' => 'decimal:2',
        'dimensions' => 'json',
        'warranty_period_months' => 'integer',
    ];

    const STATUS_ACTIVE = 'active';

    const STATUS_DISCONTINUED = 'discontinued';

    const STATUS_OUT_OF_STOCK = 'out_of_stock';

    const UNIT_EACH = 'each';

    const UNIT_FOOT = 'foot';

    const UNIT_METER = 'meter';

    const UNIT_POUND = 'pound';

    const UNIT_KILOGRAM = 'kilogram';

    public function workOrderParts()
    {
        return $this->hasMany(ServiceWorkOrderPart::class);
    }

    public function equipment()
    {
        return $this->belongsToMany(Equipment::class, 'field_service_equipment_parts')
            ->withPivot('quantity', 'installed_date', 'warranty_end_date');
    }

    public function suppliers()
    {
        return $this->belongsToMany(ServiceSupplier::class, 'field_service_part_suppliers')
            ->withPivot('supplier_part_number', 'cost', 'lead_time_days');
    }

    public function isLowStock()
    {
        return $this->stock_quantity <= $this->minimum_stock_level;
    }

    public function isOutOfStock()
    {
        return $this->stock_quantity <= 0;
    }

    public function getMarginAttribute()
    {
        if ($this->unit_cost > 0) {
            return (($this->selling_price - $this->unit_cost) / $this->unit_cost) * 100;
        }

        return 0;
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeLowStock($query)
    {
        return $query->whereRaw('stock_quantity <= minimum_stock_level');
    }
}
