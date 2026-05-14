<?php

namespace Aero\Platform\Database\Seeders;

use Illuminate\Database\Seeder;
use Aero\Platform\Database\Seeders\ModulePricingSeeder;
use Aero\Platform\Database\Seeders\PlanSeeder;
use Aero\Platform\Database\Seeders\ProductSeeder;

/**
 * Main database seeder for the Aero Platform package.
 *
 * This seeder is responsible for seeding landlord-only data:
 * - LandlordUsers (platform admins)
 * - Plans (subscription plans)
 *
 * Tenant-specific data (Users, Employees, etc.) should NOT be seeded here.
 * Tenant data is seeded via tenant-aware seeders when a tenant is created.
 */
class PlatformDatabaseSeeder extends Seeder
{
    /**
     * Seed the landlord database.
     */
    public function run(): void
    {
        $this->command->info('🚀 Seeding Platform (Landlord) database...');

        $this->call([
            PlanSeeder::class,
            ProductSeeder::class,
            ModulePricingSeeder::class,
        ]);

        $this->command->info('✅ Platform seeding complete!');
    }
}
