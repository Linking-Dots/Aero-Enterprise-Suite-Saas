<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class TrainingAssignment extends TenantModel implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'training_id',
        'title',
        'description',
        'due_date',
        'points',
        'is_required',
        'instructions',
        'created_by',
        'passing_score',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'points' => 'integer',
        'is_required' => 'boolean',
        'passing_score' => 'integer',
    ];

    /**
     * Get the training this assignment belongs to.
     */
    public function training()
    {
        return $this->belongsTo(Training::class);
    }

    /**
     * Get the user who created the assignment.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the submissions for this assignment.
     */
    public function submissions()
    {
        return $this->hasMany(TrainingAssignmentSubmission::class);
    }

    /**
     * Check if the assignment is overdue.
     */
    public function isOverdue()
    {
        return $this->due_date !== null && now()->gt($this->due_date);
    }

    /**
     * Check if the assignment is due soon (within 24 hours).
     */
    public function isDueSoon()
    {
        if ($this->due_date === null) {
            return false;
        }

        $hoursUntilDue = now()->diffInHours($this->due_date, false);

        return $hoursUntilDue >= 0 && $hoursUntilDue <= 24;
    }

    /**
     * Get the user's submission for this assignment.
     */
    public function getUserSubmission(User $user)
    {
        return $this->submissions()->where('user_id', $user->id)->latest()->first();
    }

    /**
     * Check if a user has passed this assignment.
     */
    public function userHasPassed(User $user)
    {
        $submission = $this->getUserSubmission($user);

        if (! $submission || $submission->score === null) {
            return false;
        }

        return $submission->score >= $this->passing_score;
    }
}
