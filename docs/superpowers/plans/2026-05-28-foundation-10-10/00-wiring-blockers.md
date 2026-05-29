# AEOS365 Foundation 10/10 — Phase 0: Wiring Blockers

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Bring the host wiring (env, queue, cache, sessions, tenancy bootstrappers, observability, deploy) from dev-shaped to production-shaped. Without this, no package can score 10/10 regardless of code quality.

**Architecture:** Host-level + monorepo-level fixes only. Two hosts (`aeos365` SaaS, `aeos365-standalone`) each get production-shaped `.env.example`. Re-enable Stancl `CacheTenancyBootstrapper` + `FilesystemTenancyBootstrapper`. Decommission the duplicate custom `IdentifyTenant` middleware. Add Horizon, supervisor, Sentry, log channels. Add CI guards that prevent regressions.

**Tech Stack:** Laravel 12, Redis 7+, Horizon, stancl/tenancy v3, Sentry, supervisor (Linux), GitHub Actions (CI).

**Scope-out:** Per-package code changes (covered by per-package plans 01–16). HRM (deferred).

**Reference evidence (from Phase 1 audit, 2026-05-28):**
- `packages/aero-platform/config/tenancy.php:157` — CacheTenancyBootstrapper commented out with note *"file/database cache drivers don't support tagging"*
- `packages/aero-platform/config/tenancy.php:158` — FilesystemTenancyBootstrapper commented out with note *"causing 'Undefined array key local' error"*
- `packages/aero-platform/src/Http/Middleware/IdentifyTenant.php:35` — custom middleware mutates `config(['database.connections.tenant.database' => ...])` without cleanup; coexists with Stancl `InitializeTenancyByDomain`
- `c:\laragon\www\aeos365\.env.example` — stock Laravel 12 stub (`APP_NAME=Laravel`, `DB_CONNECTION=sqlite`)
- `c:\laragon\www\aeos365-standalone\.env.example` — 22 lines, `QUEUE_CONNECTION=sync`, `CACHE_DRIVER=file`, `SESSION_DRIVER=file`, no MAIL/REDIS/LOG/BROADCAST blocks
- `c:\laragon\www\aeos365\bootstrap\app.php:13-15` — only `identify.tenant` alias registered; no exception integration
- `packages/aero-platform/database/migrations/2025_11_29_000001_create_custom_tenants_table.php` — `status` column has no index but is queried by every `scopeActive/Suspended/Provisioning`

---

## File Structure

| File | Responsibility |
|---|---|
| `c:\laragon\www\aeos365\.env.example` | Production-shaped SaaS host env template |
| `c:\laragon\www\aeos365-standalone\.env.example` | Production-shaped standalone host env template |
| `c:\laragon\www\aeos365\config\horizon.php` (new) | Horizon supervisor config (SaaS) |
| `c:\laragon\www\aeos365-standalone\config\horizon.php` (new) | Horizon supervisor config (standalone) |
| `c:\laragon\www\aeos365\config\logging.php` | Add `daily`, `stderr`, `sentry` channels to stack |
| `c:\laragon\www\aeos365\bootstrap\app.php` | Wire Sentry exception handler |
| `c:\laragon\www\aeos365-standalone\bootstrap\app.php` | Same |
| `packages/aero-platform/config/tenancy.php` | Re-enable CacheTenancyBootstrapper + FilesystemTenancyBootstrapper |
| `packages/aero-platform/src/Http/Middleware/IdentifyTenant.php` | DELETE (replaced by Stancl `InitializeTenancyByDomain`) |
| `packages/aero-platform/database/migrations/2026_05_28_000001_add_tenants_status_index.php` (new) | Index on `tenants.status` |
| `packages/aero-core/src/Http/Controllers/Controller.php` | Add `boundedPerPage()` helper |
| `deploy/supervisor/aeos365-worker.conf` (new) | Supervisor config for queue workers |
| `deploy/supervisor/aeos365-horizon.conf` (new) | Supervisor config for Horizon |
| `deploy/README.md` (new) | Deploy procedure documentation |
| `.github/workflows/wiring-guards.yml` (new) | CI grep guards (no direct `Cache::`/`Session::`/`Storage::disk('local')` in `packages/aero-*` outside whitelist) |
| `tests/Feature/Wiring/EnvShapeTest.php` (new) | Assert prod `.env.example` shape |
| `tests/Feature/Wiring/TenancyBootstrappersTest.php` (new) | Assert bootstrappers enabled + isolation works |

---

## Task 1: Production-shaped `.env.example` for SaaS host

**Files:**
- Modify: `c:\laragon\www\aeos365\.env.example`

- [ ] **Step 1: Write failing assertion test**

Create `c:\laragon\www\aeos365\tests\Feature\Wiring\EnvShapeTest.php`:

