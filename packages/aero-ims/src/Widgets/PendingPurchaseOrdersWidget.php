<?php

declare(strict_types=1);

namespace Aero\Ims\Widgets;

use Aero\Core\Contracts\AbstractDashboardWidget;
use Aero\Contracts\CoreWidgetCategory;

/**
 * Pending Purchase Orders Widget
 *
 * Shows purchase orders awaiting approval or receipt.
 * This is an ALERT widget - procurement workflow.
 *
 * Appears on: Core Dashboard (/dashboard)
 */
class PendingPurchaseOrdersWidget extends AbstractDashboardWidget
{
    protected string $position = 'main_left';

    protected int $order = 41;

    protected int|string $span = 1;

    protected CoreWidgetCategory $category = CoreWidgetCategory::ALERT;

    protected array $requiredPermissions = ['ims.purchase_orders'];

    protected array $dashboards = ['ims'];

    public function getKey(): string
    {
        return 'ims.pending_purchase_orders';
    }

    public function getComponent(): string
    {
        return 'Widgets/IMS/PendingPurchaseOrdersWidget';
    }

    public function getTitle(): string
    {
        return 'Pending Purchase Orders';
    }

    public function getDescription(): string
    {
        return 'POs awaiting approval or receipt';
    }

    public function getModuleCode(): string
    {
        return 'ims';
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

            // Get pending purchase orders
            // In production: Query from PurchaseOrder model
            // For now, return structure with sample data
            $pendingApproval = 0;
            $awaitingReceipt = 0;
            $totalAmount = 0;

            // TODO: Implement actual queries when PurchaseOrder model is ready
            // $pendingApproval = PurchaseOrder::where('status', 'pending_approval')->count();
            // $awaitingReceipt = PurchaseOrder::where('status', 'approved')->whereNull('received_at')->count();
            // $totalAmount = PurchaseOrder::whereIn('status', ['pending_approval', 'approved'])->sum('total_amount');

            return [
                'pending_approval' => $pendingApproval,
                'awaiting_receipt' => $awaitingReceipt,
                'total_amount' => $totalAmount,
                'currency' => 'BDT',
                'show_more_url' => route('ims.purchase-orders.index', [], false),
            ];
        });
    }

    /**
     * Empty state when no data or user not authenticated.
     */
    protected function getEmptyState(): array
    {
        return [
            'pending_approval' => 0,
            'awaiting_receipt' => 0,
            'total_amount' => 0,
            'currency' => 'BDT',
            'message' => 'No pending purchase orders',
        ];
    }
}
