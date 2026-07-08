<?php

namespace Aero\Platform\Models;

use Aero\HRMAC\Models\Module;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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

    /**
     * The technical modules this product grants. A product bundles one-or-more
     * modules via the product_modules pivot (module_code → modules.code). This is
     * the canonical "what does buying this unlock" relationship; the legacy scalar
     * module_code is retained only until the Phase 4 cleanup.
     */
    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(
            Module::class,
            'product_modules',
            'product_id',
            'module_code',
            'id',
            'code',
        )->withTimestamps();
    }

    /** The module codes this product grants (bundle-aware). */
    public function moduleCodes(): array
    {
        return $this->modules()->pluck('modules.code')->all();
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
