<?php

namespace Aero\Commerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Brand extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'commerce_brands';

    protected $fillable = [
        'name', 'description', 'slug', 'logo', 'website',
        'is_active', 'sort_order', 'seo_title', 'seo_description', 'meta_keywords',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function getActiveProductsCountAttribute()
    {
        return $this->products()->where('status', Product::STATUS_ACTIVE)->count();
    }
}
