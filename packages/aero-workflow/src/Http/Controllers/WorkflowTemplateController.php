<?php

namespace Aero\Workflow\Http\Controllers;

use Aero\Workflow\Models\WorkflowTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class WorkflowTemplateController
{
    /**
     * Display a listing of workflow templates.
     */
    public function index(Request $request): \Inertia\Response
    {
        $templates = WorkflowTemplate::with(['createdBy'])
            ->when($request->entity_type, fn ($q, $type) => $q->forEntity($type))
            ->when($request->search, fn ($q, $search) => $q->where('name', 'like', "%{$search}%"))
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('Core/Workflows/Templates/Index', [
            'templates' => $templates,
            'filters' => [
                'entity_type' => $request->entity_type,
                'search' => $request->search,
            ],
        ]);
    }

    /**
     * Store a newly created workflow template.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:100|unique:workflow_templates,code',
            'description' => 'nullable|string',
            'entity_type' => 'required|string',
            'steps_config' => 'required|array',
            'is_active' => 'sometimes|boolean',
        ]);

        $template = WorkflowTemplate::create([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'description' => $validated['description'] ?? null,
            'entity_type' => $validated['entity_type'],
            'steps_config' => $validated['steps_config'],
            'is_active' => $validated['is_active'] ?? true,
            'is_system' => false,
            'created_by' => Auth::id(),
        ]);

        return response()->json([
            'message' => 'Workflow template created successfully',
            'template' => $template,
        ], 201);
    }

    /**
     * Display the specified workflow template.
     */
    public function show(WorkflowTemplate $template): JsonResponse
    {
        $template->load(['workflows', 'createdBy']);

        return response()->json($template);
    }

    /**
     * Update the specified workflow template.
     */
    public function update(Request $request, WorkflowTemplate $template): JsonResponse
    {
        if ($template->is_system) {
            return response()->json([
                'message' => 'System templates cannot be modified',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|max:100|unique:workflow_templates,code,' . $template->id,
            'description' => 'nullable|string',
            'steps_config' => 'sometimes|required|array',
            'is_active' => 'sometimes|boolean',
        ]);

        $template->update($validated);

        return response()->json([
            'message' => 'Workflow template updated successfully',
            'template' => $template->fresh(),
        ]);
    }

    /**
     * Remove the specified workflow template.
     */
    public function destroy(WorkflowTemplate $template): JsonResponse
    {
        if ($template->is_system) {
            return response()->json([
                'message' => 'System templates cannot be deleted',
            ], 403);
        }

        if ($template->workflows()->exists()) {
            return response()->json([
                'message' => 'Cannot delete template with associated workflows',
            ], 400);
        }

        $template->delete();

        return response()->json([
            'message' => 'Workflow template deleted successfully',
        ]);
    }
}
