<?php

namespace Aero\Project\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ProjectWorkflowStep
 *
 * Tracks progress through project workflow stages.
 * Each project type can have different workflow templates.
 *
 * ARCHITECTURAL PRINCIPLE:
 * - Workflow templates defined in config/project_types.php
 * - Steps track completion status and timing
 * - Supports go/no-go decisions at each stage
 */
class ProjectWorkflowStep extends TenantModel
{
    protected $table = 'project_workflow_steps';

    protected $fillable = [
        'tenant_id',
        'project_id',
        'workflow_code',
        'step_code',
        'step_name',
        'step_order',
        'status',
        'started_at',
        'completed_at',
        'completed_by',
        'step_data',
    ];

    protected $casts = [
        'step_order' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'step_data' => 'array',
    ];

    /**
     * Workflow step statuses.
     */
    public const STATUSES = [
        'pending' => 'Pending',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'skipped' => 'Skipped',
    ];

    /**
     * Parent project relationship.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Mark step as started.
     */
    public function start(): self
    {
        $this->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        return $this;
    }

    /**
     * Mark step as completed.
     */
    public function complete(?int $completedBy = null, ?array $data = null): self
    {
        $updateData = [
            'status' => 'completed',
            'completed_at' => now(),
        ];

        if ($completedBy) {
            $updateData['completed_by'] = $completedBy;
        }

        if ($data) {
            $updateData['step_data'] = array_merge($this->step_data ?? [], $data);
        }

        $this->update($updateData);

        return $this;
    }

    /**
     * Skip this step.
     */
    public function skip(?string $reason = null): self
    {
        $data = $this->step_data ?? [];
        if ($reason) {
            $data['skip_reason'] = $reason;
        }

        $this->update([
            'status' => 'skipped',
            'step_data' => $data,
        ]);

        return $this;
    }

    /**
     * Check if step is complete.
     */
    public function isComplete(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if step is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'in_progress';
    }

    /**
     * Get duration in hours.
     */
    public function getDurationHours(): ?float
    {
        if (! $this->started_at || ! $this->completed_at) {
            return null;
        }

        return $this->started_at->diffInHours($this->completed_at);
    }
}
