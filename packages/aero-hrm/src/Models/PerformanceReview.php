<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use App\Models\PerformanceCompetencyScore;
use App\Models\PerformanceGoal;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Performance Review Model
 *
 * @property int $id
 * @property int $employee_id User ID of employee being reviewed
 * @property int $reviewer_id User ID of reviewer
 * @property \Carbon\Carbon|null $review_period_start
 * @property \Carbon\Carbon|null $review_period_end
 * @property \Carbon\Carbon|null $review_date
 * @property string|null $status
 * @property float|null $overall_rating
 * @property string|null $goals_achieved
 * @property string|null $strengths
 * @property string|null $areas_for_improvement
 * @property string|null $comments
 * @property \Carbon\Carbon|null $acknowledgment_date
 * @property string|null $employee_comments
 * @property \Carbon\Carbon|null $next_review_date
 * @property int|null $department_id
 * @property int|null $template_id
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property-read User $employee
 * @property-read User $reviewer
 * @property-read string $review_period
 */
class PerformanceReview extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'reviewer_id',
        'template_id',
        'department_id',

        // Column names match the consolidated migration
        'review_period',
        'review_start_date',
        'review_end_date',
        'due_date',

        // Assessment JSON blobs
        'self_assessment',
        'manager_assessment',
        'peer_feedback',

        'overall_rating',
        'strengths',
        'areas_of_improvement',
        'goals_for_next_period',
        'training_recommendations',
        'comments',

        'status',
        'completed_at',
        'acknowledged_at',
    ];

    protected $casts = [
        'review_start_date'   => 'date',
        'review_end_date'     => 'date',
        'due_date'            => 'date',
        'completed_at'        => 'datetime',
        'acknowledged_at'     => 'datetime',
        'overall_rating'      => 'float',
        'self_assessment'     => 'array',
        'manager_assessment'  => 'array',
        'peer_feedback'       => 'array',
    ];

    /**
     * Get the employee being reviewed.
     */
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    /**
     * Get the reviewer.
     */
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /**
     * Get the department.
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the review template.
     */
    public function template()
    {
        return $this->belongsTo(PerformanceReviewTemplate::class, 'template_id');
    }

    /**
     * Get the competency scores for this review.
     */
    public function competencyScores()
    {
        return $this->hasMany(PerformanceCompetencyScore::class, 'review_id');
    }

    /**
     * Get the goals for this review.
     */
    public function goals()
    {
        return $this->hasMany(PerformanceGoal::class, 'review_id');
    }

    /**
     * Calculate the time since review in days.
     */
    public function daysSinceReview()
    {
        return $this->review_date ? $this->review_date->diffInDays(now()) : null;
    }

    /**
     * Get the status text.
     */
    public function getStatusTextAttribute()
    {
        $statusMap = [
            'draft' => 'Draft',
            'pending_employee' => 'Pending Employee Review',
            'pending_manager' => 'Pending Manager Review',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];

        return $statusMap[$this->status] ?? $this->status;
    }

    /**
     * Get the rating text.
     */
    public function getRatingTextAttribute()
    {
        if ($this->overall_rating >= 4.5) {
            return 'Exceptional';
        }
        if ($this->overall_rating >= 3.5) {
            return 'Exceeds Expectations';
        }
        if ($this->overall_rating >= 2.5) {
            return 'Meets Expectations';
        }
        if ($this->overall_rating >= 1.5) {
            return 'Needs Improvement';
        }

        return 'Unsatisfactory';
    }
}
