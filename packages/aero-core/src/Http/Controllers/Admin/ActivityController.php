<?php

namespace Aero\Core\Http\Controllers\Admin;

use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Services\ActivityService;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class ActivityController extends Controller
{
    protected ActivityService $activityService;

    public function __construct(ActivityService $activityService)
    {
        $this->activityService = $activityService;
    }

    /**
     * Display activity feed page.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['user_id', 'module', 'action', 'start_date', 'end_date']);
        
        $activities = $this->activityService->getActivities($filters);
        $stats = $this->activityService->getActivityStats();

        return Inertia::render('Core/Activity/Index', [
            'title' => 'Activity Feed',
            'activities' => $activities,
            'stats' => $stats,
            'filters' => $filters,
        ]);
    }

    /**
     * Display single activity details.
     */
    public function show($id)
    {
        $activity = $this->activityService->getActivityById($id);

        return Inertia::render('Core/Activity/Show', [
            'title' => 'Activity Details',
            'activity' => $activity,
        ]);
    }

    /**
     * Get activity statistics API.
     */
    public function stats()
    {
        $stats = $this->activityService->getActivityStats();

        return Response::json($stats);
    }

    /**
     * Export activities to CSV.
     */
    public function export(Request $request)
    {
        $filters = $request->only(['user_id', 'module', 'action', 'start_date', 'end_date']);
        $activities = $this->activityService->getActivities($filters);

        $csv = $this->convertToCsv($activities->items());
        
        return Response::streamDownload(function () use ($csv) {
            echo $csv;
        }, 'activities-' . now()->format('Y-m-d') . '.csv');
    }

    /**
     * Convert activities to CSV format.
     */
    protected function convertToCsv(array $activities): string
    {
        $headers = ['ID', 'User', 'Module', 'Action', 'Description', 'IP Address', 'Created At'];
        $rows = [];

        foreach ($activities as $activity) {
            $rows[] = [
                $activity->id,
                $activity->user?->name ?? 'System',
                $activity->module ?? 'N/A',
                $activity->action,
                $activity->description,
                $activity->ip_address ?? 'N/A',
                $activity->created_at,
            ];
        }

        $fp = fopen('php://temp', 'r+');
        fputcsv($fp, $headers);
        foreach ($rows as $row) {
            fputcsv($fp, $row);
        }
        rewind($fp);
        $csv = stream_get_contents($fp);
        fclose($fp);

        return $csv;
    }
}
