<?php

namespace Aero\Core\Http\Controllers\Admin;

use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Services\RestoreService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RestoreController extends Controller
{
    protected $restoreService;

    public function __construct(RestoreService $restoreService)
    {
        $this->restoreService = $restoreService;
    }

    /**
     * Display the restore points list.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['tenant_id', 'type']);
        $restorePoints = $this->restoreService->listRestorePoints($filters);

        return Inertia::render('Core/Restore/Index', [
            'title' => 'Restore Points',
            'restore_points' => $restorePoints,
            'filters' => $filters,
        ]);
    }

    /**
     * Display the specified restore point.
     */
    public function show($id)
    {
        $restorePoint = $this->restoreService->getRestorePoint($id);
        $validation = $this->restoreService->validateBackup($id);

        return Inertia::render('Core/Restore/Show', [
            'title' => 'Restore Point Details',
            'restore_point' => $restorePoint,
            'validation' => $validation,
        ]);
    }

    /**
     * Validate a backup.
     */
    public function validateBackup($id)
    {
        $validation = $this->restoreService->validateBackup($id);
        return response()->json($validation);
    }

    /**
     * Restore from a backup point.
     */
    public function restore(Request $request, $id)
    {
        $request->validate([
            'restore_type' => 'required|in:full,selective',
            'restore_database' => 'required|boolean',
            'restore_files' => 'required|boolean',
            'tables' => 'array',
            'files' => 'array',
        ]);

        try {
            if ($request->input('restore_type') === 'full') {
                $this->restoreService->restoreFull($id, [
                    'restore_database' => $request->input('restore_database'),
                    'restore_files' => $request->input('restore_files'),
                ]);
            } else {
                $this->restoreService->restoreSelective($id, [
                    'tables' => $request->input('tables', []),
                    'files' => $request->input('files', []),
                ]);
            }

            return redirect()->back()->with('success', 'Restore completed successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Restore failed: ' . $e->getMessage());
        }
    }
}
