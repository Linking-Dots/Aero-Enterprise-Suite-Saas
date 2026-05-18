<?php

declare(strict_types=1);

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Aero\Core\Models\User;
use Aero\HRM\Database\Factories\HrmPerformanceReviewFactory;
use Aero\HRM\Exceptions\PerformanceFinalizedException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrmPerformanceReview extends TenantModel
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SELF_SUBMITTED = 'self_submitted';

    public const STATUS_MANAGER_SUBMITTED = 'manager_submitted';

    public const STATUS_FINALIZED = 'finalized';

    protected $table = 'hrm_performance_reviews';

    /** @var list<string> */
    protected $fillable = [
        'cycle_id',
        'employee_id',
        'manager_id',
        'status',
        'self_answers',
        'manager_answers',
        'final_rating',
        'final_comment',
    ];

    // finalized_at and finalized_by are NOT in $fillable — set directly on model

    protected function casts(): array
    {
        return [
            'self_answers' => 'array',
            'manager_answers' => 'array',
            'final_rating' => 'decimal:2',
            'finalized_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (self $review): void {
            if ($review->getOriginal('status') === self::STATUS_FINALIZED) {
                throw new PerformanceFinalizedException;
            }
        });
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(ReviewCycle::class, 'cycle_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    public function finalizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    public function isFinalized(): bool
    {
        return $this->status === self::STATUS_FINALIZED;
    }

    protected static function newFactory(): HrmPerformanceReviewFactory
    {
        return HrmPerformanceReviewFactory::new();
    }
}
