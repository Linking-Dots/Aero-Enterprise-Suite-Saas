<?php

namespace Aero\Workflow\Services;

use Aero\Workflow\Models\Workflow;
use Aero\Workflow\Models\WorkflowInstance;
use Aero\Workflow\Models\WorkflowStep;
use Aero\Workflow\Models\WorkflowTransition;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WorkflowService
{
    /**
     * Start a workflow instance for an entity.
     */
    public function startWorkflow(int $workflowId, string $entityType, int $entityId, int $userId, array $context = []): WorkflowInstance
    {
        $workflow = Workflow::with('steps')->findOrFail($workflowId);

        if (!$workflow->is_active) {
            throw new \Exception('Workflow is not active');
        }

        return DB::transaction(function () use ($workflow, $entityType, $entityId, $userId, $context) {
            $firstStep = $workflow->steps()->orderBy('order')->first();

            if (!$firstStep) {
                throw new \Exception('Workflow has no steps defined');
            }

            $instance = WorkflowInstance::create([
                'workflow_id' => $workflow->id,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'current_step_id' => $firstStep->id,
                'status' => 'pending',
                'context' => array_merge($context, [
                    'workflow_name' => $workflow->name,
                    'entity_type' => $entityType,
                    'entity_id' => $entityId,
                ]),
                'started_at' => now(),
                'initiated_by' => $userId,
            ]);

            // Record initial transition
            WorkflowTransition::create([
                'instance_id' => $instance->id,
                'from_step_id' => null,
                'to_step_id' => $firstStep->id,
                'action' => 'start',
                'performed_by' => $userId,
                'occurred_at' => now(),
            ]);

            Log::info("Workflow started", [
                'instance_id' => $instance->id,
                'workflow_id' => $workflow->id,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
            ]);

            return $instance;
        });
    }

    /**
     * Advance workflow to next step.
     */
    public function advanceStep(int $instanceId, string $action, int $userId, ?string $comment = null): WorkflowInstance
    {
        return DB::transaction(function () use ($instanceId, $action, $userId, $comment) {
            $instance = WorkflowInstance::with(['workflow.steps', 'currentStep'])->findOrFail($instanceId);

            if ($instance->status === 'completed') {
                throw new \Exception('Workflow is already completed');
            }

            if ($instance->status === 'rejected') {
                throw new \Exception('Workflow has been rejected');
            }

            $currentStep = $instance->currentStep;
            $nextStep = $this->getNextStep($instance->workflow, $currentStep, $action);

            // Record transition
            WorkflowTransition::create([
                'instance_id' => $instance->id,
                'from_step_id' => $currentStep->id,
                'to_step_id' => $nextStep?->id,
                'action' => $action,
                'comment' => $comment,
                'performed_by' => $userId,
                'occurred_at' => now(),
            ]);

            // Update instance
            if ($nextStep) {
                $instance->current_step_id = $nextStep->id;
                $instance->save();
            } else {
                // No more steps - workflow completed
                $instance->current_step_id = null;
                $instance->status = 'completed';
                $instance->completed_at = now();
                $instance->save();
            }

            Log::info("Workflow advanced", [
                'instance_id' => $instance->id,
                'action' => $action,
                'from_step' => $currentStep->name,
                'to_step' => $nextStep?->name ?? 'completed',
            ]);

            return $instance->fresh();
        });
    }

    /**
     * Reject a workflow instance.
     */
    public function reject(int $instanceId, string $reason, int $userId): WorkflowInstance
    {
        return DB::transaction(function () use ($instanceId, $reason, $userId) {
            $instance = WorkflowInstance::with('currentStep')->findOrFail($instanceId);

            if ($instance->status === 'completed') {
                throw new \Exception('Cannot reject a completed workflow');
            }

            if ($instance->status === 'rejected') {
                throw new \Exception('Workflow is already rejected');
            }

            $instance->status = 'rejected';
            $instance->completed_at = now();
            $instance->save();

            // Record transition
            WorkflowTransition::create([
                'instance_id' => $instance->id,
                'from_step_id' => $instance->current_step_id,
                'to_step_id' => null,
                'action' => 'reject',
                'comment' => $reason,
                'performed_by' => $userId,
                'occurred_at' => now(),
            ]);

            Log::info("Workflow rejected", [
                'instance_id' => $instance->id,
                'reason' => $reason,
            ]);

            return $instance;
        });
    }

    /**
     * Escalate a workflow instance.
     */
    public function escalate(int $instanceId, int $userId, ?string $comment = null): WorkflowInstance
    {
        return DB::transaction(function () use ($instanceId, $userId, $comment) {
            $instance = WorkflowInstance::with(['workflow.steps', 'currentStep'])->findOrFail($instanceId);

            if ($instance->status === 'completed') {
                throw new \Exception('Cannot escalate a completed workflow');
            }

            $currentStep = $instance->currentStep;
            $escalationStep = $this->getEscalationStep($instance->workflow, $currentStep);

            if (!$escalationStep) {
                throw new \Exception('No escalation step defined for current step');
            }

            $instance->status = 'escalated';
            $instance->current_step_id = $escalationStep->id;
            $instance->save();

            // Record transition
            WorkflowTransition::create([
                'instance_id' => $instance->id,
                'from_step_id' => $currentStep->id,
                'to_step_id' => $escalationStep->id,
                'action' => 'escalate',
                'comment' => $comment,
                'performed_by' => $userId,
                'occurred_at' => now(),
            ]);

            Log::info("Workflow escalated", [
                'instance_id' => $instance->id,
                'from_step' => $currentStep->name,
                'to_step' => $escalationStep->name,
            ]);

            return $instance->fresh();
        });
    }

    /**
     * Get the next step in the workflow.
     */
    protected function getNextStep(Workflow $workflow, WorkflowStep $currentStep, string $action): ?WorkflowStep
    {
        if ($action === 'reject') {
            return null; // Reject ends the workflow
        }

        $steps = $workflow->steps()->orderBy('order')->get();
        $currentIndex = $steps->search(fn ($step) => $step->id === $currentStep->id);

        if ($currentIndex === false) {
            return null;
        }

        // Check if current step has parallel steps
        if ($currentStep->is_parallel) {
            // Find all steps at the same order level
            $parallelSteps = $steps->filter(fn ($step) => $step->order === $currentStep->order && $step->id !== $currentStep->id);
            
            // If there are unvisited parallel steps, return the first one
            foreach ($parallelSteps as $parallelStep) {
                $visited = WorkflowTransition::where('instance_id', $workflow->instances()->latest()->first()->id ?? 0)
                    ->where('to_step_id', $parallelStep->id)
                    ->exists();
                if (!$visited) {
                    return $parallelStep;
                }
            }
        }

        // Move to next order
        $nextStep = $steps->get($currentIndex + 1);

        // Skip non-required steps if action is skip
        if ($action === 'skip' && $nextStep && !$nextStep->is_required) {
            return $this->getNextStep($workflow, $nextStep, $action);
        }

        return $nextStep;
    }

    /**
     * Get the escalation step for the current step.
     */
    protected function getEscalationStep(Workflow $workflow, WorkflowStep $currentStep): ?WorkflowStep
    {
        $config = $currentStep->config;
        
        // Check if escalation is configured in step config
        if (isset($config['escalation_step_id'])) {
            return $workflow->steps()->find($config['escalation_step_id']);
        }

        // Default to the next step if no explicit escalation configured
        return $this->getNextStep($workflow, $currentStep, 'escalate');
    }

    /**
     * Get pending approvals for a user.
     */
    public function getPendingApprovals(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return WorkflowInstance::with(['workflow', 'currentStep'])
            ->pending()
            ->whereHas('currentStep', function ($query) use ($userId) {
                $query->whereJsonContains('config->approvers', $userId);
            })
            ->get();
    }

    /**
     * Check if a user can approve a workflow instance.
     */
    public function canApprove(int $instanceId, int $userId): bool
    {
        $instance = WorkflowInstance::with('currentStep')->findOrFail($instanceId);
        
        if ($instance->status !== 'pending') {
            return false;
        }

        $approvers = $instance->currentStep->config['approvers'] ?? [];
        
        return in_array($userId, $approvers);
    }
}
