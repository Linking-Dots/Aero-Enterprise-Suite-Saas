<?php

declare(strict_types=1);

namespace Aero\Cms\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CmsCategory extends TenantModel
{
    use SoftDeletes;

    protected $connection = 'central';

    protected $table = 'cms_categories';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'parent_id',
        'icon',
        'color',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    /**
     * Get the parent category.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Get child categories.
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('order');
    }

    /**
     * Get pages in this category.
     */
    public function pages(): HasMany
    {
        return $this->hasMany(CmsPage::class, 'category_id')->orderBy('order');
    }
}
