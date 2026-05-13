<?php

declare(strict_types=1);

namespace Aero\Core\Widgets;

use Aero\Core\Contracts\AbstractDashboardWidget;
use Aero\Contracts\CoreWidgetCategory;

/**
 * Quick Actions Widget for Core Dashboard
 *
 * Shows quick action buttons based on user permissions.
 * This is an ACTION widget - user can take quick actions.
 *
 * Appears on: Core Dashboard (/dashboard)
 */
class QuickActionsWidget extends AbstractDashboardWidget
{
    protected string $position = 'stats_row';

    protected int $order = 2;

    protected int|string $span = 'full';

    protected CoreWidgetCategory $category = CoreWidgetCategory::ACTION;

    protected array $requiredPermissions = []; // No permissions needed - actions have own checks

    public function getKey(): string
    {
        return 'core.quick_actions';
    }

    public function getComponent(): string
    {
        return 'Widgets/Core/QuickActionsWidget';
    }

    public function getTitle(): string
    {
        return 'Quick Actions';
    }

    public function getDescription(): string
    {
        return 'Frequently used actions';
    }

    public function getModuleCode(): string
    {
        return 'core';
    }

    /**
     * Quick actions widget is always enabled.
     */
    public function isEnabled(): bool
    {
        return true;
    }

    /**
     * Get widget data for frontend.
     */
    public function getData(): array
    {
        $user = auth()->user();
        $actions = [];
        $isSuperAdmin = $this->isSuperAdmin();

        // Core actions available to all users
        $actions[] = [
            'key' => 'profile',
            'label' => 'My Profile',
            'icon' => 'UserCircleIcon',
            'route' => 'profile.index',
            'color' => 'default',
        ];

        // Add role-based quick actions (Super Admin sees all)
        if ($isSuperAdmin || $this->userHasModuleAccess('core', 'users')) {
            $actions[] = [
                'key' => 'users',
                'label' => 'Manage Users',
                'icon' => 'UsersIcon',
                'route' => 'users.index',
                'color' => 'primary',
            ];
        }

        if ($isSuperAdmin || $this->userHasModuleAccess('core', 'roles')) {
            $actions[] = [
                'key' => 'roles',
                'label' => 'Manage Roles',
                'icon' => 'ShieldCheckIcon',
                'route' => 'roles.index',
                'color' => 'secondary',
            ];
        }

        // HRM-specific quick actions (if module is accessible)
        if ($isSuperAdmin || $this->userHasModuleAccess('hrm', 'attendance')) {
            $actions[] = [
                'key' => 'punch',
                'label' => 'Clock In/Out',
                'icon' => 'ClockIcon',
                'route' => 'hrm.attendance.index',
                'color' => 'success',
            ];
        }

        if ($isSuperAdmin || $this->userHasModuleAccess('hrm', 'leaves')) {
            $actions[] = [
                'key' => 'leave',
                'label' => 'Apply Leave',
                'icon' => 'CalendarIcon',
                'route' => 'hrm.leave.apply',
                'color' => 'warning',
            ];
        }

        return [
            'actions' => array_slice($actions, 0, 6), // Limit to 6 actions
        ];
    }
}
