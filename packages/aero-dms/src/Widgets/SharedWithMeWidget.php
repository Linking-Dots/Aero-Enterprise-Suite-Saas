<?php

declare(strict_types=1);

namespace Aero\DMS\Widgets;

use Aero\Core\Contracts\AbstractDashboardWidget;
use Aero\Contracts\CoreWidgetCategory;

/**
 * Shared With Me Widget
 *
 * Displays documents that have been shared with the current user.
 *
 * Appears on: DMS Dashboard (/dms/dashboard)
 */
class SharedWithMeWidget extends AbstractDashboardWidget
{
    protected string $position = 'main_left';

    protected int $order = 30;

    protected int|string $span = 1;

    protected array $requiredPermissions = ['dms.dashboard']; // HRMAC format: module.submodule

    protected array $dashboards = ['dms'];

    public function getCategory(): CoreWidgetCategory
    {
        return CoreWidgetCategory::ACTIVITY;
    }

    public function getKey(): string
    {
        return 'dms.shared_with_me';
    }

    public function getTitle(): string
    {
        return 'Shared With Me';
    }

    public function getDescription(): string
    {
        return 'Documents shared with you by others';
    }

    public function getModuleCode(): string
    {
        return 'dms';
    }

    public function getComponent(): string
    {
        return 'Widgets/DMS/SharedWithMeWidget';
    }

    public function getData(): array
    {
        $user = auth()->user();

        if (! $user) {
            return [
                'documents' => [],
                'count' => 0,
            ];
        }

        // In production, query from DocumentShare model
        return [
            'documents' => [],
            'count' => 0,
            'view_all_url' => route('dms.shared-documents', [], false),
        ];
    }
}
