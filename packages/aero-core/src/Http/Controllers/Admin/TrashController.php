<?php

namespace Aero\Core\Http\Controllers\Admin;

use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Services\TrashService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class TrashController extends Controller
{
    public function __construct(
        private TrashService $trashService
    ) {}

    /**
     * Display trash page.
     */
    public function index(Request $request): InertiaResponse
    {
        $entity = $request->get('entity', 'users');
        $filters = $request->only(['search']);
        
        $items = $this->trashService->getTrashedItems($entity, $filters);
        $counts = $this->trashService->getEntityCounts();
        $entityNames = $this->trashService->getEntityNames();

        return Inertia::render('Core/Trash/Index', [
            'entity' => $entity,
            'items' => $items,
            'counts' => $counts,
            'entity_names' => $entityNames,
        ]);
    }

    /**
     * Restore a trashed item.
     */
    public function restore(Request $request, string $entity, int $id): JsonResponse
    {
        $item = $this->trashService->restoreItem($entity, $id);

        return response()->json([
            'message' => 'Item restored successfully',
            'item' => $item,
        ]);
    }

    /**
     * Bulk restore trashed items.
     */
    public function bulkRestore(Request $request, string $entity): JsonResponse
    {
        $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $count = $this->trashService->bulkRestore($entity, $request->input('ids'));

        return response()->json([
            'message' => "{$count} items restored successfully",
            'count' => $count,
        ]);
    }

    /**
     * Permanently delete a trashed item.
     */
    public function forceDelete(Request $request, string $entity, int $id): JsonResponse
    {
        $this->trashService->forceDeleteItem($entity, $id);

        return response()->json([
            'message' => 'Item permanently deleted',
        ]);
    }

    /**
     * Bulk permanently delete trashed items.
     */
    public function bulkForceDelete(Request $request, string $entity): JsonResponse
    {
        $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $count = $this->trashService->bulkForceDelete($entity, $request->input('ids'));

        return response()->json([
            'message' => "{$count} items permanently deleted",
            'count' => $count,
        ]);
    }

    /**
     * Empty all trashed items for an entity.
     */
    public function emptyTrash(Request $request, string $entity): JsonResponse
    {
        $count = $this->trashService->emptyTrash($entity);

        return response()->json([
            'message' => "{$count} items permanently deleted",
            'count' => $count,
        ]);
    }

    /**
     * Empty all trashed items across all entities.
     */
    public function emptyAllTrash(Request $request): JsonResponse
    {
        $results = $this->trashService->emptyAllTrash();

        return response()->json([
            'message' => 'All trash emptied successfully',
            'results' => $results,
        ]);
    }
}
