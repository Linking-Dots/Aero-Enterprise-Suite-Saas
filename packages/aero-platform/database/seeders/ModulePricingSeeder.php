<?php

namespace Aero\Platform\Database\Seeders;

use Aero\Platform\Models\Module;
use Illuminate\Database\Seeder;

/**
 * Seeds standard pricing for all modules/products.
 *
 * Sets a standard fixed price for all modules (except core which is free).
 * Modules can be selected independently by tenants and charged separately.
 */
class ModulePricingSeeder extends Seeder
{
    /**
     * Standard pricing configuration.
     */
    private const STANDARD_MONTHLY_PRICE = 10.00;

    private const STANDARD_YEARLY_PRICE = 100.00;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🏷️  Seeding module pricing...');

        $modules = Module::all();

        $updatedCount = 0;

        foreach ($modules as $module) {
            // Core module is always free
            if ($module->code === 'core') {
                $module->monthly_price = 0;
                $module->yearly_price = 0;
                $module->is_active = true;
                $module->is_featured = false;
                $module->save();
                $updatedCount++;

                $this->command->line("   ✓ {$module->code}: Free (Core module)");
                continue;
            }

            // All other modules get standard pricing
            $module->monthly_price = self::STANDARD_MONTHLY_PRICE;
            $module->yearly_price = self::STANDARD_YEARLY_PRICE;
            $module->is_active = true;
            $module->is_featured = false;
            $module->save();
            $updatedCount++;

            $this->command->line("   ✓ {$module->code}: $".self::STANDARD_MONTHLY_PRICE.'/mo or $'.self::STANDARD_YEARLY_PRICE.'/yr');
        }

        $this->command->info("✅ Updated pricing for {$updatedCount} modules");
    }
}
