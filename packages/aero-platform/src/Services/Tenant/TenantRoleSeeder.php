<?php

declare(strict_types=1);

namespace Aero\Platform\Services\Tenant;

use Aero\Contracts\RoleModuleAccessInterface;
use Aero\HRMAC\Models\ModuleComponentAction as HrmacAction;
use Aero\HRMAC\Models\Module as HrmacModule;
use Aero\HRMAC\Models\Role as HrmacRole;
use Aero\Platform\Models\ProductSubscription;
use Aero\Platform\Models\Tenant;
use Illuminate\Support\Facades\Log;

class TenantRoleSeeder
{
    public function __construct(
        private readonly RoleModuleAccessInterface $hrmac
    ) {}

    /**
     * Seed default Tenant Admin + Tenant User roles for a newly provisioned tenant.
     * MUST be called while tenant context is initialized (tenancy()->initialized === true).
     */
    public function seedFor(Tenant $tenant): void
    {
        // Step 1: Create Tenant Admin role (or retrieve existing)
        $tenantAdmin = HrmacRole::firstOrCreate(
            ['name' => 'Tenant Admin', 'guard_name' => 'web'],
        );

        // Step 2: Determine which modules this tenant has access to via product subscriptions.
        // ProductSubscription is a central-DB model; query explicitly on the central connection.
        $subscribedModuleCodes = ProductSubscription::on('central')
            ->where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->with('product')
            ->get()
            ->pluck('product.module_code')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        // Core module is always included (free for all tenants)
        if (! in_array('core', $subscribedModuleCodes, true)) {
            $subscribedModuleCodes[] = 'core';
        }

        // Step 3: Resolve module IDs from tenant DB (synced by syncTenantModules earlier)
        $moduleIds = HrmacModule::whereIn('code', $subscribedModuleCodes)
            ->where('scope', 'tenant')
            ->pluck('id')
            ->toArray();

        // Step 4: Grant Tenant Admin full access to all subscribed modules
        $this->hrmac->syncRoleAccess($tenantAdmin, [
            'modules' => $moduleIds,
            'sub_modules' => [],
            'components' => [],
            'actions' => [],
        ]);

        // Step 5: Create Tenant User role with view-only access to all subscribed module actions
        $tenantUser = HrmacRole::firstOrCreate(
            ['name' => 'Tenant User', 'guard_name' => 'web'],
        );

        $viewActionIds = HrmacAction::where('code', 'view')
            ->whereHas('component.module', function ($q) use ($moduleIds) {
                $q->whereIn('id', $moduleIds);
            })
            ->pluck('id')
            ->toArray();

        $this->hrmac->syncRoleAccess($tenantUser, [
            'modules' => [],
            'sub_modules' => [],
            'components' => [],
            'actions' => $viewActionIds,
        ]);

        // Clear caches
        $this->hrmac->clearRoleCache($tenantAdmin);
        $this->hrmac->clearRoleCache($tenantUser);

        Log::info("[TenantRoleSeeder] Roles seeded for tenant {$tenant->id}", [
            'modules_count' => count($moduleIds),
            'module_codes' => $subscribedModuleCodes,
            'view_actions' => count($viewActionIds),
        ]);
    }
}
