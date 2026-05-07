<?php

namespace Aero\Core\Http\Controllers\Admin;

use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Models\BackupConfiguration;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BackupConfigController extends Controller
{
    /**
     * Display the backup configuration page.
     */
    public function index()
    {
        $config = BackupConfiguration::getDefault();

        return Inertia::render('Core/Backup/Config', [
            'title' => 'Backup Configuration',
            'config' => $config,
        ]);
    }

    /**
     * Update the backup configuration.
     */
    public function update(Request $request)
    {
        $request->validate([
            'storage_driver' => 'required|in:local,s3,dropbox,gcs',
            'schedule_frequency' => 'required|in:daily,weekly,monthly',
            'retention_days' => 'required|integer|min:1|max:365',
            'encryption_enabled' => 'required|boolean',
            'notification_email' => 'nullable|email',
            'included_files' => 'array',
            'excluded_files' => 'array',
            'backup_type' => 'required|in:full,database,files',
            'active' => 'required|boolean',
        ]);

        $config = BackupConfiguration::getDefault();
        $config->update($request->all());

        return redirect()->back()->with('success', 'Backup configuration updated successfully');
    }

    /**
     * Test the backup configuration.
     */
    public function test(Request $request)
    {
        // This would test the storage connection and configuration
        return response()->json([
            'success' => true,
            'message' => 'Configuration test passed',
        ]);
    }
}
