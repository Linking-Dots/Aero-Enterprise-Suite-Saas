<?php

namespace Aero\Rfi\Models;

use Aero\Project\Models\Project;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * WorkLayer Model
 *
 * Defines the layers/activities that must be completed in sequence.
 * Example: "Embankment Layer 1" must be approved before "Embankment Layer 2".
 *
 * This is a PATENTABLE component: "Sequential work validation using layer dependencies".
 *
 * @property int $id
 * @property int|null $project_id
 * @property string $code (e.g., "EMB-L1")
 * @property string $name (e.g., "Embankment Layer 1")
 * @property string $work_type (Embankment, Structure, Pavement)
 * @property int $sequence_order
 * @property int|null $prerequisite_layer_id
 * @property array|null $required_quality_checks
 */
class WorkLayer extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id',
        'code',
        'name',
        'work_type',
        'sequence_order',
        'prerequisite_layer_id',
        'required_quality_checks',
        'is_active',
    ];

    protected $casts = [
        'sequence_order' => 'integer',
        'required_quality_checks' => 'array',
        'is_active' => 'boolean',
    ];

    // ==================== Relationships ====================

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function prerequisiteLayer(): BelongsTo
    {
        return $this->belongsTo(WorkLayer::class, 'prerequisite_layer_id');
    }

    public function dependentLayers(): HasMany
    {
        return $this->hasMany(WorkLayer::class, 'prerequisite_layer_id');
    }

    public function chainageProgress(): HasMany
    {
        return $this->hasMany(ChainageProgress::class);
    }

    // ==================== Scopes ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByWorkType($query, string $workType)
    {
        return $query->where('work_type', $workType);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sequence_order');
    }

    // ==================== Business Logic ====================

    /**
     * Check if the prerequisite layer is approved at the given chainage.
     */
    public function isPrerequisiteSatisfiedAt(float $startChainage, float $endChainage): bool
    {
        if (! $this->prerequisite_layer_id) {
            return true; // No prerequisite, can proceed
        }

        // Check if prerequisite layer has approved progress covering this chainage
        return ChainageProgress::query()
            ->where('work_layer_id', $this->prerequisite_layer_id)
            ->where('status', 'approved')
            ->where('start_chainage_m', '<=', $startChainage)
            ->where('end_chainage_m', '>=', $endChainage)
            ->exists();
    }

    /**
     * Get the gap analysis - chainage ranges where this layer is not yet approved.
     *
     * @return array Array of gap ranges [{start: x, end: y}, ...]
     */
    public function getGapsInRange(float $rangeStart, float $rangeEnd): array
    {
        $approved = ChainageProgress::query()
            ->where('work_layer_id', $this->id)
            ->where('status', 'approved')
            ->where('start_chainage_m', '>=', $rangeStart)
            ->where('end_chainage_m', '<=', $rangeEnd)
            ->orderBy('start_chainage_m')
            ->get(['start_chainage_m', 'end_chainage_m']);

        $gaps = [];
        $currentPos = $rangeStart;

        foreach ($approved as $segment) {
            if ($segment->start_chainage_m > $currentPos) {
                $gaps[] = [
                    'start' => $currentPos,
                    'end' => $segment->start_chainage_m,
                ];
            }
            $currentPos = max($currentPos, $segment->end_chainage_m);
        }

        if ($currentPos < $rangeEnd) {
            $gaps[] = [
                'start' => $currentPos,
                'end' => $rangeEnd,
            ];
        }

        return $gaps;
    }
}
