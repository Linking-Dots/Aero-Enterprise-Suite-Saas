<?php

namespace Aero\Workflow\Http\Controllers;

use Aero\Workflow\Models\Workflow;
use Aero\Workflow\Models\WorkflowStep;
use Aero\Workflow\Models\WorkflowTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class WorkflowController
{
    /**
     * Display a listing of workflows.
     */
    public function index(Request $request): \Inertia\Response
    {
        $workflows = Workflow::with(['template', 'steps', 'createdBy'])
            ->when($request->entity_type, fn ($q, $type) => $q->forEntity($type))
            ->when($request->search, fn ($q, $search) => $q->where('name', 'like', "%{$search}%"))
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $templates = WorkflowTemplate::active()->get();

        return Inertia::render('Core/Workflows/Index', [
            'workflows' => $workflows,
            'templates' => $templates,
            'filters' => [
                'entity_type' => $request->entity_type,
                'search' => $request->search,
            ],
        ]);
    }

    /**
     * Store a newly created workflow.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:100|unique:workflows,code',
            'description' => 'nullable|string',
            'entity_type' => 'required|string',
            'trigger_config' => 'required|array',
            'template_id' => 'nullable|exists:workflow_templates,id',
            'steps' => 'required|array|min:1',
        ]);

        $workflow = Workflow::create([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'description' => $validated['description'] ?? null,
            'entity_type' => $validated['entity_type'],
            'trigger_config' => $validated['trigger_config'],
            'template_id' => $validated['template_id'] ?? null,
            'created_by' => Auth::id(),
        ]);

        // Create steps
        foreach ($validated['steps'] as $index => $stepData) {
            WorkflowStep::create([
                'workflow_id' => $workflow->id,
                'name' => $stepData['name'],
                'order' => $index,
                'type' => $stepData['type'],
                'config' => $stepData['config'],
                'is_parallel' => $stepData['is_parallel'] ?? false,
                'is_required' => $stepData['is_required'] ?? true,
            ]);
        }

        return response()->json([
            'message' => 'Workflow created successfully',
            'workflow' => $workflow->load('steps'),
        ], 201);
    }

    /**
     * Display the specified workflow.
     */
    public function show(Workflow $workflow): JsonResponse
    {
        $workflow->load(['template', 'steps', 'instances']);

        return response()->json($workflow);
    }

    /**
     * Update the specified workflow.
     */
    public function update(Request $request, Workflow $workflow): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|max:100|unique:workflows,code,' . $workflow->id,
            'description' => 'nullable|string',
            'trigger_config' => 'sometimes|required|array',
            'is_active' => 'sometimes|boolean',
        ]);

        $workflow->update($validated);

        return response()->json([
            'message' => 'Workflow updated successfully',
            'workflow' => $workflow->fresh(),
        ]);
    }

    /**
     * Remove the specified workflow.
     */
    public function destroy(Workflow $workflow): JsonResponse
    {
        $workflow->delete();

        return response()->json([
            'message' => 'Workflow deleted successfully',
        ]);
    }

    /**
     * Activate a workflow.
     */
    public function activate(Workflow $workflow): JsonResponse
    {
        $workflow->update(['is_active' => true]);

        return response()->json([
            'message' => 'Workflow activated successfully',
            'workflow' => $workflow->fresh(),
        ]);
    }

    /**
     * Deactivate a workflow.
     */
    public function deactivate(Workflow $workflow): JsonResponse
    {
        $workflow->update(['is_active' => false]);

        return response()->json([
            'message' => 'Workflow deactivated successfully',
            'workflow' => $workflow->fresh(),
        ]);
    }
}
