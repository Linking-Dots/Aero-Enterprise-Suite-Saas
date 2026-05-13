<?php

namespace Aero\Compliance\Models;

use Aero\Core\Models\User;
use Aero\Project\Models\Project;
use Aero\Rfi\Models\Rfi;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * PermitToWork Model - PATENTABLE CORE IP
 *
 * Digital permit authorization system for high-risk construction activities.
 * Integrates with PermitValidationService for automatic enforcement.
 *
 * NOVELTY:
 * - Automatic permit validation before work approval
 * - Emergency revocation with immediate RFI locking
 * - Worker authorization tracking
 * - Condition checking (equipment, personnel, environmental)
 *
 * @property int $id
 * @property string $permit_number PTW-YYYY-NNNN
 * @property int $project_id
 * @property string $permit_type hot_work|confined_space|work_at_height|excavation|electrical|lifting_operations
 * @property string $work_description
 * @property array $activity_types
 * @property \Carbon\Carbon $valid_from
 * @property \Carbon\Carbon $valid_until
 * @property string|null $time_from
 * @property string|null $time_until
 * @property float|null $start_chainage
 * @property float|null $end_chainage
 * @property array|null $location_details
 * @property int $requested_by
 * @property int|null $approved_by
 * @property \Carbon\Carbon|null $approved_at
 * @property array $authorized_workers User IDs
 * @property array|null $authorized_equipment
 * @property array|null $permit_conditions
 * @property bool $equipment_check_required
 * @property \Carbon\Carbon|null $equipment_last_checked
 * @property bool $personnel_requirement_met
 * @property bool $environmental_check_done
 * @property string $risk_level low|medium|high|critical
 * @property array|null $identified_hazards
 * @property array|null $control_measures
 * @property string $status draft|pending_approval|approved|active|suspended|revoked|expired|completed
 * @property string|null $status_notes
 * @property int|null $revoked_by
 * @property \Carbon\Carbon|null $revoked_at
 * @property string|null $revocation_reason
 * @property int $affected_rfis_locked
 * @property int|null $closed_by
 * @property \Carbon\Carbon|null $closed_at
 * @property string|null $completion_notes
 * @property array|null $audit_log
 */
class PermitToWork extends TenantModel
{
    use SoftDeletes;

    protected $fillable = [
        'permit_number',
        'project_id',
        'permit_type',
        'work_description',
        'activity_types',
        'valid_from',
        'valid_until',
        'time_from',
        'time_until',
        'start_chainage',
        'end_chainage',
        'location_details',
        'requested_by',
        'approved_by',
        'approved_at',
        'authorized_workers',
        'authorized_equipment',
        'permit_conditions',
        'equipment_check_required',
        'equipment_last_checked',
        'personnel_requirement_met',
        'environmental_check_done',
        'risk_level',
        'identified_hazards',
        'control_measures',
        'status',
        'status_notes',
        'revoked_by',
        'revoked_at',
        'revocation_reason',
        'affected_rfis_locked',
        'closed_by',
        'closed_at',
        'completion_notes',
        'audit_log',
    ];

    protected $casts = [
        'valid_from' => 'date',
        'valid_until' => 'date',
        'approved_at' => 'datetime',
        'equipment_last_checked' => 'datetime',
        'revoked_at' => 'datetime',
        'closed_at' => 'datetime',
        'activity_types' => 'array',
        'location_details' => 'array',
        'authorized_workers' => 'array',
        'authorized_equipment' => 'array',
        'permit_conditions' => 'array',
        'identified_hazards' => 'array',
        'control_measures' => 'array',
        'audit_log' => 'array',
        'equipment_check_required' => 'boolean',
        'personnel_requirement_met' => 'boolean',
        'environmental_check_done' => 'boolean',
        'affected_rfis_locked' => 'integer',
    ];

    /**
     * Permit type constants
     */
    const TYPE_HOT_WORK = 'hot_work';

    const TYPE_CONFINED_SPACE = 'confined_space';

    const TYPE_WORK_AT_HEIGHT = 'work_at_height';

    const TYPE_EXCAVATION = 'excavation';

    const TYPE_ELECTRICAL = 'electrical';

    const TYPE_LIFTING_OPERATIONS = 'lifting_operations';

    /**
     * Status constants
     */
    const STATUS_DRAFT = 'draft';

    const STATUS_PENDING_APPROVAL = 'pending_approval';

    const STATUS_APPROVED = 'approved';

    const STATUS_ACTIVE = 'active';

    const STATUS_SUSPENDED = 'suspended';

