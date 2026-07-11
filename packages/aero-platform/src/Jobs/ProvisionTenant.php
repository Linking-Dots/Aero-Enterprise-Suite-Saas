<?php

namespace Aero\Platform\Jobs;

use Aero\HRMAC\Models\Module;
use Aero\HRMAC\Models\ModuleComponent;
use Aero\HRMAC\Models\ModuleComponentAction;
use Aero\HRMAC\Models\Role;
use Aero\HRMAC\Models\SubModule;
use Aero\Kernel\Migration\PackageTier;
use Aero\Platform\Events\TenantProvisioningStepCompleted;
use Aero\Platform\Models\PlatformSetting;
use Aero\Platform\Models\Tenant;
use Aero\Platform\TenantDatabaseManagers\CpanelDatabaseManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Stancl\Tenancy\Jobs\CreateDatabase;
use Throwable;

/**
 * ProvisionTenant Job
 *
 * Handles the asynchronous provisioning of a new tenant, including:
 * - Database creation
 * - Schema migrations
 * - Default roles seeding (permissions are NOT used)
 *
 * NOTE: Admin user creation is NOT done here. The admin user is created
 * on the tenant domain AFTER provisioning completes, during the admin
 * setup step of the registration flow.
 *
 * This job is designed to be queued and processed in the background,
 * allowing the registration flow to complete immediately while the
 * tenant infrastructure is set up asynchronously.
 *
 * The job updates the tenant's provisioning_step at each stage for
 * real-time status tracking and debugging.
 */
