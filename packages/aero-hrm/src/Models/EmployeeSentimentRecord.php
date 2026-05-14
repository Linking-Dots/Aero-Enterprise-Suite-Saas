<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Employee Sentiment Record Model
 *
 * Stores sentiment analysis results from various sources.
 * Supports continuous engagement monitoring.
 */
class EmployeeSentimentRecord extends TenantModel
{
    protected $fillable = [
        'employee_id',
        'source_type',
        'source_reference',
        'overall_sentiment',
        'job_satisfaction',
        'manager_satisfaction',
        'team_satisfaction',
        'workload_satisfaction',
        'growth_satisfaction',
        'compensation_satisfaction',
        'detected_emotions',
        'key_themes',
        'positive_mentions',
        'negative_mentions',
        'processed_text',
        'word_count',
        'requires_followup',
        'followed_up_by',
        'followed_up_at',
        'recorded_date',
    ];

    protected function casts(): array
    {
        return [
            'overall_sentiment' => 'decimal:3',
            'job_satisfaction' => 'decimal:3',
            'manager_satisfaction' => 'decimal:3',
            'team_satisfaction' => 'decimal:3',
            'workload_satisfaction' => 'decimal:3',
            'growth_satisfaction' => 'decimal:3',
            'compensation_satisfaction' => 'decimal:3',
            'detected_emotions' => 'array',
            'key_themes' => 'array',
            'positive_mentions' => 'array',
            'negative_mentions' => 'array',
            'requires_followup' => 'boolean',
            'followed_up_at' => 'datetime',
            'recorded_date' => 'date',
        ];
    }

    public const SOURCE_TYPES = [
        'survey_response' => 'Engagement Survey',
        'feedback_submission' => 'Feedback Form',
        'pulse_check' => 'Pulse Survey',
        'exit_interview' => 'Exit Interview',
        'one_on_one' => '1:1 Meeting',
        'performance_review' => 'Performance Review',
        'self_assessment' => 'Self Assessment',
        'peer_feedback' => 'Peer Feedback',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function followedUpBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'followed_up_by');
    }

    public function isPositive(): bool
    {
        return $this->overall_sentiment > 0.2;
    }

    public function isNegative(): bool
    {
        return $this->overall_sentiment < -0.2;
    }

    public function isNeutral(): bool
    {
        return $this->overall_sentiment >= -0.2 && $this->overall_sentiment <= 0.2;
    }

    public function getSentimentLabel(): string
    {
        if ($this->isPositive()) {
            return 'Positive';
        } elseif ($this->isNegative()) {
            return 'Negative';
        }

        return 'Neutral';
    }

    public function getLowestSatisfactionArea(): ?string
    {
        $areas = [
            'job_satisfaction' => $this->job_satisfaction,
            'manager_satisfaction' => $this->manager_satisfaction,
            'team_satisfaction' => $this->team_satisfaction,
            'workload_satisfaction' => $this->workload_satisfaction,
            'growth_satisfaction' => $this->growth_satisfaction,
            'compensation_satisfaction' => $this->compensation_satisfaction,
        ];

        $areas = array_filter($areas, fn ($v) => $v !== null);

        if (empty($areas)) {
            return null;
        }

        return array_search(min($areas), $areas);
    }
}
