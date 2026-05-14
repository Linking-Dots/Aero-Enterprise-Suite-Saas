<?php

namespace Aero\Manufacturing\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductCategory extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'manufacturing_product_categories';

    protected $fillable = [
        'name', 'description', 'parent_id', 'is_active',
    ];

    protected $casts = [
        'parent_id' => 'integer',
        'is_active' => 'boolean',
    ];

    public function parent()
    {
        return $this->belongsTo(ProductCategory::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(ProductCategory::class, 'parent_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }
}
