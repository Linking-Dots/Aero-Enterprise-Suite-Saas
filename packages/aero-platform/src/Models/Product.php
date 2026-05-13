<?php

namespace Aero\Platform\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends CentralModel
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'code', 'module_code', 'name', 'description', 'icon',
        'monthly_price', 'yearly_price', 'currency',
        'is_active', 'is_marketplace_visible', 'sort_order', 'version', 'metadata',
    ];

    protected $casts = [
        'monthly_price'          => 'decimal:2',
        'yearly_price'           => 'decimal:2',
        'is_active'              => 'boolean',
        'is_marketplace_visible' => 'boolean',
        'metadata'               => 'array',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(ProductSubscription::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeMarketplaceVisible($query)
    {
        return $query->where('is_marketplace_visible', true)->where('is_active', true);
    }
}
