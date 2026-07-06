<?php

declare(strict_types=1);

namespace Aero\HRMAC\Console\Commands;

use Aero\Contracts\ModuleSyncFilterInterface;
use Aero\HRMAC\Models\ModuleComponentAction;
use Aero\HRMAC\Models\ModuleComponent;
use Aero\HRMAC\Models\Module;
use Aero\HRMAC\Models\SubModule;
use Aero\HRMAC\Services\ModuleDiscoveryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sync Module Hierarchy Command
 *
 * Syncs module definitions from packages to the 4-level hierarchy:
 * - modules (top level)
 * - sub_modules (second level)
 * - module_components (third level)
 * - module_component_actions (fourth level - leaf)
 *
 * Usage: php artisan aero:sync-module --scope=tenant|platform|all
 */
class SyncModuleHierarchy extends Command
{
    protected $signature = 'aero:sync-module
                          {--scope= : Override auto-detected scope (platform, tenant, or all)}
                          {--fresh : Clear all existing modules before syncing}
                          {--force : Force sync even if tables do not exist}
                          {--prune : Remove modules that are no longer installed}';

    protected $description = 'Sync module hierarchy from package configs to database. Scope is supplied via --scope (no context auto-detection).';

    protected ModuleDiscoveryService $moduleDiscovery;

    protected array $stats = [
        'modules_created' => 0,
        'modules_updated' => 0,
        'modules_removed' => 0,
        'submodules_created' => 0,
        'submodules_updated' => 0,
        'submodules_removed' => 0,
        'components_created' => 0,
        'components_updated' => 0,
        'components_removed' => 0,
        'actions_created' => 0,
        'actions_updated' => 0,
        'actions_removed' => 0,
    ];

    public function __construct(ModuleDiscoveryService $moduleDiscovery)
    {
        parent::__construct();
        $this->moduleDiscovery = $moduleDiscovery;
    }

    /**
     * Advisory lock key for hrmac:sync-modules.
     *
     * Plan 04 T5 — prevents concurrent runs from racing on
     * updateOrCreate() and producing duplicate (module_id, code) rows.
     */
    protected const SYNC_LOCK_KEY = 'aero:hrmac:sync-modules';

    public function handle(): int
    {
        $this->info('🚀 HRMAC: Starting Module Hierarchy Sync...');
        $this->newLine();

        // Plan 04 T5 — acquire advisory lock to serialize concurrent runs.
        // On MySQL this uses GET_LOCK; sqlite (tests) gracefully no-ops.
        if (! $this->acquireSyncLock()) {
            $this->warn('⚠️  Another aero:sync-module run is in progress. Aborting to avoid duplicate rows.');

            return self::FAILURE;
        }

        try {

            // Schema validation
            if (! $this->validateSchema()) {
                return self::FAILURE;
            }

            // Scope is supplied by the consumer (no context auto-detection in HRMAC).
            $scope = $this->option('scope') ?: 'all';
            $fresh = $this->option('fresh');
            $prune = $this->option('prune');

            $this->info("📍 Context: {$scope}");
            $this->newLine();

            // The module hierarchy (modules/sub_modules/components/actions) is
            // central catalog data. Under SaaS the HrmacContextGuard blocks model
            // writes that run without a resolved request context — but a CLI sync
            // is exactly the central/CLI case the guard tells callers to bypass.
            // Without this wrap, `aero:sync-module` fails on the first model touch
            // in SaaS ("queried outside of a valid HRMAC context").
            return \Aero\Contracts\AeroMode::withoutTenantContextGuard(function () use ($scope, $fresh, $prune) {
            try {
                DB::beginTransaction();

                // Fresh sync
                if ($fresh) {
                    $this->clearExistingModules($scope);
                }

                $modules = $this->moduleDiscovery->getModuleDefinitions();

                // The consuming package decides which modules a given scope receives
                // (e.g. the SaaS platform filters a tenant to its subscribed products —
                // Audit D15). HRMAC stays context-free: if no filter is bound, every
                // discovered module is synced.
                if (app()->bound(ModuleSyncFilterInterface::class)) {
                    $modules = app(ModuleSyncFilterInterface::class)->filter($modules, $scope);
                }

                if ($modules->isEmpty()) {
                    $this->warn('⚠️  No module definitions found in packages.');

                    if ($prune) {
                        $this->pruneRemovedModules(collect([]));
                        DB::commit();
                        $this->displayStats();

                        return self::SUCCESS;
                    }

                    DB::rollBack();

                    return self::SUCCESS;
                }

                $this->info("📦 Found {$modules->count()} module(s) to sync");
                $this->newLine();

                $progressBar = $this->output->createProgressBar($modules->count());
                $progressBar->setFormat('verbose');

                foreach ($modules as $moduleDef) {
                    // Filter by scope. Shared foundations (scope 'infrastructure' or
                    // 'shared' — e.g. aero-auth, aero-hrmac) belong to BOTH the tenant
                    // and platform permission sets, so they match every scoped run;
                    // only marketplace/context modules ('tenant' | 'platform') are gated.
                    $moduleScope = $moduleDef['scope'] ?? 'tenant';
                    $isSharedScope = in_array($moduleScope, ['infrastructure', 'shared'], true);
                    if ($scope && $scope !== 'all' && ! $isSharedScope && $moduleScope !== $scope) {
                        $progressBar->advance();

                        continue;
                    }

                    $this->syncModule($moduleDef);
                    $progressBar->advance();
                }

                $progressBar->finish();
                $this->newLine(2);

                // Prune removed modules
                if ($prune) {
                    $this->pruneRemovedModules($modules);
                }

                DB::commit();

                $this->displayStats();
                $this->info('✅ Module hierarchy sync completed!');

                return self::SUCCESS;
            } catch (\Exception $e) {
                DB::rollBack();

                $this->error('❌ Sync failed: '.$e->getMessage());
                $this->error('Stack trace: '.$e->getTraceAsString());

                return self::FAILURE;
            }
            });

        } finally {
            // Plan 04 T5 — release advisory lock no matter what
            $this->releaseSyncLock();
        }
    }

