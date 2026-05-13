<?php

declare(strict_types=1);

namespace Aero\HRM\Widgets;

use Aero\Core\Contracts\AbstractDashboardWidget;
use Aero\Contracts\CoreWidgetCategory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * My Leave Balance Widget for Employee Dashboard
 *
 * Shows quick summary of user's leave balances.
 * This is a SUMMARY widget - quick stats, not detailed breakdown.
 *
 * Appears on: HRM Employee Dashboard (/hrm/employee/dashboard)
 */
class MyLeaveBalanceWidget extends AbstractDashboardWidget
{
    protected string $position = 'main_right';

    protected int $order = 2;

    protected int|string $span = 1;

    protected CoreWidgetCategory $category = CoreWidgetCategory::SUMMARY;

    protected array $requiredPermissions = ['hrm.leaves']; // HRMAC format: module.submodule

    protected array $dashboards = ['hrm.employee'];

    public function getKey(): string
    {
        return 'hrm.my_leave_balance';
    }

    public function getComponent(): string
    {
        // Compact widget for Core Dashboard showing leave balance summary
        return 'Widgets/HRM/MyLeaveBalanceWidget';
    }

    public function getTitle(): string
    {
        return 'Leave Balance';
    }

    public function getDescription(): string
    {
        return 'Your remaining leave days';
    }

    public function getModuleCode(): string
    {
        return 'hrm';
    }

    public function getData(): array
    {
        return $this->safeResolve(function () {
            $user = Auth::user();
            if (! $user) {
                return ['totalRemaining' => 0, 'breakdown' => []];
            }

            // Get leave balances from employee's leave allocation
            $balances = DB::table('leave_allocations')
                ->join('leave_settings', 'leave_allocations.leave_type_id', '=', 'leave_settings.id')
                ->where('leave_allocations.user_id', $user->id)
                ->where('leave_allocations.year', now()->year)
                ->select([
                    'leave_settings.name as leave_type',
                    'leave_allocations.allocated_days',
                    'leave_allocations.used_days',
                    DB::raw('(leave_allocations.allocated_days - leave_allocations.used_days) as remaining_days'),
                ])
                ->get();

            $totalRemaining = $balances->sum('remaining_days');

            return [
                'totalRemaining' => $totalRemaining,
                'breakdown' => $balances->map(fn ($b) => [
                    'type' => $b->leave_type,
                    'remaining' => $b->remaining_days,
                    'allocated' => $b->allocated_days,
                ])->toArray(),
            ];
        }, ['totalRemaining' => 0, 'breakdown' => []]);
    }
}
