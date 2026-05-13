<?php

declare(strict_types=1);

namespace Aero\Platform\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Platform Page Model
 *
 * Manages SEO settings for platform pages (landing, pricing, features, etc.).
 */
class PlatformPage extends CentralModel implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;
    use SoftDeletes;

    protected $connection = 'central';

    public const TYPE_LANDING = 'landing';

    public const TYPE_FEATURES = 'features';

    public const TYPE_PRICING = 'pricing';

    public const TYPE_ABOUT = 'about';

    public const TYPE_CONTACT = 'contact';

    public const TYPE_CUSTOM = 'custom';

    public const MEDIA_OG_IMAGE = 'og_image';

    public const MEDIA_TWITTER_IMAGE = 'twitter_image';

    protected $fillable = [
        'slug',
        'title',
        'page_type',
        'is_active',
        'is_system',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'canonical_url',
        'og_title',
        'og_description',
        'og_image',
        'og_type',
        'twitter_card',
        'twitter_title',
        'twitter_description',
        'twitter_image',
        'schema_markup',
        'robots',
        'sections',
        'metadata',
        'priority',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'schema_markup' => 'array',
        'sections' => 'array',
        'metadata' => 'array',
        'priority' => 'integer',
    ];

    protected $attributes = [
        'page_type' => self::TYPE_CUSTOM,
        'is_active' => true,
        'is_system' => false,
        'og_type' => 'website',
        'twitter_card' => 'summary_large_image',
        'robots' => 'index, follow',
        'priority' => 0,
        'sections' => '[]',
        'metadata' => '[]',
    ];

    /**
     * Scope for active pages.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by page type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('page_type', $type);
    }

    /**
     * Get SEO meta tags array for Inertia/React.
     */
    public function getSeoMeta(PlatformSetting $settings): array
    {
        $siteName = $settings->site_name ?? config('app.name');
        $defaultImage = $settings->getBrandingPayload()['social'] ?? null;

        return [
            'title' => $this->meta_title ?? $this->title,
            'description' => $this->meta_description,
            'keywords' => $this->meta_keywords,
            'canonical' => $this->canonical_url ?? url($this->slug),
            'robots' => $this->robots,
            'og' => [
                'title' => $this->og_title ?? $this->meta_title ?? $this->title,
                'description' => $this->og_description ?? $this->meta_description,
                'image' => $this->getFirstMediaUrl(self::MEDIA_OG_IMAGE) ?: $this->og_image ?: $defaultImage,
                'type' => $this->og_type,
                'site_name' => $siteName,
                'url' => url($this->slug),
            ],
            'twitter' => [
                'card' => $this->twitter_card,
                'title' => $this->twitter_title ?? $this->og_title ?? $this->meta_title ?? $this->title,
                'description' => $this->twitter_description ?? $this->og_description ?? $this->meta_description,
                'image' => $this->getFirstMediaUrl(self::MEDIA_TWITTER_IMAGE) ?: $this->twitter_image ?: $this->getFirstMediaUrl(self::MEDIA_OG_IMAGE) ?: $this->og_image ?: $defaultImage,
            ],
            'schema' => $this->schema_markup,
        ];
    }

    /**
     * Generate schema.org structured data for the page.
     */
    public function generateSchemaMarkup(PlatformSetting $settings): array
    {
        $siteName = $settings->site_name ?? config('app.name');
        $siteUrl = config('app.url');

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $this->meta_title ?? $this->title,
            'description' => $this->meta_description,
            'url' => url($this->slug),
            'isPartOf' => [
                '@type' => 'WebSite',
                'name' => $siteName,
                'url' => $siteUrl,
            ],
        ];

        // Add specific schema based on page type
        if ($this->page_type === self::TYPE_PRICING) {
            $schema['@type'] = 'WebPage';
            $schema['about'] = [
                '@type' => 'Product',
                'name' => $siteName,
            ];
        } elseif ($this->page_type === self::TYPE_ABOUT) {
            $schema['@type'] = 'AboutPage';
        } elseif ($this->page_type === self::TYPE_CONTACT) {
            $schema['@type'] = 'ContactPage';
        }

        return $schema;
    }

    /**
     * Register media collections.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA_OG_IMAGE)->singleFile();
        $this->addMediaCollection(self::MEDIA_TWITTER_IMAGE)->singleFile();
    }

    /**
     * Find by slug.
     */
    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->active()->first();
    }

    /**
     * Get or create default page for a type.
     */
    public static function getOrCreateDefault(string $type, string $title): self
    {
        $slug = match ($type) {
            self::TYPE_LANDING => '/',
            self::TYPE_PRICING => 'pricing',
            self::TYPE_FEATURES => 'features',
            self::TYPE_ABOUT => 'about',
            self::TYPE_CONTACT => 'contact',
            default => Str::slug($title),
        };

        return static::firstOrCreate(
            ['slug' => $slug, 'page_type' => $type],
            [
                'title' => $title,
                'is_system' => in_array($type, [self::TYPE_LANDING, self::TYPE_PRICING]),
            ]
        );
    }

    /**
     * Get page type options.
     */
    public static function getPageTypeOptions(): array
    {
        return [
            self::TYPE_LANDING => 'Landing Page',
            self::TYPE_FEATURES => 'Features Page',
            self::TYPE_PRICING => 'Pricing Page',
            self::TYPE_ABOUT => 'About Page',
            self::TYPE_CONTACT => 'Contact Page',
            self::TYPE_CUSTOM => 'Custom Page',
        ];
    }

    /**
     * Get robots options.
     */
    public static function getRobotsOptions(): array
    {
        return [
            'index, follow' => 'Index, Follow (Default)',
            'noindex, follow' => 'No Index, Follow',
            'index, nofollow' => 'Index, No Follow',
            'noindex, nofollow' => 'No Index, No Follow',
        ];
    }
}