    /**
     * Try to acquire the advisory sync lock.
     * Returns true if acquired or if the driver doesn't support advisory locks
     * (sqlite, test harness) — those drivers are inherently single-process so
     * the race we guard against can't happen.
     */
    protected function acquireSyncLock(): bool
    {
        try {
            $driver = DB::connection()->getDriverName();
            if ($driver !== 'mysql' && $driver !== 'mariadb') {
                return true; // sqlite/pgsql — no advisory lock needed in test/dev
            }

            $result = DB::selectOne('SELECT GET_LOCK(?, 30) AS ok', [self::SYNC_LOCK_KEY]);

            return (bool) ($result->ok ?? false);
        } catch (\Throwable) {
            return true; // Don't block the sync if lock subsystem is unavailable
        }
    }

    /**
     * Release the advisory sync lock. Safe to call even if not held.
     */
    protected function releaseSyncLock(): void
    {
        try {
            $driver = DB::connection()->getDriverName();
            if ($driver !== 'mysql' && $driver !== 'mariadb') {
                return;
            }
            DB::statement('SELECT RELEASE_LOCK(?)', [self::SYNC_LOCK_KEY]);
        } catch (\Throwable) {
            // best-effort release
        }
    }

    /**
     * Validate database schema.
     */
    protected function validateSchema(): bool
    {
        if ($this->option('force')) {
            $this->warn('⚠️  Skipping schema validation (--force)');

            return true;
        }

        $requiredTables = ['modules', 'sub_modules', 'module_components', 'module_component_actions'];
        $missingTables = [];

        foreach ($requiredTables as $table) {
            if (! Schema::hasTable($table)) {
                $missingTables[] = $table;
            }
        }

        if (! empty($missingTables)) {
            $this->error('❌ Required tables missing: '.implode(', ', $missingTables));
            $this->error('Run migrations first: php artisan migrate');

            return false;
        }

        $this->info('✅ Schema validation passed');
        $this->newLine();

        return true;
    }

    /**
     * Auto-detect scope based on context.
     */
    /**
     * Sync a module and its hierarchy.
     */
    protected function syncModule(array $moduleDef): void
    {
        $module = Module::updateOrCreate(
            ['code' => $moduleDef['code']],
            [
                'name' => $moduleDef['name'],
                'scope' => $moduleDef['scope'] ?? 'tenant',
                'description' => $moduleDef['description'] ?? null,
                'icon' => $moduleDef['icon'] ?? null,
                'route_prefix' => $moduleDef['route_prefix'] ?? null,
                'category' => $moduleDef['category'] ?? 'core_system',
                'priority' => $moduleDef['priority'] ?? 100,
                'is_active' => $moduleDef['is_active'] ?? true,
                'is_core' => $moduleDef['is_core'] ?? false,
                'settings' => $moduleDef['settings'] ?? null,
                'version' => $moduleDef['version'] ?? '1.0.0',
                'min_plan' => $moduleDef['min_plan'] ?? null,
                'license_type' => $moduleDef['license_type'] ?? null,
                'dependencies' => $moduleDef['dependencies'] ?? null,
                'release_date' => $moduleDef['release_date'] ?? null,
            ]
        );

        if ($module->wasRecentlyCreated) {
            $this->stats['modules_created']++;
        } else {
            $this->stats['modules_updated']++;
        }

        // Sync submodules
        if (isset($moduleDef['submodules']) && is_array($moduleDef['submodules'])) {
            $this->syncSubModules($module, $moduleDef['submodules']);
        }

        // Sync self-service items as a special "Self Service" submodule
        if (isset($moduleDef['self_service']) && is_array($moduleDef['self_service']) && ! empty($moduleDef['self_service'])) {
            $this->syncSelfServiceSubModule($module, $moduleDef['self_service']);
        }
    }

