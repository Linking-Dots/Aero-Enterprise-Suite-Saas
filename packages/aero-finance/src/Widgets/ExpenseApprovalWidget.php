<?php

declare(strict_types=1);

namespace Aero\Finance\Widgets;

use Aero\Core\Contracts\AbstractDashboardWidget;
use Aero\Contracts\CoreWidgetCategory;

/**
 * Expense Approval Widget
 *
 * Shows pending expense claims requiring manager approval.
 * This is an ALERT widget - manager approval workflow.
 *
 * Appears on: Core Dashboard (/dashboard)
 */
class ExpenseApprovalWidget extends AbstractDashboardWidget
{
    protected string $position = 'main_left';

    protected int $order = 21;

    protected int|string $span = 1;

    protected CoreWidgetCategory $category = CoreWidgetCategory::ALERT;

    protected array $requiredPermissions = ['finance.expenses'];

    protected array $dashboards = ['finance'];

    public function getKey(): string
    {
        return 'finance.expense_approvals';
    }

    public function getComponent(): string
    {
        return 'Widgets/Finance/ExpenseApprovalWidget';
    }

    public function getTitle(): string
    {
        return 'Expense Approvals';
    }

    public function getDescription(): string
    {
        return 'Pending expense claims for approval';
    }

    public function getModuleCode(): string
    {
        return 'finance';
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

            // Get pending expense approvals
            // In production: Query from Expense model where user is approver
            // For now, return structure with sample data
            $pendingCount = 0;
            $totalAmount = 0;
            $oldestDays = 0;

            // TODO: Implement actual queries when Expense model is ready
            // $pendingCount = Expense::where('approver_id', $user->id)->where('status', 'pending')->count();
            // $totalAmount = Expense::where('approver_id', $user->id)->where('status', 'pending')->sum('amount');
            // $oldest = Expense::where('approver_id', $user->id)->where('status', 'pending')->oldest()->first();
            // $oldestDays = $oldest ? now()->diffInDays($oldest->created_at) : 0;

            return [
                'pending' => $pendingCount,
                'total_amount' => $totalAmount,
                'oldest_days' => $oldestDays,
                'currency' => 'BDT',
                'show_more_url' => route('finance.expenses.approvals', [], false),
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
            'total_amount' => 0,
            'oldest_days' => 0,
            'currency' => 'BDT',
            'message' => 'No pending approvals',
        ];
    }
}
