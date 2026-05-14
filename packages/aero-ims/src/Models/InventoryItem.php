<?php

namespace Aero\Ims\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryItem extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'sku',
        'description',
        'category_id',
        'unit_id',
        'cost_price',
        'selling_price',
        'reorder_level',
        'current_stock',
        'image',
        'barcode',
        'status',
        'location_id',
        'is_serialized',
        'tax_rate',
        'manufacturer_id',
        'attributes', // JSON format for dynamic attributes
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'current_stock' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'attributes' => 'array',
        'is_serialized' => 'boolean',
    ];

    // Relationships
    public function category()
    {
        return $this->belongsTo(InventoryCategory::class, 'category_id');
    }

    public function unit()
    {
        return $this->belongsTo(InventoryUnit::class, 'unit_id');
    }

    public function location()
    {
        return $this->belongsTo(InventoryLocation::class, 'location_id');
    }

    public function manufacturer()
    {
        return $this->belongsTo(Manufacturer::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }
}
