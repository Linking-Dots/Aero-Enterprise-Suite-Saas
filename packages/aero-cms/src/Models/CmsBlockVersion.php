<?php

namespace Aero\Cms\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CmsBlockVersion extends TenantModel
{
    use HasFactory;

    protected $table = 'cms_block_versions';

    protected $fillable = [
        'cms_page_block_id',
        'version_number',
        'version_label',
        'block_data',
        'metadata',
        'change_summary',
        'change_description',
        'created_by_user_id',
        'edited_by_user_id',
    ];

    protected $casts = [
        'block_data' => 'json',
        'metadata' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationship: Block that this is a version of
     */
    public function block(): BelongsTo
    {
        return $this->belongsTo(CmsPageBlock::class, 'cms_page_block_id');
    }

    /**
     * Relationship: Publishing records using this version
     */
    public function publishes(): HasMany
    {
        return $this->hasMany(CmsBlockPublish::class, 'cms_block_version_id');
    }

    /**
     * Relationship: Revisions for this version
     */
    public function revisions(): HasMany
    {
        return $this->hasMany(CmsBlockRevision::class, 'cms_block_version_id');
    }

    /**
     * Get the next version number for a block
     */
    public static function getNextVersionNumber(int $blockId): int
    {
        return (self::where('cms_page_block_id', $blockId)
            ->max('version_number') ?? 0) + 1;
    }

    /**
     * Create a new version from current block data
     */
    public static function createFromBlock(
        CmsPageBlock $block,
        string $changeSummary = null,
        string $changeDescription = null,
        string $createdByUserId = null
    ): self {
        $version = new static([
            'cms_page_block_id' => $block->id,
            'version_number' => self::getNextVersionNumber($block->id),
            'block_data' => $block->block_data ?? [],
            'metadata' => $block->metadata ?? [],
            'change_summary' => $changeSummary ?? 'Initial version',
            'change_description' => $changeDescription,
            'created_by_user_id' => $createdByUserId,
        ]);

        if ($changeSummary) {
            $version->version_label = "v{$version->version_number} - {$changeSummary}";
        }

        $version->save();
        return $version;
    }

    /**
     * Restore this version to the block
     */
    public function restoreToBlock(): CmsPageBlock
    {
        $this->block->update([
            'block_data' => $this->block_data,
            'metadata' => $this->metadata,
        ]);

        return $this->block;
    }

    /**
     * Check if this is the latest version
     */
    public function isLatestVersion(): bool
    {
        $latestVersion = self::where('cms_page_block_id', $this->cms_page_block_id)
            ->orderByDesc('version_number')
            ->first();

        return $latestVersion?->id === $this->id;
    }

    /**
     * Get the previous version
     */
    public function getPreviousVersion(): ?self
    {
        return self::where('cms_page_block_id', $this->cms_page_block_id)
            ->where('version_number', '<', $this->version_number)
            ->orderByDesc('version_number')
            ->first();
    }

    /**
     * Compare with another version
     */
    public function diffWith(CmsBlockVersion $other): array
    {
        return [
            'current_version' => $this->version_number,
            'compared_version' => $other->version_number,
            'block_data_diff' => $this->calculateDiff($this->block_data ?? [], $other->block_data ?? []),
            'metadata_diff' => $this->calculateDiff($this->metadata ?? [], $other->metadata ?? []),
        ];
    }

    /**
     * Calculate diff between two arrays
     */
    private function calculateDiff(array $current, array $previous): array
    {
        $added = array_diff_key($current, $previous);
        $removed = array_diff_key($previous, $current);
        $modified = [];

        foreach ($current as $key => $value) {
            if (isset($previous[$key]) && $previous[$key] !== $value) {
                $modified[$key] = [
                    'old' => $previous[$key],
                    'new' => $value,
                ];
            }
        }

        return [
            'added' => $added,
            'removed' => $removed,
            'modified' => $modified,
        ];
    }

    /**
     * Scope: Get versions for a specific block
     */
    public function scopeForBlock($query, int $blockId)
    {
        return $query->where('cms_page_block_id', $blockId)->orderByDesc('version_number');
    }

    /**
     * Scope: Get versions created after date
     */
    public function scopeCreatedAfter($query, \DateTime $date)
    {
        return $query->whereDate('created_at', '>=', $date->format('Y-m-d'));
    }

    /**
     * Scope: Get versions with search
     */
    public function scopeSearch($query, string $searchTerm)
    {
        return $query->where('version_label', 'like', "%{$searchTerm}%")
            ->orWhere('change_summary', 'like', "%{$searchTerm}%");
    }
}
