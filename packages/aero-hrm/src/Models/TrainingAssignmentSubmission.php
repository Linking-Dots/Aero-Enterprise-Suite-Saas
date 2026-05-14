<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use App\Models\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class TrainingAssignmentSubmission extends TenantModel implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'training_assignment_id',
        'user_id',
        'submission_text',
        'submitted_at',
        'score',
        'feedback',
        'graded_by',
        'graded_at',
        'status',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'graded_at' => 'datetime',
        'score' => 'float',
    ];

    /**
     * Get the assignment this submission is for.
     */
    public function assignment()
    {
        return $this->belongsTo(TrainingAssignment::class, 'training_assignment_id');
    }

    /**
     * Get the user who submitted the assignment.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who graded the submission.
     */
    public function grader()
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    /**
     * Check if the submission has been graded.
     */
    public function isGraded()
    {
        return $this->graded_at !== null;
    }

    /**
     * Check if the submission was on time.
     */
    public function isOnTime()
    {
        return $this->submitted_at <= $this->assignment->due_date || $this->assignment->due_date === null;
    }

    /**
     * Check if the submission passed.
     */
    public function hasPassed()
    {
        if ($this->score === null || ! $this->isGraded()) {
            return false;
        }

        return $this->score >= $this->assignment->passing_score;
    }

    /**
     * Get the time remaining until the due date when submitted.
     */
    public function getTimeSubmittedBeforeDue()
    {
        if ($this->assignment->due_date === null) {
            return null;
        }

        return $this->submitted_at->diffForHumans($this->assignment->due_date, ['syntax' => CarbonInterface::DIFF_ABSOLUTE]);
    }
}
