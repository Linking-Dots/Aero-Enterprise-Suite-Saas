<?php

declare(strict_types=1);

namespace Aero\DMS\Widgets;

use Aero\Core\Contracts\AbstractDashboardWidget;
use Aero\Contracts\CoreWidgetCategory;

/**
 * Storage Usage Widget
 *
 * Displays storage usage statistics and quota information.
 *
 * Appears on: DMS Dashboard (/dms/dashboard)
 */
class StorageUsageWidget extends AbstractDashboardWidget
{
    protected string $position = 'sidebar';

    protected int $order = 10;

    protected int|string $span = 1;

    protected array $requiredPermissions = ['dms.dashboard']; // HRMAC format: module.submodule

    protected array $dashboards = ['dms'];

    public function getCategory(): CoreWidgetCategory
    {
        return CoreWidgetCategory::STATS;
    }

    public function getKey(): string
    {
        return 'dms.storage_usage';
    }

    public function getTitle(): string
    {
        return 'Storage Usage';
    }

    public function getDescription(): string
    {
        return 'Document storage usage and quota';
    }

    public function getModuleCode(): string
    {
        return 'dms';
    }

    public function getComponent(): string
    {
        return 'Widgets/DMS/StorageUsageWidget';
    }

    public function getData(): array
    {
        // In production, calculate from actual storage
        return [
            'used_bytes' => 0,
            'quota_bytes' => 10 * 1024 * 1024 * 1024, // 10GB default
            'used_formatted' => '0 MB',
            'quota_formatted' => '10 GB',
            'percentage' => 0,
            'document_count' => 0,
            'folder_count' => 0,
        ];
    }
}
