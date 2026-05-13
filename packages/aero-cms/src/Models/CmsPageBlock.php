<?php

declare(strict_types=1);

namespace Aero\Cms\Models;

use Aero\Cms\Database\Factories\CmsPageBlockFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CmsPageBlock extends TenantModel
{
    use HasFactory;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): CmsPageBlockFactory
    {
        return CmsPageBlockFactory::new();
    }

    /**
     * CMS blocks are stored in the central (landlord) database.
     */
    protected $connection = 'central';

    protected $table = 'cms_page_blocks';

    protected $fillable = [
        'page_id',
        'parent_block_id',
        'type',
        'data',
        'order_index',
        'is_visible',
        'conditions',
        'variant',
        'dependencies',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'data' => 'array',
        'conditions' => 'array',
        'dependencies' => 'array',
        'metadata' => 'array',
        'is_visible' => 'boolean',
    ];

    protected $attributes = [
        'data' => '{}',
        'conditions' => '{}',
        'dependencies' => '{}',
        'metadata' => '{}',
        'is_visible' => true,
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $block) {
            // Generate unique block ID if not set
            if (empty($block->block_id)) {
                $block->block_id = Str::uuid()->toString();
            }

            // Auto-set order index if not provided
            if ($block->order_index === 0 || $block->order_index === null) {
                $maxOrder = static::where('page_id', $block->page_id)->max('order_index') ?? -1;
                $block->order_index = $maxOrder + 1;
            }
        });
    }

    /**
     * Get the page this block belongs to.
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(CmsPage::class, 'page_id');
    }

    /**
     * Get the block configuration from cms.php config.
     */
    public function getBlockConfig(): ?array
    {
        return config("cms.blocks.{$this->block_type}");
    }

    /**
     * Get a specific content field.
     */
    public function getContentField(string $field, mixed $default = null): mixed
    {
        return data_get($this->content, $field, $default);
    }

    /**
     * Set a specific content field.
     */
    public function setContentField(string $field, mixed $value): self
    {
        $content = $this->content ?? [];
        data_set($content, $field, $value);
        $this->content = $content;

        return $this;
    }

    /**
     * Get a specific setting.
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * Set a specific setting.
     */
    public function setSetting(string $key, mixed $value): self
    {
        $settings = $this->settings ?? [];
        data_set($settings, $key, $value);
        $this->settings = $settings;

        return $this;
    }

    /**
     * Check if block should be visible based on visibility rules.
     */
    public function isVisible(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $visibility = $this->visibility ?? [];

        // Check scheduled visibility
        if (isset($visibility['start_date'])) {
            $startDate = \Carbon\Carbon::parse($visibility['start_date']);
            if (now()->lt($startDate)) {
                return false;
            }
        }

        if (isset($visibility['end_date'])) {
            $endDate = \Carbon\Carbon::parse($visibility['end_date']);
            if (now()->gt($endDate)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Duplicate this block.
     */
    public function duplicate(?int $targetPageId = null): self
    {
        $newBlock = $this->replicate();
        $newBlock->page_id = $targetPageId ?? $this->page_id;
        $newBlock->block_id = null; // Will be regenerated
        $newBlock->order_index = 0; // Will be recalculated
        $newBlock->save();

        return $newBlock;
    }

    /**
     * Move block to a new position.
     */
    public function moveTo(int $newPosition): void
    {
        $currentPosition = $this->order_index;

        if ($newPosition === $currentPosition) {
            return;
        }

        if ($newPosition > $currentPosition) {
            // Moving down: shift blocks between current and new position up
            static::where('page_id', $this->page_id)
                ->where('order_index', '>', $currentPosition)
                ->where('order_index', '<=', $newPosition)
                ->decrement('order_index');
        } else {
            // Moving up: shift blocks between new and current position down
            static::where('page_id', $this->page_id)
                ->where('order_index', '>=', $newPosition)
                ->where('order_index', '<', $currentPosition)
                ->increment('order_index');
        }

        $this->order_index = $newPosition;
        $this->save();
    }

    /**
     * Get all versions of this block.
     */
    public function versions()
    {
        return $this->hasMany(CmsBlockVersion::class, 'cms_page_block_id');
    }

    /**
     * Get current publishing record.
     */
    public function currentPublish()
    {
        return $this->hasOne(CmsBlockPublish::class, 'cms_page_block_id')
            ->latest('created_at');
    }

    /**
     * Get all publishing records.
     */
    public function publishes()
    {
        return $this->hasMany(CmsBlockPublish::class, 'cms_page_block_id');
    }

    /**
     * Get revision history.
     */
    public function revisions()
    {
        return $this->hasMany(CmsBlockRevision::class, 'cms_page_block_id')
            ->orderByDesc('created_at');
    }

    /**
     * Get the latest published version.
     */
    public function getLatestPublishedVersion(): ?CmsBlockVersion
    {
        return $this->versions()
            ->orderByDesc('version_number')
            ->first();
    }

    /**
     * Check if block is published and visible.
     */
    public function isPublished(): bool
    {
        $publish = $this->currentPublish()->first();
        return $publish && $publish->isActive();
    }

    /**
     * Get publishing status.
     */
    public function getPublishingStatus(): string
    {
        $publish = $this->currentPublish()->first();
        return $publish?->status ?? 'unpublished';
    }
}
