<?php

namespace Aero\Commerce\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductCategory extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'commerce_product_categories';

    protected $fillable = [
        'name', 'description', 'slug', 'parent_id', 'sort_order',
        'image', 'is_active', 'seo_title', 'seo_description', 'meta_keywords',
    ];

    protected $casts = [
        'parent_id' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function parent()
    {
        return $this->belongsTo(ProductCategory::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(ProductCategory::class, 'parent_id')->orderBy('sort_order');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    public function getFullPathAttribute()
    {
        $path = collect([$this->name]);
        $parent = $this->parent;

        while ($parent) {
            $path->prepend($parent->name);
            $parent = $parent->parent;
        }

        return $path->implode(' > ');
    }

    public function getActiveProductsCountAttribute()
    {
        return $this->products()->where('status', Product::STATUS_ACTIVE)->count();
    }
}
