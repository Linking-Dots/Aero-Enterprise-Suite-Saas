<?php

declare(strict_types=1);

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Aero\HRM\Database\Factories\TrainingSessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingSession extends TenantModel
{
    use HasFactory;

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'training_sessions';

    protected $fillable = [
        'course_id',
        'starts_at',
        'ends_at',
        'location',
        'meeting_link',
        'capacity',
        'instructor_id',
        'status',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'capacity' => 'int',
        ];
    }

    protected static function newFactory(): TrainingSessionFactory
    {
        return TrainingSessionFactory::new();
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(TrainingCourse::class, 'course_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(TrainingEnrollment::class, 'session_id');
    }

    public function enrolledCount(): int
    {
        return $this->enrollments()
            ->whereNotIn('status', [
                TrainingEnrollment::STATUS_WAITLISTED,
                TrainingEnrollment::STATUS_CANCELLED,
            ])
            ->count();
    }

    public function hasCapacity(): bool
    {
        return $this->enrolledCount() < $this->capacity;
    }
}
