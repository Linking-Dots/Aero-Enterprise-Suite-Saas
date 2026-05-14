<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Approval Action Model
 *
 * Individual approval step actions within a workflow.
 */
class ApprovalAction extends TenantModel
{
    protected $fillable = [
        'workflow_instance_id',
        'step_number',
        'step_name',
        'approver_id',
        'delegate_id',
        'action',
        'remarks',
        'conditions_met',
        'due_at',
        'actioned_at',
        'is_overdue',
    ];

    protected function casts(): array
    {
        return [
            'conditions_met' => 'array',
            'due_at' => 'datetime',
            'actioned_at' => 'datetime',
            'is_overdue' => 'boolean',
        ];
    }

    public function workflowInstance(): BelongsTo
    {
        return $this->belongsTo(ApprovalWorkflowInstance::class, 'workflow_instance_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approver_id');
    }

    public function delegate(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'delegate_id');
    }

    public function isPending(): bool
    {
        return $this->action === 'pending';
    }

    public function isOverdue(): bool
    {
        return $this->due_at && now()->isAfter($this->due_at) && $this->isPending();
    }

    public function checkOverdue(): void
    {
        if ($this->isOverdue() && ! $this->is_overdue) {
            $this->update(['is_overdue' => true]);
        }
    }
}