class ProvisionTenant implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public array $backoff = [30, 60, 120];

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     * Fix #12: Must equal $tries (3) so the backoff schedule is actually used.
     * Setting this to 1 made the job fail permanently on the first exception,
     * rendering the $backoff array useless.
     */
    public int $maxExceptions = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * Set to 10 minutes to allow for large database migrations.
     * If exceeded, the job will fail and trigger rollback.
     */
    public int $timeout = 600;

    /**
     * The tenant to provision.
     */
    public Tenant $tenant;

    /**
     * Create a new job instance.
     *
     * @param  Tenant  $tenant  The tenant to provision
     */
    public function __construct(Tenant $tenant)
    {
        $this->tenant = $tenant;
        // Axis C C3 — dedicated queue so heavy provisioning can't starve fast jobs.
        $this->onQueue('provisioning');
    }

    /**
     * Execute the job.
     *
     * Provision the tenant through the following steps:
     * 0. Pre-flight validation (plan, modules, database connection)
     * 1. Create the tenant database
     * 2. Run migrations on the tenant database
     * 3. Sync module hierarchy
     * 4. Seed default roles
     * 5. Verify provisioning (check required tables)
     * 6. Activate the tenant
     * 7. Send notification email
     *
     * NOTE: Admin user creation is done AFTER provisioning on the tenant domain.
     *
     * If any step fails, the entire provisioning is rolled back.
     */
    public function handle(): void
    {
        // Fix #26: Eager-load frequently accessed relations up-front to prevent N+1 queries
        // throughout the provisioning pipeline.
        // 'plan' is an accessor (via currentSubscription), not a direct relationship.
        $this->tenant->loadMissing(['domains', 'currentSubscription.plan']);

        $context = [
            'tenant_id' => $this->tenant->id,
            'tenant_name' => $this->tenant->name,
            'subdomain' => $this->tenant->domains->first()?->domain ?? 'unknown',
        ];

        $this->logStep('🚀 STARTING TENANT PROVISIONING', $context);

        // Audit: Log provisioning started
        $this->logAuditEvent('tenant.provisioning.started', $context);

        $databaseCreated = false;

        try {
            // Step 0: Pre-flight validation
            $this->logStep('✅ Step 0: Running pre-flight validation', $context);
            $this->validatePrerequisites();
            $this->logStep('✅ Step 0 Complete: Pre-flight validation passed', $context);

            // Step 1: Mark as provisioning
            $this->logStep('📋 Step 1: Marking tenant as provisioning', $context);
            $this->tenant->startProvisioning(Tenant::STEP_CREATING_DB);
            $this->logStep('✅ Step 1 Complete: Tenant marked as provisioning', $context);

            // Step 2: Create the database
            $this->logStep('🗄️  Step 2: Creating tenant database', $context);
            $this->createDatabase();
            $databaseCreated = true;
            $this->logStep('✅ Step 2 Complete: Database created - '.$this->tenant->database()->getName(), $context);

            // Step 3: Run migrations
            $this->logStep('🔄 Step 3: Running database migrations', $context);
            $this->migrateDatabase();
            $this->logStep('✅ Step 3 Complete: Migrations applied successfully', $context);

            // Step 4: Sync module hierarchy
            $this->logStep('📦 Step 4: Syncing module hierarchy', $context);
            $this->syncModuleHierarchy();
            $this->logStep('✅ Step 4 Complete: Module hierarchy synced', $context);

            // Step 5: Seed default roles
            $this->logStep('🔐 Step 5: Seeding default roles', $context);
            $this->seedDefaultRoles();
            $this->logStep('✅ Step 5 Complete: Default roles seeded', $context);

            // Step 6: Verify provisioning
            $this->logStep('🔍 Step 6: Verifying provisioning', $context);
            $this->verifyProvisioning();
            $this->logStep('✅ Step 6 Complete: Provisioning verified', $context);

            // Step 6.5: Build the Aeon knowledge base (fail-soft — never blocks provisioning)
            $this->logStep('🧠 Step 6.5: Building Aeon knowledge base', $context);
            $this->buildAeonKnowledgeBase();

            // Step 7: Activate the tenant (ready for admin setup on tenant domain)
            $this->logStep('🎉 Step 7: Activating tenant', $context);
            $this->activateTenant();
            $this->logStep('✅ Step 7 Complete: Tenant activated and ready for admin setup', $context);

            // Step 8: Send notification email
            $this->logStep('📧 Step 8: Sending notification email', $context);
            $this->sendWelcomeEmail();
            $this->logStep('✅ Step 8 Complete: Notification email sent', $context);

            $this->logStep('🎊 PROVISIONING COMPLETED SUCCESSFULLY - AWAITING ADMIN SETUP', $context);

            // Audit: Log provisioning completed
            $planId = $this->tenant->data['plan_id'] ?? null;
            $planName = $planId ? (\Aero\Platform\Models\Plan::find($planId)?->name) : null;
            $this->logAuditEvent('tenant.provisioning.completed', array_merge($context, [
                'plan_id' => $planId,
                'plan_name' => $planName,
                // Canonical subscribed product modules (product_subscriptions + baseline),
                // read from the central connection — no HRMAC tenant-context guard needed.
                'modules' => $this->tenant->subscribed_product_modules,
                'database' => $this->tenant->database()?->getName(),
            ]));
        } catch (Throwable $e) {
            $errorContext = array_merge($context, [
                'failed_step' => $this->tenant->provisioning_step,
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
            ]);

            $this->logStep('❌ PROVISIONING FAILED', $errorContext, 'error');
            $this->logStep('⚠️  Error: '.$e->getMessage(), $errorContext, 'error');

            // Audit: Log provisioning failed
            $this->logAuditEvent('tenant.provisioning.failed', $errorContext);

            // Rollback: Drop the database if it was created
            if ($databaseCreated) {
                $this->logStep('🔙 Initiating database rollback', $errorContext, 'warning');
                $this->rollbackDatabase();
                $this->logStep('✅ Database rollback completed', $errorContext, 'warning');
            }

            // Re-throw to trigger the failed() method
            throw $e;
        } finally {
            // Plan 03 T11 — always restore the cPanel config so the next job
            // on the same queue worker doesn't inherit this tenant's BYOC creds.
            $this->restoreCpanelConfig();
        }
    }

    /**
     * Validate prerequisites before starting provisioning.
     * Throws exception if any validation fails.
     */
    protected function validatePrerequisites(): void
    {
        $this->logStep('   → Validating tenant data', []);

        // 1. Validate tenant has a subdomain
        if (empty($this->tenant->subdomain)) {
            throw new \RuntimeException('Tenant subdomain is required for provisioning');
        }

        // 2. Validate tenant has at least one domain
        if ($this->tenant->domains()->count() === 0) {
            throw new \RuntimeException('Tenant must have at least one domain configured');
        }

        // 3. Validate database connection
        try {
            DB::connection()->getPdo();
            $this->logStep('   → Database connection verified', []);
        } catch (\Exception $e) {
            throw new \RuntimeException('Database connection failed: '.$e->getMessage());
        }

        // 4. Validate tenant has a plan selected (stored in data JSON)
        $planId = $this->tenant->data['plan_id'] ?? null;
        $plan = $planId ? \Aero\Platform\Models\Plan::find($planId) : null;
        if (! $planId || ! $plan) {
            $this->logStep('   ⚠️  Tenant has no plan assigned - will provision with core only', [], 'warning');
        }

        // 5. Validate tenant has modules (carried by product subscriptions, not plans)
        $moduleCount = count($this->tenant->subscribed_product_modules ?? []);
        if ($moduleCount === 0) {
            $this->logStep('   ⚠️  Tenant has no product modules - will provision with core only', [], 'warning');
        } else {
            $this->logStep("   → Tenant has {$moduleCount} product module(s)", ['module_count' => $moduleCount]);
        }

        // 6. Validate migration paths exist
        $migrationPaths = $this->getTenantMigrationPaths();
        if (empty($migrationPaths)) {
            throw new \RuntimeException('No migration paths found - cannot provision without migrations');
        }

        $this->logStep('   → All prerequisite checks passed', []);
    }

    /**
     * Create the tenant database.
     *
     * Hosting mode is resolved from (in order of precedence):
     *   1. platform_settings.hosting_settings.mode  (DB — Admin UI toggle)
     *   2. TENANCY_DATABASE_MANAGER env variable     (legacy .env fallback)
     *   3. Default → 'dedicated'                     (standard VPS / MySQL CREATE DATABASE)
     *
     * shared    → cPanel UAPI (Namecheap / any cPanel shared host)
     * dedicated → standard SQL CREATE DATABASE
     */
    protected function createDatabase(): void
    {
        $this->tenant->updateProvisioningStep(Tenant::STEP_CREATING_DB);

        $mode = $this->resolveHostingMode();

        $this->logStep("   → Hosting mode resolved: {$mode}", [
            'mode' => $mode,
        ]);

        if ($mode === 'shared') {
            $this->injectCpanelConfigFromDb();
            $this->createDatabaseViaCpanel();
        } else {
            $this->createDatabaseViaSQL();
        }
    }

    /**
     * Resolve the active hosting mode.
     *
     * Falls back gracefully if the DB is unavailable (e.g. first-time setup).
     */
    protected function resolveHostingMode(): string
    {
        try {
            return PlatformSetting::current()->getHostingMode();
        } catch (Throwable $e) {
            // DB unavailable during provisioning — fall back to env
            $this->logStep('   ⚠️  Could not read hosting mode from DB, using .env fallback: '.$e->getMessage(), [], 'warning');

            return env('TENANCY_DATABASE_MANAGER', 'mysql') === 'cpanel' ? 'shared' : 'dedicated';
        }
    }

    /**
     * Snapshot of the cPanel config that was in place BEFORE this job overlaid
     * BYOC credentials. Captured by injectCpanelConfigFromDb() and restored by
     * restoreCpanelConfig() — see Plan 03 T11.
     *
     * @var array<string, mixed>|null
     */
    protected ?array $originalCpanelConfig = null;

    /**
     * Inject cPanel credentials from platform_settings into the runtime config
     * so that CpanelDatabaseManager::callCpanelApi() picks them up automatically.
     *
     * DB credentials take priority over .env values.
     * If a field is empty in the DB the existing config/env value is kept.
     *
     * Plan 03 T11 — Phase 1 audit X-4: this overlay was process-global. A
     * queue worker processing multiple tenants per run would carry tenant A's
     * cPanel credentials into tenant B's provisioning unless overridden. This
     * method now captures the pre-overlay state into $originalCpanelConfig;
     * restoreCpanelConfig() in the finally branch puts it back so each job
     * leaves the runtime config exactly as it found it.
     */
    protected function injectCpanelConfigFromDb(): void
    {
        try {
            $settings = PlatformSetting::current()->getHostingSettingsDecrypted();
        } catch (Throwable $e) {
            $this->logStep('   ⚠️  Could not load cPanel credentials from DB: '.$e->getMessage(), [], 'warning');

            return;
        }

        $map = [
            'cpanel_host' => 'tenancy.cpanel.host',
            'cpanel_port' => 'tenancy.cpanel.port',
            'cpanel_username' => 'tenancy.cpanel.username',
            'cpanel_api_token' => 'tenancy.cpanel.api_token',
            'cpanel_db_user' => 'tenancy.cpanel.db_user',
        ];

        // Plan 03 T11 — snapshot ORIGINAL values so we can restore them
        // when the job ends. Only snapshot once per job run (subsequent
        // injectCpanelConfigFromDb() calls during the same job keep the
        // original baseline).
        if ($this->originalCpanelConfig === null) {
            $this->originalCpanelConfig = [];
            foreach ($map as $configKey) {
                $this->originalCpanelConfig[$configKey] = config($configKey);
            }
        }

        foreach ($map as $dbKey => $configKey) {
            if (! empty($settings[$dbKey])) {
                config([$configKey => $settings[$dbKey]]);
            }
        }

        $this->logStep('   → cPanel credentials loaded from platform_settings', [
            'host' => config('tenancy.cpanel.host'),
            'user' => config('tenancy.cpanel.username'),
        ]);
    }

    /**
     * Restore cPanel config to the state it was in before this job started.
     *
     * Plan 03 T11 — closes the cross-job BYOC credential leak. Called from
     * both the success path and the failed() callback so that whether the
     * job completes, throws, or is force-killed by Horizon, the next job
     * picked up by the same worker starts with a clean slate.
     */
    protected function restoreCpanelConfig(): void
    {
        if ($this->originalCpanelConfig === null) {
            return;
        }

        foreach ($this->originalCpanelConfig as $configKey => $originalValue) {
            config([$configKey => $originalValue]);
        }

        $this->originalCpanelConfig = null;
    }

    /**
     * Create database using cPanel API.
     */
    protected function createDatabaseViaCpanel(): void
    {
        $cpanelManager = new CpanelDatabaseManager;

        // Get the database name that cPanel will create
        $shortDbName = $this->getCpanelShortDatabaseName();
        $fullDbName = config('tenancy.cpanel.username').'_'.$shortDbName;

        $this->logStep("   → Creating database via cPanel API: {$fullDbName}", [
            'database' => $fullDbName,
            'short_name' => $shortDbName,
        ]);

        try {
            $cpanelManager->createDatabase($this->tenant);

            // Update tenant with the cPanel database name
            $this->tenant->update([
                'tenancy_db_name' => $fullDbName,
            ]);

            $this->logStep("   → Database '{$fullDbName}' created successfully via cPanel", [
                'database' => $fullDbName,
            ]);
        } catch (\Exception $e) {
            throw new \RuntimeException('cPanel database creation failed: '.$e->getMessage());
        }
    }

    /**
     * Create database using standard SQL (for VPS/dedicated servers).
     */
    protected function createDatabaseViaSQL(): void
    {
        $dbName = $this->tenant->database()->getName();

        $this->logStep("   → Creating database: {$dbName}", ['database' => $dbName]);

        CreateDatabase::dispatchSync($this->tenant);

        // Verify database was actually created
        try {
            // Use parameterized query to prevent SQL injection
            $exists = DB::select(
                'SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?',
                [$dbName]
            );

            if (empty($exists)) {
                throw new \RuntimeException("Database {$dbName} was not created successfully");
            }
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to verify database creation: '.$e->getMessage());
        }

        $this->logStep("   → Database '{$dbName}' created successfully", ['database' => $dbName]);
    }

    /**
     * Get the short database name for cPanel (without prefix).
     * Uses subdomain for readability, truncated to fit cPanel limits.
     */
    protected function getCpanelShortDatabaseName(): string
    {
        if ($this->tenant->subdomain) {
            $subdomain = preg_replace('/[^a-z0-9]/i', '', $this->tenant->subdomain);

            return 'tn_'.substr(strtolower($subdomain), 0, 16);
        }

        // Fallback: use first 16 chars of UUID
        $shortId = str_replace('-', '', substr($this->tenant->id, 0, 16));

        return 'tn_'.$shortId;
    }

    /**
     * Run migrations on the tenant database.
     * Only runs migrations for:
     * - Core package (always required)
     * - Modules included in the tenant's plan
     */
    protected function migrateDatabase(): void
    {
        $this->logStep('   → Running tenant migrations', []);

        $this->tenant->updateProvisioningStep(Tenant::STEP_MIGRATING);

        // Get dynamic migration paths based on plan modules
        $migrationPaths = $this->getTenantMigrationPaths();

        $this->logStep('   → Migration paths: '.implode(', ', $migrationPaths), [
            'paths' => $migrationPaths,
        ]);

        // Run migrations using tenancy()->run() which properly handles context
        tenancy()->runForMultiple([$this->tenant], function () use ($migrationPaths) {
            // Ensure migrations table exists
            if (! Schema::hasTable('migrations')) {
                Schema::create('migrations', function ($table) {
                    $table->id();
                    $table->string('migration');
                    $table->integer('batch');
                });
                $this->logStep('   → Created migrations table');
            }

            $batch = 1;

            foreach ($migrationPaths as $path) {
                // getTenantMigrationPaths() returns absolute realpaths (tier resolver +
                // database_path); do NOT re-wrap in base_path().
                $absolutePath = $path;
                $this->logStep("   → Running migrations from: {$absolutePath}");

                // Get all PHP files from the directory manually
                $files = glob($absolutePath.'/*.php');

                if (empty($files)) {
                    $this->logStep("   → No migration files found in: {$absolutePath}");

                    continue;
                }

                // Sort files by name (which sorts by date due to Laravel naming convention)
                sort($files);

                $this->logStep('   → Found '.count($files).' migration files');

                foreach ($files as $file) {
                    $migrationName = str_replace('.php', '', basename($file));

                    // Check if already ran
                    $alreadyRan = DB::table('migrations')
                        ->where('migration', $migrationName)
                        ->exists();

                    if ($alreadyRan) {
                        continue;
                    }

                    try {
                        // Run the migration using require which handles both named and anonymous classes
                        $migration = require $file;

                        if (is_object($migration)) {
                            // Anonymous class migration (Laravel 9+)
                            $migration->up();
                        }

                        // Record that we ran this migration
                        DB::table('migrations')->insert([
                            'migration' => $migrationName,
                            'batch' => $batch,
                        ]);

                        $this->logStep("   → Migrated: {$migrationName}");
                    } catch (Throwable $e) {
                        $this->logStep("   ❌ Failed to migrate {$migrationName}: ".$e->getMessage(), [], 'error');
                        throw $e;
                    }
                }

                $batch++;
            }
        });

        $this->logStep('   → All migrations completed', []);
    }

    /**
     * Get migration class name from file.
     */
    protected function getMigrationClassName(string $file): string
    {
        $content = file_get_contents($file);

        // Check for anonymous class migration (Laravel 9+)
        if (preg_match('/return\s+new\s+class/', $content)) {
            return 'anonymous';
        }

        // Traditional named class
        if (preg_match('/class\s+(\w+)\s+extends/', $content, $matches)) {
            return $matches[1];
        }

        return '';
    }

    /**
     * Run a single migration file.
     */
    protected function runMigrationFile(string $file): void
    {
        $migration = require $file;

        if (is_object($migration)) {
            // Anonymous class migration
            $migration->up();
        }
    }

    /**
     * Module dependency map.
     * Each module can depend on other modules that must be included.
     * Core is always included automatically (in getTenantMigrationPaths).
     *
     * @var array<string, array<string>>
     */
    private const MODULE_DEPENDENCIES = [
        'hrm' => [],
        'finance' => [],
        'crm' => [],
        'project' => [],
        'ims' => [],
        'pos' => ['ims'],        // POS requires Inventory Management
        'scm' => ['ims'],        // Supply Chain requires Inventory
        'quality' => [],
        'dms' => [],
        'compliance' => [],
        'assistant' => [],
        'rfi' => [],
    ];

    /**
     * Resolve module dependencies and return complete list.
     *
     * @param  array<string>  $moduleCodes  Original list of module codes
     * @return array<string> Module codes with dependencies resolved
     */
    protected function resolveModuleDependencies(array $moduleCodes): array
    {
        $resolved = [];
        $toProcess = $moduleCodes;

        while (! empty($toProcess)) {
            $current = array_shift($toProcess);

            if (in_array($current, $resolved, true)) {
                continue;
            }

            $resolved[] = $current;

            // Add dependencies to processing queue
            $dependencies = self::MODULE_DEPENDENCIES[$current] ?? [];
            foreach ($dependencies as $dep) {
                if (! in_array($dep, $resolved, true) && ! in_array($dep, $toProcess, true)) {
                    $toProcess[] = $dep;
                    $this->logStep("   → Auto-including dependency: {$dep} (required by {$current})", []);
                }
            }
        }

        return array_unique($resolved);
    }

    /**
     * Migration paths for a tenant DB, by the Phase-4 tier model
     * (Aero\Kernel\Migration\PackageTier): core + ALL sharable packages (unconditional:
     * core, auth, hrmac, i18n, infrastructure, notifications) + the tenant's SUBSCRIBED
     * product packages (gated to the tenant_module pivot, dependencies resolved).
     * Unknown/non-product subscription codes are skipped fail-closed. Returns absolute realpaths.
     *
     * @return array<int, string>
     */
    protected function getTenantMigrationPaths(): array
    {
        // Core + every sharable — always present in a tenant DB (the single source of truth).
        // ORDERING (Phase-1): aero-auth owns `users` + the identity tables that core/hrmac/etc FK,
        // so its path MUST run FIRST. migrationPathsForTiers returns filesystem-glob order (auth not
        // guaranteed first), so prepend auth explicitly; array_unique below keeps the first occurrence.
        $authPath = PackageTier::migrationPathForPackage('auth');
        $paths = array_merge(
            $authPath !== null ? [$authPath] : [],
            PackageTier::migrationPathsForTiers([PackageTier::CORE, PackageTier::SHARABLE])
        );
        $this->logStep('   → Auth-first core + sharable migrations: '.count($paths).' path(s)', ['paths' => $paths]);

        // Subscribed PRODUCTS only (tier=product), with dependencies auto-resolved.
        // Canonical source: Tenant::getSubscribedProductModules (product_subscriptions +
        // baseline), read from the central connection (ProductSubscription is a
        // CentralModel) — no HRMAC tenant-context guard needed.
        $tenantModules = $this->tenant->subscribed_product_modules;

        if (! empty($tenantModules)) {
            $tenantModules = $this->resolveModuleDependencies($tenantModules);

            $this->logStep('   → Tenant modules (with dependencies): '.implode(', ', $tenantModules), [
                'tenant_id' => $this->tenant->id,
                'modules' => $tenantModules,
            ]);

            foreach ($tenantModules as $moduleCode) {
                // core/sharable are already in; only product-tier subscriptions add paths here.
                // Unknown/non-product codes (e.g. dashboard with no migrations) skip fail-closed.
                if (PackageTier::tierOf($moduleCode) !== PackageTier::PRODUCT) {
                    continue;
                }

                $modulePath = PackageTier::migrationPathForPackage($moduleCode);
                if ($modulePath !== null) {
                    $paths[] = $modulePath;
                    $this->logStep("   → Including subscribed product {$moduleCode}: {$modulePath}", []);
                } else {
                    $this->logStep("   ⚠️  Subscribed product {$moduleCode} has no migrations directory", [], 'warning');
                }
            }
        } else {
            $this->logStep('   ⚠️  No plan assigned to tenant, using core + sharable only', [], 'warning');
        }

        // Host-app tenant migration overrides, if present.
        $appTenantPath = database_path('migrations'.DIRECTORY_SEPARATOR.'tenant');
        if (File::exists($appTenantPath) && ($realApp = realpath($appTenantPath)) !== false) {
            $paths[] = $realApp;
            $this->logStep("   → Including app tenant migrations: {$realApp}", []);
        }

        $paths = array_values(array_unique($paths));

        // Validate that we resolved at least the core/sharable baseline.
        if (empty($paths)) {
            throw new \RuntimeException('No migration paths found (core/sharable tier resolver returned empty). Cannot provision tenant without migrations.');
        }

        return $paths;
    }

    /**
     * Generate a username from email address.
     *
     * @deprecated Admin user creation is now done on tenant domain after provisioning
     */
    protected function generateUsername(string $email): string
    {
        // Extract local part before @
        $username = explode('@', $email)[0];

        // Replace non-alphanumeric characters with underscore
        $username = preg_replace('/[^a-zA-Z0-9]/', '_', $username);

        // Ensure it starts with a letter
        if (! preg_match('/^[a-zA-Z]/', $username)) {
            $username = 'user_'.$username;
        }

        return strtolower($username);
    }

    // NOTE: seedAdminUser() and assignSuperAdminRole() methods removed.
    // Admin user creation is now handled on the tenant domain after provisioning
    // completes, during the admin setup step of the registration flow.
    // See: app/Http/Controllers/Tenant/AdminSetupController.php

    /**
     * Sync module hierarchy from config to tenant database.
     * Populates modules, sub_modules, module_components, and module_component_actions tables.
     *
     * This method directly syncs modules from package config files to avoid
     * artisan command resolution issues in HTTP request context.
     */
    protected function syncModuleHierarchy(): void
    {
        $this->logStep('   → Syncing module hierarchy to tenant database', []);
        $this->tenant->updateProvisioningStep('syncing_modules');

        try {
            tenancy()->initialize($this->tenant);

            // Directly sync modules from config files
            $this->runModuleSyncDirectly();

            $this->logStep('   → Module hierarchy synced successfully', []);
        } catch (Throwable $e) {
            $this->logStep("   → Failed to sync modules: {$e->getMessage()}", [
                'error' => $e->getMessage(),
            ], 'error');
            throw $e;
        } finally {
            tenancy()->end();
        }
    }

    /**
     * Run module sync directly without artisan command.
     * Discovers module configs and syncs them to database.
     */
    protected function runModuleSyncDirectly(): void
    {
        $modules = $this->discoverModuleConfigs();

        // Filter module configs by tenant modules (from tenant_module pivot table)
        $tenantModuleCodes = $this->tenant->subscribed_product_modules;
        $tenantModuleCodes = array_values(array_filter($tenantModuleCodes));

        // Resolve dependencies to ensure required modules are included
        if (! empty($tenantModuleCodes)) {
            $tenantModuleCodes = $this->resolveModuleDependencies($tenantModuleCodes);
            $modules = array_values(array_filter($modules, function ($moduleConfig) use ($tenantModuleCodes) {
                return in_array($moduleConfig['code'] ?? null, $tenantModuleCodes, true);
            }));
        }

        if (empty($modules)) {
            $this->logStep('   → No module configs found to sync', []);

            return;
        }

        $this->logStep('   → Found '.count($modules).' module(s) to sync', []);

        DB::beginTransaction();

        try {
            foreach ($modules as $moduleConfig) {
                // Only sync tenant-scoped modules for tenant databases
                $scope = $moduleConfig['scope'] ?? 'tenant';
                if ($scope !== 'tenant') {
                    continue;
                }

                $this->syncModuleToDatabase($moduleConfig);
            }

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Discover module configs from installed packages.
     *
     * Uses composer.json-based discovery (same algorithm as the installation
     * package) and only includes core + the tenant's selected modules.
     */
    protected function discoverModuleConfigs(): array
    {
        $modules = [];

        // Get tenant-selected module codes (core is always included)
        $tenantModuleCodes = $this->tenant->subscribed_product_modules;
        $tenantModuleCodes = array_values(array_filter($tenantModuleCodes));
        $allowedCodes = array_merge(['core'], $tenantModuleCodes);
        $allowedCodes = array_values(array_unique($allowedCodes));

        // Read composer.json to discover installed aero packages
        $composerPath = base_path('composer.json');
        $composerData = [];
        if (File::exists($composerPath)) {
            try {
                $content = File::get($composerPath);
                $composerData = json_decode($content, true) ?? [];
            } catch (Throwable $e) {
                Log::warning("Failed to read composer.json: ".$e->getMessage());
            }
        }

        $aeroPackages = array_filter(
            array_keys($composerData['require'] ?? []),
            fn (string $package) => str_starts_with($package, 'aero/')
        );

        // Map packages to module codes and load configs for allowed ones
        foreach ($aeroPackages as $package) {
            $code = str_replace('aero/', '', $package);

            if (! in_array($code, $allowedCodes, true)) {
                continue;
            }

            $config = $this->loadModuleConfig($code);
            if ($config !== null) {
                $modules[] = $config;
            }
        }

        // Always ensure core is present even if not in composer.json
        if (! in_array('core', array_column($modules, 'code'), true)) {
            $coreConfig = $this->loadModuleConfig('core');
            if ($coreConfig !== null) {
                $modules[] = $coreConfig;
            }
        }

        return $modules;
    }

    /**
     * Load module config from a package path.
     */
    protected function loadModuleConfig(string $code): ?array
    {
        $paths = [
            base_path("vendor/aero/{$code}/config/module.php"),
            base_path("packages/aero-{$code}/config/module.php"),
        ];

        foreach ($paths as $path) {
            if (! File::exists($path)) {
                continue;
            }

            try {
                $config = require $path;
                if (is_array($config) && isset($config['code'], $config['name'])) {
                    return $config;
                }
            } catch (Throwable $e) {
                Log::warning("Failed to load module config for {$code} at {$path}: ".$e->getMessage());
            }
        }

        return null;
    }

    /**
     * Sync a single module and its hierarchy to the database.
     * Uses HRMAC models if available, falls back to Core models.
     */
    protected function syncModuleToDatabase(array $moduleDef): void
    {
        // HRMAC owns the canonical module-hierarchy models. It is a foundational
        // shared package that always boots in both central and tenant contexts,
        // so these are the single source of truth — no core/platform fallback.
        $moduleClass = Module::class;
        $subModuleClass = SubModule::class;
        $componentClass = ModuleComponent::class;
        $actionClass = ModuleComponentAction::class;

        // Sync module (top level)
        $module = $moduleClass::updateOrCreate(
            ['code' => $moduleDef['code']],
            [
                'name' => $moduleDef['name'],
                'scope' => $moduleDef['scope'] ?? 'tenant',
                'description' => $moduleDef['description'] ?? null,
                'icon' => $moduleDef['icon'] ?? null,
                'route_prefix' => $moduleDef['route_prefix'] ?? null,
                'category' => $moduleDef['category'] ?? 'core_system',
                'priority' => $moduleDef['priority'] ?? 100,
                'is_active' => $moduleDef['is_active'] ?? true,
                'is_core' => $moduleDef['is_core'] ?? false,
                'settings' => $moduleDef['settings'] ?? null,
                'version' => $moduleDef['version'] ?? '1.0.0',
                'min_plan' => $moduleDef['min_plan'] ?? null,
                'license_type' => $moduleDef['license_type'] ?? null,
                'dependencies' => $moduleDef['dependencies'] ?? null,
                'release_date' => $moduleDef['release_date'] ?? null,
            ]
        );

        $this->logStep("      → Synced module: {$moduleDef['name']}", []);

        // Sync submodules
        if (isset($moduleDef['submodules']) && is_array($moduleDef['submodules'])) {
            foreach ($moduleDef['submodules'] as $subModuleDef) {
                $subModule = $subModuleClass::updateOrCreate(
                    [
                        'module_id' => $module->id,
                        'code' => $subModuleDef['code'],
                    ],
                    [
                        'name' => $subModuleDef['name'],
                        'description' => $subModuleDef['description'] ?? null,
                        'icon' => $subModuleDef['icon'] ?? null,
                        'route' => $subModuleDef['route'] ?? null,
                        'priority' => $subModuleDef['priority'] ?? 100,
                        'is_active' => $subModuleDef['is_active'] ?? true,
                    ]
                );

                // Sync components
                if (isset($subModuleDef['components']) && is_array($subModuleDef['components'])) {
                    foreach ($subModuleDef['components'] as $componentDef) {
                        $component = $componentClass::updateOrCreate(
                            [
                                'module_id' => $module->id,
                                'sub_module_id' => $subModule->id,
                                'code' => $componentDef['code'],
                            ],
                            [
                                'name' => $componentDef['name'],
                                'description' => $componentDef['description'] ?? null,
                                'type' => $componentDef['type'] ?? 'page',
                                'route' => $componentDef['route'] ?? null,
                                'priority' => $componentDef['priority'] ?? 100,
                                'is_active' => $componentDef['is_active'] ?? true,
                            ]
                        );

                        // Sync actions
                        if (isset($componentDef['actions']) && is_array($componentDef['actions'])) {
                            foreach ($componentDef['actions'] as $actionDef) {
                                $actionClass::updateOrCreate(
                                    [
                                        'module_component_id' => $component->id,
                                        'code' => $actionDef['code'],
                                    ],
                                    [
                                        'name' => $actionDef['name'],
                                        'description' => $actionDef['description'] ?? null,
                                        'is_active' => $actionDef['is_active'] ?? true,
                                    ]
                                );
                            }
                        }
                    }
                }
            }
        }

        // Sync self-service items as a special "Self Service" submodule
        if (isset($moduleDef['self_service']) && is_array($moduleDef['self_service']) && ! empty($moduleDef['self_service'])) {
            $this->syncSelfServiceSubModule($module, $moduleDef['self_service'], $subModuleClass, $componentClass, $actionClass);
        }
    }

    /**
     * Sync self-service items as a "Self Service" submodule.
     *
     * Self-service items from config are synced as components under a special
     * "Self Service" submodule, allowing role-based access control for
     * employee-facing features like "My Dashboard", "My Leaves", etc.
     *
     * @param  mixed  $module  The parent module model
     * @param  array  $selfServiceItems  Array of self-service item definitions
     * @param  string  $subModuleClass  The SubModule model class to use
     * @param  string  $componentClass  The Component model class to use
     * @param  string  $actionClass  The Action model class to use
     */
    protected function syncSelfServiceSubModule($module, array $selfServiceItems, string $subModuleClass, string $componentClass, string $actionClass): void
    {
        // Create/update the "Self Service" submodule
        $subModule = $subModuleClass::updateOrCreate(
            [
                'module_id' => $module->id,
                'code' => 'self_service',
            ],
            [
                'name' => 'Self Service',
                'description' => 'Employee self-service features (My Workspace items)',
                'icon' => 'UserCircleIcon',
                'route' => null,
                'priority' => 0, // Show first in the module
                'is_active' => true,
            ]
        );

        $this->logStep('      → Synced self-service submodule with '.count($selfServiceItems).' items', []);

        // Convert each self-service item to a component
        foreach ($selfServiceItems as $item) {
            $component = $componentClass::updateOrCreate(
                [
                    'module_id' => $module->id,
                    'sub_module_id' => $subModule->id,
                    'code' => $item['code'],
                ],
                [
                    'name' => $item['name'],
                    'description' => $item['description'] ?? 'Self-service feature',
                    'type' => 'page',
                    'route' => $item['route'] ?? null,
                    'priority' => $item['priority'] ?? 100,
                    'is_active' => $item['is_active'] ?? true,
                ]
            );

            // Create standard self-service actions for each item
            $selfServiceActions = [
                ['code' => 'view', 'name' => 'View'],
                ['code' => 'access', 'name' => 'Access'],
            ];

            foreach ($selfServiceActions as $actionDef) {
                $actionClass::updateOrCreate(
                    [
                        'module_component_id' => $component->id,
                        'code' => $actionDef['code'],
                    ],
                    [
                        'name' => $actionDef['name'],
                        'description' => $actionDef['name'].' '.$item['name'],
                        'is_active' => true,
                    ]
                );
            }
        }
    }

    /**
     * Seed default roles for the tenant.
     *
     * Creates the essential roles that every tenant needs.
     * Permissions are NOT seeded - the system uses role-based access control only.
     */
    protected function seedDefaultRoles(): void
    {
        $this->logStep('   → Seeding default tenant roles', []);
        $this->tenant->updateProvisioningStep('seeding_roles');

        try {
            tenancy()->initialize($this->tenant);

            // Default roles that every tenant should have
            $defaultRoles = [
                [
                    'name' => 'Super Administrator',
                    'guard_name' => 'web',
                    'description' => 'Full access to all tenant features',
                    'is_protected' => true,
                ],
                [
                    'name' => 'Administrator',
                    'guard_name' => 'web',
                    'description' => 'Administrative access with most features',
                    'is_protected' => false,
                ],
                [
                    'name' => 'HR Manager',
                    'guard_name' => 'web',
                    'description' => 'Human Resources management access',
                    'is_protected' => false,
                ],
                [
                    'name' => 'Employee',
                    'guard_name' => 'web',
                    'description' => 'Basic employee access - self-service features',
                    'is_protected' => false,
                ],
            ];

            foreach ($defaultRoles as $roleData) {
                Role::firstOrCreate(
                    ['name' => $roleData['name'], 'guard_name' => $roleData['guard_name']],
                    [
                        'description' => $roleData['description'],
                        'is_protected' => $roleData['is_protected'] ?? false,
                    ]
                );
            }

            // Grant full module access to Super Administrator role
            $superAdminRole = Role::where('name', 'Super Administrator')->first();
            if ($superAdminRole) {
                $subModuleIds = DB::table('sub_modules')->pluck('id');
                foreach ($subModuleIds as $subModuleId) {
                    DB::table('role_module_access')->insertOrIgnore([
                        'role_id' => $superAdminRole->id,
                        'sub_module_id' => $subModuleId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                $this->logStep('   → Granted full module access to Super Administrator', [
                    'submodules_count' => $subModuleIds->count(),
                ]);
            }

            // Grant full module access to Administrator role (same scope as Super Admin)
            $adminRole = Role::where('name', 'Administrator')->first();
            if ($adminRole) {
                $subModuleIds = DB::table('sub_modules')->pluck('id');
                foreach ($subModuleIds as $subModuleId) {
                    DB::table('role_module_access')->insertOrIgnore([
                        'role_id' => $adminRole->id,
                        'sub_module_id' => $subModuleId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                $this->logStep('   → Granted full module access to Administrator', [
                    'submodules_count' => $subModuleIds->count(),
                ]);
            }

            // Grant self-service sub-module access to Employee role
            $employeeRole = Role::where('name', 'Employee')->first();
            if ($employeeRole) {
                $selfServiceSubModuleIds = DB::table('sub_modules')
                    ->where('code', 'self_service')
                    ->pluck('id');
                foreach ($selfServiceSubModuleIds as $subModuleId) {
                    DB::table('role_module_access')->insertOrIgnore([
                        'role_id' => $employeeRole->id,
                        'sub_module_id' => $subModuleId,
                        'access_scope' => 'own',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                // Also grant Employee access to core dashboard sub-module
                $dashboardSubModuleIds = DB::table('sub_modules')
                    ->where('code', 'dashboard')
                    ->pluck('id');
                foreach ($dashboardSubModuleIds as $subModuleId) {
                    DB::table('role_module_access')->insertOrIgnore([
                        'role_id' => $employeeRole->id,
                        'sub_module_id' => $subModuleId,
                        'access_scope' => 'own',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                $this->logStep('   → Granted self-service + dashboard access to Employee', [
                    'self_service_count' => $selfServiceSubModuleIds->count(),
                    'dashboard_count' => $dashboardSubModuleIds->count(),
                ]);
            }

            // ── Approach-2 secure admin binding ───────────────────────────────
            // Pre-create the tenant admin HERE, bound to the email verified during
            // signup, with NO usable password. This closes the tenant-hijack hole: the
            // public /admin-setup page can no longer be claimed (an admin now exists, so
            // it redirects to login), and the ONLY way in is the password-set link emailed
            // to the verified address below.
            $this->createTenantAdminUser();

            $this->logStep('   → Default roles seeded successfully', []);
        } catch (Throwable $e) {
            $this->logStep("   → Failed to seed default roles: {$e->getMessage()}", [
                'error' => $e->getMessage(),
            ], 'error');
            throw $e;
        } finally {
            tenancy()->end();
        }
    }

    /**
     * Pre-create the tenant admin, bound to the verified signup email, with no usable
     * password (Approach 2). Runs inside the tenant context established by
     * seedDefaultRoles(); the owner sets a real password via the emailed link.
     */
    protected function createTenantAdminUser(): void
    {
        $email = $this->tenant->email;
        if (empty($email)) {
            $this->logStep('   → No tenant email; skipping admin pre-creation', [], 'warning');

            return;
        }

        if (\Aero\Auth\Models\User::query()->where('email', $email)->exists()) {
            $this->logStep('   → Tenant admin already exists; skipping pre-creation', []);

            return;
        }

        $local = \Illuminate\Support\Str::of($email)->before('@');
        $userName = $local->slug('_')->value() ?: 'admin';
        $base = $userName;
        $n = 1;
        while (\Aero\Auth\Models\User::where('user_name', $userName)->exists()) {
            $userName = $base.'_'.(++$n);
        }

        $admin = \Aero\Auth\Models\User::create([
            'name' => $this->tenant->name ?: $local->title()->value(),
            'user_name' => $userName,
            'email' => $email,
            // Unusable random password — the owner sets a real one via the secure link.
            'password' => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(48)),
            'email_verified_at' => now(), // email was verified during signup
        ]);

        $superAdmin = Role::where('name', 'Super Administrator')->first();
        if ($superAdmin) {
            $admin->assignRole($superAdmin);
        }

        $this->logStep('   → Pre-created tenant admin (no password) bound to verified email', [
            'user_id' => $admin->id,
            'email' => $email,
        ]);

        $this->sendAdminPasswordSetupLink($admin);
    }

    /**
     * Email the verified admin a single-use, expiring link to set their initial
     * password (reuses Laravel's password-reset flow; token stored in the tenant DB).
     */
    protected function sendAdminPasswordSetupLink(\Aero\Auth\Models\User $admin): void
    {
        try {
            $token = \Illuminate\Support\Facades\Password::broker()->createToken($admin);

            // Use the eager-loaded domains collection (loaded in handle() on central conn).
            $domain = $this->tenant->domains->firstWhere('is_primary', true)?->domain
                ?? $this->tenant->domains->first()?->domain;
            if (! $domain) {
                $this->logStep('   → No tenant domain; cannot build password-set link', [], 'warning');

                return;
            }

            $url = "https://{$domain}/reset-password/{$token}?email=".urlencode($admin->email);
            $appName = config('app.name');

            $html = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <h1 style='color: #4F46E5;'>Set your password</h1>
                    <p>Your <strong>{$this->tenant->name}</strong> workspace is ready. For security,
                       set your admin password using the secure link below — it is tied to your
                       verified email and expires soon.</p>
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='{$url}' style='background: #4F46E5; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; font-weight: bold;'>Set your password</a>
                    </div>
                    <p style='color:#666;'>If you didn't expect this email, you can safely ignore it.</p>
                    <p style='color: #666;'>The {$appName} Team</p>
                </div>
            ";

            app(\Aero\Notifications\Services\Mail\MailService::class)
                ->usePlatformSettings()
                ->sendMail($admin->email, "Set your password · {$this->tenant->name}", $html);

            // Dev aid: also log the link so it is retrievable when MAIL_MAILER=log.
            Log::info('[AdminPasswordSetup] password-set link generated', [
                'tenant_id' => $this->tenant->id,
                'email' => $admin->email,
                'url' => $url,
            ]);
        } catch (Throwable $e) {
            $this->logStep('   → Failed to send password-set link: '.$e->getMessage(), [], 'warning');
        }
    }

    /**
     * Build the tenant's Aeon knowledge base (embeds the module registry, schema
     * and docs) so the assistant is grounded from the first login.
     *
     * Loosely coupled: aero-platform never imports aero-assistant classes — it
     * calls the `aeon:index` artisan command only if that command is registered.
     * Fail-soft: the embedding API being slow/down must never fail provisioning;
     * the checksum-guarded indexer completes the KB on the next `aeon:index` run.
     */
    protected function buildAeonKnowledgeBase(): void
    {
        try {
            if (! array_key_exists('aeon:index', Artisan::all())) {
                $this->logStep('   → aeon:index command not registered; skipping knowledge base', [], 'warning');

                return;
            }

            tenancy()->initialize($this->tenant);

            $exit = Artisan::call('aeon:index');
            $summary = trim(Artisan::output());

            $this->logStep('✅ Step 6.5 Complete: Aeon knowledge base built (exit '.$exit.')', [
                'output' => \Illuminate\Support\Str::limit($summary, 300),
            ]);
        } catch (Throwable $e) {
            // Never fail provisioning over the assistant's knowledge base.
            $this->logStep('   ⚠️  Aeon knowledge base build failed (will retry on next aeon:index): '.$e->getMessage(), [], 'warning');
        } finally {
            tenancy()->end();
        }
    }

    /**
     * Verify that provisioning completed successfully.
     * Checks that all required tables and roles exist.
     */
    protected function verifyProvisioning(): void
    {
        $this->logStep('   → Verifying provisioning completion', []);
        $this->tenant->updateProvisioningStep('verifying');

        try {
            tenancy()->initialize($this->tenant);

            // 1. Check required core tables exist
            $requiredTables = [
                'users',
                'roles',
                'model_has_roles',
                'modules',
                'sub_modules',
                'module_components',
                'module_component_actions',
            ];

            $missingTables = [];
            foreach ($requiredTables as $table) {
                if (! Schema::hasTable($table)) {
                    $missingTables[] = $table;
                }
            }

            if (! empty($missingTables)) {
                throw new \RuntimeException('Required tables missing: '.implode(', ', $missingTables));
            }

            $this->logStep('   → All required tables verified', []);

            // 2. Verify roles were seeded
            $roleCount = DB::table('roles')->count();
            if ($roleCount === 0) {
                throw new \RuntimeException('No roles found after seeding');
            }

            // Check Super Administrator role exists
            $superAdminExists = DB::table('roles')
                ->where('name', 'Super Administrator')
                ->exists();

            if (! $superAdminExists) {
                throw new \RuntimeException('Super Administrator role not found after seeding');
            }

            $this->logStep("   → Roles verified ({$roleCount} roles found)", ['role_count' => $roleCount]);

            // 3. Verify modules were synced
            $moduleCount = DB::table('modules')->count();
            if ($moduleCount === 0) {
                $this->logStep('   ⚠️  No modules found after sync', [], 'warning');
            } else {
                $this->logStep("   → Modules verified ({$moduleCount} modules found)", ['module_count' => $moduleCount]);
            }

            $this->logStep('   → Provisioning verification passed', []);
        } catch (Throwable $e) {
            $this->logStep("   → Provisioning verification failed: {$e->getMessage()}", [
                'error' => $e->getMessage(),
            ], 'error');
            throw $e;
        } finally {
            tenancy()->end();
        }
    }

    /**
     * Assign Super Administrator role to the admin user.
     */
    // NOTE: assignSuperAdminRole() method removed.
    // Admin user and role assignment is now handled on the tenant domain
    // after provisioning completes, during the admin setup step.
    // See: app/Http/Controllers/Tenant/AdminSetupController.php

    /**
     * Activate the tenant and clear sensitive data.
     */
    protected function activateTenant(): void
    {
        $this->logStep('   → Activating tenant and clearing provisioning data', []);

        // Activate clears admin_data and provisioning_step automatically
        $this->tenant->activate();

        $this->logStep("   → Tenant status: {$this->tenant->status}", [
            'status' => $this->tenant->status,
            'activated_at' => now()->toDateTimeString(),
        ]);
    }

    /**
     * Send notification email that tenant is ready for admin setup.
     */
    protected function sendWelcomeEmail(): void
    {
        try {
            // Get the company email from tenant details
            $email = $this->tenant->email;

            if (empty($email)) {
                $this->logStep('   → No email found for notification, skipping', [], 'warning');

                return;
            }

            $this->logStep('   → Sending provisioning complete notification', [
                'tenant_email' => $email,
            ]);

            // Check if notification class exists (may not be present in all installations)
            // Try package namespace first, then fall back to host app namespace
            $notificationClass = class_exists('\\Aero\\Platform\\Notifications\\WelcomeToTenant')
                ? '\\Aero\\Platform\\Notifications\\WelcomeToTenant'
                : '\\App\\Notifications\\WelcomeToTenant';
            if (! class_exists($notificationClass)) {
                $this->logStep('   → WelcomeToTenant notification not found, skipping email', [], 'warning');

                return;
            }

            // Use the notification's sendEmail method with MailService
            $notification = new $notificationClass($this->tenant);
            $sent = $notification->sendEmail($email);

            if ($sent) {
                $this->logStep('   → Notification email sent successfully', [
                    'tenant_email' => $email,
                ]);
            } else {
                $this->logStep('   → Notification email sending failed', [
                    'tenant_email' => $email,
                ], 'warning');
            }
        } catch (Throwable $e) {
            // Don't fail provisioning if email fails
            Log::error('Failed to send notification email', [
                'tenant_id' => $this->tenant->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Rollback: Drop the tenant database if provisioning fails.
     *
     * This ensures we don't leave orphaned databases in an incomplete state.
     */
    protected function rollbackDatabase(): void
    {
        try {
            $mode = $this->resolveHostingMode();

            if ($mode === 'shared') {
                // cPanel: cannot DROP DATABASE via SQL — must use the UAPI
                $this->injectCpanelConfigFromDb();

                $this->logStep('   → Rolling back cPanel database', ['mode' => 'shared'], 'warning');

                $manager = new CpanelDatabaseManager;
                $manager->deleteDatabase($this->tenant);

                $this->logStep('   → cPanel database deleted successfully', [], 'warning');

                return;
            }

            // VPS / dedicated: standard SQL DROP DATABASE
            $databaseName = $this->tenant->database()->getName();

            if (empty($databaseName)) {
                $this->logStep('   → No database name found for rollback', [], 'warning');

                return;
            }

            $this->logStep("   → Dropping database: {$databaseName}", ['database' => $databaseName], 'warning');

            // Shared safety guard (Axis A A9): safe chars + tenant prefix + not
            // central. Non-throwing here — we're already on an error/rollback path.
            // Plan 03 T12 closed the regex-loophole + central-DB risk; this now
            // routes through the single guard shared with Forget/Purge.
            if (! \Aero\Platform\Support\TenantDatabaseDropGuard::isSafe($databaseName)) {
                $this->logStep(
                    "   → REFUSED to drop '{$databaseName}': failed tenant DB drop guard (unsafe chars, missing tenant prefix, or central DB)",
                    ['database' => $databaseName],
                    'error'
                );

                return;
            }

            \DB::statement("DROP DATABASE IF EXISTS `{$databaseName}`");

            $this->logStep("   → Database '{$databaseName}' dropped successfully", ['database' => $databaseName], 'warning');
        } catch (Throwable $e) {
            // Log rollback failure but don't throw — we're already in error state
            $this->logStep("   → Failed to drop database: {$e->getMessage()}", [
                'error' => $e->getMessage(),
            ], 'error');
        }
    }

    /**
     * Handle a job failure.
     *
     * Performs rollback based on configuration:
     * - If PRESERVE_FAILED_TENANTS=true: Keep tenant record for debugging (default in dev)
     * - If PRESERVE_FAILED_TENANTS=false: Delete completely to allow re-registration (production)
     *
     * Steps:
     * 1. Mark tenant as failed with error details
     * 2. Drop tenant database (if created)
     * 3. Send failure notification to user
     * 4. Optionally delete tenant record (based on config)
     */
    public function failed(?Throwable $exception): void
    {
        $this->logStep('❌ TENANT PROVISIONING FAILED - PERFORMING ROLLBACK', [
            'step' => $this->tenant->provisioning_step,
            'error' => $exception?->getMessage(),
            'trace' => $exception?->getTraceAsString(),
        ], 'error');

        // Send failure notification to user before rollback
        $this->notifyProvisioningFailure($exception);

        try {
            // Step 1: Mark tenant as failed with error details
            $this->logStep('🔙 Step 1/3: Marking tenant as failed', [], 'warning');
            $this->tenant->markProvisioningFailed($exception?->getMessage());

            // Step 2: Drop tenant database if it exists
            $this->logStep('🔙 Step 2/3: Rolling back database', [], 'warning');
            $this->rollbackDatabase();

            // Step 3: Decide whether to delete tenant completely
            $preserveFailedTenants = config('platform.preserve_failed_tenants', config('app.debug', false));

            if ($preserveFailedTenants) {
                // Keep tenant record for debugging (useful in development)
                $this->logStep('✅ ROLLBACK COMPLETE - Tenant preserved for debugging', [
                    'tenant_id' => $this->tenant->id,
                    'subdomain' => $this->tenant->subdomain,
                    'note' => 'Admin can retry provisioning or manually delete',
                ], 'warning');
            } else {
                // Archive tenant (soft delete) to allow re-registration
                // Don't use forceDelete - maintain audit trail
                $this->logStep('🔙 Step 3/3: Archiving failed tenant', [], 'warning');
                $this->tenant->delete(); // Soft delete instead of force delete

                $this->logStep('✅ COMPLETE ROLLBACK SUCCESSFUL - Tenant archived, user can re-register', [], 'warning');
            }
        } catch (Throwable $e) {
            $this->logStep('❌ ROLLBACK FAILED: '.$e->getMessage(), [
                'error' => $e->getMessage(),
            ], 'error');

            // As a last resort, try to mark as failed so admin can manually clean up
            try {
                $this->tenant->refresh();
                $this->tenant->markProvisioningFailed($exception?->getMessage());

                $this->logStep('⚠️  Tenant marked as failed - manual cleanup required', [
                    'tenant_id' => $this->tenant->id,
                ], 'error');
            } catch (Throwable $markError) {
                $this->logStep('❌ CRITICAL: Could not even mark tenant as failed', [
                    'tenant_id' => $this->tenant->id ?? 'unknown',
                    'error' => $markError->getMessage(),
                ], 'critical');
            }
        } finally {
            // Plan 03 T11 — rollbackDatabase() above re-overlays cPanel
            // credentials via injectCpanelConfigFromDb(); make sure they
            // don't leak into the next job on this worker.
            $this->restoreCpanelConfig();
        }
    }

    /**
     * Notify user that provisioning failed.
     */
    protected function notifyProvisioningFailure(?Throwable $exception): void
    {
        try {
            // Use tenant contact email
            if ($this->tenant->email) {
                // Try package namespace first, then fall back to host app namespace
                $notificationClass = class_exists('\\Aero\\Platform\\Notifications\\TenantProvisioningFailed')
                    ? '\\Aero\\Platform\\Notifications\\TenantProvisioningFailed'
                    : '\\App\\Notifications\\TenantProvisioningFailed';

                if (! class_exists($notificationClass)) {
                    $this->logStep('   → TenantProvisioningFailed notification class not found, skipping', [], 'warning');

                    return;
                }

                $notification = new $notificationClass(
                    $this->tenant,
                    $exception?->getMessage() ?? 'Unknown error'
                );
                $notification->sendEmail($this->tenant->email);

                $this->logStep('📧 Provisioning failure notification sent', [
                    'email' => $this->tenant->email,
                ], 'warning');
            }
        } catch (Throwable $e) {
            Log::error('Failed to send provisioning failure notification', [
                'tenant_id' => $this->tenant->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Log a provisioning step to both console and Laravel log.
     * Also broadcasts the step completion for real-time updates.
     */
    protected function logStep(string $message, array $context = [], string $level = 'info'): void
    {
        // Add tenant context to all logs
        $fullContext = array_merge([
            'tenant_id' => $this->tenant->id,
            'tenant_name' => $this->tenant->name,
        ], $context);

        // Log to Laravel log
        Log::log($level, $message, $fullContext);

        // Log to console ONLY when running from CLI (not HTTP context)
        // This prevents output from corrupting Inertia responses when using sync queue
        if (app()->runningInConsole()) {
            echo '['.now()->format('Y-m-d H:i:s')."] {$message}".PHP_EOL;

            // Flush output immediately
            if (function_exists('flush')) {
                flush();
            }
        }

        // Broadcast step completion for real-time updates (if WebSocket configured)
        // This will only broadcast if BROADCAST_DRIVER is set to pusher/redis/etc
        // Skip if provisioning_step is null (happens after activation clears it)
        $step = $this->tenant->provisioning_step ?? 'completed';
        if (str_contains($message, '✅') && config('broadcasting.default') !== 'null') {
            try {
                broadcast(new TenantProvisioningStepCompleted(
                    $this->tenant,
                    $step,
                    $message
                ));
            } catch (Throwable $e) {
                // Don't fail provisioning if broadcasting fails
                Log::debug('Broadcasting failed', ['error' => $e->getMessage()]);
            }
        }
    }

    /**
     * Log structured audit event for compliance tracking.
     *
     * Stores audit events in the central database for tracking
     * tenant provisioning lifecycle events.
     */
    protected function logAuditEvent(string $event, array $context = []): void
    {
        try {
            $auditData = [
                'action' => $event,
                'context' => json_encode(array_merge([
                    'tenant_id' => $this->tenant->id,
                    'tenant_name' => $this->tenant->name,
                    'subdomain' => $this->tenant->subdomain,
                    'timestamp' => now()->toISOString(),
                ], $context)),
                'level' => str_contains($event, 'failed') ? 'error' : 'info',
                'user_id' => null, // System event, no user
                'ip_address' => request()->ip() ?? '127.0.0.1',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Plan 03 T5 — write through the central connection (NOT hardcoded
            // 'mysql'). Standalone deployments and Postgres SaaS clusters use
            // different connection names; the canonical 'central' alias is
            // declared in config/database.php and always points to the right
            // place regardless of driver / host name.
            DB::connection('central')->table('audit_logs')->insert($auditData);
        } catch (Throwable $e) {
            // Don't fail provisioning if audit logging fails
            Log::warning('Failed to store provisioning audit event', [
                'event' => $event,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
