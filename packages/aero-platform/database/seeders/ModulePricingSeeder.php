<?php

namespace Aero\Platform\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds standard pricing for all product modules into the module_pricing table.
 *
 * Only seeds pricing for packages where extra.aero.category === 'product'.
 * Foundation packages (core, auth, ui, platform, etc.) are excluded.
 *
 * Billing pricing lives in module_pricing, not the modules table (which is
 * reserved for the feature/permission registry).
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

        // Discover installed aero packages from composer.json
        $composerJsonPath = base_path('composer.json');
        if (! file_exists($composerJsonPath)) {
            $this->command->warn('composer.json not found, skipping module pricing seeding');
            return;
        }

        $composerJson = json_decode(file_get_contents($composerJsonPath), true);
        $aeroPackages = array_filter(array_keys($composerJson['require'] ?? []), function ($package) {
            return str_starts_with($package, 'aero/');
        });

        $updatedCount = 0;

        foreach ($aeroPackages as $package) {
            $moduleCode = str_replace('aero/', '', $package);

            // Only seed pricing for product packages (read category from package's own composer.json)
            if ($this->getPackageCategory($moduleCode) !== 'product') {
                $this->command->line("   ⊘ {$moduleCode}: skipped (foundation package)");
                continue;
            }

            DB::table('module_pricing')->updateOrInsert(
                ['module_code' => $moduleCode],
                [
                    'monthly_price' => self::STANDARD_MONTHLY_PRICE,
                    'yearly_price'  => self::STANDARD_YEARLY_PRICE,
                    'is_active'     => true,
                    'updated_at'    => now(),
                    'created_at'    => now(),
                ]
            );

            $updatedCount++;
            $this->command->line("   ✓ {$moduleCode}: $" . self::STANDARD_MONTHLY_PRICE . '/mo or $' . self::STANDARD_YEARLY_PRICE . '/yr');
        }

        $this->command->info("✅ Updated pricing for {$updatedCount} product modules in module_pricing table");
    }

    /**
     * Read the category from a package's own composer.json.
     * Checks vendor/aero/{code}/composer.json and packages/aero-{code}/composer.json.
     */
    protected function getPackageCategory(string $code): ?string
    {
        $paths = [
            base_path("vendor/aero/{$code}/composer.json"),
            base_path("packages/aero-{$code}/composer.json"),
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                $content = file_get_contents($path);
                $data = json_decode($content, true);
                if (is_array($data) && isset($data['extra']['aero']['category'])) {
                    return $data['extra']['aero']['category'];
                }
            }
        }

        return null;
    }
}
