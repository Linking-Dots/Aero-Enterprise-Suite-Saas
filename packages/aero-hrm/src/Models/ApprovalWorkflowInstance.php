<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Approval Workflow Instance Model
 *
 * Tracks individual workflow instances for approval processing.
 */
class ApprovalWorkflowInstance extends TenantModel
{
    protected $fillable = [
        'template_id',
        'approvable_type',
        'approvable_id',
        'requester_id',
        'current_step',
        'status',
        'step_history',
        'final_remarks',
        'submitted_at',
        'completed_at',
        'total_duration_hours',
    ];

    protected function casts(): array
    {
        return [
            'step_history' => 'array',
            'submitted_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ApprovalWorkflowTemplate::class, 'template_id');
    }

    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'requester_id');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(ApprovalAction::class, 'workflow_instance_id');
    }

    public function currentAction(): ?ApprovalAction
    {
        return $this->actions()
            ->where('step_number', $this->current_step)
            ->where('action', 'pending')
            ->first();
    }

    public function isPending(): bool
    {
        return in_array($this->status, ['pending', 'in_progress']);
    }

    public function isCompleted(): bool
    {
        return in_array($this->status, ['approved', 'rejected']);
    }

    public function approve(int $userId, ?string $remarks = null): void
    {
        $this->processAction('approved', $userId, $remarks);
    }

    public function reject(int $userId, ?string $remarks = null): void
    {
        $this->processAction('rejected', $userId, $remarks);
    }

    protected function processAction(string $action, int $userId, ?string $remarks): void
    {
        $currentAction = $this->currentAction();

        if ($currentAction) {
            $currentAction->update([
                'action' => $action,
                'approver_id' => $userId,
                'remarks' => $remarks,
                'actioned_at' => now(),
            ]);
        }

        $template = $this->template;
        $totalSteps = $template->getStepCount();

        if ($action === 'rejected') {
            $this->update([
                'status' => 'rejected',
                'final_remarks' => $remarks,
                'completed_at' => now(),
                'total_duration_hours' => $this->submitted_at->diffInHours(now()),
            ]);
        } elseif ($this->current_step >= $totalSteps) {
            $this->update([
                'status' => 'approved',
                'completed_at' => now(),
                'total_duration_hours' => $this->submitted_at->diffInHours(now()),
            ]);
        } else {
            $this->increment('current_step');
            $this->createNextAction();
        }

        $this->addToHistory($action, $userId, $remarks);
    }

    protected function createNextAction(): void
    {
        $nextStep = $this->template->getStep($this->current_step);

        if ($nextStep) {
            ApprovalAction::create([
                'workflow_instance_id' => $this->id,
                'step_number' => $this->current_step,
                'step_name' => $nextStep['name'] ?? "Step {$this->current_step}",
                'approver_id' => $nextStep['approver_id'] ?? null,
                'action' => 'pending',
                'due_at' => isset($nextStep['due_hours'])
                    ? now()->addHours($nextStep['due_hours'])
                    : null,
            ]);
        }
    }

    protected function addToHistory(string $action, int $userId, ?string $remarks): void
    {
        $history = $this->step_history ?? [];
        $history[] = [
            'step' => $this->current_step,
            'action' => $action,
            'user_id' => $userId,
            'remarks' => $remarks,
            'timestamp' => now()->toISOString(),
        ];

        $this->update(['step_history' => $history]);
    }
}