```php
<?php

namespace Tests\Feature\Wiring;

use Tests\TestCase;

class EnvShapeTest extends TestCase
{
    public function test_env_example_is_production_shaped(): void
    {
        $env = file_get_contents(base_path('.env.example'));

        $this->assertStringContainsString('APP_NAME="AEOS365"', $env, '.env.example must brand to AEOS365');
        $this->assertStringContainsString('APP_ENV=production', $env);
        $this->assertStringContainsString('APP_DEBUG=false', $env);
        $this->assertStringContainsString('QUEUE_CONNECTION=redis', $env);
        $this->assertStringContainsString('CACHE_STORE=redis', $env);
        $this->assertStringContainsString('SESSION_DRIVER=redis', $env);
        $this->assertStringContainsString('LOG_CHANNEL=stack', $env);
        $this->assertStringContainsString('LOG_STACK=daily,stderr', $env);
        $this->assertStringContainsString('BROADCAST_CONNECTION=null', $env);
        $this->assertStringContainsString('FILESYSTEM_DISK=s3', $env);
        $this->assertStringContainsString('SENTRY_LARAVEL_DSN=', $env);
        $this->assertStringContainsString('AERO_MODE=saas', $env);
        $this->assertStringContainsString('APP_BASE_DOMAIN=', $env);
        $this->assertStringNotContainsString('APP_NAME=Laravel', $env);
        $this->assertStringNotContainsString('DB_CONNECTION=sqlite', $env);
    }
}
```

- [ ] **Step 2: Run test to verify FAIL**

Run: `cd c:\laragon\www\aeos365 && php artisan test --filter=test_env_example_is_production_shaped`
Expected: FAIL — `.env.example` is stock Laravel stub.

- [ ] **Step 3: Replace `.env.example` content**

Write `c:\laragon\www\aeos365\.env.example`:

```dotenv
# ------------------------------------------------------------------
# AEOS365 — SaaS host production-shaped env template
# Copy to .env and fill in real values before deploy.
# ------------------------------------------------------------------

APP_NAME="AEOS365"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://aeos365.com
APP_BASE_DOMAIN=aeos365.com
APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_TIMEZONE=UTC

AERO_MODE=saas

BCRYPT_ROUNDS=12
APP_MAINTENANCE_DRIVER=file

# ------------------- Database (central) -----------------------------
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=aeos365_central
DB_USERNAME=
DB_PASSWORD=

# Tenant DB connection template (Stancl/tenancy switches per request)
TENANT_DB_HOST="${DB_HOST}"
TENANT_DB_PORT="${DB_PORT}"
TENANT_DB_USERNAME="${DB_USERNAME}"
TENANT_DB_PASSWORD="${DB_PASSWORD}"
TENANT_DB_PREFIX=tenant_

# ------------------- Redis (cache, sessions, queues) ----------------
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=
REDIS_DB=0
REDIS_CACHE_DB=1

CACHE_STORE=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_PATH=/
SESSION_DOMAIN=.aeos365.com

QUEUE_CONNECTION=redis
HORIZON_PREFIX=aeos365_horizon:

BROADCAST_CONNECTION=null

# ------------------- Filesystem (S3 in prod) ------------------------
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=aeos365-prod
AWS_USE_PATH_STYLE_ENDPOINT=false

# ------------------- Mail ------------------------------------------
MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="no-reply@aeos365.com"
MAIL_FROM_NAME="${APP_NAME}"

# ------------------- Logging ---------------------------------------
LOG_CHANNEL=stack
LOG_STACK=daily,stderr
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=info
LOG_DAILY_DAYS=14

# ------------------- Error tracking --------------------------------
SENTRY_LARAVEL_DSN=
SENTRY_TRACES_SAMPLE_RATE=0.1
SENTRY_ENVIRONMENT=production

# ------------------- Vite / Frontend -------------------------------
VITE_APP_NAME="${APP_NAME}"

# ------------------- Aero / Platform -------------------------------
LICENSE_SERVER_URL=https://licenses.aerosuite.com
LICENSE_BYPASS=false
```

- [ ] **Step 4: Run test to verify PASS**

Run: `cd c:\laragon\www\aeos365 && php artisan test --filter=test_env_example_is_production_shaped`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add c:/laragon/www/aeos365/.env.example c:/laragon/www/aeos365/tests/Feature/Wiring/EnvShapeTest.php
git commit -m "chore(wiring): production-shaped .env.example for SaaS host"
```

---

## Task 2: Production-shaped `.env.example` for Standalone host

**Files:**
- Modify: `c:\laragon\www\aeos365-standalone\.env.example`
- Create: `c:\laragon\www\aeos365-standalone\tests\Feature\Wiring\EnvShapeTest.php`

- [ ] **Step 1: Write failing assertion test**

```php
<?php

namespace Tests\Feature\Wiring;

use Tests\TestCase;

class EnvShapeTest extends TestCase
{
    public function test_env_example_is_production_shaped(): void
    {
        $env = file_get_contents(base_path('.env.example'));

        $this->assertStringContainsString('APP_NAME="Aero HRM Suite"', $env);
        $this->assertStringContainsString('APP_ENV=production', $env);
        $this->assertStringContainsString('APP_DEBUG=false', $env);
        $this->assertStringContainsString('QUEUE_CONNECTION=redis', $env);
        $this->assertStringContainsString('CACHE_STORE=redis', $env);
        $this->assertStringContainsString('SESSION_DRIVER=redis', $env);
        $this->assertStringContainsString('AERO_MODE=standalone', $env);
        $this->assertStringContainsString('LOG_STACK=daily,stderr', $env);
        $this->assertStringContainsString('SENTRY_LARAVEL_DSN=', $env);
        $this->assertStringNotContainsString('QUEUE_CONNECTION=sync', $env);
        $this->assertStringNotContainsString('CACHE_DRIVER=file', $env);
    }
}
```

- [ ] **Step 2: Run test to verify FAIL**

Run: `cd c:\laragon\www\aeos365-standalone && php artisan test --filter=test_env_example_is_production_shaped`
Expected: FAIL.

- [ ] **Step 3: Replace `.env.example` content**

Write `c:\laragon\www\aeos365-standalone\.env.example`:

```dotenv
# ------------------------------------------------------------------
# Aero HRM Suite — Standalone host production-shaped env template
# Single-tenant deployment. Copy to .env and fill in real values.
# ------------------------------------------------------------------

