<?php

namespace Aero\Core\Database\Seeders;

use Aero\HRMAC\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Super Administrator',
                'guard_name' => 'web',
                'description' => 'Full system access - bypasses all HRMAC checks',
                'default_dashboard' => 'dashboard',
                'priority' => 1,
            ],
            [
                'name' => 'Administrator',
                'guard_name' => 'web',
                'description' => 'Full administrative access',
                'default_dashboard' => 'dashboard',
                'priority' => 2,
            ],
            [
                'name' => 'HR Manager',
                'guard_name' => 'web',
                'description' => 'HR management access',
                'default_dashboard' => 'hrm.dashboard',
                'priority' => 3,
            ],
            [
                'name' => 'HR Staff',
                'guard_name' => 'web',
                'description' => 'HR staff access',
                'default_dashboard' => 'hrm.dashboard',
                'priority' => 4,
            ],
            [
                'name' => 'Finance Manager',
                'guard_name' => 'web',
                'description' => 'Finance management access',
                'default_dashboard' => 'finance.dashboard',
                'priority' => 5,
            ],
            [
                'name' => 'Finance Staff',
                'guard_name' => 'web',
                'description' => 'Finance staff access',
                'default_dashboard' => 'finance.dashboard',
                'priority' => 6,
            ],
            [
                'name' => 'Project Manager',
                'guard_name' => 'web',
                'description' => 'Project management access',
                'default_dashboard' => 'projects.dashboard',
                'priority' => 7,
            ],
            [
                'name' => 'CRM Manager',
                'guard_name' => 'web',
                'description' => 'CRM management access',
                'default_dashboard' => 'crm.dashboard',
                'priority' => 8,
            ],
            [
                'name' => 'Inventory Manager',
                'guard_name' => 'web',
                'description' => 'Inventory management access',
                'default_dashboard' => 'inventory.dashboard',
                'priority' => 9,
            ],
            [
                'name' => 'Employee',
                'guard_name' => 'web',
                'description' => 'Standard employee access',
                'default_dashboard' => 'hrm.employee.dashboard',
                'priority' => 10,
            ],
            [
                'name' => 'User',
                'guard_name' => 'web',
                'description' => 'Basic user access',
                'default_dashboard' => 'hrm.employee.dashboard',
                'priority' => 11,
            ],
        ];

        // Check if roles table has is_active and is_protected columns
        $hasIsActive = \Schema::hasColumn('roles', 'is_active');
        $hasIsProtected = \Schema::hasColumn('roles', 'is_protected');

        foreach ($roles as $role) {
            // Use DB::insert instead of model to avoid default attributes
            $existing = \DB::table('roles')
                ->where('name', $role['name'])
                ->where('guard_name', $role['guard_name'])
                ->first();

            if (!$existing) {
                $attributes = [
                    'name' => $role['name'],
                    'guard_name' => $role['guard_name'],
                    'description' => $role['description'],
                    'default_dashboard' => $role['default_dashboard'],
                    'priority' => $role['priority'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                // Only add these columns if they exist in the table
                if ($hasIsActive) {
                    $attributes['is_active'] = true;
                }
                if ($hasIsProtected) {
                    $attributes['is_protected'] = ($role['name'] === 'Super Administrator');
                }

                \DB::table('roles')->insert($attributes);
            }

            if ($this->command) {
                $this->command->info("Role seeded: {$role['name']}");
            }
        }

        if ($this->command) {
            $this->command->info('Role seeding completed successfully!');
        }
    }
}
