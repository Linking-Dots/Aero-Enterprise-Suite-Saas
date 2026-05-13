<?php

namespace Aero\Ims\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'ims_products';

    protected $fillable = [
        'sku',
        'name',
        'description',
        'category_id',
        'brand',
        'unit_price',
        'cost_price',
        'minimum_stock',
        'maximum_stock',
        'reorder_point',
        'unit_of_measure',
        'barcode',
        'weight',
        'dimensions',
        'status',
        'supplier_id',
        'notes',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'minimum_stock' => 'integer',
        'maximum_stock' => 'integer',
        'reorder_point' => 'integer',
        'weight' => 'decimal:2',
        'dimensions' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the category that owns the product
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the supplier that owns the product
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the stock entries for the product
     */
    public function stockEntries()
    {
        return $this->hasMany(StockEntry::class);
    }

    /**
     * Get the stock movements for the product
     */
    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Get current total stock across all warehouses
     */
    public function getCurrentStockAttribute()
    {
        return $this->stockEntries()->sum('quantity');
    }

    /**
     * Get total inventory value
     */
    public function getTotalValueAttribute()
    {
        return $this->current_stock * $this->unit_price;
    }

    /**
     * Check if product is low stock
     */
    public function getIsLowStockAttribute()
    {
        return $this->current_stock <= $this->minimum_stock;
    }

    /**
     * Check if product is out of stock
     */
    public function getIsOutOfStockAttribute()
    {
        return $this->current_stock <= 0;
    }

    /**
     * Scope for low stock products
     */
    public function scopeLowStock($query)
    {
        return $query->whereRaw('(SELECT SUM(quantity) FROM ims_stock_entries WHERE product_id = ims_products.id) <= minimum_stock');
    }

    /**
     * Scope for out of stock products
     */
    public function scopeOutOfStock($query)
    {
        return $query->whereRaw('(SELECT SUM(quantity) FROM ims_stock_entries WHERE product_id = ims_products.id) <= 0');
    }

    /**
     * Scope for active products
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
