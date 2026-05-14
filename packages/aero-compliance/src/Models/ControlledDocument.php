<?php

namespace Aero\Compliance\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ControlledDocument extends TenantModel
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'title',
        'description',
        'category',
        'type',
        'status',
        'current_version',
        'document_owner_id',
        'approver_id',
        'reviewer_id',
        'creation_date',
        'effective_date',
        'next_review_date',
        'retention_period',
        'confidentiality_level',
        'access_level',
        'distribution_list',
        'tags',
        'file_path',
        'file_size',
        'file_type',
        'checksum',
        'is_template',
        'template_category',
        'workflow_stage',
        'approval_required',
        'metadata',
    ];

    protected $casts = [
        'creation_date' => 'date',
        'effective_date' => 'date',
        'next_review_date' => 'date',
        'distribution_list' => 'json',
        'tags' => 'json',
        'is_template' => 'boolean',
        'approval_required' => 'boolean',
        'metadata' => 'json',
    ];

    // Status constants
    const STATUS_DRAFT = 'draft';

    const STATUS_UNDER_REVIEW = 'under_review';

    const STATUS_PENDING_APPROVAL = 'pending_approval';

    const STATUS_APPROVED = 'approved';

    const STATUS_ACTIVE = 'active';

    const STATUS_SUPERSEDED = 'superseded';

    const STATUS_OBSOLETE = 'obsolete';

    const STATUS_ARCHIVED = 'archived';

    // Category constants
    const CATEGORY_POLICY = 'policy';

    const CATEGORY_PROCEDURE = 'procedure';

    const CATEGORY_WORK_INSTRUCTION = 'work_instruction';

    const CATEGORY_FORM = 'form';

    const CATEGORY_STANDARD = 'standard';

    const CATEGORY_SPECIFICATION = 'specification';

    const CATEGORY_MANUAL = 'manual';

    const CATEGORY_PLAN = 'plan';

    // Type constants
    const TYPE_QUALITY = 'quality';

    const TYPE_SAFETY = 'safety';

    const TYPE_ENVIRONMENTAL = 'environmental';

    const TYPE_REGULATORY = 'regulatory';

    const TYPE_OPERATIONAL = 'operational';

    const TYPE_TECHNICAL = 'technical';

    const TYPE_ADMINISTRATIVE = 'administrative';

    // Confidentiality level constants
    const CONFIDENTIALITY_PUBLIC = 'public';

    const CONFIDENTIALITY_INTERNAL = 'internal';

    const CONFIDENTIALITY_CONFIDENTIAL = 'confidential';

    const CONFIDENTIALITY_RESTRICTED = 'restricted';

    // Access level constants
    const ACCESS_PUBLIC = 'public';

    const ACCESS_ALL_EMPLOYEES = 'all_employees';

    const ACCESS_DEPARTMENT = 'department';

    const ACCESS_ROLE_BASED = 'role_based';

    const ACCESS_RESTRICTED = 'restricted';

    // Workflow stage constants
    const WORKFLOW_CREATION = 'creation';

    const WORKFLOW_REVIEW = 'review';

    const WORKFLOW_APPROVAL = 'approval';

    const WORKFLOW_PUBLICATION = 'publication';

    const WORKFLOW_MAINTENANCE = 'maintenance';

    /**
     * Get the document owner
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'document_owner_id');
    }

    /**
     * Get the document approver
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    /**
     * Get the document reviewer
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /**
     * Get all revisions of this document
     */
    public function revisions(): HasMany
    {
        return $this->hasMany(DocumentRevision::class)->orderBy('revision_number', 'desc');
    }

    /**
     * Get the latest revision
     */
    public function latestRevision()
    {
        return $this->revisions()->first();
    }

    /**
     * Check if document is due for review
     */
    public function isDueForReview(): bool
    {
        return $this->next_review_date && $this->next_review_date <= now();
    }

    /**
     * Check if document is overdue for review
     */
    public function isOverdueForReview(): bool
    {
        return $this->next_review_date && $this->next_review_date < now();
    }

    /**
     * Calculate days until next review
     */
    public function daysUntilReview(): int
    {
        if ($this->next_review_date) {
            return now()->diffInDays($this->next_review_date, false);
        }

        return 0;
    }

    /**
     * Check if document is active
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if document requires approval
     */
    public function requiresApproval(): bool
    {
        return $this->approval_required;
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
     * Scope for active documents
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for documents due for review
     */
    public function scopeDueForReview($query)
    {
        return $query->where('next_review_date', '<=', now());
    }

    /**
     * Scope for overdue documents
     */
    public function scopeOverdue($query)
    {
        return $query->where('next_review_date', '<', now());
    }

    /**
     * Scope for documents by category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for documents by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for template documents
     */
    public function scopeTemplates($query)
    {
        return $query->where('is_template', true);
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
     * Scope for confidential documents
     */
    public function scopeConfidential($query)
    {
        return $query->whereIn('confidentiality_level', [
            self::CONFIDENTIALITY_CONFIDENTIAL,
            self::CONFIDENTIALITY_RESTRICTED,
        ]);
    }
}
