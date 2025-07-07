<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Training extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    protected $fillable = [
        'title',
        'description',
        'category_id',
        'type',
        'status',
        'start_date',
        'end_date',
        'duration',
        'duration_unit',
        'location',
        'is_online',
        'instructor_id',
        'max_participants',
        'cost',
        'currency',
        'prerequisites',
        'learning_outcomes',
        'certification',
        'created_by',
        'approval_required',
        'department_id'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_online' => 'boolean',
        'approval_required' => 'boolean',
        'prerequisites' => 'array',
        'learning_outcomes' => 'array',
        'cost' => 'float',
    ];

    /**
     * Get the category of the training.
     */
    public function category()
    {
        return $this->belongsTo(TrainingCategory::class, 'category_id');
    }

    /**
     * Get the instructor for the training.
     */
    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    /**
     * Get the user who created the training.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the department associated with the training.
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the enrollments for this training.
     */
    public function enrollments()
    {
        return $this->hasMany(TrainingEnrollment::class);
    }

    /**
     * Get the participants of the training.
     */
    public function participants()
    {
        return $this->belongsToMany(User::class, 'training_enrollments')
            ->withPivot('status', 'enrollment_date', 'completion_date', 'score', 'feedback', 'certificate_issued')
            ->withTimestamps();
    }

    /**
     * Get the sessions for this training.
     */
    public function sessions()
    {
        return $this->hasMany(TrainingSession::class);
    }

    /**
     * Get the materials for this training.
     */
    public function materials()
    {
        return $this->hasMany(TrainingMaterial::class);
    }

    /**
     * Get the assignments for this training.
     */
    public function assignments()
    {
        return $this->hasMany(TrainingAssignment::class);
    }

    /**
     * Get the feedback for this training.
     */
    public function feedback()
    {
        return $this->hasMany(TrainingFeedback::class);
    }

    /**
     * Check if the training is currently active.
     */
    public function isActive()
    {
        $now = now();
        return $this->status === 'active' &&
            ($now->between($this->start_date, $this->end_date) ||
                ($now->gte($this->start_date) && $this->end_date === null));
    }

    /**
     * Check if the training is full.
     */
    public function isFull()
    {
        return $this->max_participants !== null &&
            $this->enrollments()->where('status', 'approved')->count() >= $this->max_participants;
    }

    /**
     * Get available spots.
     */
    public function availableSpots()
    {
        if ($this->max_participants === null) {
            return null; // Unlimited
        }

        return max(0, $this->max_participants - $this->enrollments()->where('status', 'approved')->count());
    }
}
