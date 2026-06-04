<?php

namespace Aero\Platform\Console\Commands;

use Aero\Platform\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Stancl\Tenancy\Tenancy;

/**
 * TenantMigrate Command
 *
 * Runs database migrations for one or all tenants.
 * Supports fresh migrations, seeding, and rollback operations.
 */
class TenantMigrate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:migrate
                            {tenant? : The tenant ID to migrate (optional, migrates all if not specified)}
                            {--fresh : Wipe the tenant database and re-run all migrations}
                            {--seed : Seed the database after migrations}
                            {--rollback : Rollback the last database migration}
                            {--step=1 : The number of migrations to rollback}
                            {--force : Force the operation to run in production}
                            {--path= : The path to the migrations files to be executed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run database migrations for tenant(s)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $tenantId = $this->argument('tenant');

        if ($tenantId) {
            $tenant = Tenant::find($tenantId);
            if (! $tenant) {
                $this->error("Tenant '{$tenantId}' not found.");

                return self::FAILURE;
            }

            return $this->migrateTenant($tenant);
        }

        // Migrate all tenants
        if (! $this->option('force') && ! $this->confirm('This will run migrations for ALL tenants. Continue?')) {
            return self::FAILURE;
        }

        $tenants = Tenant::where('status', '!=', 'archived')->get();

        if ($tenants->isEmpty()) {
            $this->warn('No tenants found.');

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($tenants->count());
        $bar->start();

        $failed = [];

        foreach ($tenants as $tenant) {
            $result = $this->migrateTenant($tenant, false);
            if ($result !== self::SUCCESS) {
                $failed[] = $tenant->id;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        if (count($failed) > 0) {
            $this->error('The following tenants failed to migrate:');
            foreach ($failed as $id) {
                $this->line("  - {$id}");
            }

            return self::FAILURE;
        }

        $this->info("Successfully migrated {$tenants->count()} tenants.");

        return self::SUCCESS;
    }

    /**
     * Migrate a specific tenant's database.
     */
    protected function migrateTenant(Tenant $tenant, bool $showOutput = true): int
    {
        if ($showOutput) {
            $this->info("Migrating tenant: {$tenant->id} ({$tenant->name})");
        }

        try {
            // Initialize tenancy for this tenant
            tenancy()->initialize($tenant);

            $options = [
                '--force' => true,
            ];

            // Use the CURATED tenant migration paths (baseline framework packages +
            // subscribed product modules) — the SAME set ProvisionTenant uses. Without
            // this the command ran the FULL migration set against the tenant DB, creating
            // central-only tables (tenants/plans/subscriptions/landlord_users) and hitting
            // cross-DB FK clashes. An explicit --path on the CLI overrides this.
            if ($this->option('path')) {
                $options['--path'] = $this->option('path');
            } else {
                $options['--path'] = app(\Aero\Platform\Services\TenantMigrationPaths::class)->forTenant($tenant);
            }

            // Determine which migration command to run
            if ($this->option('rollback')) {
                $options['--step'] = $this->option('step');
                Artisan::call('migrate:rollback', $options, $showOutput ? $this->output : null);
            } elseif ($this->option('fresh')) {
                Artisan::call('migrate:fresh', $options, $showOutput ? $this->output : null);
            } else {
                Artisan::call('migrate', $options, $showOutput ? $this->output : null);
            }

            // Run seeder if requested
            if ($this->option('seed') && ! $this->option('rollback')) {
                Artisan::call('db:seed', [
                    '--force' => true,
                ], $showOutput ? $this->output : null);
            }

            // End tenancy
            tenancy()->end();

            if ($showOutput) {
                $this->info("Tenant {$tenant->id} migrated successfully.");
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            tenancy()->end();

            $this->error("Failed to migrate tenant {$tenant->id}: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
