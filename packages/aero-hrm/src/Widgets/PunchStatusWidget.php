<?php

declare(strict_types=1);

namespace Aero\HRM\Widgets;

use Aero\Core\Contracts\AbstractDashboardWidget;
use Aero\Contracts\CoreWidgetCategory;
use Aero\HRM\Models\Attendance;
use Illuminate\Support\Facades\Auth;

/**
 * Punch Status Widget for Employee Dashboard
 *
 * Shows clock in/out button and current status for employees.
 * This is an ACTION widget - user needs to take action.
 *
 * Appears on: HRM Employee Dashboard (/hrm/employee/dashboard)
 */
class PunchStatusWidget extends AbstractDashboardWidget
{
    protected string $position = 'main_left';

    protected int $order = 1; // Highest priority - show first

    protected int|string $span = 1;

    protected CoreWidgetCategory $category = CoreWidgetCategory::ACTION;

    protected array $requiredPermissions = ['hrm.attendance']; // HRMAC format: module.submodule

    protected array $dashboards = ['hrm.employee'];

    public function getKey(): string
    {
        return 'hrm.punch_status';
    }

    public function getComponent(): string
    {
        // Uses existing PunchStatusCard component which is self-contained
        return 'Components/PunchStatusCard';
    }

    public function getTitle(): string
    {
        return 'Clock In/Out';
    }

    public function getDescription(): string
    {
        return 'Your attendance status';
    }

    public function getModuleCode(): string
    {
        return 'hrm';
    }

    /**
     * Override isEnabled to check HRM attendance module access.
     * Super Administrators bypass ALL checks.
     */
    public function isEnabled(): bool
    {
        // Super Admin bypass - always enabled, bypasses ALL checks
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (! $this->isModuleActive()) {
            return false;
        }

        // Check HRM attendance module access via HRMAC
        return $this->userHasModuleAccess();
    }

    public function getData(): array
    {
        return $this->safeResolve(function () {
            $user = Auth::user();
            if (! $user) {
                return $this->getEmptyState();
            }

            $today = now()->toDateString();

            // Get today's attendance record
            $attendance = Attendance::where('user_id', $user->id)
                ->whereDate('date', $today)
                ->first();

            if (! $attendance) {
                return [
                    'status' => 'not_punched',
                    'message' => 'Not clocked in yet',
                    'canPunchIn' => true,
                    'canPunchOut' => false,
                    'punchInTime' => null,
                    'punchOutTime' => null,
                    'workingHours' => null,
                ];
            }

            $isPunchedIn = $attendance->punch_in_time && ! $attendance->punch_out_time;
            $isPunchedOut = $attendance->punch_in_time && $attendance->punch_out_time;

            return [
                'status' => $isPunchedOut ? 'completed' : ($isPunchedIn ? 'working' : 'not_punched'),
                'message' => $isPunchedOut
                    ? 'Day completed'
                    : ($isPunchedIn ? 'Currently working' : 'Not clocked in'),
                'canPunchIn' => ! $attendance->punch_in_time,
                'canPunchOut' => $isPunchedIn,
                'punchInTime' => $attendance->punch_in_time?->format('h:i A'),
                'punchOutTime' => $attendance->punch_out_time?->format('h:i A'),
                'workingHours' => $attendance->working_hours,
            ];
        }, $this->getEmptyState());
    }

    private function getEmptyState(): array
    {
        return [
            'status' => 'unavailable',
            'message' => 'Attendance unavailable',
            'canPunchIn' => false,
            'canPunchOut' => false,
            'punchInTime' => null,
            'punchOutTime' => null,
            'workingHours' => null,
        ];
    }
}
