<?php

namespace Aero\Installation\Installation\Steps;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

/**
 * Plan Seeding Step
 *
 * Seeds the platform catalog (subscription plans, products, module pricing) for
 * SaaS mode. The seed DATA is owned by the platform package — this step only
 * invokes Aero\Platform\Database\Seeders\PlatformDatabaseSeeder (Plan + Product +
 * ModulePricing seeders), so the installation package never carries its own copy
 * of the catalog (which previously drifted from the platform definitions).
 *
 * Only runs in SaaS mode. The platform seeders are idempotent (updateOrCreate),
 * so re-running is safe.
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
        return 'Seed the platform catalog (plans, products, module pricing)';
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
        $this->log('Starting platform catalog seeding');

        // Skip in standalone mode (no platform package / no SaaS catalog)
        if ($this->mode !== 'saas') {
            $this->log('Skipping catalog seeding in standalone mode');

            return [
                'status' => 'skipped',
                'reason' => 'Standalone mode - no platform package',
            ];
        }

        // Plans table is the canary for the platform schema being migrated.
        if (! Schema::hasTable('plans')) {
            $this->warn('Plans table does not exist, skipping catalog seeding');

            return [
                'status' => 'skipped',
                'reason' => 'Plans table does not exist',
            ];
        }

        // Delegate to the platform package's catalog orchestrator. PlatformDatabaseSeeder
        // calls PlanSeeder + ProductSeeder + ModulePricingSeeder — the single source of
        // truth for the catalog. Idempotent (updateOrCreate), so safe to re-run.
        $exitCode = Artisan::call('db:seed', [
            '--class' => 'Aero\\Platform\\Database\\Seeders\\PlatformDatabaseSeeder',
            '--force' => true,
        ]);

        if ($exitCode !== 0) {
            $this->error('Platform catalog seeding returned a non-zero exit code');

            throw new \RuntimeException('PlatformDatabaseSeeder failed with exit code '.$exitCode);
        }

        $this->log('Platform catalog seeding completed');

        return [
            'status' => 'success',
            'seeder' => 'Aero\\Platform\\Database\\Seeders\\PlatformDatabaseSeeder',
            'output' => trim(Artisan::output()),
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
}