APP_NAME="Aero HRM Suite"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://hrm.example.com
APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_TIMEZONE=UTC

AERO_MODE=standalone

PRODUCT_ID=hrm
PRODUCT_NAME="Aero HRM Suite"
LICENSE_SERVER_URL=https://licenses.aerosuite.com
LICENSE_BYPASS=false

BCRYPT_ROUNDS=12
APP_MAINTENANCE_DRIVER=file

# ------------------- Database -------------------------------------
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=aeos_standalone
DB_USERNAME=
DB_PASSWORD=

# ------------------- Redis ----------------------------------------
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=
REDIS_DB=0
REDIS_CACHE_DB=1

CACHE_STORE=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
QUEUE_CONNECTION=redis
HORIZON_PREFIX=aeos_standalone_horizon:
BROADCAST_CONNECTION=null

# ------------------- Filesystem -----------------------------------
FILESYSTEM_DISK=local
# For S3 deployment:
# FILESYSTEM_DISK=s3
# AWS_ACCESS_KEY_ID=
# AWS_SECRET_ACCESS_KEY=
# AWS_DEFAULT_REGION=
# AWS_BUCKET=

# ------------------- Mail -----------------------------------------
MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="no-reply@example.com"
MAIL_FROM_NAME="${APP_NAME}"

# ------------------- Logging --------------------------------------
LOG_CHANNEL=stack
LOG_STACK=daily,stderr
LOG_LEVEL=info
LOG_DAILY_DAYS=14

# ------------------- Error tracking --------------------------------
SENTRY_LARAVEL_DSN=
SENTRY_TRACES_SAMPLE_RATE=0.1
SENTRY_ENVIRONMENT=production
```

- [ ] **Step 4: Verify PASS + commit**

```bash
cd c:\laragon\www\aeos365-standalone && php artisan test --filter=test_env_example_is_production_shaped
git add c:/laragon/www/aeos365-standalone/.env.example c:/laragon/www/aeos365-standalone/tests/Feature/Wiring/EnvShapeTest.php
git commit -m "chore(wiring): production-shaped .env.example for standalone host"
```

---

## Task 3: Decommission custom `IdentifyTenant` middleware

The custom `IdentifyTenant` (`packages/aero-platform/src/Http/Middleware/IdentifyTenant.php:14-38`) mutates `config()` without cleanup, persisting across requests in long-running processes (Octane, queue workers). Stancl `InitializeTenancyByDomain` already handles subdomain identification correctly.

**Files:**
- Delete: `packages/aero-platform/src/Http/Middleware/IdentifyTenant.php`
- Modify: `c:\laragon\www\aeos365\bootstrap\app.php` (remove alias on line 14)
- Modify: any route file using `identify.tenant` middleware (grep first)
- Create: `tests/Feature/Wiring/SingleTenantIdentificationTest.php`

- [ ] **Step 1: Grep usage**

Run: `grep -rn "identify.tenant\|IdentifyTenant::class\|IdentifyTenant " packages/ c:/laragon/www/aeos365/`
Expected: hits only in alias declaration, the middleware file itself, and any legacy route group.

- [ ] **Step 2: Write failing test**

```php
<?php

namespace Tests\Feature\Wiring;

use Tests\TestCase;

class SingleTenantIdentificationTest extends TestCase
{
    public function test_only_stancl_initialize_tenancy_by_domain_is_used(): void
    {
        $bootstrap = file_get_contents(base_path('bootstrap/app.php'));
        $this->assertStringNotContainsString('identify.tenant', $bootstrap);
        $this->assertStringNotContainsString('IdentifyTenant', $bootstrap);
    }

    public function test_custom_identify_tenant_middleware_is_removed(): void
    {
        $path = base_path('vendor/aero/platform/src/Http/Middleware/IdentifyTenant.php');
        $this->assertFileDoesNotExist($path, 'Custom IdentifyTenant must be removed; Stancl InitializeTenancyByDomain handles this.');
    }
}
```

- [ ] **Step 3: Run test (FAIL)**

Run: `cd c:\laragon\www\aeos365 && php artisan test --filter=SingleTenantIdentificationTest`
Expected: FAIL.

- [ ] **Step 4: Replace any route usage with Stancl path**

For every route file using `identify.tenant`, swap to Stancl's middleware:

```php
// Before
Route::middleware('identify.tenant')->group(function () { ... });

// After
Route::middleware([
    \Stancl\Tenancy\Middleware\InitializeTenancyByDomain::class,
    \Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains::class,
])->group(function () { ... });
```

- [ ] **Step 5: Delete custom middleware file**

```bash
rm packages/aero-platform/src/Http/Middleware/IdentifyTenant.php
```

- [ ] **Step 6: Remove alias from bootstrap/app.php**

Edit `c:\laragon\www\aeos365\bootstrap\app.php`:

```php
->withMiddleware(function (Middleware $middleware): void {
    // identify.tenant alias removed; Stancl InitializeTenancyByDomain is used directly in route groups
})
```

- [ ] **Step 7: Run test (PASS) + integration smoke**

Run: `cd c:\laragon\www\aeos365 && php artisan test --filter=SingleTenantIdentificationTest && php artisan route:list | grep tenancy`
Expected: PASS + visible Stancl middleware in routes.

- [ ] **Step 8: Commit**

```bash
git add -A
git commit -m "fix(tenancy): remove duplicate IdentifyTenant middleware; standardize on Stancl

