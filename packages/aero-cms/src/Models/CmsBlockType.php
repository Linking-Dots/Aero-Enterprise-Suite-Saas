<?php

declare(strict_types=1);

namespace Aero\Cms\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class CmsBlockType extends TenantModel
{
    use HasFactory;

    protected $table = 'cms_block_types';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'schema_data',
        'category',
        'icon',
        'preview_image',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'schema_data' => 'json',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get all active block types.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Filter by category.
     */
    public function scopeCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Get block type by slug.
     */
    public function scopeBySlug(Builder $query, string $slug): Builder
    {
        return $query->where('slug', $slug);
    }

    /**
     * Get list of advanced block types.
     */
    public static function getAdvancedBlockTypes(): Collection
    {
        return self::active()
            ->category('advanced')
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Get all block types grouped by category.
     */
    public static function getGroupedByCategory(): Collection
    {
        return self::active()
            ->orderBy('category')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('category');
    }

    /**
     * Get schema for form field generation.
     */
    public function getSchemaFields(): array
    {
        return $this->schema_data ?? [];
    }

    /**
     * Get block type as array including rendered fields.
     */
    public function toBlockTypeArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'category' => $this->category,
            'icon' => $this->icon,
            'preview_image' => $this->preview_image,
            'schema' => $this->schema_data,
            'isActive' => $this->is_active,
            'sortOrder' => $this->sort_order,
        ];
    }
}

