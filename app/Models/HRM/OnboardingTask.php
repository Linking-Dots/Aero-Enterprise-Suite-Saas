<?php

namespace App\Models\HRM;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OnboardingTask extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress'; // normalized
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_NOT_APPLICABLE = 'not-applicable';

    protected $fillable = [
        'onboarding_id',
        'task',
        'description',
        'due_date',
        'completed_date',
        'status',
        'assigned_to',
        'notes',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_date' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function (self $model) {
            if (empty($model->status)) {
                $model->status = self::STATUS_PENDING;
            }
        });
    }

    /**
     * Get the onboarding process that this task belongs to.
     */
    public function onboarding(): BelongsTo
    {
        return $this->belongsTo(Onboarding::class);
    }

    /**
     * Get the user assigned to this task.
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