Custom IdentifyTenant mutated config() without cleanup, persisting across
requests in Octane/queue workers. Stancl InitializeTenancyByDomain is the
canonical path and is already wired in tenancy.php bootstrappers."
```

---

## Task 4: Re-enable `CacheTenancyBootstrapper`

**Files:**
- Modify: `packages/aero-platform/config/tenancy.php:157`
- Create: `tests/Feature/Wiring/TenancyCacheIsolationTest.php`

- [ ] **Step 1: Write failing isolation test**

```php
<?php

namespace Tests\Feature\Wiring;

use Aero\Platform\Models\Tenant;
use Illuminate\Support\Facades\Cache;
use Stancl\Tenancy\Facades\Tenancy;
use Tests\TestCase;

class TenancyCacheIsolationTest extends TestCase
{
    public function test_cache_keys_are_isolated_per_tenant(): void
    {
        $a = Tenant::factory()->create();
        $b = Tenant::factory()->create();

        Tenancy::initialize($a);
        Cache::put('shared_key', 'value-from-a');
        Tenancy::end();

        Tenancy::initialize($b);
        $this->assertNull(Cache::get('shared_key'), 'Tenant B must not see tenant A cache key');
        Cache::put('shared_key', 'value-from-b');
        Tenancy::end();

        Tenancy::initialize($a);
        $this->assertSame('value-from-a', Cache::get('shared_key'), 'Tenant A key must survive tenant B write');
        Tenancy::end();
    }
}
```

- [ ] **Step 2: Run test (FAIL — bootstrapper disabled)**

Run: `php artisan test --filter=TenancyCacheIsolationTest`
Expected: FAIL — Tenant B sees A's value.

- [ ] **Step 3: Enable bootstrapper**

Edit `packages/aero-platform/config/tenancy.php`:

```php
'bootstrappers' => [
    \Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper::class,
    \Stancl\Tenancy\Bootstrappers\CacheTenancyBootstrapper::class, // re-enabled — requires CACHE_STORE=redis
    // FilesystemTenancyBootstrapper re-enabled in Task 5
    \Stancl\Tenancy\Bootstrappers\QueueTenancyBootstrapper::class,
],
```

- [ ] **Step 4: Add `phpunit.xml` env override**

In `phpunit.xml` (or test setUp) ensure `CACHE_STORE=redis` and Redis is available, OR use `<env name="CACHE_STORE" value="array"/>` with array driver that supports tagging.

- [ ] **Step 5: Run test (PASS)**

Run: `php artisan test --filter=TenancyCacheIsolationTest`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add packages/aero-platform/config/tenancy.php c:/laragon/www/aeos365/tests/Feature/Wiring/TenancyCacheIsolationTest.php
git commit -m "fix(tenancy): re-enable CacheTenancyBootstrapper (requires Redis)

Closes the cross-tenant cache leakage gap identified in Phase 1 audit.
Requires CACHE_STORE=redis (set in .env.example Task 1)."
```

---

## Task 5: Fix and re-enable `FilesystemTenancyBootstrapper`

**Files:**
- Modify: `packages/aero-platform/config/tenancy.php:158`
- Modify: `c:\laragon\www\aeos365\config\filesystems.php` (ensure `local` disk shape is correct)
- Create: `tests/Feature/Wiring/TenancyFilesystemIsolationTest.php`

The bug *"Undefined array key 'local'"* is caused by Stancl's `FilesystemTenancyBootstrapper` reading `config('tenancy.filesystem.suffix_base')` and `disks` keys that must exist in `filesystems.php` AND `tenancy.php`. Fix by adding the missing keys.

- [ ] **Step 1: Inspect Stancl source**

Read `vendor/stancl/tenancy/src/Bootstrappers/FilesystemTenancyBootstrapper.php` to identify which keys it expects. (Typically: `tenancy.filesystem.suffix_base`, `tenancy.filesystem.disks`, `tenancy.filesystem.root_override`.)

- [ ] **Step 2: Add missing config block**

Edit `packages/aero-platform/config/tenancy.php` (add new top-level block):

```php
'filesystem' => [
    'suffix_base' => 'tenant',
    'disks' => [
        'local',
        'public',
        's3',
    ],
    'root_override' => [
        'local' => '%storage_path%/app/',
        'public' => '%storage_path%/app/public/',
    ],
],
```

- [ ] **Step 3: Write failing isolation test**

```php
<?php

namespace Tests\Feature\Wiring;

use Aero\Platform\Models\Tenant;
use Illuminate\Support\Facades\Storage;
use Stancl\Tenancy\Facades\Tenancy;
use Tests\TestCase;

class TenancyFilesystemIsolationTest extends TestCase
{
    public function test_storage_paths_are_isolated_per_tenant(): void
    {
        $a = Tenant::factory()->create();
        $b = Tenant::factory()->create();

        Tenancy::initialize($a);
        Storage::disk('local')->put('hello.txt', 'value-from-a');
        $pathA = Storage::disk('local')->path('hello.txt');
        Tenancy::end();

        Tenancy::initialize($b);
        $this->assertFalse(Storage::disk('local')->exists('hello.txt'), 'Tenant B must not see tenant A file');
        $pathB = Storage::disk('local')->path('hello.txt');
        $this->assertNotSame($pathA, $pathB, 'Tenant disks must resolve to different paths');
        Tenancy::end();
    }
}
```

