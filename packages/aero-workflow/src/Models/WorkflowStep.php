<?php

namespace Aero\Workflow\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowStep extends TenantModel
{
    protected $fillable = [
        'workflow_id',
        'name',
        'order',
        'type',
        'config',
        'is_parallel',
        'is_required',
    ];

    protected function casts(): array
    {
        return [
            'config' => 'array',
            'is_parallel' => 'boolean',
            'is_required' => 'boolean',
        ];
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function instances(): HasMany
    {
        return $this->hasMany(WorkflowInstance::class, 'current_step_id');
    }

    public function transitionsFrom(): HasMany
    {
        return $this->hasMany(WorkflowTransition::class, 'from_step_id');
    }

    public function transitionsTo(): HasMany
    {
        return $this->hasMany(WorkflowTransition::class, 'to_step_id');
    }

    public function scopeApproval($query)
    {
        return $query->where('type', 'approval');
    }

    public function scopeNotification($query)
    {
        return $query->where('type', 'notification');
    }

    public function scopeCondition($query)
    {
        return $query->where('type', 'condition');
    }

    public function scopeAutomation($query)
    {
        return $query->where('type', 'automation');
    }

    public function scopeParallel($query)
    {
        return $query->where('is_parallel', true);
    }

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }
}
