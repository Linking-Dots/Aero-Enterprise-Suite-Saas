<?php

namespace Aero\Cms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CmsBlockPublish extends TenantModel
{
    use HasFactory;

    protected $table = 'cms_block_publishes';

    protected $fillable = [
        'cms_page_block_id',
        'cms_block_version_id',
        'status',
        'scheduled_publish_at',
        'published_at',
        'archived_at',
        'scheduled_unpublish_at',
        'published_by_user_id',
        'archived_by_user_id',
        'visibility',
        'require_approval',
        'is_featured',
        'auto_publish',
        'auto_unpublish',
        'publish_duration_days',
        'publish_notes',
        'rejection_reason',
        'workflow_state',
        'view_count',
        'interaction_count',
    ];

    protected $casts = [
        'scheduled_publish_at' => 'datetime',
        'published_at' => 'datetime',
        'archived_at' => 'datetime',
        'scheduled_unpublish_at' => 'datetime',
        'require_approval' => 'boolean',
        'is_featured' => 'boolean',
        'auto_publish' => 'boolean',
        'auto_unpublish' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationship: The block being published
     */
    public function block(): BelongsTo
    {
        return $this->belongsTo(CmsPageBlock::class, 'cms_page_block_id');
    }

    /**
     * Relationship: The version being published
     */
    public function version(): BelongsTo
    {
        return $this->belongsTo(CmsBlockVersion::class, 'cms_block_version_id');
    }

    /**
     * Relationship: Revisions for this publishing record
     */
    public function revisions(): HasMany
    {
        return $this->hasMany(CmsBlockRevision::class, 'cms_page_block_id', 'cms_page_block_id');
    }

    /**
     * Publish this block immediately
     */
    public function publish(string $userId = null): bool
    {
        $this->update([
            'status' => 'published',
            'published_at' => now(),
            'published_by_user_id' => $userId,
            'visibility' => $this->visibility === 'draft_only' ? 'public' : $this->visibility,
            'workflow_state' => 'ready',
        ]);

        // Record revision
        CmsBlockRevision::create([
            'cms_page_block_id' => $this->cms_page_block_id,
            'cms_block_version_id' => $this->cms_block_version_id,
            'revision_type' => 'published',
            'change_details' => 'Block published to ' . $this->visibility,
            'user_id' => $userId,
            'reason' => $this->publish_notes,
        ]);

        return true;
    }

    /**
     * Schedule block for future publication
     */
    public function schedulePublish(\DateTime $publishAt, string $userId = null): bool
    {
        $this->update([
            'status' => 'scheduled',
            'scheduled_publish_at' => $publishAt,
            'auto_publish' => true,
        ]);

        // Record revision
        CmsBlockRevision::create([
            'cms_page_block_id' => $this->cms_page_block_id,
            'cms_block_version_id' => $this->cms_block_version_id,
            'revision_type' => 'scheduled',
            'change_details' => 'Block scheduled for publication on ' . $publishAt->format('Y-m-d H:i:s'),
            'user_id' => $userId,
            'reason' => $this->publish_notes,
        ]);

        return true;
    }

    /**
     * Archive (unpublish) this block
     */
    public function archive(string $userId = null, string $reason = null): bool
    {
        $this->update([
            'status' => 'archived',
            'archived_at' => now(),
            'archived_by_user_id' => $userId,
            'visibility' => 'draft_only',
        ]);

        // Record revision
        CmsBlockRevision::create([
            'cms_page_block_id' => $this->cms_page_block_id,
            'cms_block_version_id' => $this->cms_block_version_id,
            'revision_type' => 'archived',
            'change_details' => 'Block archived/unpublished',
            'user_id' => $userId,
            'reason' => $reason,
        ]);

        return true;
    }

    /**
     * Restore archived block to previous visibility
     */
    public function restore(string $userId = null, string $visibility = 'public'): bool
    {
        $this->update([
            'status' => 'published',
            'published_at' => now(),
            'published_by_user_id' => $userId,
            'visibility' => $visibility,
        ]);

        // Record revision
        CmsBlockRevision::create([
            'cms_page_block_id' => $this->cms_page_block_id,
            'cms_block_version_id' => $this->cms_block_version_id,
            'revision_type' => 'restored',
            'change_details' => 'Block restored to ' . $visibility,
            'user_id' => $userId,
        ]);

        return true;
    }

    /**
     * Reject pending approval
     */
    public function reject(string $reason, string $userId = null): bool
    {
        $this->update([
            'workflow_state' => 'rejected',
            'rejection_reason' => $reason,
        ]);

        // Record revision
        CmsBlockRevision::create([
            'cms_page_block_id' => $this->cms_page_block_id,
            'revision_type' => 'rejected',
            'change_details' => 'Publishing rejected',
            'user_id' => $userId,
            'reason' => $reason,
        ]);

        return true;
    }

    /**
     * Approve publishing
     */
    public function approve(string $userId = null, string $notes = null): bool
    {
        $this->update([
            'workflow_state' => 'approved',
            'approval_notes' => $notes,
        ]);

        // Record revision
        CmsBlockRevision::create([
            'cms_page_block_id' => $this->cms_page_block_id,
            'cms_block_version_id' => $this->cms_block_version_id,
            'revision_type' => 'approved',
            'change_details' => 'Publishing approved',
            'user_id' => $userId,
            'reason' => $notes,
        ]);

        return true;
    }

    /**
     * Check if block is currently published and visible
     */
    public function isActive(): bool
    {
        return $this->status === 'published'
            && $this->visibility !== 'draft_only'
            && $this->published_at !== null
            && ($this->scheduled_unpublish_at === null || $this->scheduled_unpublish_at->isFuture());
    }

    /**
     * Check if publishing is pending approval
     */
    public function isPendingApproval(): bool
    {
        return $this->workflow_state === 'pending_review';
    }

    /**
     * Get publishing status label
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'scheduled' => 'Scheduled (' . $this->scheduled_publish_at?->format('M d, Y H:i') . ')',
            'published' => 'Published',
            'archived' => 'Archived',
            default => 'Unknown',
        };
    }

    /**
     * Record view/interaction
     */
    public function recordView(): void
    {
        $this->increment('view_count');
    }

    /**
     * Record interaction
     */
    public function recordInteraction(): void
    {
        $this->increment('interaction_count');
    }

    /**
     * Calculate CTR (Click-Through Rate)
     */
    public function getClickThroughRate(): float
    {
        if ($this->view_count === 0) {
            return 0;
        }
        return ($this->interaction_count / $this->view_count) * 100;
    }

    /**
     * Scope: Get published blocks
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published')->where('visibility', '!=', 'draft_only');
    }

    /**
     * Scope: Get scheduled blocks
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    /**
     * Scope: Get featured blocks
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope: Get by visibility
     */
    public function scopeWithVisibility($query, string $visibility)
    {
        return $query->where('visibility', $visibility);
    }

    /**
     * Scope: Get by workflow state
     */
    public function scopeWithWorkflowState($query, string $state)
    {
        return $query->where('workflow_state', $state);
    }

    /**
     * Scope: Get due for publishing (scheduled blocks that are now past scheduled time)
     */
    public function scopeDueForPublishing($query)
    {
        return $query->where('status', 'scheduled')
            ->where('auto_publish', true)
            ->whereNotNull('scheduled_publish_at')
            ->where('scheduled_publish_at', '<=', now());
    }
}