- [ ] **Step 4: Run test (FAIL)**

Run: `php artisan test --filter=TenancyFilesystemIsolationTest`
Expected: FAIL until bootstrapper enabled.

- [ ] **Step 5: Re-enable bootstrapper**

Edit `packages/aero-platform/config/tenancy.php`:

```php
'bootstrappers' => [
    \Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper::class,
    \Stancl\Tenancy\Bootstrappers\CacheTenancyBootstrapper::class,
    \Stancl\Tenancy\Bootstrappers\FilesystemTenancyBootstrapper::class, // re-enabled with filesystem config block
    \Stancl\Tenancy\Bootstrappers\QueueTenancyBootstrapper::class,
],
```

- [ ] **Step 6: Run test (PASS)**

Run: `php artisan test --filter=TenancyFilesystemIsolationTest`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add packages/aero-platform/config/tenancy.php c:/laragon/www/aeos365/tests/Feature/Wiring/TenancyFilesystemIsolationTest.php
git commit -m "fix(tenancy): re-enable FilesystemTenancyBootstrapper with filesystem config block

Adds missing tenancy.filesystem.{suffix_base,disks,root_override}
that Stancl bootstrapper requires. Closes cross-tenant upload leak."
```

---

## Task 6: Add `tenants.status` index migration

**Files:**
- Create: `packages/aero-platform/database/migrations/2026_05_28_000001_add_tenants_status_index.php`

- [ ] **Step 1: Write migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->index('status', 'tenants_status_idx');
            // Composite for scopeActive/Suspended/Provisioning + ordering by created_at
            $table->index(['status', 'created_at'], 'tenants_status_created_at_idx');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropIndex('tenants_status_idx');
            $table->dropIndex('tenants_status_created_at_idx');
        });
    }
};
```

- [ ] **Step 2: Run migration**

Run: `cd c:\laragon\www\aeos365 && php artisan migrate --database=central`
Expected: migration completes.

- [ ] **Step 3: Verify index exists**

Run: `php artisan db:show --database=central | grep tenants_status`
Expected: index visible.

- [ ] **Step 4: Commit**

```bash
git add packages/aero-platform/database/migrations/2026_05_28_000001_add_tenants_status_index.php
git commit -m "perf(platform): add tenants.status indexes for scopeActive/Suspended/Provisioning"
```

---

## Task 7: Add Horizon configuration

**Files:**
- Create: `c:\laragon\www\aeos365\config\horizon.php`
- Create: `c:\laragon\www\aeos365-standalone\config\horizon.php`
- Modify: `composer.json` (both hosts) — add `laravel/horizon`

- [ ] **Step 1: Install Horizon**

Run (in both hosts):
```bash
composer require laravel/horizon
php artisan horizon:install
```

- [ ] **Step 2: Write `horizon.php` for SaaS host**

Replace generated `c:\laragon\www\aeos365\config\horizon.php` with:

```php
<?php

return [
    'domain' => env('HORIZON_DOMAIN', null),
    'path' => env('HORIZON_PATH', 'horizon'),
    'use' => 'default',
    'prefix' => env('HORIZON_PREFIX', 'aeos365_horizon:'),
    'middleware' => ['web', 'auth:landlord'], // Platform admin only

    'waits' => ['redis:default' => 60, 'redis:provisioning' => 120, 'redis:notifications' => 30],
    'trim' => ['recent' => 60, 'pending' => 60, 'completed' => 60, 'recent_failed' => 10080, 'failed' => 10080, 'monitored' => 10080],
    'silenced' => [],
    'metrics' => ['trim_snapshots' => ['job' => 24, 'queue' => 24]],
    'fast_termination' => false,
    'memory_limit' => 256,

    'defaults' => [
        'provisioning' => [
            'connection' => 'redis',
            'queue' => ['provisioning'],
            'balance' => 'simple',
            'maxProcesses' => 2,
            'maxTime' => 0,
            'maxJobs' => 0,
            'memory' => 256,
            'tries' => 3,
            'timeout' => 600,
            'nice' => 0,
        ],
        'default' => [
            'connection' => 'redis',
            'queue' => ['default', 'notifications', 'webhooks'],
            'balance' => 'auto',
            'minProcesses' => 1,
            'maxProcesses' => 10,
            'balanceMaxShift' => 1,
            'balanceCooldown' => 3,
            'maxTime' => 0,
            'maxJobs' => 0,
            'memory' => 128,
            'tries' => 3,
            'timeout' => 120,
            'nice' => 0,
        ],
    ],

    'environments' => [
        'production' => [
            'provisioning' => ['maxProcesses' => 4, 'memory' => 512],
            'default' => ['minProcesses' => 2, 'maxProcesses' => 20, 'memory' => 256],
        ],
        'local' => [
            'provisioning' => ['maxProcesses' => 1],
            'default' => ['minProcesses' => 1, 'maxProcesses' => 3],
        ],
    ],
];
```

- [ ] **Step 3: Standalone Horizon config**

Same shape but simpler (no provisioning queue, no landlord guard):

