<?php

namespace Aero\Dms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends TenantModel
{
    use HasFactory;

    protected $table = 'dms_categories';

    protected $fillable = [
        'name',
        'code',
        'description',
        'color',
        'icon',
        'allowed_file_types',
        'max_file_size',
        'retention_period',
        'requires_approval',
        'is_active',
        'sort_order',
        'parent_id',
    ];

    protected $casts = [
        'allowed_file_types' => 'array',
        'requires_approval' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the parent category.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Get the child categories.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Get all documents in this category.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'category_id');
    }

    /**
     * Get all templates in this category.
     */
    public function templates(): HasMany
    {
        return $this->hasMany(Template::class, 'category_id');
    }

    /**
     * Scope for active categories.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get category hierarchy as breadcrumb.
     */
    public function getBreadcrumbAttribute(): array
    {
        $breadcrumb = [];
        $category = $this;

        while ($category) {
            array_unshift($breadcrumb, [
                'id' => $category->id,
                'name' => $category->name,
                'code' => $category->code,
            ]);
            $category = $category->parent;
        }

        return $breadcrumb;
    }
}
