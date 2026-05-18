<?php

declare(strict_types=1);

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Aero\Core\Models\User;
use Aero\HRM\Database\Factories\Feedback360ResponseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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

    public function isSubmitted(): bool
    {
        return $this->submitted_at !== null;
    }

    public function getAverageCompetencyRating(): float
    {
        if (empty($this->competency_ratings)) {
            return 0.0;
        }

        $ratings = array_values($this->competency_ratings);

        return count($ratings) > 0 ? array_sum($ratings) / count($ratings) : 0.0;
    }

    protected static function newFactory(): Feedback360ResponseFactory
    {
        return Feedback360ResponseFactory::new();
    }
}
