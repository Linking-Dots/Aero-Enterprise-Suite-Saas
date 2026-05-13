<?php

namespace Aero\Compliance\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentRevision extends TenantModel
{
    use HasFactory;

    protected $fillable = [
        'controlled_document_id',
        'revision_number',
        'version',
        'revision_date',
        'effective_date',
        'revised_by',
        'approved_by',
        'revision_reason',
        'changes_summary',
        'change_details',
        'review_comments',
        'approval_comments',
        'status',
        'file_path',
        'file_size',
        'file_type',
        'checksum',
        'previous_revision_id',
        'superseded_date',
        'distribution_method',
        'notification_sent',
        'metadata',
    ];

    protected $casts = [
        'revision_date' => 'date',
        'effective_date' => 'date',
        'superseded_date' => 'date',
        'notification_sent' => 'boolean',
        'change_details' => 'json',
        'review_comments' => 'json',
        'metadata' => 'json',
    ];

    // Status constants
    const STATUS_DRAFT = 'draft';

    const STATUS_UNDER_REVIEW = 'under_review';

    const STATUS_PENDING_APPROVAL = 'pending_approval';

    const STATUS_APPROVED = 'approved';

    const STATUS_ACTIVE = 'active';

    const STATUS_SUPERSEDED = 'superseded';

    const STATUS_REJECTED = 'rejected';

    // Distribution method constants
    const DISTRIBUTION_EMAIL = 'email';

    const DISTRIBUTION_PORTAL = 'portal';

    const DISTRIBUTION_PRINT = 'print';

    const DISTRIBUTION_TRAINING = 'training';

    /**
     * Get the controlled document this revision belongs to
     */
    public function controlledDocument(): BelongsTo
    {
        return $this->belongsTo(ControlledDocument::class);
    }

    /**
     * Get the user who revised the document
     */
    public function revisedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revised_by');
    }

    /**
     * Get the user who approved the revision
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the previous revision
     */
    public function previousRevision(): BelongsTo
    {
        return $this->belongsTo(DocumentRevision::class, 'previous_revision_id');
    }

    /**
     * Get the next revision (superseding revision)
     */
    public function nextRevision()
    {
        return $this->hasOne(DocumentRevision::class, 'previous_revision_id');
    }

    /**
     * Check if this is the current/active revision
     */
    public function isCurrent(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if this revision is superseded
     */
    public function isSuperseded(): bool
    {
        return $this->status === self::STATUS_SUPERSEDED;
    }

    /**
     * Check if this revision is effective
     */
    public function isEffective(): bool
    {
        return $this->effective_date <= now() && $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Get the age of this revision in days
     */
    public function getAgeInDays(): int
    {
        return $this->revision_date->diffInDays(now());
    }

    /**
     * Get formatted version string
     */
    public function getFormattedVersion(): string
    {
        return $this->version ?: "Rev {$this->revision_number}";
    }

    /**
     * Get formatted file size
     */
    public function getFormattedFileSize(): string
    {
        if (! $this->file_size) {
            return 'Unknown';
        }

        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;

        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }

        return round($bytes, 2).' '.$units[$unitIndex];
    }

    /**
     * Generate comparison summary with previous revision
     */
    public function getComparisonSummary(): array
    {
        $previous = $this->previousRevision;
        if (! $previous) {
            return ['type' => 'initial', 'message' => 'Initial version'];
        }

        $changes = [];

        if ($this->file_size !== $previous->file_size) {
            $changes[] = 'File size changed';
        }

        if ($this->checksum !== $previous->checksum) {
            $changes[] = 'Content modified';
        }

        return [
            'type' => 'revision',
            'changes' => $changes,
            'previous_version' => $previous->getFormattedVersion(),
            'revision_reason' => $this->revision_reason,
        ];
    }

    /**
     * Scope for active revisions
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for superseded revisions
     */
    public function scopeSuperseded($query)
    {
        return $query->where('status', self::STATUS_SUPERSEDED);
    }

    /**
     * Scope for pending approval
     */
    public function scopePendingApproval($query)
    {
        return $query->where('status', self::STATUS_PENDING_APPROVAL);
    }

    /**
     * Scope for under review
     */
    public function scopeUnderReview($query)
    {
        return $query->where('status', self::STATUS_UNDER_REVIEW);
    }

    /**
     * Scope for effective revisions
     */
    public function scopeEffective($query)
    {
        return $query->where('effective_date', '<=', now())
            ->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for recent revisions
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('revision_date', '>=', now()->subDays($days));
    }

    /**
     * Scope for revisions by approver
     */
    public function scopeByApprover($query, $userId)
    {
        return $query->where('approved_by', $userId);
    }

    /**
     * Scope for revisions by revisor
     */
    public function scopeByRevisor($query, $userId)
    {
        return $query->where('revised_by', $userId);
    }
}
