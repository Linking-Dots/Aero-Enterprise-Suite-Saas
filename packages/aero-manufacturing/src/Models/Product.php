<?php

namespace Aero\Manufacturing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'manufacturing_products';

    protected $fillable = [
        'sku', 'name', 'description', 'product_type', 'make_or_buy',
        'standard_cost', 'lead_time_days', 'safety_stock', 'reorder_point',
        'unit_of_measure', 'category_id', 'is_active', 'drawing_number',
        'revision', 'weight', 'dimensions',
    ];

    protected $casts = [
        'standard_cost' => 'decimal:4',
        'lead_time_days' => 'integer',
        'safety_stock' => 'decimal:3',
        'reorder_point' => 'decimal:3',
        'category_id' => 'integer',
        'is_active' => 'boolean',
        'weight' => 'decimal:3',
        'dimensions' => 'json',
    ];

    const TYPE_RAW_MATERIAL = 'raw_material';

    const TYPE_COMPONENT = 'component';

    const TYPE_SUBASSEMBLY = 'subassembly';

    const TYPE_FINISHED_GOOD = 'finished_good';

    const TYPE_SERVICE = 'service';

    const MAKE_OR_BUY_MAKE = 'make';

    const MAKE_OR_BUY_BUY = 'buy';

    const MAKE_OR_BUY_BOTH = 'both';

    public function category()
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function billsOfMaterials()
    {
        return $this->hasMany(BillOfMaterials::class);
    }

    public function routes()
    {
        return $this->hasMany(Route::class);
    }

    public function workOrders()
    {
        return $this->hasMany(WorkOrder::class);
    }

    public function bomItems()
    {
        return $this->hasMany(BillOfMaterialsItem::class, 'component_id');
    }

    public function getActiveBomAttribute()
    {
        return $this->billsOfMaterials()->where('status', BillOfMaterials::STATUS_ACTIVE)->first();
    }

    public function getActiveRouteAttribute()
    {
        return $this->routes()->where('status', Route::STATUS_ACTIVE)->first();
    }
}
