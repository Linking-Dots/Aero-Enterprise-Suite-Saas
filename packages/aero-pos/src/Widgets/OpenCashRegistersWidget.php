<?php

declare(strict_types=1);

namespace Aero\Pos\Widgets;

use Aero\Core\Contracts\AbstractDashboardWidget;
use Aero\Contracts\CoreWidgetCategory;

/**
 * Open Cash Registers Widget
 *
 * Shows cash registers needing end-of-day closure.
 * This is an ALERT widget - daily closing workflow.
 *
 * Appears on: Core Dashboard (/dashboard)
 */
class OpenCashRegistersWidget extends AbstractDashboardWidget
{
    protected string $position = 'main_left';

    protected int $order = 51;

    protected int|string $span = 1;

    protected CoreWidgetCategory $category = CoreWidgetCategory::ALERT;

    protected array $requiredPermissions = ['pos.registers'];

    protected array $dashboards = ['pos'];

    public function getKey(): string
    {
        return 'pos.open_cash_registers';
    }

    public function getComponent(): string
    {
        return 'Widgets/POS/OpenCashRegistersWidget';
    }

    public function getTitle(): string
    {
        return 'Open Cash Registers';
    }

    public function getDescription(): string
    {
        return 'Registers needing closure';
    }

    public function getModuleCode(): string
    {
        return 'pos';
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

            // Get open cash registers
            // In production: Query from CashRegister model
            // For now, return structure with sample data
            $openCount = 0;
            $myRegisterOpen = false;
            $totalCash = 0;

            // TODO: Implement actual queries when CashRegister model is ready
            // $openCount = CashRegister::where('status', 'open')->count();
            // $myRegisterOpen = CashRegister::where('cashier_id', $user->id)->where('status', 'open')->exists();
            // $totalCash = CashRegister::where('status', 'open')->sum('current_balance');

            return [
                'open_count' => $openCount,
                'my_register_open' => $myRegisterOpen,
                'total_cash' => $totalCash,
                'currency' => 'BDT',
                'show_more_url' => route('pos.registers.index', [], false),
            ];
        });
    }

    /**
     * Empty state when no data or user not authenticated.
     */
    protected function getEmptyState(): array
    {
        return [
            'open_count' => 0,
            'my_register_open' => false,
            'total_cash' => 0,
            'currency' => 'BDT',
            'message' => 'All registers closed',
        ];
    }
}