    /**
     * Sync self-service items as a "Self Service" submodule.
     *
     * Self-service items from config are synced as components under a special
     * "Self Service" submodule, allowing role-based access control for
     * employee-facing features like "My Dashboard", "My Leaves", etc.
     */
    protected function syncSelfServiceSubModule(Module $module, array $selfServiceItems): void
    {
        // Create/update the "Self Service" submodule
        $subModule = SubModule::updateOrCreate(
            [
                'module_id' => $module->id,
                'code' => 'self_service',
            ],
            [
                'name' => 'Self Service',
                'description' => 'Employee self-service features (My Workspace items)',
                'icon' => 'UserCircleIcon',
                'route' => null,
                'priority' => 0, // Show first in the module
                'is_active' => true,
            ]
        );

        if ($subModule->wasRecentlyCreated) {
            $this->stats['submodules_created']++;
        } else {
            $this->stats['submodules_updated']++;
        }

        // Convert each self-service item to a component
        foreach ($selfServiceItems as $item) {
            $component = ModuleComponent::updateOrCreate(
                [
                    'module_id' => $module->id,
                    'sub_module_id' => $subModule->id,
                    'code' => $item['code'],
                ],
                [
                    'name' => $item['name'],
                    'description' => $item['description'] ?? 'Self-service feature',
                    'type' => 'page',
                    'route' => $item['route'] ?? null,
                    'is_active' => $item['is_active'] ?? true,
                ]
            );

            if ($component->wasRecentlyCreated) {
                $this->stats['components_created']++;
            } else {
                $this->stats['components_updated']++;
            }

            // Create standard self-service actions for each item
            $selfServiceActions = [
                ['code' => 'view', 'name' => 'View'],
                ['code' => 'access', 'name' => 'Access'],
            ];

            foreach ($selfServiceActions as $actionDef) {
                $action = ModuleComponentAction::updateOrCreate(
                    [
                        'module_component_id' => $component->id,
                        'code' => $actionDef['code'],
                    ],
                    [
                        'name' => $actionDef['name'],
                        'description' => $actionDef['name'].' '.$item['name'],
                        'is_active' => true,
                    ]
                );

                if ($action->wasRecentlyCreated) {
                    $this->stats['actions_created']++;
                } else {
                    $this->stats['actions_updated']++;
                }
            }
        }
    }

    /**
     * Sync submodules for a module.
     */
    protected function syncSubModules(Module $module, array $subModules, ?int $parentId = null): void
    {
        foreach ($subModules as $subModuleDef) {
            $subModule = SubModule::updateOrCreate(
                [
                    'module_id' => $module->id,
                    'code' => $subModuleDef['code'],
                ],
                [
                    'parent_id' => $parentId,
                    'name' => $subModuleDef['name'],
                    'description' => $subModuleDef['description'] ?? null,
                    'icon' => $subModuleDef['icon'] ?? null,
                    'route' => $subModuleDef['route'] ?? null,
                    'priority' => $subModuleDef['priority'] ?? 100,
                    'is_active' => $subModuleDef['is_active'] ?? true,
                ]
            );

            if ($subModule->wasRecentlyCreated) {
                $this->stats['submodules_created']++;
            } else {
                $this->stats['submodules_updated']++;
            }

            // Sync components
            if (isset($subModuleDef['components']) && is_array($subModuleDef['components'])) {
                $this->syncComponents($module, $subModule, $subModuleDef['components']);
            }

            // Nested sub-modules: recurse, linking children to this sub-module.
            if (isset($subModuleDef['submodules']) && is_array($subModuleDef['submodules'])) {
                $this->syncSubModules($module, $subModuleDef['submodules'], $subModule->id);
            }
        }
    }

    /**
     * Sync components for a submodule.
     */
    protected function syncComponents(Module $module, SubModule $subModule, array $components): void
    {
        foreach ($components as $componentDef) {
            $component = ModuleComponent::updateOrCreate(
                [
                    'module_id' => $module->id,
                    'sub_module_id' => $subModule->id,
                    'code' => $componentDef['code'],
                ],
                [
                    'name' => $componentDef['name'],
                    'description' => $componentDef['description'] ?? null,
                    'type' => $componentDef['type'] ?? 'page',
                    'route' => $componentDef['route'] ?? null,
                    'is_active' => $componentDef['is_active'] ?? true,
                ]
            );

            if ($component->wasRecentlyCreated) {
                $this->stats['components_created']++;
            } else {
                $this->stats['components_updated']++;
            }

            // Sync actions
            if (isset($componentDef['actions']) && is_array($componentDef['actions'])) {
                $this->syncActions($component, $componentDef['actions']);
            }
        }
    }

