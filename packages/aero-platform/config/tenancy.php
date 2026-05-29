<?php

declare(strict_types=1);

use Stancl\Tenancy\Database\Models\Domain;

return [

    /*
    |--------------------------------------------------------------------------
    | Tenant Model
    |--------------------------------------------------------------------------
    |
    | The tenant model used by the application.
    |
    */

    'tenant_model' => \Aero\Platform\Models\Tenant::class,

    /*
    |--------------------------------------------------------------------------
    | Central Domains
    |--------------------------------------------------------------------------
    |
    | Domains that should not be handled by tenancy (central app domains).
    | This includes the main platform domain and admin subdomain.
    |
    | IMPORTANT: This is a static fallback list. The actual central domains
    | are dynamically configured at runtime by AeroPlatformServiceProvider::configureCentralDomains()
    | which auto-detects from the current HTTP request.
    |
    | These static values are used only during:
    | - config:cache generation
    | - Console/artisan commands
    | - Queue workers
    |
    | For production, set PLATFORM_DOMAIN in .env to your actual domain.
    |
    */

    'central_domains' => [
        'localhost',
        'admin.localhost',
        '127.0.0.1',
    ],

    /*
    |--------------------------------------------------------------------------
    | Domain Model
    |--------------------------------------------------------------------------
    */

    'domain_model' => Domain::class,

    /*
    |--------------------------------------------------------------------------
    | Identification Middleware
    |--------------------------------------------------------------------------
    |
    | The middleware used to identify tenants based on the incoming request.
    |
    */

    'identification' => [
        'middleware' => \Stancl\Tenancy\Middleware\InitializeTenancyByDomain::class,
        'driver' => 'domain',
    ],

    /*
    |--------------------------------------------------------------------------
    | Database
    |--------------------------------------------------------------------------
    |
    | Database configuration for tenant databases.
    |
    */

    'database' => [
        // The connection to use when reverting to central database
        // This is the default connection before tenancy is initialized
        'central_connection' => env('DB_CONNECTION', 'mysql'),

        'prefix' => 'tenant',
        'suffix' => '',

        // Template for tenant database connection
        // Uses the default DB connection as the template for creating tenant databases
        'template_tenant_connection' => env('DB_CONNECTION', 'mysql'),

        // Managers that handle tenant database creation/deletion.
        //
        // HOSTING MODE PRECEDENCE (resolved at provisioning time in ProvisionTenant::createDatabase):
        //   1. platform_settings.hosting_settings.mode  (DB — set via Admin → Settings → Infrastructure)
        //   2. TENANCY_DATABASE_MANAGER env variable     (legacy .env fallback)
        //   3. Default → 'mysql'                         (dedicated/VPS)
        //
        // 'shared'    mode → CpanelDatabaseManager  (Namecheap / cPanel shared hosting)
        // 'dedicated' mode → MySQLDatabaseManager   (VPS, cloud, local)
        'managers' => [
            'mysql' => env('TENANCY_DATABASE_MANAGER', 'mysql') === 'cpanel'
                ? \Aero\Platform\TenantDatabaseManagers\CpanelDatabaseManager::class
                : \Stancl\Tenancy\TenantDatabaseManagers\MySQLDatabaseManager::class,
            'mariadb' => env('TENANCY_DATABASE_MANAGER', 'mysql') === 'cpanel'
                ? \Aero\Platform\TenantDatabaseManagers\CpanelDatabaseManager::class
                : \Stancl\Tenancy\TenantDatabaseManagers\MySQLDatabaseManager::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | cPanel Configuration (for shared hosting)
    |--------------------------------------------------------------------------
    |
    | These settings are only used when TENANCY_DATABASE_MANAGER=cpanel
    |
    | To use cPanel mode:
    | 1. Set TENANCY_DATABASE_MANAGER=cpanel in .env
    | 2. Generate an API token in cPanel → Security → Manage API Tokens
    | 3. Configure the credentials below
    |
    */

    'cpanel' => [
        'host' => env('CPANEL_HOST'),           // e.g., 'aeos365.com' or 'cpanel.aeos365.com'
        'username' => env('CPANEL_USERNAME'),    // cPanel username (e.g., 'aeos365')
        'api_token' => env('CPANEL_API_TOKEN'),  // API token from cPanel
        'port' => env('CPANEL_PORT', 2083),      // cPanel HTTPS port (usually 2083)
        'db_user' => env('CPANEL_DB_USER'),      // Database user (defaults to username)
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | Tenant resolution caching configuration.
    |
    */

    'cache' => [
        'tag' => 'tenancy',
        'ttl' => 3600, // 1 hour
    ],

    /*
    |--------------------------------------------------------------------------
    | Filesystem (Phase 0 T5)
    |--------------------------------------------------------------------------
    |
    | Required by Stancl FilesystemTenancyBootstrapper. The disks listed here
    | get a tenant-suffixed root at runtime; uploads to disk('local') and
    | disk('public') become tenant-isolated paths under storage/app/.
    |
    | Operator must ensure config/filesystems.php defines 'local' and 'public'
    | disks (Laravel defaults). For S3 deployments, also list 's3'.
    |
    */

    'filesystem' => [
        'suffix_base' => 'tenant',
        'disks' => [
            'local',
            'public',
            's3',
        ],
        'root_override' => [
            'local'  => '%storage_path%/app/',
            'public' => '%storage_path%/app/public/',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Bootstrappers
    |--------------------------------------------------------------------------
    |
    | The bootstrappers are executed when tenancy is initialized.
    | They configure the application for the specific tenant.
    |
    */

    'bootstrappers' => [
        \Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper::class,
        \Stancl\Tenancy\Bootstrappers\CacheTenancyBootstrapper::class, // Re-enabled (Phase 0 T4) — REQUIRES CACHE_STORE=redis (tagging support)
        \Stancl\Tenancy\Bootstrappers\FilesystemTenancyBootstrapper::class, // Re-enabled (Phase 0 T5) — requires 'filesystem' config block above
        \Stancl\Tenancy\Bootstrappers\QueueTenancyBootstrapper::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    |
    | Enabled tenancy features.
    |
    */

    'features' => [
        \Stancl\Tenancy\Features\TenantConfig::class,
        \Stancl\Tenancy\Features\CrossDomainRedirect::class,
        \Stancl\Tenancy\Features\UserImpersonation::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Parameters
    |--------------------------------------------------------------------------
    |
    | Parameters passed to tenant migrations.
    |
    */

    'migration_parameters' => [
        '--force' => true,
        '--path' => [
            // Tenant migrations from packages (monorepo structure)
            // aero-core provides base tenant tables (users, roles, permissions, etc.)
            'vendor/aero/core/database/migrations',
            // aero-hrm provides HRM-specific tables
            'vendor/aero/hrm/database/migrations',
            // App-level tenant migrations (if any)
            database_path('migrations/tenant'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Seeder Parameters
    |--------------------------------------------------------------------------
    */

    'seeder_parameters' => [
        '--class' => 'Database\\Seeders\\TenantDatabaseSeeder',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenant Retention Policy
    |--------------------------------------------------------------------------
    |
    | When a tenant is archived (soft deleted), it enters a retention window
    | where it can be restored. After the retention period expires, it can
    | be permanently purged.
    |
    | This ensures compliance with data retention regulations and provides
    | a safety net for accidental deletions.
    |
    */
    'retention' => [
        'enabled' => env('TENANT_RETENTION_ENABLED', true),
        'days' => env('TENANT_RETENTION_DAYS', 30),
        'auto_purge' => env('TENANT_AUTO_PURGE', false),
        'notify_before_purge_days' => env('TENANT_NOTIFY_BEFORE_PURGE', 7),
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenant Deletion Policy
    |--------------------------------------------------------------------------
    |
    | Controls what happens when a tenant is deleted.
    |
    */
    'deletion' => [
        'require_confirmation' => true,
        'require_reason' => true,
        'notify_tenant' => true,
        'backup_before_purge' => true,
    ],

];
