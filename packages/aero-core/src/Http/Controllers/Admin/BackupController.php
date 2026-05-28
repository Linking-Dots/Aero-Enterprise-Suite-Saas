<?php

namespace Aero\Core\Http\Controllers\Admin;

use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Services\BackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class BackupController extends Controller
{
    protected $backupService;

    public function __construct(BackupService $backupService)
    {
        $this->backupService = $backupService;
    }

    /**
     * Display the backup dashboard.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['tenant_id', 'status', 'type']);
        $backups = $this->backupService->listBackups($filters);
        $stats = $this->backupService->getBackupStats();

        return Inertia::render('Core/Backup/Index', [
            'title' => 'Backup Dashboard',
            'backups' => $backups,
            'stats' => $stats,
            'filters' => $filters,
        ]);
    }

    /**
     * Store a newly created backup.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'type' => 'required|in:full,database,files',
            'tenant_id' => 'nullable|exists:tenants,id',
        ]);

        try {
            $backup = $this->backupService->createBackup(
                $request->input('name'),
                $request->input('type'),
                $request->input('tenant_id')
            );

            return redirect()->back()->with('success', 'Backup created successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to create backup: '.$e->getMessage());
        }
    }

    /**
     * Display the specified backup.
     */
    public function show($id)
    {
        $backup = $this->backupService->getBackup($id);

        return Inertia::render('Core/Backup/Show', [
            'title' => 'Backup Details',
            'backup' => $backup,
        ]);
    }

    /**
     * Remove the specified backup.
     */
    public function destroy($id)
    {
        try {
            $this->backupService->deleteBackup($id);

            return redirect()->back()->with('success', 'Backup deleted successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete backup: '.$e->getMessage());
        }
    }

    /**
     * Download the specified backup.
     */
    public function download($id)
    {
        try {
            return $this->backupService->downloadBackup($id);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to download backup: '.$e->getMessage());
        }
    }

    /**
     * Get backup statistics (API endpoint).
     */
    public function stats()
    {
        return response()->json($this->backupService->getBackupStats());
    }

    /**
     * CA-3: Manual backup page.
     */
    public function manualPage(): Response
    {
        return Inertia::render('Core/Backup/Manual', [
            'last_backup' => DB::table('backups')->orderByDesc('created_at')->first(),
            'backup_size_estimate' => 'Calculating…',
        ]);
    }
}