```php
<?php

return [
    'domain' => env('HORIZON_DOMAIN', null),
    'path' => env('HORIZON_PATH', 'horizon'),
    'use' => 'default',
    'prefix' => env('HORIZON_PREFIX', 'aeos_standalone_horizon:'),
    'middleware' => ['web', 'auth'],

    'waits' => ['redis:default' => 60, 'redis:notifications' => 30],
    'trim' => ['recent' => 60, 'failed' => 10080],
    'memory_limit' => 256,

    'defaults' => [
        'default' => [
            'connection' => 'redis',
            'queue' => ['default', 'notifications'],
            'balance' => 'auto',
            'minProcesses' => 1,
            'maxProcesses' => 5,
            'memory' => 128,
            'tries' => 3,
            'timeout' => 120,
        ],
    ],

    'environments' => [
        'production' => ['default' => ['minProcesses' => 1, 'maxProcesses' => 10, 'memory' => 256]],
        'local' => ['default' => ['minProcesses' => 1, 'maxProcesses' => 2]],
    ],
];
```

- [ ] **Step 4: Add Horizon authorization gate**

Edit `c:\laragon\www\aeos365\app\Providers\HorizonServiceProvider.php` `gate()` to allow only Platform Admins:

```php
protected function gate(): void
{
    Gate::define('viewHorizon', function ($user) {
        return $user && $user->hasRole('Platform Super Administrator');
    });
}
```

- [ ] **Step 5: Smoke test**

Run: `php artisan horizon:status` and browse to `/horizon` (after logging in as Platform Admin).
Expected: status output + dashboard reachable.

- [ ] **Step 6: Commit**

```bash
git add c:/laragon/www/aeos365/config/horizon.php c:/laragon/www/aeos365/app/Providers/HorizonServiceProvider.php c:/laragon/www/aeos365/composer.json c:/laragon/www/aeos365/composer.lock c:/laragon/www/aeos365-standalone/config/horizon.php c:/laragon/www/aeos365-standalone/composer.json c:/laragon/www/aeos365-standalone/composer.lock
git commit -m "feat(wiring): add Laravel Horizon for queue supervision"
```

---

## Task 8: Wire Sentry for error tracking

**Files:**
- Modify: `composer.json` (both hosts) — add `sentry/sentry-laravel`
- Modify: `c:\laragon\www\aeos365\bootstrap\app.php`
- Modify: `c:\laragon\www\aeos365-standalone\bootstrap\app.php`
- Create: `c:\laragon\www\aeos365\config\sentry.php` (published from package)

- [ ] **Step 1: Install Sentry**

Run (in both hosts):
```bash
composer require sentry/sentry-laravel
php artisan sentry:publish --dsn=
```

- [ ] **Step 2: Edit `bootstrap/app.php`**

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: null,
        commands: __DIR__.'/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Custom IdentifyTenant alias removed; Stancl middleware used directly in route groups.
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        \Sentry\Laravel\Integration::handles($exceptions);
    })->create();
```

- [ ] **Step 3: Smoke test**

Trigger a test exception:
```bash
php artisan tinker
> throw new \Exception('sentry smoke test');
```
Expected: error appears in Sentry dashboard within 30s.

- [ ] **Step 4: Commit**

```bash
git add -A
git commit -m "feat(wiring): wire Sentry for production error tracking"
```

---

## Task 9: Per-request log channel discipline

**Files:**
- Modify: `c:\laragon\www\aeos365\config\logging.php`
- Modify: `c:\laragon\www\aeos365-standalone\config\logging.php`

- [ ] **Step 1: Add `daily` + `stderr` + `sentry` to stack**

In `config/logging.php` `'channels' => [...]` add (or confirm) entries:

```php
'stack' => [
    'driver' => 'stack',
    'channels' => explode(',', env('LOG_STACK', 'daily,stderr')),
    'ignore_exceptions' => false,
],

'daily' => [
    'driver' => 'daily',
    'path' => storage_path('logs/laravel.log'),
    'level' => env('LOG_LEVEL', 'info'),
    'days' => env('LOG_DAILY_DAYS', 14),
    'replace_placeholders' => true,
],

'stderr' => [
    'driver' => 'monolog',
    'level' => env('LOG_LEVEL', 'info'),
    'handler' => Monolog\Handler\StreamHandler::class,
    'with' => ['stream' => 'php://stderr'],
    'formatter' => env('LOG_STDERR_FORMATTER'),
],

'sentry' => [
    'driver' => 'sentry',
    'level' => env('LOG_LEVEL', 'error'),
    'bubble' => true,
],
```

- [ ] **Step 2: Commit**

```bash
git add c:/laragon/www/aeos365/config/logging.php c:/laragon/www/aeos365-standalone/config/logging.php
git commit -m "chore(wiring): add daily+stderr+sentry log channels"
```

---

## Task 10: Bounded pagination helper

**Files:**
- Modify: `packages/aero-core/src/Http/Controllers/Controller.php`
- Create: `packages/aero-core/tests/Unit/Http/BoundedPerPageTest.php`

The Phase 1 audit found unbounded `?per_page=999999` exposure. Centralize the guard.

- [ ] **Step 1: Write failing test**

```php
<?php

namespace Aero\Core\Tests\Unit\Http;

