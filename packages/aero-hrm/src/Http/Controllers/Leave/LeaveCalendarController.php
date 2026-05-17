<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\Leave;

use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Models\LeaveApplication;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeaveCalendarController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('hrm.leaves.holiday-calendar.view');

        $from = $request->date('from', now()->startOfMonth());
        $to = $request->date('to', now()->endOfMonth());

        $events = LeaveApplication::query()
            ->with(['employee.user:id,name', 'leaveType:id,name,color'])
            ->where('status', LeaveApplication::STATUS_APPROVED)
            ->where('start_date', '<=', $to)
            ->where('end_date', '>=', $from)
            ->get()
            ->map(fn (LeaveApplication $a) => [
                'id' => $a->id,
                'title' => ($a->employee?->user?->name ?? '—').' — '.$a->leaveType?->name,
                'start' => $a->start_date->toDateString(),
                'end' => $a->end_date->copy()->addDay()->toDateString(),
                'color' => $a->leaveType?->color,
            ]);

        return Inertia::render('HRM/Leave/Calendar/Index', [
            'events' => $events,
            'range' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
        ]);
    }
}
