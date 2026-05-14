<?php

declare(strict_types=1);

namespace Aero\Cms\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Cms\Database\Factories\CmsPageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CmsPage extends TenantModel
{
    use HasFactory;
    use SoftDeletes;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): CmsPageFactory
    {
        return CmsPageFactory::new();
    }

    /**
     * CMS pages are stored in the central (landlord) database.
     */
    protected $connection = 'central';

    protected $table = 'cms_pages';

    protected $fillable = [
        'slug',
        'title',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'canonical_url',
        'og_image',
        'status',
        'published_at',
        'scheduled_at',
        'layout',
        'settings',
        'created_by',
        'updated_by',
        'parent_id',
        'category_id',
        'template_id',
        'order',
        'show_in_nav',
        'nav_label',
        'is_homepage',
        'view_count',
        'seo_index',
        'language',
        'translation_key',
    ];

    protected $casts = [
        'settings' => 'array',
        'published_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'show_in_nav' => 'boolean',
        'is_homepage' => 'boolean',
        'seo_index' => 'boolean',
        'view_count' => 'integer',
    ];

    protected $attributes = [
        'status' => 'draft',
        'layout' => 'public',
        'settings' => '{}',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $page) {
            // Auto-generate slug from title if not provided
            if (empty($page->slug)) {
                $page->slug = Str::slug($page->title);
            }

            // Ensure slug is unique
            $originalSlug = $page->slug;
            $counter = 1;
            while (static::where('slug', $page->slug)->exists()) {
                $page->slug = $originalSlug.'-'.$counter++;
            }
        });

        static::saving(function (self $page) {
            // If setting as homepage, unset other homepages
            if ($page->is_homepage && $page->isDirty('is_homepage')) {
                static::where('id', '!=', $page->id)
                    ->where('is_homepage', true)
                    ->update(['is_homepage' => false]);
            }
        });
    }

    /**
     * Get the blocks for this page.
     */
    public function blocks(): HasMany
    {
        return $this->hasMany(CmsPageBlock::class, 'page_id')
            ->orderBy('order_index');
    }

    /**
     * Get active blocks only.
     */
    public function activeBlocks(): HasMany
    {
        return $this->blocks()->where('is_active', true);
    }

    /**
     * Get the versions for this page.
     */
    public function versions(): HasMany
    {
        return $this->hasMany(CmsPageVersion::class, 'page_id')
            ->orderByDesc('version_number');
    }

    /**
     * Get the parent page.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Get child pages.
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')
            ->orderBy('order');
    }

    /**
     * Get the category.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(CmsCategory::class, 'category_id');
    }

    /**
     * Get the template.
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(CmsTemplate::class, 'template_id');
    }

    /**
     * Get menu items for this page.
     */
    public function menuItems()
    {
        return $this->hasMany(CmsMenuItem::class, 'page_id');
    }

    /**
     * Check if page is published.
     */
    public function isPublished(): bool
    {
        return $this->status === 'published' &&
            ($this->published_at === null || $this->published_at->isPast());
    }

    /**
     * Publish the page.
     */
    public function publish(): bool
    {
        return $this->update([
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    /**
     * Unpublish the page.
     */
    public function unpublish(): bool
    {
        return $this->update([
            'status' => 'draft',
        ]);
    }

    /**
     * Create a new version of this page.
     */
    public function createVersion(?string $changeSummary = null): CmsPageVersion
    {
        $latestVersion = $this->versions()->max('version_number') ?? 0;

        return $this->versions()->create([
            'version_number' => $latestVersion + 1,
            'blocks' => $this->blocks->map(fn ($block) => [
                'block_type' => $block->block_type,
                'block_id' => $block->block_id,
                'order_index' => $block->order_index,
                'content' => $block->content,
                'settings' => $block->settings,
                'visibility' => $block->visibility,
                'is_active' => $block->is_active,
            ])->toArray(),
            'settings' => [
                'meta_title' => $this->meta_title,
                'meta_description' => $this->meta_description,
                'layout' => $this->layout,
                'settings' => $this->settings,
            ],
            'change_summary' => $changeSummary,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Scope for published pages.
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    /**
     * Scope for navigation pages.
     */
    public function scopeInNavigation($query)
    {
        return $query->where('show_in_nav', true)->orderBy('order');
    }

    /**
     * Get the URL for this page.
     */
    public function getUrlAttribute(): string
    {
        if ($this->is_homepage) {
            return '/';
        }

        return '/'.$this->slug;
    }

    /**
     * Get the full URL path including parent slugs.
     */
    public function getFullPathAttribute(): string
    {
        if ($this->parent) {
            return $this->parent->full_path.'/'.$this->slug;
        }

        return $this->slug;
    }

    /**
     * Scope for publicly visible pages (published and scheduled).
     */
    public function scopePublic($query)
    {
        return $query->where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('scheduled_at')
                    ->orWhere('scheduled_at', '<=', now());
            });
    }

    /**
     * Scope for indexable pages (SEO).
     */
    public function scopeIndexable($query)
    {
        return $query->public()->where('allow_indexing', true);
    }

    /**
     * Scope for pages in a specific category.
     */
    public function scopeInCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Increment view count.
     */
    public function recordView(): void
    {
        $this->increment('view_count');
    }

    /**
     * Check if page is currently published and visible.
     */
    public function isPubliclyVisible(): bool
    {
        return $this->status === 'published'
            && (!$this->scheduled_at || $this->scheduled_at <= now());
    }

    /**
     * Check if page should be indexed by search engines.
     */
    public function isIndexable(): bool
    {
        return $this->isPubliclyVisible() && $this->allow_indexing;
    }

    /**
     * Filter pages by language/locale.
     */
    public function scopeLanguage(Builder $query, ?string $language = null): Builder
    {
        $language = $language ?? app()->getLocale();
        return $query->where('language', $language);
    }

    /**
     * Get alternative language versions of the same page (by translation_key).
     */
    public function scopeTranslation(Builder $query, ?string $translationKey = null): Builder
    {
        $translationKey = $translationKey ?? $this->translation_key;
        return $query->where('translation_key', $translationKey);
    }

    /**
     * Get pages for a specific locale with fallback to default.
     */
    public function scopeForLocale(Builder $query, ?string $locale = null): Builder
    {
        $locale = $locale ?? app()->getLocale();
        return $query->where('language', $locale);
    }

    /**
     * Get translations for this page across all languages.
     */
    public function translations(): Collection
    {
        return static::where('translation_key', $this->translation_key)
            ->where('id', '!=', $this->id)
            ->get();
    }

    /**
     * Get translation for specific language.
     */
    public function getTranslation(string $language): ?self
    {
        return static::where('translation_key', $this->translation_key)
            ->where('language', $language)
            ->first();
    }
}