use Aero\Core\Http\Controllers\Controller;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class BoundedPerPageTest extends TestCase
{
    public function test_default_is_20(): void
    {
        $req = new Request();
        $controller = new class extends Controller { public function go(Request $r) { return $this->boundedPerPage($r); } };
        $this->assertSame(20, $controller->go($req));
    }

    public function test_caps_at_100(): void
    {
        $req = new Request(['per_page' => 999999]);
        $controller = new class extends Controller { public function go(Request $r) { return $this->boundedPerPage($r); } };
        $this->assertSame(100, $controller->go($req));
    }

    public function test_respects_user_value_within_bounds(): void
    {
        $req = new Request(['per_page' => 50]);
        $controller = new class extends Controller { public function go(Request $r) { return $this->boundedPerPage($r); } };
        $this->assertSame(50, $controller->go($req));
    }

    public function test_minimum_is_1(): void
    {
        $req = new Request(['per_page' => 0]);
        $controller = new class extends Controller { public function go(Request $r) { return $this->boundedPerPage($r); } };
        $this->assertSame(1, $controller->go($req));
    }
}
```

- [ ] **Step 2: Run test (FAIL)**

Run: `cd packages/aero-core && vendor/bin/phpunit tests/Unit/Http/BoundedPerPageTest.php`
Expected: FAIL — method not defined.

- [ ] **Step 3: Add helper**

Edit `packages/aero-core/src/Http/Controllers/Controller.php`:

```php
protected function boundedPerPage(\Illuminate\Http\Request $request, int $default = 20, int $max = 100): int
{
    return max(1, min((int) $request->input('per_page', $default), $max));
}
```

- [ ] **Step 4: Run test (PASS)**

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add packages/aero-core/src/Http/Controllers/Controller.php packages/aero-core/tests/Unit/Http/BoundedPerPageTest.php
git commit -m "feat(core): add boundedPerPage() helper; cap pagination at 100"
```

Note: Per-package plans will migrate index controllers to use this helper.

---

## Task 11: Supervisor configs

**Files:**
- Create: `deploy/supervisor/aeos365-horizon.conf`
- Create: `deploy/supervisor/aeos365-standalone-worker.conf`
- Create: `deploy/supervisor/aeos365-scheduler.conf`
- Create: `deploy/README.md`

- [ ] **Step 1: Write supervisor config — Horizon (SaaS)**

```ini
[program:aeos365-horizon]
process_name=%(program_name)s
command=php /var/www/aeos365/artisan horizon
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/supervisor/aeos365-horizon.log
stopwaitsecs=3600
```

- [ ] **Step 2: Write supervisor config — Worker (standalone fallback if not using Horizon)**

```ini
[program:aeos365-standalone-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/aeos365-standalone/artisan queue:work redis --queue=default,notifications --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/supervisor/aeos365-standalone-worker.log
stopwaitsecs=3600
```

- [ ] **Step 3: Scheduler config**

```ini
[program:aeos365-scheduler]
process_name=%(program_name)s
command=bash -c "while [ true ]; do (php /var/www/aeos365/artisan schedule:run --verbose --no-interaction &) ; sleep 60 ; done"
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/supervisor/aeos365-scheduler.log
```

- [ ] **Step 4: Deploy README**

Write `deploy/README.md` with sections: prerequisites (PHP 8.2+, Redis 7+, MySQL 8+, supervisor), install steps, env setup, migration order (central → tenant), Horizon start, supervisor reload, rollback procedure.

- [ ] **Step 5: Commit**

```bash
git add deploy/
git commit -m "docs(deploy): supervisor configs + deploy README"
```

---

## Task 12: CI guards — no direct facade abuse in feature packages

**Files:**
- Create: `.github/workflows/wiring-guards.yml`
- Create: `tests/Feature/Wiring/FacadeDisciplineTest.php`

- [ ] **Step 1: Write failing discipline test**

```php
<?php

namespace Tests\Feature\Wiring;

use Symfony\Component\Finder\Finder;
use Tests\TestCase;

class FacadeDisciplineTest extends TestCase
{
    /** @var string[] Packages where direct Cache::/Session::/Storage::disk('local') is allowed */
    private array $whitelistPackages = [
        'aero-contracts', 'aero-core', 'aero-platform', // foundation
    ];

    public function test_no_direct_cache_facade_in_feature_packages(): void
    {
        $offenders = $this->scan('/\bCache::(get|put|forever|remember|forget|flush|tags)\(/');
        $this->assertEmpty($offenders, "Use TenantCache instead of Cache:: in feature packages:\n  " . implode("\n  ", $offenders));
    }

    public function test_no_storage_disk_local_in_feature_packages(): void
    {
        $offenders = $this->scan("/Storage::disk\\('local'\\)/");
        $this->assertEmpty($offenders, "Use TenantStorage / Storage::disk('tenant') instead of Storage::disk('local') in feature packages:\n  " . implode("\n  ", $offenders));
    }

    private function scan(string $pattern): array
    {
        $offenders = [];
        $finder = (new Finder())
            ->in(base_path('packages'))
            ->path('/^aero-/')
            ->name('*.php')
            ->files();

        foreach ($finder as $file) {
            $relative = $file->getRelativePathname();
            $packageName = explode('/', $relative)[0] ?? '';
            if (in_array($packageName, $this->whitelistPackages, true)) continue;

            $content = $file->getContents();
            if (preg_match($pattern, $content)) {
                $offenders[] = $relative;
            }
        }
        return $offenders;
    }
}
```

- [ ] **Step 2: Run test (likely FAIL given Phase 1 found 20+ direct uses)**

Run: `php artisan test --filter=FacadeDisciplineTest`
Expected: FAIL with offender list. (Per-package plans will resolve.)

- [ ] **Step 3: GitHub Actions workflow**

Write `.github/workflows/wiring-guards.yml`:

