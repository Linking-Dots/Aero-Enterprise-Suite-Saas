<?php

namespace Aero\Core\Database\Seeders;

use Aero\HRMAC\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleModuleAccessSeeder extends Seeder
{
    protected string $mode = 'standalone';

    public function __construct(string $mode = 'standalone')
    {
        $this->mode = $mode;
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all roles
        $roles = Role::all();

        // Get modules based on mode
        if ($this->mode === 'saas') {
            // In SaaS mode, only get platform modules
            $modules = DB::table('modules')
                ->where('is_active', true)
                ->where(function ($query) {
                    $query->where('is_core', false)
                          ->orWhere('code', 'platform');
                })
                ->get();
        } else {
            // In standalone mode, get core + other package modules (excluding platform)
            $modules = DB::table('modules')
                ->where('is_active', true)
                ->where('code', '!=', 'platform')
                ->get();
        }

        $roleModuleMappings = [
            'Super Administrator' => 'all', // Access to ALL modules (bypasses HRMAC)
            'Administrator' => 'core', // Access to all core modules
            'HR Manager' => ['hrm'],
            'HR Staff' => ['hrm'],
            'Finance Manager' => ['finance'],
            'Finance Staff' => ['finance'],
            'Project Manager' => ['project'],
            'CRM Manager' => ['crm'],
            'Inventory Manager' => ['ims'],
            'Employee' => ['core'],
            'User' => ['core'],
        ];

        foreach ($roles as $role) {
            $accessConfig = $roleModuleMappings[$role->name] ?? [];

            if ($accessConfig === 'all') {
                // Super Administrator gets access to all modules in current mode
                foreach ($modules as $module) {
                    $this->assignModuleAccess($role->id, $module->id);
                }
                if ($this->command) {
                    $this->command->info("Role '{$role->name}' assigned access to ALL modules ({$this->mode} mode)");
                }
            } elseif ($accessConfig === 'core') {
                // Administrator/User gets access to core modules
                foreach ($modules as $module) {
                    if ($module->is_core) {
                        $this->assignModuleAccess($role->id, $module->id);
                    }
                }
                if ($this->command) {
                    $this->command->info("Role '{$role->name}' assigned access to core modules");
                }
            } elseif (is_array($accessConfig)) {
                // Other roles get access to specific modules by code
                // Uses dynamic discovery to find modules by code
                foreach ($accessConfig as $moduleCode) {
                    $module = $modules->firstWhere('code', $moduleCode);
                    if ($module) {
                        $this->assignModuleAccess($role->id, $module->id);
                        if ($this->command) {
                            $this->command->info("Role '{$role->name}' assigned access to module: {$moduleCode}");
                        }
                    } else {
                        // Module not found - log warning but don't fail
                        if ($this->command) {
                            $this->command->warn("Module '{$moduleCode}' not found for role '{$role->name}' - skipped");
                        }
                    }
                }
            }
        }

        if ($this->command) {
            $this->command->info('Role module access seeding completed successfully for ' . $this->mode . ' mode!');
        }
    }

    /**
     * Assign module access to a role
     */
    protected function assignModuleAccess(string $roleId, string $moduleId): void
    {
        // Check if the assignment already exists
        $existing = DB::table('role_module_access')
            ->where('role_id', $roleId)
            ->where('module_id', $moduleId)
            ->first();

        if (!$existing) {
            // Check if role_module_access table has is_active column
            $hasIsActive = \Schema::hasColumn('role_module_access', 'is_active');

            $attributes = [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'role_id' => $roleId,
                'module_id' => $moduleId,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Only add is_active if the column exists
            if ($hasIsActive) {
                $attributes['is_active'] = true;
            }

            DB::table('role_module_access')->insert($attributes);
        }
    }
}
