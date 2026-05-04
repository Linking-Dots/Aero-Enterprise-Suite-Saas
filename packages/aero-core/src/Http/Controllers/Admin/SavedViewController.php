<?php

declare(strict_types=1);

namespace Aero\Core\Http\Controllers\Admin;

use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Models\SavedView;
use Aero\Core\Services\SavedViewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SavedViewController extends Controller
{
    public function __construct(
        private SavedViewService $service
    ) {}

    /**
     * Display saved views list.
     */
    public function index(Request $request): Response
    {
        $moduleCode = $request->get('module');
        $route = $request->get('route');

        $views = $this->service->getViewsForModule($moduleCode ?? 'core');
        $sharedViews = $this->service->getSharedViews($moduleCode ?? 'core');

        return Inertia::render('Core/SavedViews/Index', [
            'title' => 'Saved Views',
            'views' => $views,
            'shared_views' => $sharedViews,
            'module_code' => $moduleCode,
            'route' => $route,
        ]);
    }

    /**
     * Store a new saved view.
     */
    public function store(Request $request): mixed
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'module_code' => 'required|string|max:50',
            'route' => 'required|string',
            'description' => 'nullable|string',
            'filters' => 'required|array',
            'sort' => 'nullable|array',
            'columns' => 'nullable|array',
            'is_default' => 'boolean',
            'is_shared' => 'boolean',
            'shared_with' => 'nullable|array',
        ]);

        $view = $this->service->saveCurrentFilters(
            name: $validated['name'],
            moduleCode: $validated['module_code'],
            route: $validated['route'],
            filters: $validated['filters'],
            description: $validated['description'] ?? null,
            sort: $validated['sort'] ?? null,
            columns: $validated['columns'] ?? null,
            isDefault: $validated['is_default'] ?? false,
            isShared: $validated['is_shared'] ?? false,
            sharedWith: $validated['shared_with'] ?? null,
        );

        return redirect()->route('core.saved-views.index')
            ->with('success', "View '{$view->name}' created successfully.");
    }

    /**
     * Show a specific saved view.
     */
    public function show(SavedView $savedView): JsonResponse
    {
        $this->authorizeView($savedView);

        return response()->json($savedView);
    }

    /**
     * Update a saved view.
     */
    public function update(Request $request, SavedView $savedView): mixed
    {
        $this->authorizeView($savedView);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'filters' => 'sometimes|array',
            'sort' => 'nullable|array',
            'columns' => 'nullable|array',
            'is_default' => 'sometimes|boolean',
            'is_shared' => 'sometimes|boolean',
            'shared_with' => 'nullable|array',
        ]);

        $view = $this->service->updateView(
            view: $savedView,
            name: $validated['name'] ?? null,
            description: $validated['description'] ?? null,
            filters: $validated['filters'] ?? null,
            sort: $validated['sort'] ?? null,
            columns: $validated['columns'] ?? null,
            isDefault: $validated['is_default'] ?? null,
            isShared: $validated['is_shared'] ?? null,
            sharedWith: $validated['shared_with'] ?? null,
        );

        return redirect()->route('core.saved-views.index')
            ->with('success', "View '{$view->name}' updated successfully.");
    }

    /**
     * Delete a saved view.
     */
    public function destroy(SavedView $savedView): mixed
    {
        $this->authorizeView($savedView);

        $name = $savedView->name;
        $this->service->deleteView($savedView);

        return redirect()->route('core.saved-views.index')
            ->with('success', "View '{$name}' deleted successfully.");
    }

    /**
     * Apply a saved view (return view configuration).
     */
    public function apply(SavedView $savedView): JsonResponse
    {
        $this->authorizeView($savedView);

        $config = $this->service->applyView($savedView);

        return response()->json([
            'success' => true,
            'config' => $config,
        ]);
    }

    /**
     * Set a view as default for a route.
     */
    public function setAsDefault(SavedView $savedView): JsonResponse
    {
        $this->authorizeView($savedView);

        $view = $this->service->setAsDefault($savedView);

        return response()->json([
            'success' => true,
            'view' => $view,
        ]);
    }

    /**
     * Share a view with users/roles.
     */
    public function share(Request $request, SavedView $savedView): JsonResponse
    {
        $this->authorizeView($savedView);

        $validated = $request->validate([
            'shared_with' => 'required|array',
        ]);

        $view = $this->service->shareView($savedView, $validated['shared_with']);

        return response()->json([
            'success' => true,
            'view' => $view,
        ]);
    }

    /**
     * Duplicate a saved view.
     */
    public function duplicate(Request $request, SavedView $savedView): mixed
    {
        $this->authorizeView($savedView);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $newView = $this->service->duplicateView($savedView, $validated['name']);

        return redirect()->route('core.saved-views.index')
            ->with('success', "View '{$newView->name}' duplicated successfully.");
    }

    /**
     * Authorize that the user can access the view.
     */
    private function authorizeView(SavedView $view): void
    {
        if (! $this->service->canAccessView($view)) {
            abort(403, 'You do not have permission to access this view.');
        }
    }
}
