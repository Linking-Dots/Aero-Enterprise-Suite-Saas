<?php

namespace Aero\Education\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Assignment extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'education_assignments';

    protected $fillable = [
        'course_section_id', 'assignment_name', 'description', 'assignment_type',
        'due_date', 'available_date', 'points_possible', 'weight_percentage',
        'instructions', 'rubric', 'submission_type', 'allow_late_submission',
        'late_penalty_per_day', 'max_late_days', 'is_extra_credit',
        'group_assignment', 'peer_review', 'created_by',
    ];

    protected $casts = [
        'course_section_id' => 'integer',
        'due_date' => 'datetime',
        'available_date' => 'datetime',
        'points_possible' => 'decimal:2',
        'weight_percentage' => 'decimal:2',
        'rubric' => 'json',
        'allow_late_submission' => 'boolean',
        'late_penalty_per_day' => 'decimal:2',
        'max_late_days' => 'integer',
        'is_extra_credit' => 'boolean',
        'group_assignment' => 'boolean',
        'peer_review' => 'boolean',
        'created_by' => 'integer',
    ];

    const TYPE_HOMEWORK = 'homework';

    const TYPE_QUIZ = 'quiz';

    const TYPE_EXAM = 'exam';

    const TYPE_PROJECT = 'project';

    const TYPE_PAPER = 'paper';

    const TYPE_PRESENTATION = 'presentation';

    const TYPE_LAB = 'lab';

    const TYPE_DISCUSSION = 'discussion';

    const TYPE_PORTFOLIO = 'portfolio';

    const TYPE_CASE_STUDY = 'case_study';

    const SUBMISSION_ONLINE = 'online';

    const SUBMISSION_PAPER = 'paper';

    const SUBMISSION_BOTH = 'both';

    const SUBMISSION_NO_SUBMISSION = 'no_submission';

    public function courseSection()
    {
        return $this->belongsTo(CourseSection::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function grades()
    {
        return $this->hasMany(Grade::class);
    }

    public function submissions()
    {
        return $this->hasMany(AssignmentSubmission::class);
    }

    public function getSubmissionCountAttribute()
    {
        return $this->submissions()->count();
    }

    public function getGradedCountAttribute()
    {
        return $this->grades()->whereNotNull('graded_date')->count();
    }

    public function getAverageScoreAttribute()
    {
        $grades = $this->grades();

        return $grades->count() > 0 ? round($grades->avg('percentage'), 2) : null;
    }

    public function isDue()
    {
        return $this->due_date && $this->due_date <= now();
    }

    public function isAvailable()
    {
        return ! $this->available_date || $this->available_date <= now();
    }

    public function isOverdue()
    {
        return $this->due_date && $this->due_date < now();
    }

    public function canAcceptLateSubmission()
    {
        if (! $this->allow_late_submission || ! $this->isOverdue()) {
            return false;
        }

        if ($this->max_late_days) {
            $daysPastDue = now()->diffInDays($this->due_date);

            return $daysPastDue <= $this->max_late_days;
        }

        return true;
    }

    public function calculateLatePenalty($submissionDate = null)
    {
        $submissionDate = $submissionDate ?: now();

        if (! $this->isOverdue() || ! $this->late_penalty_per_day) {
            return 0;
        }

        $daysPastDue = $submissionDate->diffInDays($this->due_date);

        if ($this->max_late_days && $daysPastDue > $this->max_late_days) {
            return $this->points_possible; // Full penalty if beyond max late days
        }

        return min($this->late_penalty_per_day * $daysPastDue, $this->points_possible);
    }

    public function getDaysUntilDueAttribute()
    {
        if (! $this->due_date) {
            return null;
        }

        $days = now()->diffInDays($this->due_date, false);

        return $days >= 0 ? $days : null;
    }

    public function getCompletionRateAttribute()
    {
        $totalStudents = $this->courseSection->current_enrollment;
        $submittedCount = $this->getSubmissionCountAttribute();

        return $totalStudents > 0 ? round(($submittedCount / $totalStudents) * 100, 2) : 0;
    }

    public function scopeDue($query)
    {
        return $query->where('due_date', '<=', now());
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now());
    }

    public function scopeUpcoming($query, $days = 7)
    {
        return $query->whereBetween('due_date', [now(), now()->addDays($days)]);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('assignment_type', $type);
    }

    public function scopeExtraCredit($query)
    {
        return $query->where('is_extra_credit', true);
    }

    public function scopeAvailable($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('available_date')
                ->orWhere('available_date', '<=', now());
        });
    }
}
