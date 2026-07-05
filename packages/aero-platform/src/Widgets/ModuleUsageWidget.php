<?php

declare(strict_types=1);

namespace Aero\Platform\Widgets;

use Aero\Platform\Contracts\AbstractPlatformWidget;
use Aero\Platform\Contracts\PlatformWidgetCategory;
use Aero\Platform\Models\ProductSubscription;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Module Usage Widget for Admin Dashboard
 *
 * Displays module adoption metrics:
 * - Which modules are most used
 * - Module activation by tenant
 * - Feature utilization
 *
 * This is a SUMMARY widget - shows usage statistics.
 */
class ModuleUsageWidget extends AbstractPlatformWidget
{
    protected string $position = 'main_left';

    protected int $order = 15;

    protected int|string $span = 1;

    protected PlatformWidgetCategory $category = PlatformWidgetCategory::ANALYTICS;

    protected array $requiredPermissions = [];

    public function getKey(): string
    {
        return 'platform.module_usage';
    }

    public function getComponent(): string
    {
        return 'Widgets/Platform/ModuleUsageWidget';
    }

    public function getTitle(): string
    {
        return 'Module Usage';
    }

    public function getDescription(): string
    {
        return 'Module adoption across tenants';
    }

    public function getModuleCode(): string
    {
        return 'platform';
    }

    /**
     * Module usage is always enabled.
     */
    public function isEnabled(): bool
    {
        return true;
    }

    /**
     * Get widget data for frontend.
     * Cached for 10 minutes.
     */
    public function getData(): array
    {
        return Cache::remember('platform.dashboard.module_usage', 600, function () {
            return $this->calculateModuleUsage();
        });
    }

    /**
     * Calculate module usage metrics.
     */
    protected function calculateModuleUsage(): array
    {
        $modules = $this->getModuleList();

        // Modules are carried by products: count distinct tenants holding an
        // active product subscription per module code.
        $tenantsByModule = $this->getActiveTenantsByModule();

        $moduleUsage = [];

        foreach ($modules as $code => $module) {
            $moduleUsage[] = [
                'code' => $code,
                'name' => $module['name'],
                'icon' => $module['icon'],
                'color' => $module['color'],
                'activeCount' => $tenantsByModule[$code] ?? 0,
                'status' => $module['status'] ?? 'active',
            ];
        }

        // Sort by usage count
        usort($moduleUsage, fn ($a, $b) => $b['activeCount'] <=> $a['activeCount']);

        // Get total active tenants for percentage calculation
        $totalActiveTenants = ProductSubscription::query()->hasAccess()
            ->distinct('tenant_id')
            ->count('tenant_id');

        return [
            'modules' => $moduleUsage,
            'totalTenants' => $totalActiveTenants,
            'mostUsed' => $moduleUsage[0] ?? null,
            'leastUsed' => end($moduleUsage) ?: null,
        ];
    }

    /**
     * Get list of available modules.
     */
    protected function getModuleList(): array
    {
        // Get from config or database
        $configModules = config('modules.modules', []);

        if (! empty($configModules)) {
            $modules = [];
            foreach ($configModules as $code => $config) {
                $modules[$code] = [
                    'name' => $config['label'] ?? $config['name'] ?? ucfirst($code),
                    'icon' => $this->getModuleIcon($code),
                    'color' => $this->getModuleColor($code),
                    'status' => $config['status'] ?? 'active',
                ];
            }

            return $modules;
        }

        // Fallback to hardcoded list of known modules
        return [
            'hrm' => ['name' => 'HR Management', 'icon' => 'UserGroupIcon', 'color' => '#0ea5e9'],
            'project' => ['name' => 'Project Management', 'icon' => 'ClipboardDocumentCheckIcon', 'color' => '#8b5cf6'],
            'crm' => ['name' => 'CRM', 'icon' => 'UsersIcon', 'color' => '#10b981'],
            'dms' => ['name' => 'Document Management', 'icon' => 'DocumentTextIcon', 'color' => '#f59e0b'],
            'quality' => ['name' => 'Quality Control', 'icon' => 'BeakerIcon', 'color' => '#ef4444'],
            'compliance' => ['name' => 'Compliance', 'icon' => 'ShieldCheckIcon', 'color' => '#06b6d4'],
            'finance' => ['name' => 'Finance & Accounting', 'icon' => 'BanknotesIcon', 'color' => '#84cc16'],
            'scm' => ['name' => 'Supply Chain', 'icon' => 'TruckIcon', 'color' => '#f97316'],
            'pos' => ['name' => 'Point of Sale', 'icon' => 'CreditCardIcon', 'color' => '#ec4899'],
            'helpdesk' => ['name' => 'Helpdesk', 'icon' => 'LifebuoyIcon', 'color' => '#14b8a6'],
        ];
    }

    /**
     * Get module icon.
     */
    protected function getModuleIcon(string $code): string
    {
        $icons = [
            'hrm' => 'UserGroupIcon',
            'project' => 'ClipboardDocumentCheckIcon',
            'crm' => 'UsersIcon',
            'dms' => 'DocumentTextIcon',
            'quality' => 'BeakerIcon',
            'compliance' => 'ShieldCheckIcon',
            'finance' => 'BanknotesIcon',
            'scm' => 'TruckIcon',
            'pos' => 'CreditCardIcon',
            'helpdesk' => 'LifebuoyIcon',
            'ims' => 'ArchiveBoxIcon',
            'analytics' => 'ChartBarIcon',
        ];

        return $icons[$code] ?? 'CubeIcon';
    }

    /**
     * Get module color.
     */
    protected function getModuleColor(string $code): string
    {
        $colors = [
            'hrm' => '#0ea5e9',
            'project' => '#8b5cf6',
            'crm' => '#10b981',
            'dms' => '#f59e0b',
            'quality' => '#ef4444',
            'compliance' => '#06b6d4',
            'finance' => '#84cc16',
            'scm' => '#f97316',
            'pos' => '#ec4899',
            'helpdesk' => '#14b8a6',
            'ims' => '#6366f1',
            'analytics' => '#a855f7',
        ];

        return $colors[$code] ?? '#6b7280';
    }

    /**
     * Distinct active-tenant counts per module code, from product subscriptions.
     *
     * @return array<string, int>
     */
    protected function getActiveTenantsByModule(): array
    {
        try {
            return ProductSubscription::query()->hasAccess()
                ->join('products', 'product_subscriptions.product_id', '=', 'products.id')
                ->select(
                    'products.module_code',
                    DB::raw('COUNT(DISTINCT product_subscriptions.tenant_id) as tenants')
                )
                ->groupBy('products.module_code')
                ->pluck('tenants', 'module_code')
                ->map(fn ($v) => (int) $v)
                ->all();
        } catch (\Exception $e) {
            // Return empty if tables don't exist (e.g. standalone mode)
            return [];
        }
    }
}
