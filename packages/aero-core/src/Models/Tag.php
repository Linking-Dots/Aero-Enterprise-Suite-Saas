<?php

declare(strict_types=1);

namespace Aero\Core\Models;

use Aero\Contracts\Searchable;
use Aero\Kernel\Traits\Searchable as SearchableTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Tag Model
 *
 * Cross-module tag/label definitions scoped per tenant.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $name
 * @property string $slug
 * @property string $color
 * @property string|null $description
 * @property string|null $icon
 */
class Tag extends TenantModel implements Searchable
{
    use SearchableTrait;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'color',
        'description',
        'icon',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Tag $tag): void {
            if (empty($tag->slug) && ! empty($tag->name)) {
                $tag->slug = Str::slug($tag->name);
            }
            if (empty($tag->color)) {
                $tag->color = '#0ea5e9';
            }
        });
    }

    /**
     * Scope: filter by current tenant.
     */
    public function scopeForTenant(Builder $query, ?int $tenantId = null): Builder
    {
        $tenantId ??= tenant('id');

        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope: search by name or slug.
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $term).'%';

        return $query->where(function (Builder $q) use ($like): void {
            $q->where('name', 'LIKE', $like)
                ->orWhere('slug', 'LIKE', $like);
        });
    }

    /**
     * Rows in the `taggables` pivot for this tag (across all taggable types).
     * Used for `withCount('records')`. A morphedByMany to the abstract base Model
     * is not instantiable, so this maps to the pivot model instead.
     */
    public function records(): HasMany
    {
        return $this->hasMany(Taggable::class, 'tag_id');
    }

    // =====================================================================
    // Searchable Interface Implementation
    // =====================================================================

    public function getSearchableColumns(): array
    {
        return ['name', 'slug', 'description'];
    }

    public function getSearchResultTitle(): string
    {
        return $this->name;
    }

    public function getSearchResultUrl(): ?string
    {
        return route('core.tags.index');
    }

    public function getSearchResultType(): string
    {
        return 'Tag';
    }

    public function getSearchResultSubtitle(): ?string
    {
        return $this->description;
    }

    public function getSearchResultIcon(): ?string
    {
        return 'tag';
    }
}
