<?php

namespace Aero\HRM\Listeners\Leave;

use Aero\HRM\Events\Leave\LeaveRequested;
use Aero\HRM\Models\Leave;
use Aero\Workflow\Models\Workflow;
use Aero\Workflow\Services\WorkflowService;
use Illuminate\Support\Facades\Log;

class StartWorkflowOnLeaveRequest
{
    public function __construct(
        private WorkflowService $workflowService
    ) {}

    public function handle(LeaveRequested $event): void
    {
        $leave = $event->leave;

        // Find active workflow for leave requests
        $workflow = Workflow::active()
            ->forEntity('leave_request')
            ->first();

        if (!$workflow) {
            Log::info('No active workflow found for leave requests', [
                'leave_id' => $leave->id,
            ]);
            return;
        }

        try {
            // Start workflow instance for this leave request
            $instance = $this->workflowService->startWorkflow(
                $workflow->id,
                'leave_request',
                $leave->id,
                $leave->user_id,
                [
                    'leave_type' => $leave->leave_type,
                    'from_date' => $leave->from_date,
                    'to_date' => $leave->to_date,
                    'no_of_days' => $leave->no_of_days,
                    'reason' => $leave->reason,
                ]
            );

            // Link the workflow instance to the leave
            $leave->update([
                'workflow_instance_id' => $instance->id,
            ]);

            Log::info('Workflow started for leave request', [
                'leave_id' => $leave->id,
                'workflow_id' => $workflow->id,
                'instance_id' => $instance->id,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to start workflow for leave request', [
                'leave_id' => $leave->id,
                'error' => $e->getMessage(),
            ]);
            // Don't throw - leave request should still succeed even if workflow fails
        }
    }
}
