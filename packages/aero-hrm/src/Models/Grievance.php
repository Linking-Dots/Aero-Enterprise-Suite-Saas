<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Grievance Model
 *
 * Manages employee grievances and complaints separate from disciplinary cases.
 */
class Grievance extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'grievance_number',
        'employee_id',
        'category_id',
        'subject',
        'description',
        'grievance_type',
        'severity',
        'against_employee_id',
        'against_department_id',
        'incident_date',
        'incident_location',
        'witnesses',
        'supporting_documents',
        'desired_resolution',
        'status',
        'assigned_to',
        'assigned_at',
        'investigation_notes',
        'resolution',
        'resolution_date',
        'resolved_by',
        'employee_satisfaction',
        'appeal_status',
        'appeal_notes',
        'is_anonymous',
        'is_confidential',
    ];

    protected function casts(): array
    {
        return [
            'incident_date' => 'date',
            'witnesses' => 'array',
            'supporting_documents' => 'array',
            'assigned_at' => 'datetime',
            'resolution_date' => 'date',
            'is_anonymous' => 'boolean',
            'is_confidential' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Grievance types.
     */
    public const TYPE_HARASSMENT = 'harassment';

    public const TYPE_DISCRIMINATION = 'discrimination';

    public const TYPE_WORKPLACE_SAFETY = 'workplace_safety';

    public const TYPE_COMPENSATION = 'compensation';

    public const TYPE_MANAGEMENT = 'management';

    public const TYPE_POLICY = 'policy';

    public const TYPE_WORKLOAD = 'workload';

    public const TYPE_INTERPERSONAL = 'interpersonal';

    public const TYPE_OTHER = 'other';

    /**
     * Severity levels.
     */
    public const SEVERITY_LOW = 'low';

    public const SEVERITY_MEDIUM = 'medium';

    public const SEVERITY_HIGH = 'high';

    public const SEVERITY_CRITICAL = 'critical';

    /**
     * Status options.
     */
    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_UNDER_REVIEW = 'under_review';

    public const STATUS_INVESTIGATING = 'investigating';

    public const STATUS_PENDING_RESOLUTION = 'pending_resolution';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_APPEALED = 'appealed';

    public const STATUS_WITHDRAWN = 'withdrawn';

    /**
     * Appeal statuses.
     */
    public const APPEAL_NONE = 'none';

    public const APPEAL_PENDING = 'pending';

    public const APPEAL_APPROVED = 'approved';

    public const APPEAL_REJECTED = 'rejected';

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(GrievanceCategory::class, 'category_id');
    }

    public function againstEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'against_employee_id');
    }

    public function againstDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'against_department_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_to');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'resolved_by');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(GrievanceNote::class);
    }

    /**
     * Generate grievance number.
     */
    public static function generateNumber(): string
    {
        $year = date('Y');
        $count = self::whereYear('created_at', $year)->count() + 1;

        return "GRV-{$year}-".str_pad($count, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Scope for open grievances.
     */
    public function scopeOpen($query)
    {
        return $query->whereNotIn('status', [self::STATUS_RESOLVED, self::STATUS_CLOSED, self::STATUS_WITHDRAWN]);
    }

    /**
     * Scope for high severity.
     */
    public function scopeHighSeverity($query)
    {
        return $query->whereIn('severity', [self::SEVERITY_HIGH, self::SEVERITY_CRITICAL]);
    }

    /**
     * Scope for confidential.
     */
    public function scopeConfidential($query)
    {
        return $query->where('is_confidential', true);
    }

    /**
     * Boot method for model events.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->grievance_number)) {
                $model->grievance_number = self::generateNumber();
            }
        });
    }
}
