<?php

declare(strict_types=1);

namespace Aero\HRM\Widgets;

use Aero\Core\Contracts\AbstractDashboardWidget;
use Aero\Contracts\CoreWidgetCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Organization Info Widget
 *
 * Displays organizational structure overview:
 * - Departments count
 * - Designations count
 * - Skills defined
 * - Competencies count
 * - Work locations
 *
 * This is a DISPLAY widget - static information.
 * Appears on: HRM Dashboard (/hrm/dashboard)
 */
class OrganizationInfoWidget extends AbstractDashboardWidget
{
    protected string $position = 'sidebar';

    protected int $order = 5;

    protected int|string $span = 1;

    protected CoreWidgetCategory $category = CoreWidgetCategory::DISPLAY;

    protected array $requiredPermissions = [];

    protected array $dashboards = ['hrm'];

    public function getKey(): string
    {
        return 'hrm.organization_info';
    }

    public function getComponent(): string
    {
        return 'Widgets/HRM/OrganizationInfoWidget';
    }

    public function getTitle(): string
    {
        return 'Organization';
    }

    public function getDescription(): string
    {
        return 'Organizational structure overview';
    }

    public function getModuleCode(): string
    {
        return 'hrm';
    }

    /**
     * Check if widget is enabled.
     * Super Administrators bypass ALL checks.
     */
    public function isEnabled(): bool
    {
        // Super Admin bypass - always enabled, bypasses ALL checks
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Display widget - only requires module to be active
        return $this->isModuleActive();
    }

    /**
     * Get widget data for frontend.
     */
    public function getData(): array
    {
        $items = [];

        // Departments
        try {
            if (Schema::hasTable('departments')) {
                $count = DB::table('departments')->count();
                $items[] = [
                    'key' => 'departments',
                    'label' => 'Departments',
                    'value' => $count,
                    'icon' => 'BuildingOfficeIcon',
                    'color' => 'primary',
                ];
            }
        } catch (\Throwable $e) {
            // Silently ignore
        }

        // Designations
        try {
            if (Schema::hasTable('designations')) {
                $count = DB::table('designations')->count();
                $items[] = [
                    'key' => 'designations',
                    'label' => 'Designations',
                    'value' => $count,
                    'icon' => 'IdentificationIcon',
                    'color' => 'secondary',
                ];
            }
        } catch (\Throwable $e) {
            // Silently ignore
        }

        // Skills
        try {
            if (Schema::hasTable('skills')) {
                $count = DB::table('skills')->count();
                $items[] = [
                    'key' => 'skills',
                    'label' => 'Skills',
                    'value' => $count,
                    'icon' => 'SparklesIcon',
                    'color' => 'success',
                ];
            }
        } catch (\Throwable $e) {
            // Silently ignore
        }

        // Competencies
        try {
            if (Schema::hasTable('competencies')) {
                $count = DB::table('competencies')->count();
                $items[] = [
                    'key' => 'competencies',
                    'label' => 'Competencies',
                    'value' => $count,
                    'icon' => 'AcademicCapIcon',
                    'color' => 'warning',
                ];
            }
        } catch (\Throwable $e) {
            // Silently ignore
        }

        // Work Locations
        try {
            if (Schema::hasTable('work_locations')) {
                $count = DB::table('work_locations')->count();
                $items[] = [
                    'key' => 'work_locations',
                    'label' => 'Work Locations',
                    'value' => $count,
                    'icon' => 'MapPinIcon',
                    'color' => 'default',
                ];
            }
        } catch (\Throwable $e) {
            // Silently ignore
        }

        // Jurisdictions
        try {
            if (Schema::hasTable('jurisdictions')) {
                $count = DB::table('jurisdictions')->count();
                $items[] = [
                    'key' => 'jurisdictions',
                    'label' => 'Jurisdictions',
                    'value' => $count,
                    'icon' => 'GlobeAltIcon',
                    'color' => 'default',
                ];
            }
        } catch (\Throwable $e) {
            // Silently ignore
        }

        // If no items found, show placeholder
        if (empty($items)) {
            $items = [
                [
                    'key' => 'info',
                    'label' => 'No Data',
                    'value' => '-',
                    'icon' => 'InformationCircleIcon',
                    'color' => 'default',
                ],
            ];
        }

        return [
            'items' => $items,
        ];
    }
}
