<?php

namespace Aero\Workflow\Http\Controllers;

use Aero\Workflow\Models\WorkflowInstance;
use Aero\Workflow\Services\WorkflowService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class WorkflowInstanceController
{
    public function __construct(
        private WorkflowService $workflowService
    ) {}

    /**
     * Display pending approvals for the current user.
     */
    public function approvals(Request $request): \Inertia\Response
    {
        $approvals = WorkflowInstance::with(['workflow', 'currentStep'])
            ->pendingApproval(Auth::id())
            ->when($request->entity_type, fn ($q, $type) => $q->where('entity_type', $type))
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('Core/Workflows/Approvals/Index', [
            'approvals' => $approvals,
            'filters' => [
                'entity_type' => $request->entity_type,
            ],
        ]);
    }

    /**
     * Display workflow instances.
     */
    public function index(Request $request): \Inertia\Response
    {
        $instances = WorkflowInstance::with(['workflow', 'currentStep', 'initiatedBy'])
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->entity_type, fn ($q, $type) => $q->where('entity_type', $type))
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('Core/Workflows/Instances/Index', [
            'instances' => $instances,
            'filters' => [
                'status' => $request->status,
                'entity_type' => $request->entity_type,
            ],
        ]);
    }

    /**
     * Display the specified workflow instance.
     */
    public function show(WorkflowInstance $instance): JsonResponse
    {
        $instance->load(['workflow', 'currentStep', 'initiatedBy', 'transitions.fromStep', 'transitions.toStep', 'transitions.performedBy']);

        return response()->json($instance);
    }

    /**
     * Approve a workflow instance.
     */
    public function approve(Request $request, WorkflowInstance $instance): JsonResponse
    {
        $validated = $request->validate([
            'comment' => 'nullable|string',
        ]);

        if (!$this->workflowService->canApprove($instance->id, Auth::id())) {
            return response()->json([
                'message' => 'You are not authorized to approve this workflow',
            ], 403);
        }

        $instance = $this->workflowService->advanceStep(
            $instance->id,
            'approve',
            Auth::id(),
            $validated['comment'] ?? null
        );

        return response()->json([
            'message' => 'Workflow approved successfully',
            'instance' => $instance->load('currentStep'),
        ]);
    }

    /**
     * Reject a workflow instance.
     */
    public function reject(Request $request, WorkflowInstance $instance): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string',
        ]);

        if (!$this->workflowService->canApprove($instance->id, Auth::id())) {
            return response()->json([
                'message' => 'You are not authorized to reject this workflow',
            ], 403);
        }

        $instance = $this->workflowService->reject(
            $instance->id,
            $validated['reason'],
            Auth::id()
        );

        return response()->json([
            'message' => 'Workflow rejected successfully',
            'instance' => $instance,
        ]);
    }

    /**
     * Escalate a workflow instance.
     */
    public function escalate(Request $request, WorkflowInstance $instance): JsonResponse
    {
        $validated = $request->validate([
            'comment' => 'nullable|string',
        ]);

        $instance = $this->workflowService->escalate(
            $instance->id,
            Auth::id(),
            $validated['comment'] ?? null
        );

        return response()->json([
            'message' => 'Workflow escalated successfully',
            'instance' => $instance->load('currentStep'),
        ]);
    }

    /**
     * Retry a failed workflow instance.
     */
    public function retry(WorkflowInstance $instance): JsonResponse
    {
        if ($instance->status !== 'rejected') {
            return response()->json([
                'message' => 'Only rejected workflows can be retried',
            ], 400);
        }

        $instance->update([
            'status' => 'pending',
            'completed_at' => null,
        ]);

        return response()->json([
            'message' => 'Workflow retried successfully',
            'instance' => $instance->fresh(),
        ]);
    }
}
