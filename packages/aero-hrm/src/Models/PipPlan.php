<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PipPlan extends TenantModel
{
    use SoftDeletes;

    protected $table = 'pip_plans';

    protected $fillable = [
        'employee_id',
        'manager_id',
        'title',
        'reason',
        'start_date',
        'end_date',
        'status',
        'description',
        'expected_outcomes',
        'notes',
        'created_by',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'closed_at' => 'datetime',
            'status' => 'string',
        ];
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function goals(): HasMany
    {
        return $this->hasMany(PipGoal::class, 'pip_plan_id');
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeForEmployee(Builder $query, int $employeeId): Builder
    {
        return $query->where('employee_id', $employeeId);
    }

    // =========================================================================
    // Computed Properties
    // =========================================================================

    public function isOverdue(): bool
    {
        return ! in_array($this->status, ['completed', 'terminated'])
            && $this->end_date->isPast();
    }

    public function completionRate(): float
    {
        $total = $this->goals()->count();

        if ($total === 0) {
            return 0.0;
        }

        $achieved = $this->goals()->where('status', 'achieved')->count();

        return round(($achieved / $total) * 100, 2);
    }
}
