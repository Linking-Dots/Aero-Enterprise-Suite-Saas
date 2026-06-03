<?php

namespace Aero\Core\Console\Commands;

use Aero\Core\Models\Module;
use Aero\Core\Models\ModuleComponent;
use Aero\Core\Models\ModuleComponentAction;
use Aero\Core\Models\SubModule;
use Aero\Core\Services\Module\ModuleDiscoveryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Stancl\Tenancy\Tenancy;

/**
 * Sync Module Hierarchy Command
 *
 * Syncs module definitions from packages to the custom 4-level hierarchy:
 * - modules (top level)
 * - sub_modules (second level)
 * - module_components (third level)
 * - module_component_actions (fourth level - leaf)
 *
 * This command does NOT use Spatie Permissions. It uses custom hierarchy tables
 * with role_module_access for authorization.
 *
 * Usage: php artisan aero:sync-module-hierarchy
 */
class SyncModuleHierarchy extends Command
{
    protected $signature = 'aero:sync-module
                          {--scope= : Override auto-detected scope (platform or tenant)}
                          {--fresh : Clear all existing modules before syncing (fresh seed)}
                          {--force : Force sync even if modules table does not exist}
                          {--prune : Remove modules that are no longer installed}';

    protected $description = 'Sync module hierarchy from package configs to database. Auto-detects context: central DB = platform modules, tenant DB = tenant modules.';

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

    public function handle(): int
    {
        $this->info('🚀 Starting Module Hierarchy Sync...');
        $this->newLine();

        // CRITICAL: Schema validation to prevent crashes in Standalone mode
        if (! $this->validateSchema()) {
            return self::FAILURE;
        }

        // Auto-detect scope based on current context (tenant vs central)
        $scope = $this->option('scope') ?: $this->detectScope();
        $prune = $this->option('prune');
        $fresh = $this->option('fresh');

        $this->info("📍 Detected context: {$scope}");
        $this->newLine();

        try {
            DB::beginTransaction();

            // Fresh sync: Clear all existing modules before syncing
            if ($fresh) {
                $this->clearExistingModules($scope);
            }

            $modules = $this->moduleDiscovery->getModuleDefinitions();

            if ($modules->isEmpty()) {
                $this->warn('⚠️  No module definitions found in packages.');

                // If pruning is enabled, remove all non-core modules
                if ($prune) {
                    $this->warn('🗑️  Pruning enabled - removing all non-core modules from database...');
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

            $syncedModuleCodes = [];

            foreach ($modules as $moduleDef) {
                // Filter by scope (skip if scope is 'all' - sync everything in standalone mode)
                $moduleScope = $moduleDef['scope'] ?? 'tenant';
                if ($scope && $scope !== 'all' && $moduleScope !== $scope) {
                    $progressBar->advance();

                    continue;
                }

                $this->syncModule($moduleDef);
                $syncedModuleCodes[] = $moduleDef['code'];
                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine(2);

            // Prune removed modules (always prune to keep DB in sync)
            $this->pruneRemovedModules(collect($modules));

            DB::commit();

            $this->displayStats();

            $this->info('✅ Module hierarchy sync completed successfully!');

            return self::SUCCESS;
        } catch (\Exception $e) {
            DB::rollBack();

            $this->error('❌ Sync failed: '.$e->getMessage());
            $this->error('Stack trace: '.$e->getTraceAsString());

            return self::FAILURE;
        }
    }

    /**
     * Prune modules that are no longer installed.
     * Only removes non-core modules to protect essential system modules.
     */
    protected function pruneRemovedModules($installedModules): void
    {
        $installedCodes = $installedModules->pluck('code')->toArray();

        // Find modules in DB that are not in installed packages (exclude core modules)
        $removedModules = Module::whereNotIn('code', $installedCodes)
            ->where('is_core', false)
            ->get();

        if ($removedModules->isEmpty()) {
            return;
        }

        $this->warn("🗑️  Removing {$removedModules->count()} uninstalled module(s)...");

        foreach ($removedModules as $module) {
            $this->line("   - Removing: {$module->name} ({$module->code})");

            // Count items being removed for stats
            $submoduleCount = $module->subModules()->count();
            $componentCount = ModuleComponent::where('module_id', $module->id)->count();
            $actionCount = ModuleComponentAction::whereIn(
                'module_component_id',
                ModuleComponent::where('module_id', $module->id)->pluck('id')
            )->count();

            // Delete in reverse order (actions -> components -> submodules -> module)
            // Due to foreign key constraints
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
     * Clear all existing modules before fresh sync.
     * Respects scope filter if provided.
     */
    protected function clearExistingModules(?string $scope): void
    {
        $this->warn('🧹 Fresh sync: Clearing existing modules...');

        $query = Module::query();

        // Filter by scope if specified (unless 'all' which clears everything)
        if ($scope && $scope !== 'all') {
            $query->where('scope', $scope);
            $this->line("   Clearing only '{$scope}' scoped modules");
        } else {
            $this->line('   Clearing all modules');
        }

        $modules = $query->get();

        if ($modules->isEmpty()) {
            $this->info('   No existing modules to clear.');

            return;
        }

        foreach ($modules as $module) {
            // Count items being removed for stats
            $submoduleCount = $module->subModules()->count();
            $componentCount = ModuleComponent::where('module_id', $module->id)->count();
            $actionCount = ModuleComponentAction::whereIn(
                'module_component_id',
                ModuleComponent::where('module_id', $module->id)->pluck('id')
            )->count();

            // Delete in reverse order (actions -> components -> submodules -> module)
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
     * Validate database schema before syncing.
     * Prevents crashes in Standalone mode if migrations haven't run.
     */
    protected function validateSchema(): bool
    {
        if ($this->option('force')) {
            $this->warn('⚠️  Skipping schema validation (--force flag)');

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
            $this->error('❌ Required tables do not exist: '.implode(', ', $missingTables));
            $this->error('Run migrations first: php artisan migrate');
            $this->newLine();
            $this->info('💡 Or use --force flag to skip validation (not recommended)');

            return false;
        }

        $this->info('✅ Schema validation passed');
        $this->newLine();

        return true;
    }

    /**
     * Auto-detect scope based on current database context.
     *
     * - If running in tenant context (tenancy initialized) → tenant scope
     * - If running on central/landlord database → platform scope
     *
     * Detection logic:
     * 1. Check if tenancy() helper exists and tenant is initialized
     * 2. Check if 'tenants' table exists (indicates central database)
     * 3. Default to tenant if unclear
     */
    protected function detectScope(): string
    {
        // Check if we're in a tenant context (stancl/tenancy)
        if (function_exists('tenancy') && tenancy()->initialized) {
            return 'tenant';
        }

        // Check if tenants table exists - indicates central/landlord database
        if (Schema::hasTable('tenants')) {
            return 'platform';
        }

        // Check if this is a standalone app (no tenancy package)
        // In standalone mode, we sync all modules
        if (! class_exists(Tenancy::class)) {
            // Return null to sync all modules in standalone mode
            return 'all';
        }

        // Default to tenant scope if running in an ambiguous context
        return 'tenant';
    }

    /**
     * Sync a module and its hierarchy.
     */
    protected function syncModule(array $moduleDef): void
    {
        // Sync module (top level)
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
                    'priority' => $componentDef['priority'] ?? 100,
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
                    'is_active' => $actionDef['is_active'] ?? true,
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
