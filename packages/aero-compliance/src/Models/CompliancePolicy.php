<?php

namespace Aero\Compliance\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompliancePolicy extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'policy_id',
        'title',
        'description',
        'content',
        'type',
        'category',
        'department',
        'owner_id',
        'approver_id',
        'effective_date',
        'review_frequency_months',
        'next_review_date',
        'expiry_date',
        'version',
        'status',
        'priority',
        'applicable_locations',
        'applicable_roles',
        'requires_acknowledgment',
        'approval_notes',
        'approved_by',
        'approved_at',
        'published_by',
        'published_at',
        'archived_by',
        'archived_at',
        'created_by',
        'updated_by',
        'tags',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'next_review_date' => 'date',
        'expiry_date' => 'date',
        'approved_at' => 'datetime',
        'published_at' => 'datetime',
        'archived_at' => 'datetime',
        'applicable_locations' => 'array',
        'applicable_roles' => 'array',
        'requires_acknowledgment' => 'boolean',
        'tags' => 'array',
    ];

    // Status constants
    const STATUS_DRAFT = 'draft';

    const STATUS_UNDER_REVIEW = 'under_review';

    const STATUS_PENDING_APPROVAL = 'pending_approval';

    const STATUS_APPROVED = 'approved';

    const STATUS_ACTIVE = 'active';

    const STATUS_ARCHIVED = 'archived';

    const STATUS_SUPERSEDED = 'superseded';

    // Category constants
    const CATEGORY_HR = 'hr';

    const CATEGORY_SAFETY = 'safety';

    const CATEGORY_SECURITY = 'security';

    const CATEGORY_QUALITY = 'quality';

    const CATEGORY_FINANCIAL = 'financial';

    const CATEGORY_OPERATIONAL = 'operational';

    const CATEGORY_REGULATORY = 'regulatory';

    const CATEGORY_ENVIRONMENTAL = 'environmental';

    // Type constants
    const TYPE_POLICY = 'policy';

    const TYPE_PROCEDURE = 'procedure';

    const TYPE_GUIDELINE = 'guideline';

    const TYPE_STANDARD = 'standard';

    // Priority constants
    const PRIORITY_LOW = 'low';

    const PRIORITY_MEDIUM = 'medium';

    const PRIORITY_HIGH = 'high';

    const PRIORITY_CRITICAL = 'critical';

    /**
     * Get the policy owner.
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Get the approver.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    /**
     * Get the user who created the policy.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who updated the policy.
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who published the policy.
     */
    public function publishedBy()
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    /**
     * Get the user who archived the policy.
     */
    public function archivedBy()
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    /**
     * Get policy acknowledgments.
     */
    public function acknowledgments()
    {
        return $this->hasMany(CompliancePolicyAcknowledgment::class, 'compliance_policy_id');
    }

    /**
     * Scope to get active policies.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope to get policies due for review.
     */
    public function scopeDueForReview($query)
    {
        return $query->where('next_review_date', '<=', now());
    }

    /**
     * Scope to get overdue policies.
     */
    public function scopeOverdue($query)
    {
        return $query->where('next_review_date', '<', now());
    }

    /**
     * Scope to get policies by type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get policies by category.
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Check if policy is expired.
     */
    public function isExpired()
    {
        return $this->expiry_date && $this->expiry_date < now()->toDateString();
    }

    /**
     * Check if policy needs review.
     */
    public function needsReview()
    {
        return $this->next_review_date && $this->next_review_date <= now()->toDateString();
    }

    /**
     * Get acknowledgment rate.
     */
    public function getAcknowledgmentRateAttribute()
    {
        $totalUsers = User::count();
        $acknowledgedUsers = $this->acknowledgments()->count();

        return $totalUsers > 0 ? ($acknowledgedUsers / $totalUsers) * 100 : 0;
    }

    /**
     * Check if user has acknowledged this policy.
     */
    public function isAcknowledgedBy(User $user)
    {
        return $this->acknowledgments()->where('user_id', $user->id)->exists();
    }
}
