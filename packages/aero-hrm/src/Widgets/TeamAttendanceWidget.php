<?php

declare(strict_types=1);

namespace Aero\HRM\Widgets;

use Aero\Core\Contracts\AbstractDashboardWidget;
use Aero\Contracts\CoreWidgetCategory;

/**
 * Team Attendance Widget
 *
 * Displays team attendance overview for managers.
 *
 * Appears on: HRM Manager Dashboard (/hrm/dashboard)
 */
class TeamAttendanceWidget extends AbstractDashboardWidget
{
    protected string $position = 'stats_row';

    protected int $order = 95;

    protected int|string $span = 1;

    protected CoreWidgetCategory $category = CoreWidgetCategory::SUMMARY;

    protected array $requiredPermissions = ['hrm.attendance']; // HRMAC format: module.submodule

    protected array $dashboards = ['hrm'];

    public function getKey(): string
    {
        return 'hrm.team_attendance';
    }

    public function getComponent(): string
    {
        return 'Widgets/HRM/TeamAttendanceWidget';
    }

    public function getTitle(): string
    {
        return 'Team Attendance';
    }

    public function getDescription(): string
    {
        return 'Today\'s team attendance overview';
    }

    public function getModuleCode(): string
    {
        return 'hrm';
    }

    public function getData(): array
    {
        $user = auth()->user();

        if (! $user) {
            return [
                'present' => 0,
                'absent' => 0,
                'late' => 0,
                'on_leave' => 0,
                'total_team' => 0,
                'attendance_rate' => 0,
            ];
        }

        // In production, query from Attendance model for team members
        return [
            'present' => 0,
            'absent' => 0,
            'late' => 0,
            'on_leave' => 0,
            'total_team' => 0,
            'attendance_rate' => 0,
        ];
    }

    public function getProps(): array
    {
        return array_merge($this->getData(), [
            'title' => $this->getTitle(),
            'view_details_url' => route('hrm.attendance.index', [], false),
        ]);
    }

    /**
     * Check if widget is enabled.
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

    public function getPriority(): int
    {
        return 95;
    }
}
