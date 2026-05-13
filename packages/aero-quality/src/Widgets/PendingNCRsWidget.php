<?php

declare(strict_types=1);

namespace Aero\Quality\Widgets;

use Aero\Core\Contracts\AbstractDashboardWidget;
use Aero\Contracts\CoreWidgetCategory;

/**
 * Pending Non-Conformance Reports Widget
 *
 * Shows pending NCRs requiring action (investigation, approval, closure).
 * This is an ALERT widget - user needs to take action on quality issues.
 *
 * Appears on: Core Dashboard (/dashboard)
 */
class PendingNCRsWidget extends AbstractDashboardWidget
{
    protected string $position = 'main_right';

    protected int $order = 30;

    protected int|string $span = 1;

    protected CoreWidgetCategory $category = CoreWidgetCategory::ALERT;

    protected array $requiredPermissions = ['quality.ncr'];

    protected array $dashboards = ['quality'];

    public function getKey(): string
    {
        return 'quality.pending_ncrs';
    }

    public function getComponent(): string
    {
        return 'Widgets/Quality/PendingNCRsWidget';
    }

    public function getTitle(): string
    {
        return 'Pending NCRs';
    }

    public function getDescription(): string
    {
        return 'Non-conformance reports requiring action';
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

            // Get pending NCRs count
            // In production: Query from NCR model filtered by status
            // For now, return structure with sample data
            $pendingCount = 0;
            $overdueCount = 0;
            $assignedToMe = 0;

            // TODO: Implement actual queries when NCR model is ready
            // $pendingCount = NCR::where('status', 'pending')->count();
            // $overdueCount = NCR::where('status', 'overdue')->count();
            // $assignedToMe = NCR::where('assigned_to', $user->id)->whereIn('status', ['pending', 'in_progress'])->count();

            return [
                'total' => $pendingCount,
                'overdue' => $overdueCount,
                'assigned_to_me' => $assignedToMe,
                'show_more_url' => route('quality.ncr.index', [], false),
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
            'overdue' => 0,
            'assigned_to_me' => 0,
            'message' => 'No pending NCRs',
        ];
    }
}
