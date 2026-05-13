<?php

declare(strict_types=1);

namespace Aero\Scm\Widgets;

use Aero\Core\Contracts\AbstractDashboardWidget;
use Aero\Contracts\CoreWidgetCategory;

/**
 * Pending Purchase Requisitions Widget
 *
 * Shows purchase requisitions awaiting approval.
 * This is an ALERT widget - procurement workflow.
 *
 * Appears on: Core Dashboard (/dashboard)
 */
class PendingPurchaseRequisitionsWidget extends AbstractDashboardWidget
{
    protected string $position = 'main_left';

    protected int $order = 60;

    protected int|string $span = 1;

    protected CoreWidgetCategory $category = CoreWidgetCategory::ALERT;

    protected array $requiredPermissions = ['scm.requisitions'];

    protected array $dashboards = ['scm'];

    public function getKey(): string
    {
        return 'scm.pending_requisitions';
    }

    public function getComponent(): string
    {
        return 'Widgets/SCM/PendingPurchaseRequisitionsWidget';
    }

    public function getTitle(): string
    {
        return 'Pending Requisitions';
    }

    public function getDescription(): string
    {
        return 'Purchase requisitions awaiting approval';
    }

    public function getModuleCode(): string
    {
        return 'scm';
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

            // Get pending purchase requisitions
            // In production: Query from PurchaseRequisition model
            // For now, return structure with sample data
            $pendingCount = 0;
            $myRequests = 0;
            $totalAmount = 0;

            // TODO: Implement actual queries when PurchaseRequisition model is ready
            // $pendingCount = PurchaseRequisition::where('status', 'pending')->count();
            // $myRequests = PurchaseRequisition::where('requester_id', $user->id)->where('status', 'pending')->count();
            // $totalAmount = PurchaseRequisition::where('status', 'pending')->sum('estimated_amount');

            return [
                'pending' => $pendingCount,
                'my_requests' => $myRequests,
                'total_amount' => $totalAmount,
                'currency' => 'BDT',
                'show_more_url' => route('scm.requisitions.index', [], false),
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
            'my_requests' => 0,
            'total_amount' => 0,
            'currency' => 'BDT',
            'message' => 'No pending requisitions',
        ];
    }
}
