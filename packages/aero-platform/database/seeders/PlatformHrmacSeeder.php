<?php

namespace Aero\Platform\Database\Seeders;

use Aero\Contracts\RoleModuleAccessInterface;
use Aero\HRMAC\Models\ModuleComponentAction as HrmacAction;
use Aero\HRMAC\Models\Module as HrmacModule;
use Aero\HRMAC\Models\Role as HrmacRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class PlatformHrmacSeeder extends Seeder
{
    public function run(): void
    {
        // This seeds landlord HRMAC roles on the CENTRAL DB. The HRMAC Role/Module
        // models are tenant-scoped (TenantModel) and their guard fails-closed off
        // tenant context in SaaS. Central landlord seeding is legitimate (no tenant
        // to leak between), so run with the guard disabled.
        \Aero\Contracts\AeroMode::withoutTenantContextGuard(function () {
            $this->seedLandlordRoles();
        });
    }

    protected function seedLandlordRoles(): void
    {
        // Ensure all writes go to the central (landlord) DB
        \Illuminate\Support\Facades\DB::setDefaultConnection('central');

        /** @var RoleModuleAccessInterface $hrmac */
        $hrmac = app(RoleModuleAccessInterface::class);

        // 1. Ensure platform module hierarchy is synced into HRMAC tables.
        //    aero:sync-module is a console-only command (registered under
        //    runningInConsole), so it is unavailable during the WEB install. There the
        //    installer's ModuleDiscoveryStep has already synced the hierarchy WITH scope,
        //    so a failure here is non-fatal — guard it and continue.
        try {
            Artisan::call('aero:sync-module');
        } catch (\Throwable $e) {
            logger()->info('[PlatformHrmacSeeder] aero:sync-module unavailable (web install) — relying on prior module sync: '.$e->getMessage());
        }

        // 2. Create Super Platform Admin role (full access)
        $superAdmin = HrmacRole::firstOrCreate(
            ['name' => 'Super Platform Admin'],
            ['guard_name' => 'landlord']
        );

        // 3. Grant module-level access to the platform module
        //    HRMAC cascade: module grant covers ALL sub_modules, components, actions
        $platformModule = HrmacModule::where('code', 'platform')
            ->where('scope', 'platform')
            ->first();

        if ($platformModule) {
            $hrmac->syncRoleAccess($superAdmin, [
                'modules'     => [$platformModule->id],
                'sub_modules' => [],
                'components'  => [],
                'actions'     => [],
            ]);
            $this->command->info("Super Platform Admin granted full access to platform module (id: {$platformModule->id})");
        } else {
            $this->command->warn('Platform module not found in HRMAC tables. Run: php artisan aero:sync-module');
        }

        // 4. Create Platform Admin role (view actions only)
        $platformAdmin = HrmacRole::firstOrCreate(
            ['name' => 'Platform Admin'],
            ['guard_name' => 'landlord']
        );

        if ($platformModule) {
            // Find all 'view' actions under the platform module.
            // Action → component() (FK: module_component_id) → subModule() → module_id
            $viewActionIds = HrmacAction::where('code', 'view')
                ->whereHas('component', function ($q) use ($platformModule) {
                    $q->whereHas('subModule', function ($sq) use ($platformModule) {
                        $sq->where('module_id', $platformModule->id);
                    });
                })
                ->pluck('id')
                ->toArray();

            $hrmac->syncRoleAccess($platformAdmin, [
                'modules'     => [],
                'sub_modules' => [],
                'components'  => [],
                'actions'     => $viewActionIds,
            ]);
            $this->command->info('Platform Admin granted '.count($viewActionIds).' view-only actions');
        }

        $hrmac->clearRoleCache($superAdmin);
        $hrmac->clearRoleCache($platformAdmin);

        $this->command->info('Platform HRMAC roles seeded successfully');
    }
}
