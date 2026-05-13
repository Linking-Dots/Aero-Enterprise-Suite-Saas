<?php

declare(strict_types=1);

namespace Aero\Compliance\Widgets;

use Aero\Core\Contracts\AbstractDashboardWidget;
use Aero\Contracts\CoreWidgetCategory;

/**
 * Pending Compliance Actions Widget
 *
 * Shows pending compliance tasks requiring attention.
 * This is an ALERT widget - regulatory risk management.
 *
 * Appears on: Core Dashboard (/dashboard)
 */
class PendingComplianceActionsWidget extends AbstractDashboardWidget
{
    protected string $position = 'main_left';

    protected int $order = 70;

    protected int|string $span = 1;

    protected CoreWidgetCategory $category = CoreWidgetCategory::ALERT;

    protected array $requiredPermissions = ['compliance.actions'];

    protected array $dashboards = ['compliance'];

    public function getKey(): string
    {
        return 'compliance.pending_actions';
    }

    public function getComponent(): string
    {
        return 'Widgets/Compliance/PendingComplianceActionsWidget';
    }

    public function getTitle(): string
    {
        return 'Pending Compliance Actions';
    }

    public function getDescription(): string
    {
        return 'Compliance tasks requiring attention';
    }

    public function getModuleCode(): string
    {
        return 'compliance';
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

            // Get pending compliance actions
            // In production: Query from ComplianceAction model
            // For now, return structure with sample data
            $pendingCount = 0;
            $overdueCount = 0;
            $assignedToMe = 0;

            // TODO: Implement actual queries when ComplianceAction model is ready
            // $pendingCount = ComplianceAction::where('status', 'pending')->count();
            // $overdueCount = ComplianceAction::where('due_date', '<', now())->whereIn('status', ['pending', 'in_progress'])->count();
            // $assignedToMe = ComplianceAction::where('assigned_to', $user->id)->whereIn('status', ['pending', 'in_progress'])->count();

            return [
                'pending' => $pendingCount,
                'overdue' => $overdueCount,
                'assigned_to_me' => $assignedToMe,
                'show_more_url' => route('compliance.actions.index', [], false),
            ];
        });
    }

    /**
     * Empty state when no data or user not authenticated.
     */
    protected function getEmptyState(): array
    {
        return [
            'pending' => 0,
            'overdue' => 0,
            'assigned_to_me' => 0,
            'message' => 'No pending compliance actions',
        ];
    }
}
