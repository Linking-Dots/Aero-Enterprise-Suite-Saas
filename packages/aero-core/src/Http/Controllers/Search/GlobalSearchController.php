<?php

declare(strict_types=1);

namespace Aero\Core\Http\Controllers\Search;

use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Services\Search\GlobalSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Global Search Controller
 *
 * Handles search page rendering and JSON API endpoints for the
 * global search overlay and full results page.
 */
class GlobalSearchController extends Controller
{
    public function __construct(
        protected GlobalSearchService $searchService
    ) {}

    /**
     * Render the full search results page.
     */
    public function index(Request $request): Response
    {
        $query = $request->input('q', '');
        $type = $request->input('type');

        $searchData = $query !== ''
            ? $this->searchService->search($query, $type)
            : ['results' => collect(), 'total' => 0, 'types' => []];

        return Inertia::render('Core/Search/Index', [
            'title' => $query ? "Search: {$query}" : 'Search',
            'query' => $query,
            'type' => $type,
            'results' => $searchData['results'],
            'total' => $searchData['total'],
            'types' => $searchData['types'],
        ]);
    }

    /**
     * JSON API endpoint for live search results.
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('q', '');
        $type = $request->input('type');
        $perPage = (int) $request->input('per_page', 20);

        if ($query === '') {
            return response()->json([
                'results' => [],
                'total' => 0,
                'types' => array_map('class_basename', $this->searchService->getModels()),
            ]);
        }

        $searchData = $this->searchService->search($query, $type, $perPage);

        return response()->json([
            'results' => $searchData['results'],
            'total' => $searchData['total'],
            'types' => $searchData['types'],
        ]);
    }

    /**
     * JSON API endpoint for quick autocomplete suggestions.
     */
    public function suggestions(Request $request): JsonResponse
    {
        $query = $request->input('q', '');
        $limit = (int) $request->input('limit', 5);

        if ($query === '') {
            return response()->json(['results' => []]);
        }

        $results = $this->searchService->suggest($query, $limit);

        return response()->json([
            'results' => $results,
        ]);
    }
}
