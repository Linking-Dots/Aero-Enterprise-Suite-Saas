<?php

namespace Aero\Compliance\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RegulatoryRequirement extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'requirement_number',
        'title',
        'description',
        'regulatory_body',
        'regulation_reference',
        'requirement_type',
        'industry',
        'applicable_locations',
        'effective_date',
        'compliance_deadline',
        'status',
        'priority',
        'assigned_to',
        'compliance_percentage',
        'implementation_notes',
        'evidence_documents',
    ];

    protected $casts = [
        'applicable_locations' => 'array',
        'effective_date' => 'date',
        'compliance_deadline' => 'date',
        'compliance_percentage' => 'decimal:2',
        'evidence_documents' => 'array',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';

    const STATUS_IN_PROGRESS = 'in_progress';

    const STATUS_COMPLIANT = 'compliant';

    const STATUS_NON_COMPLIANT = 'non_compliant';

    const STATUS_NOT_APPLICABLE = 'not_applicable';

    // Priority constants
    const PRIORITY_LOW = 'low';

    const PRIORITY_MEDIUM = 'medium';

    const PRIORITY_HIGH = 'high';

    const PRIORITY_CRITICAL = 'critical';

    // Type constants
    const TYPE_SAFETY = 'safety';

    const TYPE_ENVIRONMENTAL = 'environmental';

    const TYPE_FINANCIAL = 'financial';

    const TYPE_DATA_PROTECTION = 'data_protection';

    const TYPE_EMPLOYMENT = 'employment';

    const TYPE_QUALITY = 'quality';

    /**
     * Get the assigned user.
     */
    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Scope to get requirements by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get requirements by industry.
     */
    public function scopeByIndustry($query, $industry)
    {
        return $query->where('industry', $industry);
    }

    /**
     * Scope to get requirements by regulatory body.
     */
    public function scopeByRegulatoryBody($query, $body)
    {
        return $query->where('regulatory_body', $body);
    }

    /**
     * Check if requirement is overdue.
     */
    public function isOverdue()
    {
        return $this->compliance_deadline &&
            $this->compliance_deadline < now()->toDateString() &&
            $this->status !== self::STATUS_COMPLIANT;
    }

    /**
     * Check if requirement is fully compliant.
     */
    public function isCompliant()
    {
        return $this->status === self::STATUS_COMPLIANT && $this->compliance_percentage >= 100;
    }

    /**
     * Scope for active requirements
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [self::STATUS_NOT_APPLICABLE]);
    }

    /**
     * Scope for overdue requirements
     */
    public function scopeOverdue($query)
    {
        return $query->where('compliance_deadline', '<', now())
            ->where('status', '!=', self::STATUS_COMPLIANT);
    }

    /**
     * Scope for due soon requirements
     */
    public function scopeDueSoon($query, $days = 30)
    {
        return $query->where('compliance_deadline', '<=', now()->addDays($days))
            ->where('compliance_deadline', '>', now())
            ->where('status', '!=', self::STATUS_COMPLIANT);
    }

    /**
     * Get compliance status color.
     */
    public function getStatusColorAttribute()
    {
        return match ($this->status) {
            self::STATUS_COMPLIANT => 'green',
            self::STATUS_NON_COMPLIANT => 'red',
            self::STATUS_IN_PROGRESS => 'yellow',
            self::STATUS_PENDING => 'gray',
            self::STATUS_NOT_APPLICABLE => 'blue',
            default => 'gray'
        };
    }
}
