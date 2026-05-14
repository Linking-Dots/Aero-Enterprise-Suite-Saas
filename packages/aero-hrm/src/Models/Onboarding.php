<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

/**
 * Onboarding Model
 *
 * @property int $id
 * @property int $employee_id
 * @property \Carbon\Carbon|null $start_date
 * @property \Carbon\Carbon|null $expected_completion_date
 * @property \Carbon\Carbon|null $actual_completion_date
 * @property string $status
 * @property string|null $notes
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property-read Employee $employee
 * @property-read User|null $creator
 * @property-read User|null $updater
 */
class Onboarding extends TenantModel
{
    use HasFactory, SoftDeletes;

    // Status constants
    public const STATUS_PENDING = 'pending';

    public const STATUS_IN_PROGRESS = 'in_progress'; // changed from in-progress

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'employee_id',
        'start_date',
        'expected_completion_date',
        'actual_completion_date',
        'status',
        'notes',
        // created_by / updated_by guarded via hooks
    ];

    protected $casts = [
        'start_date' => 'date',
        'expected_completion_date' => 'date',
        'actual_completion_date' => 'date',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $model) {
            if (empty($model->status)) {
                $model->status = self::STATUS_PENDING;
            }
            if (Auth::id()) {
                $model->created_by = Auth::id();
            }
        });
        static::updating(function (self $model) {
            if (Auth::id()) {
                $model->updated_by = Auth::id();
            }
        });
    }

    // Relationships
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(OnboardingTask::class);
    }

    // Accessors
    public function getProgressAttribute(): float
    {
        $total = $this->tasks->count();
        if ($total === 0) {
            return 0.0;
        }
        $completed = $this->tasks->where('status', 'completed')->count();

        return round(($completed / $total) * 100, 2);
    }

    public function isCompletable(): bool
    {
        return $this->tasks()->whereNotIn('status', ['completed', 'not-applicable'])->count() === 0;
    }

    // Accessor to normalize legacy hyphenated value
    public function getStatusAttribute($value)
    {
        return $value === 'in-progress' ? self::STATUS_IN_PROGRESS : $value;
    }

    // Mutator to always persist normalized value
    public function setStatusAttribute($value)
    {
        $this->attributes['status'] = $value === 'in-progress' ? self::STATUS_IN_PROGRESS : $value;
    }

    /**
     * Get onboarding analytics data
     *
     * @param  int  $days  Number of days to analyze
     */
    public static function getAnalytics(int $days = 30): array
    {
        $startDate = now()->subDays($days);

        // Get all onboardings in the date range
        $onboardings = self::with(['employee.department', 'tasks'])
            ->where('created_at', '>=', $startDate)
            ->get();

        // Calculate metrics
        $total = $onboardings->count();
        $completed = $onboardings->where('status', self::STATUS_COMPLETED)->count();
        $inProgress = $onboardings->where('status', self::STATUS_IN_PROGRESS)->count();
        $pending = $onboardings->where('status', self::STATUS_PENDING)->count();

        // Calculate overdue (in_progress but past expected_completion_date)
        $overdue = $onboardings->filter(function ($onboarding) {
            return $onboarding->status === self::STATUS_IN_PROGRESS
                && $onboarding->expected_completion_date
                && $onboarding->expected_completion_date->lt(now());
        })->count();

        // Calculate completion rate
        $completionRate = $total > 0 ? ($completed / $total) * 100 : 0;

        // Calculate average completion time (in days)
        $completedOnboardings = $onboardings->where('status', self::STATUS_COMPLETED)
            ->filter(fn ($o) => $o->actual_completion_date && $o->start_date);

        $avgCompletionDays = 0;
        if ($completedOnboardings->count() > 0) {
            $totalDays = $completedOnboardings->sum(function ($onboarding) {
                return $onboarding->start_date->diffInDays($onboarding->actual_completion_date);
            });
            $avgCompletionDays = $totalDays / $completedOnboardings->count();
        }

        // Department breakdown
        $departments = $onboardings->groupBy('employee.department.name')
            ->map(function ($deptOnboardings, $deptName) {
                $deptTotal = $deptOnboardings->count();
                $deptCompleted = $deptOnboardings->where('status', self::STATUS_COMPLETED)->count();
                $deptInProgress = $deptOnboardings->where('status', self::STATUS_IN_PROGRESS)->count();

                return [
                    'name' => $deptName ?: 'Unknown',
                    'total' => $deptTotal,
                    'completed' => $deptCompleted,
                    'in_progress' => $deptInProgress,
                    'completion_rate' => $deptTotal > 0 ? ($deptCompleted / $deptTotal) * 100 : 0,
                ];
            })->values()->toArray();

        // Trend data (daily completions for the period)
        $trend = [];
        for ($i = 0; $i < $days; $i++) {
            $date = now()->subDays($days - $i - 1)->startOfDay();
            $nextDate = $date->copy()->addDay();

            $completedOnDay = self::where('status', self::STATUS_COMPLETED)
                ->whereBetween('actual_completion_date', [$date, $nextDate])
                ->count();

            $startedOnDay = self::whereBetween('created_at', [$date, $nextDate])->count();

            $trend[] = [
                'date' => $date->format('Y-m-d'),
                'completed' => $completedOnDay,
                'started' => $startedOnDay,
            ];
        }

        return [
            'metrics' => [
                'total_onboardings' => $total,
                'completed' => $completed,
                'in_progress' => $inProgress,
                'pending' => $pending,
                'overdue' => $overdue,
                'completion_rate' => round($completionRate, 2),
                'average_completion_days' => round($avgCompletionDays, 1),
            ],
            'departments' => $departments,
            'trend' => $trend,
            'status_distribution' => [
                'pending' => $pending,
                'in_progress' => $inProgress,
                'completed' => $completed,
                'overdue' => $overdue,
            ],
        ];
    }
}
