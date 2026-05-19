<?php

declare(strict_types=1);

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HrmCareerPath extends TenantModel
{
    use HasFactory;

    protected $table = 'hrm_career_paths';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'target_role_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(HrmCareerMilestone::class, 'career_path_id')->orderBy('order_index');
    }

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'hrm_career_path_employees', 'hrm_career_path_id', 'employee_id')
            ->withPivot(['current_milestone_id', 'assigned_at'])
            ->withTimestamps();
    }

    public function targetRole(): BelongsTo
    {
        return $this->belongsTo(Designation::class, 'target_role_id');
    }
}
