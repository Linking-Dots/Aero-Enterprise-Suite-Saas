<?php

namespace Aero\Pos\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'pos_products';

    protected $fillable = [
        'name',
        'description',
        'sku',
        'barcode',
        'price',
        'cost_price',
        'stock_quantity',
        'min_stock_level',
        'max_stock_level',
        'category_id',
        'image_url',
        'is_active',
        'tax_rate',
        'weight',
        'dimensions',
        'supplier_id',
        'brand',
        'model',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'min_stock_level' => 'integer',
        'max_stock_level' => 'integer',
        'is_active' => 'boolean',
        'tax_rate' => 'decimal:2',
        'weight' => 'decimal:2',
        'dimensions' => 'json',
    ];

    /**
     * Get the category that owns the product
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * Get the transaction items for the product
     */
    public function transactionItems()
    {
        return $this->hasMany(TransactionItem::class, 'product_id');
    }

    /**
     * Check if product is low stock
     */
    public function isLowStock()
    {
        return $this->stock_quantity <= $this->min_stock_level;
    }

    /**
     * Check if product is out of stock
     */
    public function isOutOfStock()
    {
        return $this->stock_quantity <= 0;
    }

    /**
     * Get profit margin
     */
    public function getProfitMargin()
    {
        if ($this->cost_price <= 0) {
            return 0;
        }

        return (($this->price - $this->cost_price) / $this->cost_price) * 100;
    }

    /**
     * Scope for active products
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for low stock products
     */
    public function scopeLowStock($query)
    {
        return $query->whereColumn('stock_quantity', '<=', 'min_stock_level');
    }

    /**
     * Scope for out of stock products
     */
    public function scopeOutOfStock($query)
    {
        return $query->where('stock_quantity', '<=', 0);
    }
}
