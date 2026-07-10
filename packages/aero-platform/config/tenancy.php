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
    | Reserved Subdomains (Plan 03 Task 6 — subdomain hijack guard)
    |--------------------------------------------------------------------------
    |
    | A tenant must not be allowed to register a subdomain that collides with
    | platform infrastructure DNS (mail., smtp., static., etc.). The Phase 1
    | audit identified the previous reserved list (admin, www, api) as
    | dangerously incomplete — a tenant could register 'mail.aeos365.com' and
    | intercept platform mail.
    |
    | Operators can extend this list via config override without re-deploying
    | the package. Order doesn't matter; matching is case-insensitive (the
    | validator lowercases the input first).
    |
    */

    'reserved_subdomains' => [
        // Generic admin / API surfaces
        'admin', 'www', 'api', 'app', 'apps',
        // Mail infrastructure
        'mail', 'smtp', 'imap', 'pop', 'pop3', 'webmail',
        // CDN / static
        'cdn', 'static', 'media', 'assets', 'images', 'img',
        // Platform identity
        'central', 'platform', 'landlord', 'tenant',
        // Support / status
        'support', 'status', 'help', 'docs', 'doc', 'documentation',
        // Realtime / broadcast
        'ws', 'websocket', 'broadcast', 'pusher', 'socket',
        // Analytics / metrics
        'stats', 'metrics', 'analytics', 'dashboard', 'monitor', 'monitoring',
        // Network infrastructure
        'ftp', 'sftp', 'ssh', 'ns1', 'ns2', 'ns3', 'ns4', 'dns',
        // System / root
        'root', 'system', 'sys', 'core', 'kernel',
        // Laravel UI tools
        'horizon', 'telescope', 'pulse', 'pulse-server',
        // Auth surfaces
        'auth', 'sso', 'oauth', 'login', 'logout', 'register', 'signup',
        // Common service names
        'blog', 'news', 'shop', 'store', 'billing', 'payments',
        // NOTE: 'demo' is intentionally NOT reserved — the flagship demo tenant
        // lives at demo.<platform-domain> (Boss decision, 2026-07-05).
        'test', 'staging', 'preview', 'dev', 'sandbox',
        // Reserved single-letter / minimal
        'a', 'b', 'c', 'x', 'y', 'z',
    ],

    /*
    |--------------------------------------------------------------------------
    | Subdomain Rules (Axis A A7 — single source of truth)
    |--------------------------------------------------------------------------
    |
    | Length bounds shared by every subdomain validator (the registration
    | availability probe AND the actual register call) so they can never
    | disagree on what is allowed. Reserved names live in reserved_subdomains
    | above — there is no second hardcoded list.
    |
    */
    'subdomain' => [
        'min_length' => 3,
        'max_length' => 63,
    ],

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

        // Env-driven so constrained hosts (cPanel) can force their required
        // account prefix on tenant databases (e.g. TENANCY_DB_PREFIX=aeos365_t).
        'prefix' => env('TENANCY_DB_PREFIX', 'tenant'),
        'suffix' => env('TENANCY_DB_SUFFIX', ''),

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
    | Filesystem (Phase 0 T5 + Audit D5a)
    |--------------------------------------------------------------------------
    |
    | Required by Stancl FilesystemTenancyBootstrapper. The disks listed here
    | get a tenant-suffixed root at runtime; uploads to disk('local'),
    | disk('public'), and disk('s3') become tenant-isolated paths.
    |
    | For local/public: root_override sets the disk root to a per-tenant
    | subdirectory under storage/app/.
    |
    | For S3: the bootstrapper prepends `tenant-{id}/` to every key. This is
    | the PREFIX strategy — single bucket, per-tenant key namespace. An
    | alternative (per-tenant BUCKET) is documented at `s3_strategy` below
    | and requires custom provisioning per tenant create.
    |
    | Operator must ensure config/filesystems.php defines 'local', 'public',
    | and 's3' disks. The s3 disk must have valid AWS credentials, region,
    | and bucket configured via env (AWS_*).
    |
    */

    'filesystem' => [
        'suffix_base' => 'tenant',
        // false: keep asset()/@vite pointing at the shared central /build assets.
        // With the default (true), FilesystemTenancyBootstrapper rewrites asset()
        // to the per-tenant /tenancy/assets route, 404-ing the shared Vite build on
        // tenant subdomains. Tenant-uploaded files use Storage::url()/tenant_asset().
        'asset_helper_tenancy' => false,
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
    | S3 Tenancy Strategy (Audit D5a)
    |--------------------------------------------------------------------------
    |
    | How tenant data is isolated on S3:
    |   - 'prefix' (default): single bucket, every key is namespaced as
    |     tenant-{id}/path/to/file. Cheap, no per-tenant provisioning, but
    |     cross-tenant data leak risk if a bug bypasses the prefix.
    |   - 'bucket': per-tenant bucket (tenant-{id}-aeos365). More isolation,
    |     requires bucket creation on tenant provisioning + IAM scoping.
    |     Use this for tenants with strict data-residency requirements.
    |
    */
    's3_strategy' => env('TENANCY_S3_STRATEGY', 'prefix'),

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
        // Driver-agnostic per-tenant cache key prefix (Axis A A5). Works on any
        // cache driver — chosen over Stancl's CacheTenancyBootstrapper which requires
        // a tagging store (Redis/Memcached). Keep this in sync with the runtime list
        // set in AeroPlatformServiceProvider::boot(); TenancyRuntimeConfigTest pins it.
        \Aero\Platform\Bootstrappers\CachePrefixTenancyBootstrapper::class,
        \Stancl\Tenancy\Bootstrappers\FilesystemTenancyBootstrapper::class, // Per-tenant storage roots (Phase 0 T5) — requires 'filesystem' config block above
        \Aero\Platform\Bootstrappers\FailClosedQueueTenancyBootstrapper::class, // Audit D5c — refuses jobs for suspended/deleted tenants instead of running them against a missing DB
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
            // aero-assistant provides the Aeon (AI assistant) tables
            'vendor/aero/assistant/database/migrations',
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