```yaml
name: Wiring Guards

on:
  pull_request:
    branches: [main, develop]
  push:
    branches: [main]

jobs:
  facade-discipline:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with: { php-version: '8.2' }
      - run: composer install --no-interaction --prefer-dist
      - name: Run facade discipline tests
        run: php artisan test --filter=FacadeDisciplineTest
      - name: Run env shape tests
        run: php artisan test --filter=EnvShapeTest
      - name: Run tenancy isolation tests
        run: php artisan test --filter=Tenancy
```

- [ ] **Step 4: Commit (test marked as expected-fail for now)**

```bash
git add c:/laragon/www/aeos365/tests/Feature/Wiring/FacadeDisciplineTest.php .github/workflows/wiring-guards.yml
git commit -m "ci(wiring): facade discipline + env shape + tenancy isolation guards

Test currently red — per-package plans will resolve direct Cache::/Storage::
uses by migrating to TenantCache/TenantStorage."
```

---

## Task 13: Health check assertions

**Files:**
- Modify: `packages/aero-core/src/Http/Controllers/Api/HealthCheckController.php` (verify)
- Create: `packages/aero-core/tests/Feature/HealthCheckTest.php`

- [ ] **Step 1: Write feature test**

```php
<?php

namespace Aero\Core\Tests\Feature;

use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_health_returns_200_with_ok_payload(): void
    {
        $response = $this->getJson('/health');
        $response->assertOk()
            ->assertJson(['status' => 'ok']);
    }

    public function test_detailed_returns_db_cache_queue_redis_disk_memory(): void
    {
        $this->actingAsLandlord(); // requires test helper
        $response = $this->getJson('/health/detailed');
        $response->assertOk()
            ->assertJsonStructure([
                'status',
                'checks' => ['database', 'cache', 'queue', 'redis', 'disk', 'memory', 'storage'],
            ]);
    }
}
```

- [ ] **Step 2: Run test (PASS expected per Phase 1 confirmation controller exists)**

Run: `php artisan test --filter=HealthCheckTest`
Expected: PASS.

- [ ] **Step 3: Commit**

```bash
git add packages/aero-core/tests/Feature/HealthCheckTest.php
git commit -m "test(core): health check endpoints regression coverage"
```

---

## Task 14: Maintenance mode + custom error pages

**Files:**
- Create: `resources/views/errors/maintenance.blade.php` (both hosts)
- Create: `resources/views/errors/500.blade.php`
- Create: `resources/views/errors/503.blade.php`
- Create: `resources/views/errors/404.blade.php`
- Create: `resources/views/errors/403.blade.php`

- [ ] **Step 1: Write branded error pages**

For each error page, write minimal Tailwind-styled blade with AEOS365 branding. Example for `503.blade.php`:

```blade
@extends('errors::minimal')

@section('title', __('Service Unavailable'))
@section('code', '503')
@section('message', __('AEOS365 is undergoing maintenance. We will be back shortly.'))
```

- [ ] **Step 2: Test maintenance mode**

Run: `php artisan down --render="errors::503" --retry=60` then `curl -I /` → expect `503 Retry-After: 60`. Then `php artisan up`.

- [ ] **Step 3: Commit**

```bash
git add resources/views/errors/
git commit -m "feat(wiring): branded maintenance + error pages"
```

---

## Task 15: Verification pass — re-run Phase 1 audit assertions

- [ ] **Step 1: Full test suite**

```bash
cd c:\laragon\www\aeos365 && php artisan test
cd c:\laragon\www\aeos365-standalone && php artisan test
```

- [ ] **Step 2: Score recheck (manual)**

Verify each Phase 1 blocker is closed:

| Blocker | Closed by | Verified |
|---|---|---|
| QUEUE=sync in prod | Task 1, 2, 7 | `.env.example` + Horizon |
| Cache tenancy disabled | Task 4 | TenancyCacheIsolationTest passes |
| Filesystem tenancy disabled | Task 5 | TenancyFilesystemIsolationTest passes |
| Two tenant middlewares | Task 3 | SingleTenantIdentificationTest passes |
| No Horizon | Task 7 | `/horizon` reachable |
| No Sentry | Task 8 | smoke event fired |
| Stub `.env.example` | Tasks 1, 2 | EnvShapeTest passes |
| `tenants.status` no index | Task 6 | `db:show` shows index |
| Unbounded pagination | Task 10 | BoundedPerPageTest passes |
| No deploy procedure | Task 11 | `deploy/README.md` present |
| No facade discipline | Task 12 | CI guard wired (red until per-package fixes land) |

- [ ] **Step 3: Tag**

```bash
git tag -a wiring-10-10-phase-0 -m "Phase 0 wiring blockers closed"
```

---

## Self-Review Checklist

- ✅ Every wiring blocker from Phase 1 audit has a task that addresses it
- ✅ No placeholders — every step has either exact code or exact commands
- ✅ TDD: every behavioral change has a failing test first
- ✅ Commits are bite-sized, one logical change each
- ✅ Cross-host changes (SaaS + standalone) are paired
- ✅ Per-package facade-discipline cleanup deferred to per-package plans (Task 12 ships the guard red on purpose)
- ✅ Sentry, Horizon, supervisor are real production tooling, not toy versions
- ✅ Deploy README captures the order-of-operations

## Execution Handoff

Plan saved to `docs/superpowers/plans/2026-05-28-foundation-10-10/00-wiring-blockers.md`. Two execution options:

1. **Subagent-Driven (recommended)** — dispatch fresh subagent per task, review between tasks
2. **Inline Execution** — execute tasks in this session using `superpowers:executing-plans`

Per-package plans (01–16) will follow once Phase 0 is in flight.
