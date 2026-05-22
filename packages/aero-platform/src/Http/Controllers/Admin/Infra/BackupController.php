<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin\Infra;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Http\Requests\Admin\Infra\StoreBackupScheduleRequest;
use Aero\Platform\Models\Infra\BackupRecord;
use Aero\Platform\Models\Infra\BackupSchedule;
use Aero\Platform\Services\Infra\BackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BackupController extends Controller
{
    public function __construct(private readonly BackupService $svc) {}

    public function dashboard(): Response
    {
        return Inertia::render('Platform/Admin/Backup/Index', [
            'schedules' => BackupSchedule::withCount('records')->orderByDesc('created_at')->get(),
            'recentRecords' => BackupRecord::orderByDesc('created_at')->limit(20)->get(),
        ]);
    }

    public function schedules(): Response
    {
        return Inertia::render('Platform/Admin/Backup/Schedules', [
            'schedules' => $this->svc->paginateSchedules(),
        ]);
    }

    public function storeSchedule(StoreBackupScheduleRequest $request): RedirectResponse
    {
        $this->svc->createSchedule($request->validated());

        return back()->with('success', 'Backup schedule created.');
    }

    public function updateSchedule(StoreBackupScheduleRequest $request, BackupSchedule $schedule): RedirectResponse
    {
        $this->svc->updateSchedule($schedule, $request->validated());

        return back()->with('success', 'Backup schedule updated.');
    }

    public function destroySchedule(BackupSchedule $schedule): RedirectResponse
    {
        $this->svc->deleteSchedule($schedule);

        return back()->with('success', 'Backup schedule deleted.');
    }

    public function manualBackup(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tenant_id' => ['required', 'string', 'max:255'],
            'storage_backend' => ['required', 'string', 'max:64'],
        ]);

        $this->svc->runManual($data['tenant_id'], $data['storage_backend']);

        return back()->with('success', 'Manual backup queued.');
    }

    public function restore(Request $request, BackupRecord $backup): RedirectResponse
    {
        $data = $request->validate([
            'target_tenant_id' => ['required', 'string', 'max:255'],
        ]);

        $this->svc->restore($backup, $data['target_tenant_id']);

        return back()->with('success', 'Restore job queued.');
    }

    public function storage(): Response
    {
        return Inertia::render('Platform/Admin/Backup/Storage');
    }

    public function retention(): Response
    {
        return Inertia::render('Platform/Admin/Backup/Retention', [
            'schedules' => BackupSchedule::orderByDesc('created_at')->get(['id', 'tenant_id', 'frequency', 'retention_days']),
        ]);
    }
}
