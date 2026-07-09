<?php

declare(strict_types=1);

namespace Aero\Platform\Database\Seeders;

use Aero\Contracts\AeroMode;
use Aero\HRMAC\Models\Module;
use Aero\HRMAC\Models\ModuleComponent;
use Aero\HRMAC\Models\Role as HrmacRole;
use Aero\HRMAC\Models\RoleModuleAccess;
use Aero\HRMAC\Models\SubModule;
use Illuminate\Database\Seeder;

/**
 * Seeds realistic per-role module access for the 5 landlord roles so the Roles &
 * Access coverage matrix is populated (role_module_access ships empty).
 *
 * Grant depth demonstrates every coverage state:
 *  - Super Platform Admin → MODULE grant (cascades to all sub-modules) = full everywhere
 *  - Platform Admin / Support Admin / Billing Manager → SUB-MODULE grants (full for those areas)
 *  - Auditor → COMPONENT grants (partial coverage — some components under a sub-module)
 *
 * Idempotent: wipes + re-inserts these five roles' grants. access_scope 'all'
 * (own/team/department are org-relative and don't apply to landlord admins).
 */
class PlatformRoleAccessSeeder extends Seeder
{
    public function run(): void
    {
        AeroMode::withoutTenantContextGuard(fn () => $this->seed());
    }

    private function seed(): void
    {
        $module = Module::where('scope', 'platform')->where('is_active', true)->first();
        if (! $module) {
            $this->command?->warn('No platform module — skipping role-access seed.');

            return;
        }

        $roles = HrmacRole::where('guard_name', 'landlord')
            ->whereIn('name', ['Super Platform Admin', 'Platform Admin', 'Support Admin', 'Billing Manager', 'Auditor'])
            ->pluck('id', 'name');

        // Clean slate for these roles (demo grants only).
        RoleModuleAccess::whereIn('role_id', $roles->values())->delete();

        // Sub-module id lookup by code (platform scope).
        $subByCode = SubModule::where('module_id', $module->id)->pluck('id', 'code');

        $subGrants = [
            'Platform Admin' => ['tenants', 'platform-onboarding', 'plan-management', 'billing-management', 'quota-management', 'product-catalog', 'module-management', 'platform-analytics', 'audit-logs', 'tenant-operations', 'entitlement-overrides', 'subscription-lifecycle', 'invoicing', 'platform-users'],
            'Support Admin' => ['tenants', 'tenant-operations', 'tenant-communications', 'help-center', 'customer-success', 'status-incidents'],
            'Billing Manager' => ['plan-management', 'billing-management', 'product-catalog', 'invoicing', 'payment-methods', 'subscription-lifecycle', 'refunds-credits', 'dunning', 'coupons-promotions', 'tax-engine'],
        ];
        // Auditor: partial coverage — grant the FIRST component under each of these areas.
        $auditorPartialSubs = ['audit-logs', 'access-logs', 'platform-analytics', 'compliance-legal', 'security-center', 'product-analytics'];

        $rows = [];

        // Super Platform Admin — module-level grant cascades to everything.
        if (isset($roles['Super Platform Admin'])) {
            $rows[] = $this->row($roles['Super Platform Admin'], ['module_id' => $module->id]);
        }

        foreach ($subGrants as $roleName => $codes) {
            if (! isset($roles[$roleName])) {
                continue;
            }
            foreach ($codes as $code) {
                if (isset($subByCode[$code])) {
                    $rows[] = $this->row($roles[$roleName], ['sub_module_id' => $subByCode[$code]]);
                }
            }
        }

        // Auditor component grants (partial coverage).
        if (isset($roles['Auditor'])) {
            $subIds = collect($auditorPartialSubs)->map(fn ($c) => $subByCode[$c] ?? null)->filter()->values();
            $comps = ModuleComponent::whereIn('sub_module_id', $subIds)
                ->orderBy('id')
                ->get(['id', 'sub_module_id'])
                ->groupBy('sub_module_id')
                ->map(fn ($g) => $g->first()->id) // first component per sub-module
                ->values();
            foreach ($comps as $componentId) {
                $rows[] = $this->row($roles['Auditor'], ['component_id' => $componentId]);
            }
        }

        foreach ($rows as $r) {
            RoleModuleAccess::create($r);
        }

        $this->command?->info('Seeded '.count($rows).' role-access grants across '.count($roles).' landlord roles.');
    }

    private function row(int $roleId, array $target): array
    {
        return array_merge([
            'role_id' => $roleId,
            'module_id' => null,
            'sub_module_id' => null,
            'component_id' => null,
            'action_id' => null,
            'access_scope' => RoleModuleAccess::SCOPE_ALL,
            'status' => RoleModuleAccess::STATUS_ACTIVE,
        ], $target);
    }
}
