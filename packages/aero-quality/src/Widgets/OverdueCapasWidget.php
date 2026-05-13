<?php

declare(strict_types=1);

namespace Aero\Quality\Widgets;

use Aero\Core\Contracts\AbstractDashboardWidget;
use Aero\Contracts\CoreWidgetCategory;

/**
 * Overdue CAPAs Widget
 *
 * Shows overdue Corrective and Preventive Actions requiring immediate attention.
 * This is an ALERT widget - critical action items overdue.
 *
 * Appears on: Core Dashboard (/dashboard)
 */
class OverdueCapasWidget extends AbstractDashboardWidget
{
    protected string $position = 'main_right';

    protected int $order = 31;

    protected int|string $span = 1;

    protected CoreWidgetCategory $category = CoreWidgetCategory::ALERT;

    protected array $requiredPermissions = ['quality.capa'];

    protected array $dashboards = ['quality'];

    public function getKey(): string
    {
        return 'quality.overdue_capas';
    }

    public function getComponent(): string
    {
        return 'Widgets/Quality/OverdueCapasWidget';
    }

    public function getTitle(): string
    {
        return 'Overdue CAPAs';
    }

    public function getDescription(): string
    {
        return 'Corrective actions past due date';
    }

    public function getModuleCode(): string
    {
        return 'quality';
    }

    /**
     * Check if widget is enabled for current user.
     * Super Administrators bypass ALL checks.
     */
    public function isEnabled(): bool
    {
        // Super Admin bypass - MUST BE FIRST
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (! $this->isModuleActive()) {
            return false;
        }

        // Check HRMAC module access
        return $this->userHasModuleAccess();
    }

    /**
     * Get widget data for frontend.
     */
    public function getData(): array
    {
        return $this->safeResolve(function () {
            $user = auth()->user();
            if (! $user) {
                return $this->getEmptyState();
            }

            // Get overdue CAPAs count
            // In production: Query from CAPA model filtered by due_date < today
            // For now, return structure with sample data
            $overdueCount = 0;
            $dueThisWeek = 0;
            $assignedToMe = 0;

            // TODO: Implement actual queries when CAPA model is ready
            // $overdueCount = CAPA::where('due_date', '<', now())->whereIn('status', ['open', 'in_progress'])->count();
            // $dueThisWeek = CAPA::whereBetween('due_date', [now(), now()->addWeek()])->count();
            // $assignedToMe = CAPA::where('assigned_to', $user->id)->where('due_date', '<', now())->count();

            return [
                'total' => $overdueCount,
                'due_this_week' => $dueThisWeek,
                'assigned_to_me' => $assignedToMe,
                'show_more_url' => route('quality.capa.index', [], false),
            ];
        });
    }

    /**
     * Empty state when no data or user not authenticated.
     */
    protected function getEmptyState(): array
    {
        return [
            'total' => 0,
            'due_this_week' => 0,
            'assigned_to_me' => 0,
            'message' => 'No overdue CAPAs',
        ];
    }
}
