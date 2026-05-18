<?php

declare(strict_types=1);

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Aero\HRM\Database\Factories\HrmGoalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrmGoal extends TenantModel
{
    use HasFactory;

    public const STATUS_OPEN = 'open';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_ACHIEVED = 'achieved';

    public const STATUS_MISSED = 'missed';

    public const STATUS_CLOSED = 'closed';

    protected $table = 'hrm_goals';

    /** @var list<string> */
    protected $fillable = [
        'employee_id',
        'title',
        'specific',
        'measurable',
        'achievable',
        'relevant',
        'time_bound',
        'progress',
        'status',
    ];

    // closed_at is NOT in $fillable — set directly on model

    protected function casts(): array
    {
        return [
            'time_bound' => 'date',
            'closed_at' => 'datetime',
            'progress' => 'int',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function isClosed(): bool
    {
        return in_array($this->status, [
            self::STATUS_ACHIEVED,
            self::STATUS_MISSED,
            self::STATUS_CLOSED,
        ], true);
    }

    protected static function newFactory(): HrmGoalFactory
    {
        return HrmGoalFactory::new();
    }
}
