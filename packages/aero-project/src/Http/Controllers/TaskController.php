<?php

declare(strict_types=1);

namespace Aero\Project\Http\Controllers;

use Aero\Project\Contracts\UserResolverContract;
use Aero\Project\Services\Task\TaskCrudService;
use Aero\Project\Services\Task\TaskImportService;
use Aero\Project\Services\Task\TaskNotificationService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class TaskController extends Controller
{
    public function __construct(
        protected TaskCrudService $crudService,
        protected TaskImportService $importService,
        protected TaskNotificationService $notificationService,
        protected UserResolverContract $userResolver
    ) {}

    /**
     * Display a listing of the tasks.
     */
    public function tasks()
    {
        // Note: Report model reference removed as it should be in its own package
        // $reports = Report::all();
        // $reports_with_tasks = Report::with('tasks')->has('tasks')->get();

        // Get users with project module access for in-charges
        $incharges = $this->getUsersWithProjectAccess();
        $users = User::with('roles')->get();

        // Loop through each user and add a new field 'role' with the role name
        $users->transform(function ($user) {
            $user->role = $user->roles->first()->name ?? 'No Role';

            return $user;
        });

        return Inertia::render('Project/Rfis/Index', [
            'users' => $users,
            'allincharges' => $incharges,
            'title' => 'Tasks',
            // 'reports' => $reports,
            // 'reports_with_tasks' => $reports_with_tasks,
        ]);
    }

    public function getLatestTimestamp()
    {
        $latestTimestamp = \Aero\Rfi\Models\Rfi::max('updated_at');

        return response()->json(['timestamp' => $latestTimestamp]);
    }

    public function allTasks(Request $request)
    {
        try {
            $result = $this->crudService->getAllTasks($request);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function addTask(Request $request)
    {
        try {
            $result = $this->crudService->create($request);

            return response()->json($result);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function updateTask(Request $request)
    {
        try {
            $result = $this->crudService->update($request);

            return response()->json($result);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function deleteTask(Request $request)
    {
        try {
            $result = $this->crudService->delete($request);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function importTasks(Request $request)
    {
        try {
            $results = $this->importService->processImport($request);

            return response()->json([
                'message' => 'Import completed successfully',
                'results' => $results,
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display tasks assigned to the current user (self-service).
     */
    public function myTasks(Request $request)
    {
        $userId = auth()->id();

        try {
            // Fetch tasks assigned to the current user
            $tasks = $this->crudService->getTasks([
                'assignee_id' => $userId,
            ]);

            // Calculate stats
            $stats = [
                'total' => $tasks->count(),
                'pending' => $tasks->where('status', 'pending')->count(),
                'in_progress' => $tasks->where('status', 'in_progress')->count(),
                'completed' => $tasks->where('status', 'completed')->count(),
            ];
        } catch (\Throwable $e) {
            // Handle case where tasks table doesn't exist or other DB errors
            $tasks = collect([]);
            $stats = [
                'total' => 0,
                'pending' => 0,
                'in_progress' => 0,
                'completed' => 0,
            ];
        }

        // Render self-service page
        return Inertia::render('Project/SelfService/MyTasks', [
            'title' => 'My Tasks',
            'tasks' => $tasks,
            'stats' => $stats,
            'filters' => $request->only(['status', 'priority', 'search']),
        ]);
    }

    /**
     * Get users with project module access for in-charge dropdowns.
     */
    protected function getUsersWithProjectAccess(): \Illuminate\Support\Collection
    {
        try {
            return \Aero\HRMAC\Facades\HRMAC::getUsersWithModuleAccess('project');
        } catch (\Exception $e) {
            // Fallback to all active users
            return User::active()->get();
        }
    }
}
