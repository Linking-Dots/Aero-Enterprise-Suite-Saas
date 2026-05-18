<?php

declare(strict_types=1);

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Aero\HRM\Database\Factories\ReviewCycleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReviewCycle extends TenantModel
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_CLOSED = 'closed';

    protected $table = 'hrm_review_cycles';

    protected $fillable = [
        'name',
        'template_id',
        'starts_on',
        'ends_on',
        'status',
        'employee_ids',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'employee_ids' => 'array',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ReviewTemplate::class, 'template_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(HrmPerformanceReview::class, 'cycle_id');
    }

    public function calibrationSessions(): HasMany
    {
        return $this->hasMany(CalibrationSession::class, 'cycle_id');
    }

    protected static function newFactory(): ReviewCycleFactory
    {
        return ReviewCycleFactory::new();
    }
}
