<?php

declare(strict_types=1);

namespace Aero\Cms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CmsMenuItem extends TenantModel
{
    protected $connection = 'central';

    protected $table = 'cms_menu_items';

    protected $fillable = [
        'menu_id',
        'page_id',
        'label',
        'url',
        'parent_id',
        'order',
        'icon',
        'is_visible',
        'metadata',
    ];

    protected $casts = [
        'order' => 'integer',
        'is_visible' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Get the menu.
     */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(CmsMenu::class, 'menu_id');
    }

    /**
     * Get the page.
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(CmsPage::class, 'page_id');
    }

    /**
     * Get parent menu item.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Get child menu items.
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('order');
    }

    /**
     * Get the URL for this menu item.
     */
    public function getUrlAttribute(): string
    {
        if ($this->url) {
            return $this->url;
        }

        return $this->page?->url ?? '#';
    }
}
