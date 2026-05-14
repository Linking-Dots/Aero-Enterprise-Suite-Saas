<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pulse Survey Response Model
 *
 * Individual responses to pulse surveys.
 */
class PulseSurveyResponse extends TenantModel
{
    use HasFactory;

    protected $fillable = [
        'pulse_survey_id',
        'employee_id',
        'responses',
        'overall_score',
        'sentiment',
        'comments',
        'submitted_at',
        'is_complete',
    ];

    protected function casts(): array
    {
        return [
            'responses' => 'array',
            'overall_score' => 'decimal:2',
            'submitted_at' => 'datetime',
            'is_complete' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Sentiment classifications.
     */
    public const SENTIMENT_POSITIVE = 'positive';

    public const SENTIMENT_NEUTRAL = 'neutral';

    public const SENTIMENT_NEGATIVE = 'negative';

    public function pulseSurvey(): BelongsTo
    {
        return $this->belongsTo(PulseSurvey::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Calculate overall score from responses.
     */
    public function calculateOverallScore(): float
    {
        if (empty($this->responses)) {
            return 0;
        }

        $scores = array_filter($this->responses, fn ($v) => is_numeric($v));

        if (empty($scores)) {
            return 0;
        }

        return round(array_sum($scores) / count($scores), 2);
    }

    /**
     * Determine sentiment from score.
     */
    public function determineSentiment(): string
    {
        $score = $this->overall_score ?? $this->calculateOverallScore();

        if ($score >= 4) {
            return self::SENTIMENT_POSITIVE;
        }

        if ($score >= 3) {
            return self::SENTIMENT_NEUTRAL;
        }

        return self::SENTIMENT_NEGATIVE;
    }

    /**
     * Boot method.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saving(function ($model) {
            if ($model->is_complete && ! $model->submitted_at) {
                $model->submitted_at = now();
            }

            if (! empty($model->responses)) {
                $model->overall_score = $model->calculateOverallScore();
                $model->sentiment = $model->determineSentiment();
            }
        });
    }
}
