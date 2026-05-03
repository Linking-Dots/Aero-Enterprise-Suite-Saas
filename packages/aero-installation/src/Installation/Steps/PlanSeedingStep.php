<?php

namespace Aero\Installation\Installation\Steps;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Plan Seeding Step
 *
 * Seeds default subscription plans and module pricing for SaaS mode
 * Only runs in SaaS mode and only if no plans exist
 */
class PlanSeedingStep extends BaseInstallationStep
{
    protected string $mode;

    public function __construct(string $mode = 'standalone')
    {
        $this->mode = $mode;
    }

    public function name(): string
    {
        return 'plan_seeding';
    }

    public function description(): string
    {
        return 'Seed default subscription plans and module pricing';
    }

    public function order(): int
    {
        return 6;
    }

    public function dependencies(): array
    {
        return ['config', 'database', 'migration', 'modules'];
    }

    public function execute(): array
    {
        $this->log('Starting plan seeding');

        // Skip in standalone mode
        if ($this->mode !== 'saas') {
            $this->log('Skipping plan seeding in standalone mode');
            return [
                'status' => 'skipped',
                'reason' => 'Standalone mode - no platform package',
            ];
        }

        // Check if plans table exists and has records
        if (! Schema::hasTable('plans')) {
            $this->warn('Plans table does not exist, skipping plan seeding');
            return [
                'status' => 'skipped',
                'reason' => 'Plans table does not exist',
            ];
        }

        $existingPlans = DB::table('plans')->count();
        if ($existingPlans > 0) {
            $this->log("Plans table already has {$existingPlans} records, skipping seeding");
            return [
                'status' => 'skipped',
                'reason' => 'Plans already exist',
                'existing_count' => $existingPlans,
            ];
        }

        // Seed default plans
        $plansSeeded = $this->seedDefaultPlans();

        // Seed module pricing
        $modulePricingSeeded = $this->seedModulePricing();

        $this->log('Plan seeding completed');

        return [
            'status' => 'success',
            'plans_seeded' => $plansSeeded,
            'module_pricing_seeded' => $modulePricingSeeded,
        ];
    }

    public function validate(): bool
    {
        // Check that plans table exists in SaaS mode
        if ($this->mode !== 'saas') {
            return true; // Skip validation in standalone mode
        }

        try {
            return Schema::hasTable('plans');
        } catch (\Exception) {
            return false;
        }
    }

    public function canSkip(): bool
    {
        // Skip in standalone mode
        return $this->mode !== 'saas';
    }

    public function isRetriable(): bool
    {
        return true;
    }

    /**
     * Seed default plans for SaaS mode
     * Returns the count of plans seeded
     */
    protected function seedDefaultPlans(): int
    {
        $now = now();

        $plans = [
            [
                'id' => (string) Str::uuid(),
                'name' => 'Starter',
                'slug' => 'starter',
                'tier' => 1,
                'plan_type' => 'subscription',
                'monthly_price' => 29.00,
                'yearly_price' => 290.00,
                'setup_fee' => 0,
                'trial_days' => 14,
                'grace_days' => 7,
                'features' => json_encode([
                    'Up to 10 users',
                    '10GB storage',
                    'Email support',
                ]),
                'limits' => json_encode([
                    'max_users' => 10,
                    'max_storage_gb' => 10,
                    'max_modules' => 3,
                ]),
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Professional',
                'slug' => 'professional',
                'tier' => 2,
                'plan_type' => 'subscription',
                'monthly_price' => 79.00,
                'yearly_price' => 790.00,
                'setup_fee' => 0,
                'trial_days' => 14,
                'grace_days' => 7,
                'features' => json_encode([
                    'Up to 50 users',
                    '100GB storage',
                    'Priority support',
                    'SSO',
                ]),
                'limits' => json_encode([
                    'max_users' => 50,
                    'max_storage_gb' => 100,
                    'max_modules' => null, // Unlimited
                ]),
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Business',
                'slug' => 'business',
                'tier' => 3,
                'plan_type' => 'subscription',
                'monthly_price' => 149.00,
                'yearly_price' => 1490.00,
                'setup_fee' => 0,
                'trial_days' => 14,
                'grace_days' => 7,
                'features' => json_encode([
                    'Up to 100 users',
                    '250GB storage',
                    'Priority support',
                    'SSO',
                    'API access',
                ]),
                'limits' => json_encode([
                    'max_users' => 100,
                    'max_storage_gb' => 250,
                    'max_modules' => null, // Unlimited
                ]),
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'tier' => 4,
                'plan_type' => 'subscription',
                'monthly_price' => 299.00,
                'yearly_price' => 2990.00,
                'setup_fee' => 0,
                'trial_days' => 30,
                'grace_days' => 14,
                'features' => json_encode([
                    'Unlimited users',
                    'Unlimited storage',
                    '24/7 dedicated support',
                    'Custom domain',
                    'API access',
                    'White label',
                ]),
                'limits' => json_encode([
                    'max_users' => null, // Unlimited
                    'max_storage_gb' => null, // Unlimited
                    'max_modules' => null, // Unlimited
                ]),
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($plans as $plan) {
            DB::table('plans')->insert($plan);
            $this->log("Seeded plan: {$plan['name']}");
        }

        return count($plans);
    }

    /**
     * Seed module pricing for all discovered aero packages into module_pricing table.
     * Reads composer.json directly so all tenant modules get priced even though the
     * central modules table only stores 'platform' in SaaS mode.
     * Core module is free; all others are $20/mo or $200/yr.
     * Returns the count of module pricing records seeded
     */
    protected function seedModulePricing(): int
    {
        if (! Schema::hasTable('module_pricing')) {
            $this->warn('module_pricing table does not exist, skipping module pricing seeding');
            return 0;
        }

        // Read composer.json to discover all aero packages for pricing
        $composerJson = $this->readComposerJson();
        $aeroPackages = array_filter(array_keys($composerJson['require'] ?? []), function ($package) {
            return str_starts_with($package, 'aero/');
        });

        $count = 0;

        foreach ($aeroPackages as $package) {
            $moduleCode = str_replace('aero/', '', $package);

            // Only seed pricing for product packages (read category from package's own composer.json)
            if ($this->getPackageCategory($moduleCode) !== 'product') {
                $this->log("Skipped module pricing for {$moduleCode} (foundation package)");
                continue;
            }

            DB::table('module_pricing')->updateOrInsert(
                ['module_code' => $moduleCode],
                [
                    'monthly_price' => 20.00,
                    'yearly_price'  => 200.00,
                    'is_active'     => true,
                    'updated_at'    => now(),
                    'created_at'    => now(),
                ]
            );

            $this->log("Seeded module pricing: {$moduleCode} ($20/mo, $200/yr)");
            $count++;
        }

        return $count;
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

    /**
     * Read composer.json file
     */
    private function readComposerJson(): array
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
}
