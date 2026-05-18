<?php

namespace Aero\HRM\Http\Controllers\Attendance;

use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Models\Timesheet;
use Aero\HRM\Services\Attendance\TimesheetAggregator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TimesheetController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('hrmac', 'hrm.attendance.attendance-logs.view');

        $weekStart = Carbon::parse($request->date('week', Carbon::now()->startOfWeek()))->toDateString();
        $employee = $request->user()->employee;
        abort_unless($employee, 403, 'No employee profile.');

        $timesheet = Timesheet::with('entries')
            ->firstOrCreate(
                ['employee_id' => $employee->id, 'week_start' => $weekStart],
                ['status' => 'draft', 'total_hours' => 0],
            );

        return Inertia::render('HRM/Attendance/Timesheets/Index', [
            'timesheet' => $timesheet,
            'filters' => ['week' => $weekStart],
        ]);
    }

    public function update(Request $request, Timesheet $timesheet, TimesheetAggregator $agg): RedirectResponse
    {
        Gate::authorize('hrmac', 'hrm.attendance.attendance-logs.view');

        $data = $request->validate([
            'entries' => ['required', 'array'],
            'entries.*.entry_date' => ['required', 'date'],
            'entries.*.task' => ['required', 'string', 'max:250'],
            'entries.*.project' => ['nullable', 'string', 'max:150'],
            'entries.*.hours' => ['required', 'numeric', 'min:0', 'max:24'],
        ]);

        $timesheet->entries()->delete();
        foreach ($data['entries'] as $entry) {
            $timesheet->entries()->create($entry);
        }
        $agg->recompute($timesheet);

        return back()->with('success', 'Timesheet saved.');
    }
}
