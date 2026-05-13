<?php

namespace Aero\Project\Http\Controllers;

use Aero\Project\Models\Project;
use Aero\Project\Models\Task;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class ProjectDashboardController extends Controller
{
    public function index()
    {
        // Build stats with defensive checks for missing tables
        $stats = [
            'total_projects' => 0,
            'active_projects' => 0,
            'completed_projects' => 0,
            'pending_tasks' => 0,
        ];

        try {
            if (Schema::hasTable('projects')) {
                $stats['total_projects'] = Project::count();
                $stats['active_projects'] = Project::where('status', 'in_progress')->count();
                $stats['completed_projects'] = Project::where('status', 'completed')->count();
            }

            if (Schema::hasTable('tasks')) {
                $stats['pending_tasks'] = Task::where('status', 'pending')->count();
            }
        } catch (\Exception $e) {
            // Log error but continue - dashboard should still load
            report($e);
        }

        // Dynamic widgets removed — widget registry is being deleted
        $dynamicWidgets = [];

        return Inertia::render('Project/Dashboard', [
            'title' => 'Project Dashboard',
            'stats' => $stats,
            'dynamicWidgets' => $dynamicWidgets,
        ]);
    }
}
