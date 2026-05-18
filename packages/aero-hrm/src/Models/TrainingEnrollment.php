<?php

declare(strict_types=1);

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Aero\HRM\Database\Factories\TrainingEnrollmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TrainingEnrollment extends TenantModel
{
    use HasFactory;

    public const STATUS_ENROLLED = 'enrolled';

    public const STATUS_WAITLISTED = 'waitlisted';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_ATTENDED = 'attended';

    public const STATUS_NO_SHOW = 'no_show';

    protected $table = 'training_enrollments';

    protected $fillable = [
        'session_id',
        'employee_id',
        'status',
        'source',
        'enrolled_by',
    ];

    protected static function newFactory(): TrainingEnrollmentFactory
    {
        return TrainingEnrollmentFactory::new();
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(TrainingSession::class, 'session_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function feedback(): HasOne
    {
        return $this->hasOne(TrainingFeedback::class, 'enrollment_id');
    }
}
