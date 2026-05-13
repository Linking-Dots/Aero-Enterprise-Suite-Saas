<?php

declare(strict_types=1);

namespace Aero\Cms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CmsBlockTemplate extends TenantModel
{
    use HasFactory;

    /**
     * Block templates are stored in the central (landlord) database.
     */
    protected $connection = 'central';

    protected $table = 'cms_block_templates';

    protected $fillable = [
        'name',
        'block_type',
        'description',
        'content',
        'settings',
        'thumbnail',
        'category',
        'is_global',
        'is_system',
        'created_by',
    ];

    protected $casts = [
        'content' => 'array',
        'settings' => 'array',
        'is_global' => 'boolean',
        'is_system' => 'boolean',
    ];

    protected $attributes = [
        'content' => '{}',
        'settings' => '{}',
        'is_global' => false,
        'is_system' => false,
    ];

    /**
     * Scope for global blocks.
     */
    public function scopeGlobal($query)
    {
        return $query->where('is_global', true);
    }

    /**
     * Scope for a specific block type.
     */
    public function scopeOfType($query, string $blockType)
    {
        return $query->where('block_type', $blockType);
    }

    /**
     * Scope for a specific category.
     */
    public function scopeInCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for non-system templates (user-created).
     */
    public function scopeUserCreated($query)
    {
        return $query->where('is_system', false);
    }

    /**
     * Create a block from this template.
     */
    public function createBlock(int $pageId, ?int $orderIndex = null): CmsPageBlock
    {
        return CmsPageBlock::create([
            'page_id' => $pageId,
            'block_type' => $this->block_type,
            'order_index' => $orderIndex ?? 0,
            'content' => $this->content,
            'settings' => $this->settings,
        ]);
    }

    /**
     * Get the block configuration from cms.php config.
     */
    public function getBlockConfig(): ?array
    {
        return config("cms.blocks.{$this->block_type}");
    }

    /**
     * Create a template from an existing block.
     */
    public static function createFromBlock(CmsPageBlock $block, string $name, ?string $description = null): self
    {
        return static::create([
            'name' => $name,
            'block_type' => $block->block_type,
            'description' => $description,
            'content' => $block->content,
            'settings' => $block->settings,
            'created_by' => auth()->id(),
        ]);
    }
}
