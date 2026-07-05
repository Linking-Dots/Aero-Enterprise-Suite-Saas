<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\Leave;

use Aero\HRM\Http\Controllers\Concerns\ProvidesLeaveRailStats;
use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Models\Employee;
use Aero\HRM\Models\LeaveBalance;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeaveBalanceController extends Controller
{
    use ProvidesLeaveRailStats;

    public function index(Request $request): Response
    {
        $this->authorize('hrm.leaves.leave-balances.view');

        $year = (int) $request->integer('year', now()->year);

        $balances = LeaveBalance::query()
            ->with(['employee.user:id,name', 'leaveType:id,name,color'])
            ->where('year', $year)
            ->when($request->integer('employee_id'), fn ($q, $id) => $q->where('employee_id', $id))
            ->orderBy('employee_id')
            ->paginate(50)
            ->withQueryString()
            ->through(fn (LeaveBalance $b) => [
                'id' => $b->id,
                'employee' => $b->employee?->user?->name,
                'leave_type' => $b->leaveType?->name,
                'leave_type_color' => $b->leaveType?->color,
                'entitled' => (float) $b->entitled,
                'used' => (float) $b->used,
                'carried_forward' => (float) $b->carried_forward,
                'remaining' => $b->remaining,
            ]);

        $yearAgg = LeaveBalance::query()->where('year', $year);

        return Inertia::render('HRM/Leave/Balance/Index', [
            'balances' => $balances,
            'employees' => Employee::with('user:id,name')->get()
                ->map(fn ($e) => ['id' => $e->id, 'label' => $e->user?->name ?? '—']),
            'year' => $year,
            'stats' => $this->leaveRailStats() + [
                'tracked_employees' => (int) (clone $yearAgg)->distinct('employee_id')->count('employee_id'),
                'total_entitled'    => (float) (clone $yearAgg)->sum('entitled'),
                'total_remaining'   => (float) (clone $yearAgg)
                    ->selectRaw('COALESCE(SUM(entitled + carried_forward - used - encashed), 0) as r')
                    ->value('r'),
            ],
        ]);
    }
}
