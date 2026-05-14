<?php

namespace Aero\Quality\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Aero\Project\Models\Project;
use Aero\Rfi\Models\Rfi;
use Aero\Rfi\Models\WorkLayer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * QualityInspection Model
 *
 * Enhanced for RFI integration with geo-fencing and chainage indexing.
 * PATENTABLE: "Geo-verified quality inspection with spatial progress tracking"
 *
 * @property int $id
 * @property int|null $daily_work_id
 * @property int|null $project_id
 * @property float|null $start_chainage_m
 * @property float|null $end_chainage_m
 * @property float|null $inspector_latitude
 * @property float|null $inspector_longitude
 * @property bool $geo_verified
 * @property string|null $verification_hash
 */
class QualityInspection extends TenantModel
{
    use SoftDeletes;

    public const RESULT_PASS = 'pass';

    public const RESULT_FAIL = 'fail';

    public const RESULT_CONDITIONAL = 'conditional';

    public const RESULT_PENDING = 'pending';

    protected $fillable = [
        'inspection_number',
        'inspection_type',
        'product_id',
        'batch_number',
        'inspector_id',
        'inspection_date',
        'status',
        'result',
        'notes',
        'checklist_data',
        // New RFI integration fields
        'daily_work_id',
        'project_id',
        'start_chainage_m',
        'end_chainage_m',
        'inspector_latitude',
        'inspector_longitude',
        'geo_accuracy_m',
        'geo_verified',
        'verification_hash',
        'hash_generated_at',
        'work_layer_id',
        'checklist_results',
        'pass_count',
        'fail_count',
        'compliance_percentage',
    ];

    protected $casts = [
        'inspection_date' => 'datetime',
        'checklist_data' => 'array',
        'checklist_results' => 'array',
        'start_chainage_m' => 'decimal:3',
        'end_chainage_m' => 'decimal:3',
        'inspector_latitude' => 'decimal:8',
        'inspector_longitude' => 'decimal:8',
        'geo_accuracy_m' => 'decimal:2',
        'geo_verified' => 'boolean',
        'hash_generated_at' => 'datetime',
        'pass_count' => 'integer',
        'fail_count' => 'integer',
        'compliance_percentage' => 'decimal:2',
    ];

    // ==================== Relationships ====================

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

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    public function workLayer(): BelongsTo
    {
        return $this->belongsTo(WorkLayer::class);
    }

    // ==================== Scopes ====================

    public function scopeByProject($query, int $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    public function scopeInChainageRange($query, float $start, float $end)
    {
        return $query->where('start_chainage_m', '<=', $end)
            ->where('end_chainage_m', '>=', $start);
    }

    public function scopePassed($query)
    {
        return $query->where('result', self::RESULT_PASS);
    }

    public function scopeFailed($query)
    {
        return $query->where('result', self::RESULT_FAIL);
    }

    public function scopeGeoVerified($query)
    {
        return $query->where('geo_verified', true);
    }

    // ==================== Business Logic ====================

    /**
     * Generate verification hash for immutability.
     * PATENTABLE: "Tamper-evident inspection records"
     */
    public function generateVerificationHash(): string
    {
        $data = [
            'id' => $this->id,
            'inspection_number' => $this->inspection_number,
            'daily_work_id' => $this->daily_work_id,
            'result' => $this->result,
            'checklist_results' => $this->checklist_results,
            'inspector_id' => $this->inspector_id,
            'inspection_date' => $this->inspection_date?->toIso8601String(),
            'geo_coords' => [
                'lat' => $this->inspector_latitude,
                'lng' => $this->inspector_longitude,
            ],
        ];

        $hash = hash('sha256', json_encode($data));

        $this->update([
            'verification_hash' => $hash,
            'hash_generated_at' => now(),
        ]);

        return $hash;
    }

    /**
     * Verify inspector is within allowed radius of work site.
     * PATENTABLE: "Geo-fenced inspection validation"
     */
    public function verifyGeoLocation(float $siteLat, float $siteLng, float $allowedRadiusM = 100): bool
    {
        if (! $this->inspector_latitude || ! $this->inspector_longitude) {
            return false;
        }

        $distance = $this->calculateDistance(
            $this->inspector_latitude,
            $this->inspector_longitude,
            $siteLat,
            $siteLng
        );

        $verified = $distance <= $allowedRadiusM;

        $this->update(['geo_verified' => $verified]);

        return $verified;
    }

    /**
     * Calculate compliance percentage from checklist results.
     */
    public function calculateCompliance(): void
    {
        if (empty($this->checklist_results)) {
            return;
        }

        $pass = 0;
        $fail = 0;

        foreach ($this->checklist_results as $item) {
            if (($item['result'] ?? '') === 'pass') {
                $pass++;
            } else {
                $fail++;
            }
        }

        $total = $pass + $fail;
        $percentage = $total > 0 ? ($pass / $total) * 100 : 0;

        $this->update([
            'pass_count' => $pass,
            'fail_count' => $fail,
            'compliance_percentage' => $percentage,
        ]);
    }

    /**
     * Calculate distance between two GPS coordinates in meters.
     */
    protected function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // meters

        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLon = deg2rad($lon2 - $lon1);

        $a = sin($deltaLat / 2) ** 2 +
            cos($lat1Rad) * cos($lat2Rad) * sin($deltaLon / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