    /**
     * Sync actions for a component.
     */
    protected function syncActions(ModuleComponent $component, array $actions): void
    {
        foreach ($actions as $actionDef) {
            $action = ModuleComponentAction::updateOrCreate(
                [
                    'module_component_id' => $component->id,
                    'code' => $actionDef['code'],
                ],
                [
                    'name' => $actionDef['name'],
                    'description' => $actionDef['description'] ?? null,
                ]
            );

            if ($action->wasRecentlyCreated) {
                $this->stats['actions_created']++;
            } else {
                $this->stats['actions_updated']++;
            }
        }
    }

    /**
     * Clear existing modules.
     */
    protected function clearExistingModules(?string $scope): void
    {
        $this->warn('🧹 Fresh sync: Clearing existing modules...');

        $query = Module::query();

        if ($scope && $scope !== 'all') {
            $query->where('scope', $scope);
        }

        $modules = $query->get();

        foreach ($modules as $module) {
            $submoduleCount = $module->subModules()->count();
            $componentCount = ModuleComponent::where('module_id', $module->id)->count();
            $actionCount = ModuleComponentAction::whereIn(
                'module_component_id',
                ModuleComponent::where('module_id', $module->id)->pluck('id')
            )->count();

            ModuleComponentAction::whereIn(
                'module_component_id',
                ModuleComponent::where('module_id', $module->id)->pluck('id')
            )->delete();

            ModuleComponent::where('module_id', $module->id)->delete();
            $module->subModules()->delete();
            $module->delete();

            $this->stats['modules_removed']++;
            $this->stats['submodules_removed'] += $submoduleCount;
            $this->stats['components_removed'] += $componentCount;
            $this->stats['actions_removed'] += $actionCount;
        }

        $this->info("   ✓ Cleared {$modules->count()} module(s)");
        $this->newLine();
    }

    /**
     * Prune removed modules.
     */
    protected function pruneRemovedModules($installedModules): void
    {
        $installedCodes = $installedModules->pluck('code')->toArray();

        $removedModules = Module::whereNotIn('code', $installedCodes)
            ->where('is_core', false)
            ->get();

        if ($removedModules->isEmpty()) {
            return;
        }

        $this->warn("🗑️  Removing {$removedModules->count()} uninstalled module(s)...");

        foreach ($removedModules as $module) {
            $this->line("   - Removing: {$module->name} ({$module->code})");

            $submoduleCount = $module->subModules()->count();
            $componentCount = ModuleComponent::where('module_id', $module->id)->count();
            $actionCount = ModuleComponentAction::whereIn(
                'module_component_id',
                ModuleComponent::where('module_id', $module->id)->pluck('id')
            )->count();

            ModuleComponentAction::whereIn(
                'module_component_id',
                ModuleComponent::where('module_id', $module->id)->pluck('id')
            )->delete();

            ModuleComponent::where('module_id', $module->id)->delete();
            $module->subModules()->delete();
            $module->delete();

            $this->stats['modules_removed']++;
            $this->stats['submodules_removed'] += $submoduleCount;
            $this->stats['components_removed'] += $componentCount;
            $this->stats['actions_removed'] += $actionCount;
        }
    }

    /**
     * Display sync statistics.
     */
    protected function displayStats(): void
    {
        $this->info('📊 Sync Statistics:');
        $this->table(
            ['Entity', 'Created', 'Updated', 'Removed'],
            [
                ['Modules', $this->stats['modules_created'], $this->stats['modules_updated'], $this->stats['modules_removed']],
                ['Sub-Modules', $this->stats['submodules_created'], $this->stats['submodules_updated'], $this->stats['submodules_removed']],
                ['Components', $this->stats['components_created'], $this->stats['components_updated'], $this->stats['components_removed']],
                ['Actions', $this->stats['actions_created'], $this->stats['actions_updated'], $this->stats['actions_removed']],
            ]
        );

        $totalCreated = $this->stats['modules_created'] + $this->stats['submodules_created'] +
                       $this->stats['components_created'] + $this->stats['actions_created'];
        $totalUpdated = $this->stats['modules_updated'] + $this->stats['submodules_updated'] +
                       $this->stats['components_updated'] + $this->stats['actions_updated'];
        $totalRemoved = $this->stats['modules_removed'] + $this->stats['submodules_removed'] +
                       $this->stats['components_removed'] + $this->stats['actions_removed'];

        $this->newLine();
        $this->info("📈 Total: {$totalCreated} created, {$totalUpdated} updated, {$totalRemoved} removed");
    }
}
