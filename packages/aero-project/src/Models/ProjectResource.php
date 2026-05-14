<?php

namespace Aero\Project\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectResource extends TenantModel
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'user_id',
        'role',
        'allocation_percentage',
        'start_date',
        'end_date',
        'hourly_rate',
        'cost_per_hour',
        'availability_status',
        'skills',
        'notes',
        'active',
    ];

    protected $casts = [
        'allocation_percentage' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'hourly_rate' => 'decimal:2',
        'cost_per_hour' => 'decimal:2',
        'skills' => 'array',
        'active' => 'boolean',
    ];

    /**
     * Get the project that owns the resource.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the user assigned as resource.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get utilization percentage based on time entries.
     */
    public function getUtilizationPercentageAttribute(): float
    {
        // Calculate based on time entries vs allocated time
        $totalWorkingDays = $this->getWorkingDaysBetween($this->start_date, $this->end_date);
        $allocatedHours = $totalWorkingDays * 8 * ($this->allocation_percentage / 100);

        $actualHours = ProjectTimeEntry::where('project_id', $this->project_id)
            ->where('user_id', $this->user_id)
            ->whereBetween('date', [$this->start_date, $this->end_date])
            ->sum('duration_minutes') / 60;

        return $allocatedHours > 0 ? ($actualHours / $allocatedHours) * 100 : 0;
    }

    /**
     * Get total cost for this resource allocation.
     */
    public function getTotalCostAttribute(): float
    {
        $totalWorkingDays = $this->getWorkingDaysBetween($this->start_date, $this->end_date);
        $allocatedHours = $totalWorkingDays * 8 * ($this->allocation_percentage / 100);

        return $allocatedHours * $this->cost_per_hour;
    }

    /**
     * Get working days between two dates.
     */
    private function getWorkingDaysBetween($startDate, $endDate): int
    {
        $start = \Carbon\Carbon::parse($startDate);
        $end = \Carbon\Carbon::parse($endDate);

        return $start->diffInWeekdays($end) + 1;
    }

    /**
     * Check if resource is currently active.
     */
    public function getIsActiveAttribute(): bool
    {
        $now = now();

        return $this->active &&
            $this->start_date <= $now &&
            $this->end_date >= $now;
    }

    /**
     * Get availability status color.
     */
    public function getAvailabilityColorAttribute(): string
    {
        $statusColors = [
            'available' => 'green',
            'partially_available' => 'yellow',
            'busy' => 'orange',
            'unavailable' => 'red',
        ];

        return $statusColors[$this->availability_status] ?? 'gray';
    }

    /**
     * Scope for active resources.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope for current resources.
     */
    public function scopeCurrent($query)
    {
        $now = now();

        return $query->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now);
    }

    /**
     * Get role options.
     */
    public static function getRoleOptions(): array
    {
        return [
            'project_manager' => 'Project Manager',
            'team_lead' => 'Team Lead',
            'developer' => 'Developer',
            'designer' => 'Designer',
            'tester' => 'Tester',
            'analyst' => 'Analyst',
            'consultant' => 'Consultant',
            'coordinator' => 'Coordinator',
        ];
    }
}
