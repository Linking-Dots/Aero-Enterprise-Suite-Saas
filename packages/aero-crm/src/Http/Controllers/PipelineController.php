<?php

namespace Aero\Crm\Http\Controllers;

use Aero\Core\Models\User;
use Aero\Crm\Models\Pipeline;
use App\Services\CRM\PipelineService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PipelineController extends Controller
{
    public function __construct(
        protected PipelineService $pipelineService
    ) {}

    /**
     * Display the Kanban pipeline board
     */
    public function index(Request $request)
    {
        $this->authorize('view_sales_pipeline');

        // Get the requested pipeline or default
        $pipelineId = $request->query('pipeline');

        $pipeline = $pipelineId
            ? Pipeline::findOrFail($pipelineId)
            : Pipeline::where('is_default', true)->first() ?? Pipeline::first();

        if (! $pipeline) {
            return Inertia::render('CRM/Pipeline/Index', [
                'pipeline' => null,
                'columns' => [],
                'summary' => [],
                'pipelines' => [],
                'users' => [],
            ]);
        }

        // Get Kanban data
        $kanbanData = $this->pipelineService->getPipelineForKanban(
            $pipeline->id,
            $request->only(['assigned_to', 'status', 'search', 'is_rotting'])
        );

        // Get all pipelines for selector
        $pipelines = Pipeline::where('is_active', true)
            ->select('id', 'name', 'type', 'is_default')
            ->orderBy('name')
            ->get();

        // Get users for assignee filter
        $users = User::select('id', 'name')
            ->whereNull('deleted_at') // active = not soft-deleted
            ->orderBy('name')
            ->get();

        return Inertia::render('CRM/Pipeline/Index', [
            'pipeline' => $kanbanData['pipeline'],
            'columns' => $kanbanData['columns'],
            'summary' => $kanbanData['summary'],
            'pipelines' => $pipelines,
            'users' => $users,
        ]);
    }

    /**
     * Get pipeline data for AJAX refresh
     */
    public function getData(Request $request, Pipeline $pipeline)
    {
        $this->authorize('view_sales_pipeline');

        $kanbanData = $this->pipelineService->getPipelineForKanban(
            $pipeline->id,
            $request->only(['assigned_to', 'status', 'search', 'is_rotting'])
        );

        return response()->json([
            'columns' => $kanbanData['columns'],
            'summary' => $kanbanData['summary'],
        ]);
    }
}
