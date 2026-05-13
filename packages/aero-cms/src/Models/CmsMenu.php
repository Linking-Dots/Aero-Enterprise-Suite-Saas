<?php

declare(strict_types=1);

namespace Aero\Cms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CmsMenu extends TenantModel
{
    protected $connection = 'central';

    protected $table = 'cms_menus';

    protected $fillable = [
        'name',
        'slug',
        'location',
    ];

    /**
     * Get menu items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(CmsMenuItem::class, 'menu_id')
            ->whereNull('parent_id')
            ->orderBy('order');
    }

    /**
     * Get all menu items (includes nested).
     */
    public function allItems(): HasMany
    {
        return $this->hasMany(CmsMenuItem::class, 'menu_id')->orderBy('order');
    }
}
