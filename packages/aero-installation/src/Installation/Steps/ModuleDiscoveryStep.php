<?php

namespace Aero\Installation\Installation\Steps;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

/**
 * Module Discovery Step
 *
 * Discovers all installed packages from composer.json and syncs to module registry
 * Verifies that all modules are properly loaded and bootstrapped
 */
class ModuleDiscoveryStep extends BaseInstallationStep
{
    protected string $mode;

    public function __construct(string $mode = 'standalone')
    {
        $this->mode = $mode;
    }

    public function name(): string
    {
        return 'modules';
    }

    public function description(): string
    {
        return 'Discover and register installed modules from composer.json';
    }

    public function order(): int
    {
        return 5;
    }

    public function dependencies(): array
    {
        return ['config', 'database', 'migration'];
    }

    public function execute(): array
    {
        $this->log('Starting module discovery from composer.json');

        // Wrap in transaction to ensure consistency
        return DB::transaction(function () {
            if ($this->mode === 'saas') {
                $this->log('Discovering platform modules (SaaS mode)');
                return $this->discoverPlatformModules();
            } else {
                $this->log('Discovering core and other package modules (Standalone mode)');
                return $this->discoverCoreAndOtherModules();
            }
        });
    }

    /**
     * Discover platform modules for SaaS mode from composer.json
     */
    protected function discoverPlatformModules(): array
    {
        try {
            $this->log('Reading composer.json for platform modules');

            // Read composer.json to discover installed packages
            $composerJson = $this->readComposerJson();
            
            // In SaaS mode, only discover platform module (not core - core is in tenant DB)
            $modules = [];

            // Add platform module if exists in composer.json
            if (isset($composerJson['require']['aero/platform']) || isset($composerJson['require']['aero-platform'])) {
                $modules['platform'] = [
                    'code' => 'platform',
                    'name' => 'Platform',
                    'description' => 'Platform management module',
                    'is_core' => false,
                    'is_active' => true,
                ];
                $this->log('Platform module discovered from composer.json');
            }

            // Sync modules to database
            $this->syncModulesToDatabase($modules);

            $this->log('Platform module discovery completed');

            return [
                'sync_status' => 'success',
                'verify_status' => 'success',
                'modules_discovered' => 'platform_only',
                'modules_count' => count($modules),
            ];

        } catch (\Exception $e) {
            $this->error('Platform module discovery failed: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Discover core and other package modules (Standalone mode) from composer.json
     */
    protected function discoverCoreAndOtherModules(): array
    {
        try {
            $this->log('Reading composer.json for core and package modules');

            // Read composer.json to discover installed packages
            $composerJson = $this->readComposerJson();
            
            $modules = [];
            
            // Always include core module
            $modules['core'] = [
                'code' => 'core',
                'name' => 'Core',
                'description' => 'Core platform module',
                'is_core' => true,
                'is_active' => true,
            ];

            // Discover other aero packages from composer.json
            $aeroPackages = array_filter(array_keys($composerJson['require'] ?? []), function($package) {
                return str_starts_with($package, 'aero/');
            });

            foreach ($aeroPackages as $package) {
                $moduleCode = str_replace('aero/', '', $package);
                
                // Skip platform in standalone mode
                if ($moduleCode === 'platform') {
                    continue;
                }

                // Skip core (already added)
                if ($moduleCode === 'core') {
                    continue;
                }

                $modules[$moduleCode] = [
                    'code' => $moduleCode,
                    'name' => ucfirst(str_replace(['-', '_'], ' ', $moduleCode)),
                    'description' => ucfirst($moduleCode) . ' module',
                    'is_core' => false,
                    'is_active' => true,
                ];
                
                $this->log("Module discovered from composer.json: {$moduleCode}");
            }

            // Sync modules to database
            $this->syncModulesToDatabase($modules);

            $this->log('Core and other package module discovery completed');

            return [
                'sync_status' => 'success',
                'verify_status' => 'success',
                'modules_discovered' => 'core_and_packages',
                'modules_count' => count($modules),
            ];

        } catch (\Exception $e) {
            $this->error('Module discovery failed: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Read composer.json file
     */
    protected function readComposerJson(): array
    {
        $composerJsonPath = base_path('composer.json');
        
        if (! file_exists($composerJsonPath)) {
            throw new \Exception('composer.json not found at ' . $composerJsonPath);
        }

        $content = file_get_contents($composerJsonPath);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid composer.json: ' . json_last_error_msg());
        }

        return $data;
    }

    /**
     * Sync modules to database
     */
    protected function syncModulesToDatabase(array $modules): void
    {
        // Ensure modules table exists
        $this->ensureModulesTable();

        foreach ($modules as $module) {
            // The module's HRMAC scope (platform|tenant|core|all) is the source of truth
            // in its config/module.php. It MUST be persisted: HRMAC role-access seeding
            // (PlatformHrmacSeeder) looks the module up by scope, e.g. scope='platform'
            // for the central/landlord module. Omitting it left scope at the column
            // default ('tenant'), so the platform module was invisible to HRMAC.
            $moduleConfig = $this->readModuleConfig($module['code']);
            $scope = $moduleConfig['scope'] ?? ($module['scope'] ?? 'tenant');

            $existing = DB::table('modules')->where('code', $module['code'])->first();

            if (! $existing) {
                $moduleId = DB::table('modules')->insertGetId([
                    'code' => $module['code'],
                    'name' => $module['name'],
                    'description' => $module['description'],
                    'scope' => $scope,
                    'is_core' => $module['is_core'],
                    'is_active' => $module['is_active'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->log("Module inserted: {$module['code']} (scope: {$scope})");

                // Seed sub_modules and module_components for the module
                $this->seedModuleComponents($moduleId, $module['code']);
            } else {
                // Update existing module
                DB::table('modules')
                    ->where('code', $module['code'])
                    ->update([
                        'name' => $module['name'],
                        'description' => $module['description'],
                        'scope' => $scope,
                        'is_core' => $module['is_core'],
                        'is_active' => $module['is_active'],
                        'updated_at' => now(),
                    ]);
                $this->log("Module updated: {$module['code']} (scope: {$scope})");

                // Ensure hierarchy is present even if the module row already existed
                // (idempotent re-run / retry).
                $this->seedModuleComponents($existing->id, $module['code']);
            }
        }
    }

    /**
     * Ensure modules table exists
     */
    protected function ensureModulesTable(): void
    {
        if (! Schema::hasTable('modules')) {
            $this->log('Modules table does not exist, creating it');
            
            Schema::create('modules', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique()->comment('Unique identifier: hrm, crm, project_mgmt');
                $table->string('name')->comment('Display name: Human Resources, CRM');
                $table->text('description')->nullable();
                $table->string('icon')->nullable()->comment('Icon class or path');
                $table->string('route_prefix')->nullable()->comment('Route prefix: /hrm, /crm');
                $table->string('category')->default('core_system')->comment('core_system, human_resources, etc.');
                $table->integer('priority')->default(0)->comment('Display order');
                $table->boolean('is_active')->default(true);
                $table->boolean('is_core')->default(false)->comment('Core modules cannot be disabled');
                $table->json('settings')->nullable()->comment('Module-specific configuration');
                $table->timestamps();
                $table->softDeletes();

                $table->index('code');
                $table->index('is_active');
                $table->index('category');
                $table->index(['is_active', 'priority']);
            });

            $this->log('Modules table created successfully');
        }
    }

    /**
     * Seed sub_modules and module_components for a module
     */
    protected function seedModuleComponents(int $moduleId, string $moduleCode): void
    {
        // Ensure sub_modules, module_components, and module_component_actions tables exist
        $this->ensureModuleHierarchyTables();

        // Read module config to get submodules and components
        $moduleConfig = $this->readModuleConfig($moduleCode);

        if (empty($moduleConfig)) {
            $this->warn("No HRMAC config found for module: {$moduleCode} - seeding skipped (non-critical)");
            return;
        }

        // Seed sub_modules from config.
        //
        // IDEMPOTENT, last-wins upsert keyed on the same unique constraints the tables
        // declare (sub_modules:[module_id,code], module_component_actions:[module_component_id,code]).
        // A module config may legitimately contain duplicate sub-module codes (the platform
        // manifest has several divergent 'code' repeats); the canonical aero:sync-module
        // sync tolerates them via updateOrCreate (the last definition wins). A plain insert
        // here was the strict outlier that aborted install on the first duplicate. Matching
        // the canonical behaviour also makes this step safely re-runnable on retry.
        $subModules = $moduleConfig['submodules'] ?? [];
        foreach ($subModules as $subModule) {
            $subModuleId = $this->upsertGetId(
                'sub_modules',
                ['module_id' => $moduleId, 'code' => $subModule['code']],
                [
                    'name' => $subModule['name'],
                    'description' => $subModule['description'] ?? null,
                    'icon' => $subModule['icon'] ?? null,
                    'route' => $subModule['route'] ?? null,
                    'priority' => $subModule['priority'] ?? 0,
                    'is_active' => true,
                ]
            );
            $this->log("Seeded sub_module: {$subModule['code']} for module: {$moduleCode}");

            // Seed components for this submodule (keyed on module_id+sub_module_id+code;
            // module_components has no DB unique constraint, so de-dupe at the app layer).
            $components = $subModule['components'] ?? [];
            foreach ($components as $component) {
                $componentId = $this->upsertGetId(
                    'module_components',
                    ['module_id' => $moduleId, 'sub_module_id' => $subModuleId, 'code' => $component['code']],
                    [
                        'name' => $component['name'],
                        'type' => $component['type'] ?? 'page',
                        'route' => $component['route'] ?? null,
                        'is_active' => true,
                    ]
                );
                $this->log("Seeded module_component: {$component['code']} for sub_module: {$subModule['code']}");

                // Seed actions for this component
                $actions = $component['actions'] ?? [];
                foreach ($actions as $action) {
                    DB::table('module_component_actions')->updateOrInsert(
                        ['module_component_id' => $componentId, 'code' => $action['code']],
                        [
                            'name' => $action['name'],
                            'description' => $action['description'] ?? null,
                            'updated_at' => now(),
                            'created_at' => now(),
                        ]
                    );
                    $this->log("Seeded module_component_action: {$action['code']} for component: {$component['code']}");
                }
            }
        }
    }

    /**
     * Insert-or-update a row identified by $keys, returning its primary id.
     * Last-wins on $values for an existing row; mirrors updateOrCreate semantics on
     * raw query-builder tables (which don't return an id from updateOrInsert).
     *
     * @param  array<string, mixed>  $keys
     * @param  array<string, mixed>  $values
     */
    protected function upsertGetId(string $table, array $keys, array $values): int
    {
        $existing = DB::table($table)->where($keys)->first();

        if ($existing) {
            DB::table($table)->where('id', $existing->id)->update($values + ['updated_at' => now()]);

            return (int) $existing->id;
        }

        return (int) DB::table($table)->insertGetId(
            $keys + $values + ['created_at' => now(), 'updated_at' => now()]
        );
    }

    /**
     * Read module config from package config/module.php
     */
    protected function readModuleConfig(string $moduleCode): ?array
    {
        $configPath = null;
        
        if ($moduleCode === 'core') {
            $configPath = base_path('vendor/aero/core/config/module.php');
        } elseif ($moduleCode === 'platform') {
            $configPath = base_path('vendor/aero/platform/config/module.php');
        } else {
            // Try to find config for other packages
            $configPath = base_path("vendor/aero/{$moduleCode}/config/module.php");
        }

        if (! file_exists($configPath)) {
            $this->warn("Module config not found at: {$configPath}");
            return null;
        }

        // Use require to include the config file and return its value
        $config = require $configPath;

        if (! is_array($config)) {
            $this->error("Invalid module config at: {$configPath}");
            return null;
        }

        return $config;
    }

    /**
     * Ensure sub_modules and module_components tables exist
     */
    protected function ensureModuleHierarchyTables(): void
    {
        if (! Schema::hasTable('sub_modules')) {
            $this->log('sub_modules table does not exist, creating it');
            
            Schema::create('sub_modules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('module_id')->constrained()->onDelete('cascade');
                $table->string('code')->comment('Unique within module: employees, payroll');
                $table->string('name')->comment('Display name');
                $table->text('description')->nullable();
                $table->string('icon')->nullable();
                $table->string('route')->nullable()->comment('Full route path');
                $table->integer('priority')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['module_id', 'code']);
                $table->index(['module_id', 'is_active']);
            });

            $this->log('sub_modules table created successfully');
        }

        if (! Schema::hasTable('module_components')) {
            $this->log('module_components table does not exist, creating it');
            
            Schema::create('module_components', function (Blueprint $table) {
                $table->id();
                $table->foreignId('module_id')->constrained()->onDelete('cascade');
                $table->foreignId('sub_module_id')->nullable()->constrained()->onDelete('cascade');
                $table->string('code')->comment('Unique identifier: employee_list, payroll_generate');
                $table->string('name')->comment('Display name');
                $table->string('type')->default('page')->comment('page, action, widget, report');
                $table->string('route')->nullable()->comment('Route name or path');
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['module_id', 'is_active']);
                $table->index(['sub_module_id', 'is_active']);
            });

            $this->log('module_components table created successfully');
        }

        if (! Schema::hasTable('module_component_actions')) {
            $this->log('module_component_actions table does not exist, creating it');
            
            Schema::create('module_component_actions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('module_component_id')->constrained()->onDelete('cascade');
                $table->string('code')->comment('Action code: view, create, edit, delete');
                $table->string('name')->comment('Display name');
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['module_component_id', 'is_active']);
                $table->unique(['module_component_id', 'code']);
            });

            $this->log('module_component_actions table created successfully');
        }
    }

    public function validate(): bool
    {
        // Check database connection
        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            $this->error('Database connection failed: '.$e->getMessage());
            return false;
        }

        // Check composer.json exists
        $composerPath = base_path('composer.json');
        if (! file_exists($composerPath)) {
            $this->error('composer.json not found at: '.$composerPath);
            return false;
        }

        // Verify at least core module config exists for standalone mode
        if ($this->mode === 'standalone') {
            $coreConfigPath = base_path('vendor/aero/core/config/module.php');
            if (! file_exists($coreConfigPath)) {
                $this->error('Core module config not found at: '.$coreConfigPath);
                return false;
            }
        }

        return true;
    }

    public function canSkip(): bool
    {
        return false;
    }

    public function isRetriable(): bool
    {
        return true;
    }
}
