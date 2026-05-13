<?php

declare(strict_types=1);

namespace Aero\DMS\Widgets;

use Aero\Core\Contracts\AbstractDashboardWidget;
use Aero\Contracts\CoreWidgetCategory;

/**
 * Pending Approvals Widget
 *
 * Displays documents awaiting approval from the current user.
 *
 * Appears on: DMS Dashboard (/dms/dashboard)
 */
class PendingApprovalsWidget extends AbstractDashboardWidget
{
    protected string $position = 'main_right';

    protected int $order = 10;

    protected int|string $span = 1;

    protected array $requiredPermissions = ['dms.dashboard']; // HRMAC format: module.submodule

    protected array $dashboards = ['dms'];

    public function getCategory(): CoreWidgetCategory
    {
        return CoreWidgetCategory::ACTION;
    }

    public function getKey(): string
    {
        return 'dms.pending_approvals';
    }

    public function getTitle(): string
    {
        return 'Pending Document Approvals';
    }

    public function getDescription(): string
    {
        return 'Documents awaiting your approval';
    }

    public function getModuleCode(): string
    {
        return 'dms';
    }

    public function getComponent(): string
    {
        return 'Widgets/DMS/PendingApprovalsWidget';
    }

    public function getData(): array
    {
        $user = auth()->user();

        if (! $user) {
            return [
                'approvals' => [],
                'count' => 0,
            ];
        }

        // In production, query from DocumentApproval model
        return [
            'approvals' => [],
            'count' => 0,
            'action_url' => route('dms.approvals', [], false),
        ];
    }
}
