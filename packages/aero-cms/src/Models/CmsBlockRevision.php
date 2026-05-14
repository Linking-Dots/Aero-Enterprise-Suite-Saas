<?php

namespace Aero\Cms\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CmsBlockRevision extends TenantModel
{
    use HasFactory;

    protected $table = 'cms_block_revisions';

    protected $fillable = [
        'cms_page_block_id',
        'cms_block_version_id',
        'revision_type',
        'change_details',
        'diff_json',
        'before_state',
        'after_state',
        'user_id',
        'user_name',
        'user_email',
        'reason',
        'metadata',
        'approved_by_user_id',
        'approved_at',
        'approval_notes',
    ];

    protected $casts = [
        'diff_json' => 'json',
        'before_state' => 'json',
        'after_state' => 'json',
        'metadata' => 'json',
        'approved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationship: The block this revision is for
     */
    public function block(): BelongsTo
    {
        return $this->belongsTo(CmsPageBlock::class, 'cms_page_block_id');
    }

    /**
     * Relationship: The version this revision is associated with
     */
    public function version(): BelongsTo
    {
        return $this->belongsTo(CmsBlockVersion::class, 'cms_block_version_id');
    }

    /**
     * Get human-readable revision type label
     */
    public function getTypeLabel(): string
    {
        return match ($this->revision_type) {
            'created' => 'Block Created',
            'updated' => 'Content Updated',
            'published' => 'Published',
            'archived' => 'Archived',
            'restored' => 'Restored',
            'reverted' => 'Reverted to Previous Version',
            'scheduled' => 'Publishing Scheduled',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            default => 'Unknown Change',
        };
    }

    /**
     * Get icon for revision type
     */
    public function getTypeIcon(): string
    {
        return match ($this->revision_type) {
            'created' => 'plus-circle',
            'updated' => 'pencil',
            'published' => 'globe',
            'archived' => 'archive',
            'restored' => 'redo',
            'reverted' => 'undo',
            'scheduled' => 'calendar',
            'approved' => 'check-circle',
            'rejected' => 'x-circle',
            default => 'info',
        };
    }

    /**
     * Get color for revision type (Bootstrap color class)
     */
    public function getTypeColor(): string
    {
        return match ($this->revision_type) {
            'created' => 'success',
            'updated' => 'info',
            'published' => 'primary',
            'archived' => 'secondary',
            'restored' => 'success',
            'reverted' => 'warning',
            'scheduled' => 'info',
            'approved' => 'success',
            'rejected' => 'danger',
            default => 'secondary',
        };
    }

    /**
     * Check if this revision has a diff
     */
    public function hasDiff(): bool
    {
        return $this->diff_json !== null && !empty($this->diff_json);
    }

    /**
     * Get diff summary
     */
    public function getDiffSummary(): array
    {
        if (!$this->hasDiff()) {
            return [];
        }

        $diff = $this->diff_json ?? [];
        return [
            'added_count' => count($diff['added'] ?? []),
            'removed_count' => count($diff['removed'] ?? []),
            'modified_count' => count($diff['modified'] ?? []),
        ];
    }

    /**
     * Check if revision was approved
     */
    public function isApproved(): bool
    {
        return $this->approved_at !== null && $this->approved_by_user_id !== null;
    }

    /**
     * Get formatted timestamp for display
     */
    public function getDisplayTimestamp(): string
    {
        return $this->created_at->format('M d, Y \a\t H:i');
    }

    /**
     * Get author display name
     */
    public function getAuthorName(): string
    {
        return $this->user_name ?? $this->user_email ?? 'Unknown User';
    }

    /**
     * Scope: Get revisions for a block
     */
    public function scopeForBlock($query, int $blockId)
    {
        return $query->where('cms_page_block_id', $blockId)->orderByDesc('created_at');
    }

    /**
     * Scope: Get revisions of a specific type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('revision_type', $type);
    }

    /**
     * Scope: Get revisions by user
     */
    public function scopeByUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Get approved revisions
     */
    public function scopeApproved($query)
    {
        return $query->whereNotNull('approved_by_user_id')->whereNotNull('approved_at');
    }

    /**
     * Scope: Get revisions with reason
     */
    public function scopeWithReason($query)
    {
        return $query->whereNotNull('reason');
    }

    /**
     * Scope: Get recent revisions (last N days)
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->whereDate('created_at', '>=', now()->subDays($days)->toDateString());
    }

    /**
     * Scope: Search revisions
     */
    public function scopeSearch($query, string $term)
    {
        return $query->where('change_details', 'like', "%{$term}%")
            ->orWhere('reason', 'like', "%{$term}%")
            ->orWhere('user_name', 'like', "%{$term}%");
    }

    /**
     * Create revision from comparison
     */
    public static function createFromComparison(
        CmsPageBlock $block,
        string $revisionType,
        array $beforeState,
        array $afterState,
        string $userId = null,
        string $reason = null
    ): self {
        $diff = self::calculateDiff($beforeState, $afterState);

        return self::create([
            'cms_page_block_id' => $block->id,
            'revision_type' => $revisionType,
            'change_details' => "Block {$revisionType}: " . self::summarizeDiff($diff),
            'diff_json' => $diff,
            'before_state' => $beforeState,
            'after_state' => $afterState,
            'user_id' => $userId,
            'reason' => $reason,
        ]);
    }

    /**
     * Calculate diff between two states
     */
    private static function calculateDiff(array $before, array $after): array
    {
        $added = array_diff_key($after, $before);
        $removed = array_diff_key($before, $after);
        $modified = [];

        foreach ($after as $key => $value) {
            if (isset($before[$key]) && $before[$key] !== $value) {
                $modified[$key] = [
                    'old' => $before[$key],
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
     * Summarize diff into human-readable text
     */
    private static function summarizeDiff(array $diff): string
    {
        $parts = [];

        if (!empty($diff['added'])) {
            $parts[] = count($diff['added']) . ' added';
        }

        if (!empty($diff['removed'])) {
            $parts[] = count($diff['removed']) . ' removed';
        }

        if (!empty($diff['modified'])) {
            $parts[] = count($diff['modified']) . ' modified';
        }

        return implode(', ', $parts) ?: 'No changes';
    }
}
