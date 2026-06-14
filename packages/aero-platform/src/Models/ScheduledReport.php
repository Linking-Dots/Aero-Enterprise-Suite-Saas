<?php

namespace Aero\Platform\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScheduledReport extends CentralModel
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'report_type',
        'config',
        'frequency',
        'schedule_config',
        'recipients',
        'export_formats',
        'is_active',
        'last_run_at',
        'next_run_at',
    ];

    protected $casts = [
        'config' => 'array',
        'schedule_config' => 'array',
        'recipients' => 'array',
        'export_formats' => 'array',
        'is_active' => 'boolean',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
    ];

    /**
     * Get the user who owns the report.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get all executions for this report.
     */
    public function executions(): HasMany
    {
        return $this->hasMany(ReportExecution::class);
    }

    /**
     * Get the latest execution.
     */
    public function latestExecution()
    {
        return $this->hasOne(ReportExecution::class)->latestOfMany();
    }

    /**
     * Scope to get only active reports.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get reports that are due for execution.
     */
    public function scopeDueNow($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('next_run_at')
                    ->orWhere('next_run_at', '<=', now());
            });
    }

    /**
     * Scope to filter by frequency.
     */
    public function scopeFrequency($query, string $frequency)
    {
        return $query->where('frequency', $frequency);
    }

    /**
     * Calculate and update the next run time based on frequency.
     */
    public function calculateNextRunAt(): Carbon
    {
        $scheduleConfig = $this->schedule_config;
        $now = now();

        return match ($this->frequency) {
            'daily' => $now->addDay()->setTime(
                $scheduleConfig['hour'] ?? 6,
                $scheduleConfig['minute'] ?? 0
            ),
            'weekly' => $now->next($scheduleConfig['day'] ?? 'monday')->setTime(
                $scheduleConfig['hour'] ?? 6,
                $scheduleConfig['minute'] ?? 0
            ),
            'monthly' => $now->addMonth()->day($scheduleConfig['day_of_month'] ?? 1)->setTime(
                $scheduleConfig['hour'] ?? 6,
                $scheduleConfig['minute'] ?? 0
            ),
            'custom' => isset($scheduleConfig['cron'])
                ? $this->calculateFromCron($scheduleConfig['cron'])
                : $now->addWeek(),
            default => $now->addWeek(),
        };
    }

    /**
     * Calculate next run time from cron expression (simplified).
     */
    protected function calculateFromCron(string $cron): Carbon
    {
        // For production, use a cron parser library like dragonmantank/cron-expression
        // This is a simplified placeholder
        return now()->addWeek();
    }

    /**
     * Mark this report as executed and schedule next run.
     */
    public function markExecuted(): void
    {
        $this->update([
            'last_run_at' => now(),
            'next_run_at' => $this->calculateNextRunAt(),
        ]);
    }

    /**
     * Get a human-readable schedule description.
     */
    public function getScheduleDescription(): string
    {
        $config = $this->schedule_config;

        return match ($this->frequency) {
            'daily' => sprintf('Daily at %02d:%02d', $config['hour'] ?? 6, $config['minute'] ?? 0),
            'weekly' => sprintf('Weekly on %s at %02d:%02d',
                ucfirst($config['day'] ?? 'Monday'),
                $config['hour'] ?? 6,
                $config['minute'] ?? 0
            ),
            'monthly' => sprintf('Monthly on day %d at %02d:%02d',
                $config['day_of_month'] ?? 1,
                $config['hour'] ?? 6,
                $config['minute'] ?? 0
            ),
            'custom' => $config['cron'] ?? 'Custom schedule',
            default => 'Not scheduled',
        };
    }
}
