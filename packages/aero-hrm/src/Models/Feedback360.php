<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Feedback 360 Model
 *
 * 360-degree feedback review for employees.
 */
class Feedback360 extends TenantModel
{
    use HasFactory;

    protected $table = 'feedback_360_reviews';

    protected $fillable = [
        'employee_id',
        'review_period_id',
        'title',
        'description',
        'competencies_to_evaluate',
        'questions',
        'self_assessment_required',
        'manager_assessment_required',
        'peer_assessment_required',
        'direct_report_assessment_required',
        'external_assessment_required',
        'min_peer_reviewers',
        'max_peer_reviewers',
        'is_anonymous',
        'start_date',
        'end_date',
        'status',
        'overall_score',
        'summary_report',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'competencies_to_evaluate' => 'array',
            'questions' => 'array',
            'self_assessment_required' => 'boolean',
            'manager_assessment_required' => 'boolean',
            'peer_assessment_required' => 'boolean',
            'direct_report_assessment_required' => 'boolean',
            'external_assessment_required' => 'boolean',
            'is_anonymous' => 'boolean',
            'start_date' => 'date',
            'end_date' => 'date',
            'overall_score' => 'decimal:2',
            'summary_report' => 'array',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function reviewPeriod(): BelongsTo
    {
        return $this->belongsTo(PerformanceReviewTemplate::class, 'review_period_id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(Feedback360Response::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get responses by type
     */
    public function getResponsesByType(string $type)
    {
        return $this->responses()->where('reviewer_type', $type)->get();
    }

    /**
     * Calculate completion percentage
     */
    public function getCompletionPercentage(): float
    {
        $total = $this->responses()->count();
        $completed = $this->responses()->whereNotNull('submitted_at')->count();

        return $total > 0 ? ($completed / $total) * 100 : 0;
    }
}
