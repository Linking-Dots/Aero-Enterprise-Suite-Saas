<?php

namespace Aero\Quality\Models;

use Aero\Core\Models\User;
use Aero\Project\Models\Project;
use Aero\Rfi\Models\Rfi;
use Aero\Rfi\Models\WorkLayer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * NonConformanceReport Model
 *
 * Enhanced for chainage-based blocking of RFIs and payments.
 * PATENTABLE: "Spatial non-conformance blocking for construction workflows"
 *
 * @property int $id
 * @property int|null $project_id
 * @property float|null $start_chainage_m
 * @property float|null $end_chainage_m
 * @property bool $blocks_same_layer
 * @property bool $blocks_all_layers
 * @property bool $blocks_payment
 */
class NonConformanceReport extends TenantModel
{
    use SoftDeletes;

    public const STATUS_OPEN = 'open';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_CLOSED = 'closed';

    public const SEVERITY_LOW = 'low';

    public const SEVERITY_MEDIUM = 'medium';

    public const SEVERITY_HIGH = 'high';

    public const SEVERITY_CRITICAL = 'critical';

    protected $fillable = [
        'ncr_number',
        'title',
        'description',
        'severity',
        'product_id',
        'batch_number',
        'detected_by',
        'detected_date',
        'root_cause',
        'corrective_action',
        'preventive_action',
        'status',
        'closed_date',
        'closed_by',
        // New chainage blocking fields
        'project_id',
        'start_chainage_m',
        'end_chainage_m',
        'daily_work_id',
        'quality_inspection_id',
        'work_layer_id',
        'blocks_same_layer',
        'blocks_all_layers',
        'blocks_payment',
        'resolution_deadline',
        'assigned_to_user_id',
        'resolution_notes',
        'estimated_rework_cost',
        'actual_rework_cost',
        'verification_hash',
    ];

    protected $casts = [
        'detected_date' => 'datetime',
        'closed_date' => 'datetime',
        'resolution_deadline' => 'datetime',
        'start_chainage_m' => 'decimal:3',
        'end_chainage_m' => 'decimal:3',
        'blocks_same_layer' => 'boolean',
        'blocks_all_layers' => 'boolean',
        'blocks_payment' => 'boolean',
        'estimated_rework_cost' => 'decimal:2',
        'actual_rework_cost' => 'decimal:2',
    ];

    // ==================== Relationships ====================

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function rfi(): BelongsTo
    {
        return $this->belongsTo(Rfi::class, 'daily_work_id');
    }

    /**
     * Alias for rfi() for backward compatibility.
     */
    public function dailyWork(): BelongsTo
    {
        return $this->rfi();
    }

    public function qualityInspection(): BelongsTo
    {
        return $this->belongsTo(QualityInspection::class);
    }

    public function workLayer(): BelongsTo
    {
        return $this->belongsTo(WorkLayer::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function detectedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'detected_by');
    }

    public function closedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    // ==================== Scopes ====================

    public function scopeOpen($query)
    {
        return $query->whereIn('status', [self::STATUS_OPEN, self::STATUS_IN_PROGRESS]);
    }

    public function scopeClosed($query)
    {
        return $query->whereIn('status', [self::STATUS_RESOLVED, self::STATUS_CLOSED]);
    }

    public function scopeByProject($query, int $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    public function scopeInChainageRange($query, float $start, float $end)
    {
        return $query->where(function ($q) use ($start, $end) {
            $q->whereBetween('start_chainage_m', [$start, $end])
                ->orWhereBetween('end_chainage_m', [$start, $end])
                ->orWhere(function ($q2) use ($start, $end) {
                    $q2->where('start_chainage_m', '<=', $start)
                        ->where('end_chainage_m', '>=', $end);
                });
        });
    }

    public function scopeBlocking($query)
    {
        return $query->open()->where(function ($q) {
            $q->where('blocks_same_layer', true)
                ->orWhere('blocks_all_layers', true)
                ->orWhere('blocks_payment', true);
        });
    }

    // ==================== Business Logic ====================

    /**
     * Check if this NCR blocks a specific layer at a chainage.
     * PATENTABLE: "Spatial non-conformance blocking logic"
     */
    public function blocksLayerAtChainage(int $layerId, float $startM, float $endM): bool
    {
        // Must be open
        if (! in_array($this->status, [self::STATUS_OPEN, self::STATUS_IN_PROGRESS])) {
            return false;
        }

        // Must overlap chainage
        if ($this->end_chainage_m < $startM || $this->start_chainage_m > $endM) {
            return false;
        }

        // Critical NCRs block everything
        if ($this->blocks_all_layers) {
            return true;
        }

        // Same layer blocking
        if ($this->blocks_same_layer && $this->work_layer_id === $layerId) {
            return true;
        }

        return false;
    }

    /**
     * Check if this NCR blocks payment for a chainage.
     */
    public function blocksPaymentAtChainage(float $startM, float $endM): bool
    {
        if (! $this->blocks_payment) {
            return false;
        }

        if (! in_array($this->status, [self::STATUS_OPEN, self::STATUS_IN_PROGRESS])) {
            return false;
        }

        return ! ($this->end_chainage_m < $startM || $this->start_chainage_m > $endM);
    }

    /**
     * Auto-set blocking based on severity.
     */
    public function applyBlockingBySeverity(): void
    {
        $this->blocks_payment = true; // All NCRs block payment by default

        switch ($this->severity) {
            case self::SEVERITY_CRITICAL:
                $this->blocks_all_layers = true;
                $this->blocks_same_layer = true;
                break;
            case self::SEVERITY_HIGH:
                $this->blocks_same_layer = true;
                $this->blocks_all_layers = false;
                break;
            default:
                $this->blocks_same_layer = false;
                $this->blocks_all_layers = false;
        }

        $this->save();
    }

    /**
     * Generate verification hash for audit trail.
     */
    public function generateVerificationHash(): string
    {
        $data = [
            'id' => $this->id,
            'ncr_number' => $this->ncr_number,
            'project_id' => $this->project_id,
            'chainage' => [$this->start_chainage_m, $this->end_chainage_m],
            'severity' => $this->severity,
            'status' => $this->status,
            'detected_date' => $this->detected_date?->toIso8601String(),
        ];

        $hash = hash('sha256', json_encode($data));
        $this->update(['verification_hash' => $hash]);

        return $hash;
    }
}
