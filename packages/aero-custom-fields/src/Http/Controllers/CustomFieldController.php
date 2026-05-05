<?php

namespace Aero\CustomFields\Http\Controllers;

use Aero\CustomFields\Models\CustomField;
use Aero\CustomFields\Services\CustomFieldService;
use Aero\Core\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;

class CustomFieldController extends Controller
{
    public function __construct(
        private CustomFieldService $customFieldService
    ) {}

    /**
     * Display a listing of custom fields.
     */
    public function index(Request $request)
    {
        $entityType = $request->query('entity_type');
        $search = $request->query('search');

        $query = CustomField::query();

        if ($entityType) {
            $query->forEntity($entityType);
        }

        if ($search) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
        }

        $fields = $query->ordered()->paginate(20);

        return Inertia::render('Core/CustomFields/Index', [
            'fields' => $fields,
            'filters' => [
                'entity_type' => $entityType,
                'search' => $search,
            ],
        ]);
    }

    /**
     * Store a newly created custom field.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'entity_type' => 'required|string|max:255',
            'field_type' => 'required|in:text,number,email,date,datetime,boolean,select,multi_select,textarea,file,currency',
            'options' => 'nullable|array',
            'validation_rules' => 'nullable|array',
            'is_required' => 'boolean',
            'is_unique' => 'boolean',
            'is_searchable' => 'boolean',
            'is_filterable' => 'boolean',
            'sort_order' => 'integer',
            'placeholder' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        try {
            $field = $this->customFieldService->createField($validated);

            return response()->json([
                'success' => true,
                'message' => 'Custom field created successfully',
                'field' => $field,
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create custom field',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Display the specified custom field.
     */
    public function show(int $id)
    {
        $field = CustomField::with('values')->findOrFail($id);

        return Inertia::render('Core/CustomFields/Show', [
            'field' => $field,
        ]);
    }

    /**
     * Update the specified custom field.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'entity_type' => 'sometimes|required|string|max:255',
            'field_type' => 'sometimes|required|in:text,number,email,date,datetime,boolean,select,multi_select,textarea,file,currency',
            'options' => 'nullable|array',
            'validation_rules' => 'nullable|array',
            'is_required' => 'boolean',
            'is_unique' => 'boolean',
            'is_searchable' => 'boolean',
            'is_filterable' => 'boolean',
            'sort_order' => 'integer',
            'placeholder' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        try {
            $field = $this->customFieldService->updateField($id, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Custom field updated successfully',
                'field' => $field,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update custom field',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Remove the specified custom field.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->customFieldService->deleteField($id);

            return response()->json([
                'success' => true,
                'message' => 'Custom field deleted successfully',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete custom field',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get fields for a specific entity type (API endpoint).
     */
    public function getFieldsForEntity(Request $request): JsonResponse
    {
        $entityType = $request->query('entity_type');
        $activeOnly = $request->query('active_only', 'true') === 'true';

        if (!$entityType) {
            return response()->json([
                'success' => false,
                'message' => 'entity_type parameter is required',
            ], 400);
        }

        $fields = $this->customFieldService->getFieldsForEntity($entityType, $activeOnly);

        return response()->json([
            'success' => true,
            'fields' => $fields,
        ]);
    }

    /**
     * Get values for a specific entity (API endpoint).
     */
    public function getValuesForEntity(Request $request): JsonResponse
    {
        $entityType = $request->query('entity_type');
        $entityId = $request->query('entity_id');

        if (!$entityType || !$entityId) {
            return response()->json([
                'success' => false,
                'message' => 'entity_type and entity_id parameters are required',
            ], 400);
        }

        $values = $this->customFieldService->getFieldsWithValues($entityType, (int) $entityId);

        return response()->json([
            'success' => true,
            'values' => $values,
        ]);
    }

    /**
     * Save values for an entity (API endpoint).
     */
    public function saveValues(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'entity_type' => 'required|string|max:255',
            'entity_id' => 'required|integer',
            'values' => 'required|array',
        ]);

        try {
            $this->customFieldService->saveValues(
                $validated['entity_type'],
                $validated['entity_id'],
                $validated['values']
            );

            return response()->json([
                'success' => true,
                'message' => 'Custom field values saved successfully',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save custom field values',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}
