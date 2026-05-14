<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Feedback 360 Response Model
 *
 * Individual reviewer responses in a 360 feedback.
 */
class Feedback360Response extends TenantModel
{
    use HasFactory;

    protected $table = 'feedback_360_responses';

    protected $fillable = [
        'feedback_360_id',
        'reviewer_id',
        'reviewer_type',
        'relationship_to_employee',
        'competency_ratings',
        'question_responses',
        'strengths',
        'areas_for_improvement',
        'overall_rating',
        'comments',
        'invited_at',
        'started_at',
        'submitted_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'competency_ratings' => 'array',
            'question_responses' => 'array',
            'strengths' => 'array',
            'areas_for_improvement' => 'array',
            'overall_rating' => 'decimal:2',
            'invited_at' => 'datetime',
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    public function feedback360(): BelongsTo
    {
        return $this->belongsTo(Feedback360::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /**
     * Check if submitted
     */
    public function isSubmitted(): bool
    {
        return $this->submitted_at !== null;
    }

    /**
     * Calculate average competency rating
     */
    public function getAverageCompetencyRating(): float
    {
        if (empty($this->competency_ratings)) {
            return 0;
        }

        $ratings = array_values($this->competency_ratings);

        return count($ratings) > 0 ? array_sum($ratings) / count($ratings) : 0;
    }
}
