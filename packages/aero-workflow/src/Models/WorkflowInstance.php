<?php

namespace Aero\Workflow\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowInstance extends TenantModel
{
    protected $fillable = [
        'workflow_id',
        'entity_type',
        'entity_id',
        'current_step_id',
        'status',
        'context',
        'started_at',
        'completed_at',
        'initiated_by',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function currentStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'current_step_id');
    }

    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(\Aero\Core\Models\User::class, 'initiated_by');
    }

    public function transitions(): HasMany
    {
        return $this->hasMany(WorkflowTransition::class, 'instance_id')->orderBy('occurred_at');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeEscalated($query)
    {
        return $query->where('status', 'escalated');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeForEntity($query, string $entityType, $entityId)
    {
        return $query->where('entity_type', $entityType)->where('entity_id', $entityId);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('initiated_by', $userId);
    }

    public function scopePendingApproval($query, $userId)
    {
        return $query->where('status', 'pending')
            ->whereHas('currentStep', function ($q) use ($userId) {
                $q->whereJsonContains('config->approvers', $userId);
            });
    }
}
