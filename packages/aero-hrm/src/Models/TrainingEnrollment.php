<?php

namespace Aero\HRM\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrainingEnrollment extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'training_id',
        'user_id',
        'status',
        'enrollment_date',
        'completion_date',
        'score',
        'feedback',
        'certificate_issued',
        'approved_by',
        'rejected_reason',
    ];

    protected $casts = [
        'enrollment_date' => 'date',
        'completion_date' => 'date',
        'score' => 'float',
        'certificate_issued' => 'boolean',
    ];

    /**
     * Get the training that the user is enrolled in.
     */
    public function training()
    {
        return $this->belongsTo(Training::class);
    }

    /**
     * Get the user who is enrolled in the training.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who approved the enrollment.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Check if the user has completed the training.
     */
    public function isCompleted()
    {
        return $this->status === 'completed' && $this->completion_date !== null;
    }

    /**
     * Check if the user has a passing score.
     */
    public function hasPassed()
    {
        // Consider a score of 70% or higher as passing
        return $this->score !== null && $this->score >= 70;
    }
}
