<?php

declare(strict_types=1);

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Aero\HRM\Database\Factories\TrainingFeedbackFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingFeedback extends TenantModel
{
    use HasFactory;

    protected $table = 'training_feedbacks';

    protected $fillable = [
        'enrollment_id',
        'content_rating',
        'instructor_rating',
        'overall_rating',
        'would_recommend',
        'what_worked',
        'what_didnt_work',
        'suggestions',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'would_recommend' => 'bool',
            'submitted_at' => 'datetime',
        ];
    }

    protected static function newFactory(): TrainingFeedbackFactory
    {
        return TrainingFeedbackFactory::new();
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(TrainingEnrollment::class, 'enrollment_id');
    }
}
