<?php

namespace Aero\Rfi\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Aero\Project\Models\BoqMeasurement;
use Aero\Project\Models\Project;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ChainageProgress Model
 *
 * The "Golden Ledger" - tracks the status of every chainage segment for every layer.
 * This is the PATENTABLE core: "Spatial progress tracking for linear infrastructure".
 *
 * @property int $id
 * @property int $project_id
 * @property int $work_layer_id
 * @property float $start_chainage_m
 * @property float $end_chainage_m
 * @property string $status (not_started, rfi_submitted, inspected, approved, rejected)
 * @property int|null $daily_work_id
 * @property int|null $quality_inspection_id
 * @property int|null $boq_measurement_id
 */
class ChainageProgress extends TenantModel
{
    use HasFactory, SoftDeletes;

    public const STATUS_NOT_STARTED = 'not_started';

    public const STATUS_RFI_SUBMITTED = 'rfi_submitted';

    public const STATUS_INSPECTED = 'inspected';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    /**
     * Human-readable status labels for UI display.
     */
    public static array $statuses = [
        'not_started' => 'Not Started',
        'rfi_submitted' => 'RFI Submitted',
        'inspected' => 'Inspected',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ];

    protected $table = 'chainage_progress';

    protected $fillable = [
        'project_id',
        'work_layer_id',
        'start_chainage_m',
        'end_chainage_m',
        'status',
        'daily_work_id',
        'quality_inspection_id',
        'boq_measurement_id',
        'rfi_submitted_at',
        'inspected_at',
        'approved_at',
        'approved_by_user_id',
    ];

    protected $casts = [
        'start_chainage_m' => 'decimal:3',
        'end_chainage_m' => 'decimal:3',
        'rfi_submitted_at' => 'datetime',
        'inspected_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    // ==================== Relationships ====================

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function workLayer(): BelongsTo
    {
        return $this->belongsTo(WorkLayer::class);
    }

    public function rfi(): BelongsTo
    {
        return $this->belongsTo(Rfi::class);
    }

    public function boqMeasurement(): BelongsTo
    {
        return $this->belongsTo(BoqMeasurement::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    // ==================== Scopes ====================

    public function scopeByProject($query, int $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    public function scopeByLayer($query, int $layerId)
    {
        return $query->where('work_layer_id', $layerId);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', [
            self::STATUS_RFI_SUBMITTED,
            self::STATUS_INSPECTED,
        ]);
    }

    public function scopeInRange($query, float $start, float $end)
    {
        return $query->where('start_chainage_m', '<=', $end)
            ->where('end_chainage_m', '>=', $start);
    }

    // ==================== Accessors ====================

    /**
     * Get the chainage length in meters.
     */
    public function getLengthAttribute(): float
    {
        return $this->end_chainage_m - $this->start_chainage_m;
    }

    /**
     * Get formatted chainage range (e.g., "CH 100+000 - 100+200").
     */
    public function getChainageRangeFormattedAttribute(): string
    {
        $start = $this->formatChainage($this->start_chainage_m);
        $end = $this->formatChainage($this->end_chainage_m);

        return "CH {$start} - {$end}";
    }

    // ==================== Business Logic ====================

    /**
     * Check if this segment can proceed to the next status.
     */
    public function canAdvanceTo(string $newStatus): bool
    {
        $validTransitions = [
            self::STATUS_NOT_STARTED => [self::STATUS_RFI_SUBMITTED],
            self::STATUS_RFI_SUBMITTED => [self::STATUS_INSPECTED, self::STATUS_REJECTED],
            self::STATUS_INSPECTED => [self::STATUS_APPROVED, self::STATUS_REJECTED],
            self::STATUS_REJECTED => [self::STATUS_RFI_SUBMITTED], // Resubmission
        ];

        return in_array($newStatus, $validTransitions[$this->status] ?? []);
    }

    /**
     * Advance the status with validation.
     */
    public function advanceTo(string $newStatus, ?int $userId = null): bool
    {
        if (! $this->canAdvanceTo($newStatus)) {
            return false;
        }

        $this->status = $newStatus;

        if ($newStatus === self::STATUS_APPROVED) {
            $this->approved_at = now();
            $this->approved_by_user_id = $userId;
        } elseif ($newStatus === self::STATUS_INSPECTED) {
            $this->inspected_at = now();
        } elseif ($newStatus === self::STATUS_RFI_SUBMITTED) {
            $this->rfi_submitted_at = now();
        }

        return $this->save();
    }

    /**
     * Format chainage value as KM+meters (e.g., 15200 -> "15+200").
     */
    protected function formatChainage(float $meters): string
    {
        $km = floor($meters / 1000);
        $m = $meters - ($km * 1000);

        return sprintf('%d+%03d', $km, $m);
    }
}
