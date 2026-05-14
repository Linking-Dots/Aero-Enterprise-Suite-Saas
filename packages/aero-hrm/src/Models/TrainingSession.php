<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrainingSession extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'training_id',
        'title',
        'description',
        'start_time',
        'end_time',
        'location',
        'is_online',
        'meeting_link',
        'instructor_id',
        'status',
        'max_participants',
        'notes',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'is_online' => 'boolean',
    ];

    /**
     * Get the training that this session belongs to.
     */
    public function training()
    {
        return $this->belongsTo(Training::class);
    }

    /**
     * Get the instructor for the session.
     */
    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    /**
     * Get the participants for this session.
     */
    public function participants()
    {
        return $this->belongsToMany(User::class, 'training_session_attendances')
            ->withPivot('attendance_status', 'check_in_time', 'check_out_time', 'notes')
            ->withTimestamps();
    }

    /**
     * Check if the session is upcoming.
     */
    public function isUpcoming()
    {
        return now()->lt($this->start_time);
    }

    /**
     * Check if the session is in progress.
     */
    public function isInProgress()
    {
        $now = now();

        return $now->gte($this->start_time) && $now->lte($this->end_time);
    }

    /**
     * Check if the session has ended.
     */
    public function hasEnded()
    {
        return now()->gt($this->end_time);
    }

    /**
     * Get the duration in minutes.
     */
    public function getDurationInMinutes()
    {
        return $this->start_time->diffInMinutes($this->end_time);
    }
}
