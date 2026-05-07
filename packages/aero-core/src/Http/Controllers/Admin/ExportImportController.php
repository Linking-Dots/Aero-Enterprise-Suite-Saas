<?php

namespace Aero\Core\Http\Controllers\Admin;

use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Services\ExportImportService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ExportImportController extends Controller
{
    public function __construct(
        private ExportImportService $exportImportService
    ) {}

    /**
     * Display export history page.
     */
    public function exportsIndex(Request $request): InertiaResponse
    {
        $entityType = $request->query('entity', 'users');
        $history = $this->exportImportService->getExportHistory($entityType);
        $entities = $this->exportImportService->getExportableEntities();

        return Inertia::render('Core/ExportImport/Exports/Index', [
            'entity_type' => $entityType,
            'entities' => $entities,
            'history' => $history,
        ]);
    }

    /**
     * Create a new export.
     */
    public function createExport(Request $request): JsonResponse
    {
        $request->validate([
            'entity_type' => ['required', 'string'],
            'format' => ['required', 'in:csv,json,xlsx'],
            'filters' => ['nullable', 'array'],
        ]);

        $export = $this->exportImportService->export(
            $request->input('entity_type'),
            $request->input('filters', []),
            $request->input('format', 'csv')
        );

        return response()->json([
            'message' => 'Export job created successfully',
            'export' => $export,
        ]);
    }

    /**
     * Download an export file.
     */
    public function downloadExport(Request $request, $id): mixed
    {
        $export = \Aero\Core\Models\DataExport::findOrFail($id);

        if (! $export->isCompleted()) {
            return response()->json(['message' => 'Export is not completed yet'], 400);
        }

        if (! $export->file_path) {
            return response()->json(['message' => 'Export file not found'], 404);
        }

        return Response::download(storage_path('app/' . $export->file_path));
    }

    /**
     * Delete an export.
     */
    public function deleteExport(Request $request, $id): JsonResponse
    {
        $export = \Aero\Core\Models\DataExport::findOrFail($id);
        
        if ($export->file_path) {
            \Illuminate\Support\Facades\Storage::delete($export->file_path);
        }

        $export->delete();

        return response()->json(['message' => 'Export deleted successfully']);
    }

    /**
     * Display import page.
     */
    public function importsIndex(Request $request): InertiaResponse
    {
        $entityType = $request->query('entity', 'users');
        $entities = $this->exportImportService->getExportableEntities();

        return Inertia::render('Core/ExportImport/Imports/Index', [
            'entity_type' => $entityType,
            'entities' => $entities,
        ]);
    }

    /**
     * Import data from file.
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'entity_type' => ['required', 'string'],
            'file' => ['required', 'file', 'mimes:csv,json,txt,xlsx', 'max:2048'],
            'format' => ['required', 'in:csv,json'],
            'options' => ['nullable', 'array'],
        ]);

        $results = $this->exportImportService->import(
            $request->input('entity_type'),
            $request->file('file'),
            $request->input('options', [])
        );

        return response()->json([
            'message' => 'Import completed',
            'results' => $results,
        ]);
    }

    /**
     * Download import template.
     */
    public function downloadTemplate(Request $request, $entity): mixed
    {
        // Generate template based on entity type
        $headers = $this->getTemplateHeaders($entity);

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $entity . '-template.csv"',
        ];

        $callback = function () use ($headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Get template headers for entity.
     */
    protected function getTemplateHeaders(string $entity): array
    {
        $templates = [
            'users' => ['name', 'email', 'role', 'status'],
            'roles' => ['name', 'description'],
            'tags' => ['name', 'slug', 'color', 'description', 'icon'],
        ];

        return $templates[$entity] ?? [];
    }
}
