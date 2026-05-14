<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Job Interview Model
 *
 * @property int $id
 * @property int $application_id
 * @property string $title
 * @property string $interview_type
 * @property \Carbon\Carbon $scheduled_at
 * @property int $duration_minutes
 * @property string|null $location
 * @property string|null $meeting_link
 * @property string|null $notes
 * @property string $status
 * @property int|null $scheduled_by
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read JobApplication $application
 * @property-read User|null $scheduledBy
 * @property-read \Illuminate\Database\Eloquent\Collection|User[] $interviewers
 */
class JobInterview extends TenantModel
{
    use HasFactory;

    protected $table = 'job_interviews';

    protected $fillable = [
        'application_id',
        'title',
        'interview_type',
        'scheduled_at',
        'duration_minutes',
        'location',
        'meeting_link',
        'notes',
        'status',
        'scheduled_by',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'duration_minutes' => 'integer',
    ];

    /**
     * Get the job application this interview is for.
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class, 'application_id');
    }

    /**
     * Get the user who scheduled the interview.
     */
    public function scheduledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scheduled_by');
    }

    /**
     * Get the interviewers for this interview.
     */
    public function interviewers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'job_interview_interviewers', 'interview_id', 'interviewer_id')
            ->withTimestamps();
    }

    /**
     * Get the feedback for this interview.
     */
    public function feedback(): HasMany
    {
        return $this->hasMany(JobInterviewFeedback::class, 'interview_id');
    }

    /**
     * Get status color for UI display.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'scheduled' => 'primary',
            'completed' => 'success',
            'cancelled' => 'danger',
            'rescheduled' => 'warning',
            'no_show' => 'secondary',
            default => 'default',
        };
    }
}
