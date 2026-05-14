<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Engagement Survey Model
 *
 * Manages employee engagement surveys.
 */
class EngagementSurvey extends TenantModel
{
    protected $fillable = [
        'title',
        'description',
        'survey_type',
        'questions',
        'created_by',
        'department_id',
        'start_date',
        'end_date',
        'is_anonymous',
        'is_mandatory',
        'status',
        'target_respondents',
        'actual_respondents',
        'response_rate',
        'aggregate_results',
    ];

    protected function casts(): array
    {
        return [
            'questions' => 'array',
            'aggregate_results' => 'array',
            'start_date' => 'date',
            'end_date' => 'date',
            'is_anonymous' => 'boolean',
            'is_mandatory' => 'boolean',
            'response_rate' => 'decimal:2',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(EngagementSurveyResponse::class, 'survey_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active' &&
               now()->between($this->start_date, $this->end_date);
    }

    public function calculateResponseRate(): void
    {
        if ($this->target_respondents > 0) {
            $this->response_rate = ($this->actual_respondents / $this->target_respondents) * 100;
            $this->save();
        }
    }
}
