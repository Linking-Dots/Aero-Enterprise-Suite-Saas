<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Engagement Survey Response Model
 */
class EngagementSurveyResponse extends TenantModel
{
    protected $fillable = [
        'survey_id',
        'employee_id',
        'anonymous_token',
        'answers',
        'overall_score',
        'completion_time_seconds',
    ];

    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'overall_score' => 'decimal:2',
        ];
    }

    public function survey(): BelongsTo
    {
        return $this->belongsTo(EngagementSurvey::class, 'survey_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
