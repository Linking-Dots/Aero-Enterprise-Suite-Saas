<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\Leave;

use Aero\HRM\Http\Controllers\Concerns\ProvidesLeaveRailStats;
use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Models\LeaveApplication;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeaveCalendarController extends Controller
{
    use ProvidesLeaveRailStats;

    public function index(Request $request): Response
    {
        $this->authorize('hrm.leaves.holiday-calendar.view');

        // NOTE: Request::date($key, $format) — the 2nd arg is a *format*, not a
        // default value, so we fall back explicitly (SubstituteBindings gotcha
        // aside, passing a Carbon as format would throw).
        $from = $request->date('from') ?? now()->startOfMonth();
        $to = $request->date('to') ?? now()->endOfMonth();

        $applications = LeaveApplication::query()
            ->with(['employee.user:id,name', 'leaveType:id,name,color'])
            ->where('status', LeaveApplication::STATUS_APPROVED)
            ->where('start_date', '<=', $to)
            ->where('end_date', '>=', $from)
            ->get();

        $events = $applications->map(fn (LeaveApplication $a) => [
            'id' => $a->id,
            'title' => ($a->employee?->user?->name ?? '—').' — '.$a->leaveType?->name,
            'start' => $a->start_date->toDateString(),
            'end' => $a->end_date->copy()->addDay()->toDateString(),
            'color' => $a->leaveType?->color,
        ]);

        $today = now()->startOfDay();
        $onLeaveToday = LeaveApplication::query()
            ->where('status', LeaveApplication::STATUS_APPROVED)
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->distinct('employee_id')
            ->count('employee_id');

        return Inertia::render('HRM/Leave/Calendar/Index', [
            'events' => $events,
            'range' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'stats' => $this->leaveRailStats() + [
                'on_leave_today'   => $onLeaveToday,
                'in_range'         => $applications->count(),
                'employees_affected' => $applications->pluck('employee_id')->unique()->count(),
            ],
        ]);
    }
}
