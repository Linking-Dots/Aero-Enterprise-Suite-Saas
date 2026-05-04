<?php

namespace Aero\Core\Services;

use Aero\Core\Models\SavedView;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Saved View Service
 *
 * Core business logic for managing saved views and filters across modules.
 */
class SavedViewService
{
    /**
     * Save current request filters as a view.
     */
    public function saveCurrentFilters(
        string $name,
        string $moduleCode,
        string $route,
        array $filters,
        ?string $description = null,
        ?array $sort = null,
        ?array $columns = null,
        bool $isDefault = false,
        bool $isShared = false,
        ?array $sharedWith = null
    ): SavedView {
        return SavedView::create([
            'user_id' => Auth::id(),
            'module_code' => $moduleCode,
            'route' => $route,
            'name' => $name,
            'description' => $description,
            'filters' => $filters,
            'sort' => $sort,
            'columns' => $columns,
            'is_default' => $isDefault,
            'is_shared' => $isShared,
            'shared_with' => $sharedWith,
            'is_system' => false,
        ]);
    }

    /**
     * Apply a saved view to the current request.
     */
    public function applyView(SavedView $view): array
    {
        return [
            'filters' => $view->filters,
            'sort' => $view->sort,
            'columns' => $view->columns,
        ];
    }

    /**
     * Get default view for user + route.
     */
    public function getDefaultView(string $route, ?int $userId = null): ?SavedView
    {
        $userId ??= Auth::id();

        return SavedView::forUser($userId)
            ->forRoute($route)
            ->default()
            ->first();
    }

    /**
     * Get views shared with current user.
     */
    public function getSharedViews(string $moduleCode, ?int $userId = null): \Illuminate\Database\Eloquent\Collection
    {
        $userId ??= Auth::id();

        return SavedView::shared()
            ->forModule($moduleCode)
            ->where(function ($query) use ($userId) {
                $query->where('is_system', true)
                    ->orWhere(function ($q) use ($userId) {
                        $q->where('is_shared', true)
                            ->whereJsonContains('shared_with', (string) $userId);
                    });
            })
            ->get();
    }

    /**
     * Get all views for a user + module.
     */
    public function getViewsForModule(string $moduleCode, ?int $userId = null): \Illuminate\Database\Eloquent\Collection
    {
        $userId ??= Auth::id();

        return SavedView::forUser($userId)
            ->forModule($moduleCode)
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get views for a specific route.
     */
    public function getViewsForRoute(string $route, ?int $userId = null): \Illuminate\Database\Eloquent\Collection
    {
        $userId ??= Auth::id();

        return SavedView::forUser($userId)
            ->forRoute($route)
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();
    }

    /**
     * Set a view as default for a route.
     */
    public function setAsDefault(SavedView $view): SavedView
    {
        // Remove default flag from other views for this user+route
        SavedView::forUser($view->user_id)
            ->forRoute($view->route)
            ->where('id', '!=', $view->id)
            ->update(['is_default' => false]);

        // Set this view as default
        $view->is_default = true;
        $view->save();

        return $view->fresh();
    }

    /**
     * Share a view with users/roles.
     */
    public function shareView(SavedView $view, array $sharedWith): SavedView
    {
        $view->is_shared = true;
        $view->shared_with = $sharedWith;
        $view->save();

        return $view->fresh();
    }

    /**
     * Update a saved view.
     */
    public function updateView(
        SavedView $view,
        ?string $name = null,
        ?string $description = null,
        ?array $filters = null,
        ?array $sort = null,
        ?array $columns = null,
        ?bool $isDefault = null,
        ?bool $isShared = null,
        ?array $sharedWith = null
    ): SavedView {
        if ($name !== null) {
            $view->name = $name;
        }
        if ($description !== null) {
            $view->description = $description;
        }
        if ($filters !== null) {
            $view->filters = $filters;
        }
        if ($sort !== null) {
            $view->sort = $sort;
        }
        if ($columns !== null) {
            $view->columns = $columns;
        }
        if ($isDefault !== null) {
            $view->is_default = $isDefault;
        }
        if ($isShared !== null) {
            $view->is_shared = $isShared;
        }
        if ($sharedWith !== null) {
            $view->shared_with = $sharedWith;
        }

        $view->save();

        return $view->fresh();
    }

    /**
     * Delete a saved view.
     */
    public function deleteView(SavedView $view): bool
    {
        return $view->delete();
    }

    /**
     * Duplicate a saved view for the current user.
     */
    public function duplicateView(SavedView $view, string $newName): SavedView
    {
        return SavedView::create([
            'user_id' => Auth::id(),
            'module_code' => $view->module_code,
            'route' => $view->route,
            'name' => $newName,
            'description' => $view->description,
            'filters' => $view->filters,
            'sort' => $view->sort,
            'columns' => $view->columns,
            'is_default' => false,
            'is_shared' => false,
            'shared_with' => null,
            'is_system' => false,
        ]);
    }

    /**
     * Check if user can access a view.
     */
    public function canAccessView(SavedView $view, ?int $userId = null): bool
    {
        $userId ??= Auth::id();

        return $view->isAccessibleBy($userId);
    }

    /**
     * Extract filters from current request.
     */
    public function extractFiltersFromRequest(Request $request): array
    {
        return $request->only(['filter', 'search', 'date_from', 'date_to', 'status', 'category']);
    }

    /**
     * Extract sort from current request.
     */
    public function extractSortFromRequest(Request $request): ?array
    {
        if ($request->has('sort') && $request->has('direction')) {
            return [
                'column' => $request->input('sort'),
                'direction' => $request->input('direction'),
            ];
        }

        return null;
    }

    /**
     * Extract visible columns from current request.
     */
    public function extractColumnsFromRequest(Request $request): ?array
    {
        if ($request->has('columns')) {
            return $request->input('columns');
        }

        return null;
    }
}
