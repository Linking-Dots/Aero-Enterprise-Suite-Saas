<?php

namespace Aero\Commerce\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'commerce_products';

    protected $fillable = [
        'sku', 'name', 'description', 'short_description', 'product_type',
        'price', 'sale_price', 'cost_price', 'currency', 'weight', 'dimensions',
        'category_id', 'brand_id', 'vendor_id', 'stock_quantity', 'min_stock_level',
        'manage_stock', 'stock_status', 'backorders', 'featured', 'status',
        'seo_title', 'seo_description', 'meta_keywords', 'slug', 'created_by',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'weight' => 'decimal:3',
        'dimensions' => 'json',
        'category_id' => 'integer',
        'brand_id' => 'integer',
        'vendor_id' => 'integer',
        'stock_quantity' => 'integer',
        'min_stock_level' => 'integer',
        'manage_stock' => 'boolean',
        'backorders' => 'boolean',
        'featured' => 'boolean',
        'created_by' => 'integer',
    ];

    const TYPE_SIMPLE = 'simple';

    const TYPE_CONFIGURABLE = 'configurable';

    const TYPE_GROUPED = 'grouped';

    const TYPE_BUNDLE = 'bundle';

    const TYPE_VIRTUAL = 'virtual';

    const TYPE_DOWNLOADABLE = 'downloadable';

    const STATUS_ACTIVE = 'active';

    const STATUS_INACTIVE = 'inactive';

    const STATUS_DRAFT = 'draft';

    const STATUS_PENDING = 'pending';

    const STOCK_IN_STOCK = 'in_stock';

    const STOCK_OUT_OF_STOCK = 'out_of_stock';

    const STOCK_ON_BACKORDER = 'on_backorder';

    public function category()
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function attributes()
    {
        return $this->belongsToMany(ProductAttribute::class, 'commerce_product_attribute_values')
            ->withPivot('value', 'price_modifier');
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function reviews()
    {
        return $this->hasMany(ProductReview::class);
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getEffectivePriceAttribute()
    {
        return $this->sale_price ?: $this->price;
    }

    public function getDiscountPercentageAttribute()
    {
        if ($this->sale_price && $this->price > 0) {
            return (($this->price - $this->sale_price) / $this->price) * 100;
        }

        return 0;
    }

    public function isInStock()
    {
        if (! $this->manage_stock) {
            return true;
        }

        return $this->stock_quantity > 0 || ($this->stock_quantity <= 0 && $this->backorders);
    }

    public function getAverageRatingAttribute()
    {
        return $this->reviews()->avg('rating') ?: 0;
    }
}
