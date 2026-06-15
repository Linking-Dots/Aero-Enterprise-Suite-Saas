<?php

namespace Aero\Core\Database\Seeders;

use Aero\Core\Models\User;
use Aero\HRMAC\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if admin user already exists
        if (User::where('email', 'admin@dbedc.com')->exists()) {
            $this->command->info('Admin user already exists.');

            return;
        }

        // Create Super Admin role if it doesn't exist
        $adminRole = Role::firstOrCreate([
            'name' => 'Super Admin',
            'guard_name' => 'web',
        ], [
            'description' => 'Full system access',
        ]);

        // Create admin user
        $user = User::create([
            'name' => 'Admin User',
            'user_name' => 'admin',
            'email' => 'admin@dbedc.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        // Assign Super Admin role
        $user->assignRole($adminRole);

        $this->command->info('Admin user created successfully!');
        $this->command->info('Email: admin@dbedc.com');
        $this->command->info('Password: password');
    }
}