    const STATUS_REVOKED = 'revoked';

    const STATUS_EXPIRED = 'expired';

    const STATUS_COMPLETED = 'completed';

    /**
     * Risk level constants
     */
    const RISK_LOW = 'low';

    const RISK_MEDIUM = 'medium';

    const RISK_HIGH = 'high';

    const RISK_CRITICAL = 'critical';

    // Relationships

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function revokedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function rfis(): HasMany
    {
        return $this->hasMany(Rfi::class, 'permit_to_work_id');
    }

    /**
     * Alias for backward compatibility.
     */
    public function dailyWorks(): HasMany
    {
        return $this->rfis();
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeValid($query)
    {
        return $query->where('status', self::STATUS_APPROVED)
            ->where('valid_from', '<=', now())
            ->where('valid_until', '>=', now());
    }

    public function scopeExpiringSoon($query, $days = 7)
    {
        return $query->where('status', self::STATUS_APPROVED)
            ->whereBetween('valid_until', [now(), now()->addDays($days)]);
    }

    public function scopeForProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    public function scopeForPermitType($query, $type)
    {
        return $query->where('permit_type', $type);
    }

    public function scopeCoveringLocation($query, $chainage)
    {
        return $query->where(function ($q) use ($chainage) {
            $q->where(function ($subQ) use ($chainage) {
                $subQ->where('start_chainage', '<=', $chainage)
                    ->where('end_chainage', '>=', $chainage);
            })->orWhere(function ($subQ) {
                $subQ->whereNull('start_chainage')
                    ->whereNull('end_chainage');
            });
        });
    }

    // Accessors & Mutators

    public function getIsValidAttribute(): bool
    {
        return $this->status === self::STATUS_APPROVED
            && $this->valid_from <= now()
            && $this->valid_until >= now();
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->valid_until < now();
    }

    public function getIsExpiringSoonAttribute(): bool
    {
        return $this->is_valid && $this->valid_until->diffInDays(now()) <= 7;
    }

    public function getIsRevokedAttribute(): bool
    {
        return $this->status === self::STATUS_REVOKED;
    }

    public function getDaysUntilExpiryAttribute(): int
    {
        return max(0, now()->diffInDays($this->valid_until, false));
    }

    public function getConditionsMetAttribute(): bool
    {
        if (! $this->equipment_check_required) {
            return true;
        }

        return $this->personnel_requirement_met
            && $this->environmental_check_done
            && ($this->equipment_last_checked && $this->equipment_last_checked->diffInDays(now()) <= 7);
    }

    // Methods

    /**
     * Check if a user is authorized to work under this permit
     */
    public function isWorkerAuthorized(int $userId): bool
    {
        return in_array($userId, $this->authorized_workers ?? []);
    }

    /**
     * Add a worker to authorized list
     */
    public function authorizeWorker(int $userId): void
    {
        $workers = $this->authorized_workers ?? [];
        if (! in_array($userId, $workers)) {
            $workers[] = $userId;
            $this->authorized_workers = $workers;
            $this->save();

            $this->addAuditLog('worker_authorized', "User #{$userId} authorized");
        }
    }

    /**
     * Remove a worker from authorized list
     */
    public function unauthorizeWorker(int $userId): void
    {
        $workers = $this->authorized_workers ?? [];
        $this->authorized_workers = array_values(array_diff($workers, [$userId]));
        $this->save();

        $this->addAuditLog('worker_unauthorized', "User #{$userId} unauthorized");
    }

    /**
     * Add entry to audit log
     */
    public function addAuditLog(string $action, string $description, array $metadata = []): void
    {
        $log = $this->audit_log ?? [];
        $log[] = [
            'action' => $action,
            'description' => $description,
            'metadata' => $metadata,
            'user_id' => auth()->id(),
            'timestamp' => now()->toIso8601String(),
        ];
        $this->audit_log = $log;
        $this->save();
    }

    /**
     * Generate permit number (PTW-YYYY-NNNN)
     */
    public static function generatePermitNumber(): string
    {
        $year = now()->year;
        $lastPermit = self::where('permit_number', 'like', "PTW-{$year}-%")
            ->orderBy('permit_number', 'desc')
            ->first();

        $sequence = 1;
        if ($lastPermit) {
            $parts = explode('-', $lastPermit->permit_number);
            $sequence = (int) end($parts) + 1;
        }

        return sprintf('PTW-%d-%04d', $year, $sequence);
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->permit_number)) {
                $model->permit_number = self::generatePermitNumber();
            }
        });
    }
}
