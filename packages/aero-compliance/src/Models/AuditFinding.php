<?php

namespace Aero\Compliance\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditFinding extends TenantModel
{
    use HasFactory;

    protected $fillable = [
        'compliance_audit_id',
        'finding_id',
        'title',
        'description',
        'severity',
        'category',
        'status',
        'area_affected',
        'evidence',
        'root_cause',
        'immediate_action',
        'corrective_action',
        'preventive_action',
        'responsible_person_id',
        'due_date',
        'completion_date',
        'verification_date',
        'verifier_id',
        'closure_notes',
        'recurrence_risk',
        'metadata',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completion_date' => 'date',
        'verification_date' => 'date',
        'evidence' => 'json',
        'metadata' => 'json',
    ];

    // Severity constants
    const SEVERITY_CRITICAL = 'critical';

    const SEVERITY_MAJOR = 'major';

    const SEVERITY_MINOR = 'minor';

    const SEVERITY_OBSERVATION = 'observation';

    // Status constants
    const STATUS_OPEN = 'open';

    const STATUS_IN_PROGRESS = 'in_progress';

    const STATUS_PENDING_VERIFICATION = 'pending_verification';

    const STATUS_CLOSED = 'closed';

    const STATUS_OVERDUE = 'overdue';

    // Category constants
    const CATEGORY_DOCUMENTATION = 'documentation';

    const CATEGORY_PROCESS = 'process';

    const CATEGORY_TRAINING = 'training';

    const CATEGORY_RESOURCE = 'resource';

    const CATEGORY_SYSTEM = 'system';

    const CATEGORY_MANAGEMENT = 'management';

    const CATEGORY_REGULATORY = 'regulatory';

    // Recurrence risk constants
    const RECURRENCE_LOW = 'low';

    const RECURRENCE_MEDIUM = 'medium';

    const RECURRENCE_HIGH = 'high';

    /**
     * Get the compliance audit this finding belongs to
     */
    public function complianceAudit(): BelongsTo
    {
        return $this->belongsTo(ComplianceAudit::class);
    }

    /**
     * Get the responsible person
     */
    public function responsiblePerson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_person_id');
    }

    /**
     * Get the verifier
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verifier_id');
    }

    /**
     * Check if the finding is overdue
     */
    public function isOverdue(): bool
    {
        return $this->due_date < now() &&
            ! in_array($this->status, [self::STATUS_CLOSED, self::STATUS_PENDING_VERIFICATION]);
    }

    /**
     * Calculate days until due date
     */
    public function daysUntilDue(): int
    {
        if ($this->due_date) {
            return now()->diffInDays($this->due_date, false);
        }

        return 0;
    }

    /**
     * Get severity priority (higher number = higher priority)
     */
    public function getSeverityPriority(): int
    {
        switch ($this->severity) {
            case self::SEVERITY_CRITICAL:
                return 4;
            case self::SEVERITY_MAJOR:
                return 3;
            case self::SEVERITY_MINOR:
                return 2;
            case self::SEVERITY_OBSERVATION:
                return 1;
            default:
                return 0;
        }
    }

    /**
     * Check if finding is critical or major
     */
    public function isHighPriority(): bool
    {
        return in_array($this->severity, [self::SEVERITY_CRITICAL, self::SEVERITY_MAJOR]);
    }

    /**
     * Scope for critical findings
     */
    public function scopeCritical($query)
    {
        return $query->where('severity', self::SEVERITY_CRITICAL);
    }

    /**
     * Scope for major findings
     */
    public function scopeMajor($query)
    {
        return $query->where('severity', self::SEVERITY_MAJOR);
    }

    /**
     * Scope for minor findings
     */
    public function scopeMinor($query)
    {
        return $query->where('severity', self::SEVERITY_MINOR);
    }

    /**
     * Scope for open findings
     */
    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    /**
     * Scope for closed findings
     */
    public function scopeClosed($query)
    {
        return $query->where('status', self::STATUS_CLOSED);
    }

    /**
     * Scope for overdue findings
     */
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
            ->whereNotIn('status', [self::STATUS_CLOSED, self::STATUS_PENDING_VERIFICATION]);
    }

    /**
     * Scope for high priority findings
     */
    public function scopeHighPriority($query)
    {
        return $query->whereIn('severity', [self::SEVERITY_CRITICAL, self::SEVERITY_MAJOR]);
    }

    /**
     * Scope for pending verification
     */
    public function scopePendingVerification($query)
    {
        return $query->where('status', self::STATUS_PENDING_VERIFICATION);
    }
}
