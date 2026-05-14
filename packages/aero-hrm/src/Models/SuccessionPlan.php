<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Succession Plan Model
 *
 * Manages succession planning for critical positions.
 * Links key positions to potential successors with readiness levels.
 */
class SuccessionPlan extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'position_id',
        'designation_id',
        'department_id',
        'current_holder_id',
        'title',
        'description',
        'priority',
        'risk_level',
        'status',
        'target_date',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'target_date' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Priority levels for succession planning.
     */
    public const PRIORITY_CRITICAL = 'critical';

    public const PRIORITY_HIGH = 'high';

    public const PRIORITY_MEDIUM = 'medium';

    public const PRIORITY_LOW = 'low';

    /**
     * Risk levels if position becomes vacant.
     */
    public const RISK_HIGH = 'high';

    public const RISK_MEDIUM = 'medium';

    public const RISK_LOW = 'low';

    /**
     * Plan statuses.
     */
    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_ON_HOLD = 'on_hold';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function currentHolder(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'current_holder_id');
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(SuccessionCandidate::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Get ready-now candidates.
     */
    public function readyNowCandidates(): HasMany
    {
        return $this->candidates()->where('readiness_level', 'ready_now');
    }

    /**
     * Get candidates ready within 1 year.
     */
    public function readySoonCandidates(): HasMany
    {
        return $this->candidates()->where('readiness_level', 'ready_1_year');
    }

    /**
     * Scope for active plans.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for critical priority.
     */
    public function scopeCritical($query)
    {
        return $query->where('priority', self::PRIORITY_CRITICAL);
    }

    /**
     * Scope for high risk.
     */
    public function scopeHighRisk($query)
    {
        return $query->where('risk_level', self::RISK_HIGH);
    }
}
