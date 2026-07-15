# Fleet Migration Runner Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a ledgered, resumable, queue-parallel migration runner that safely applies tenant migrations across the entire database-per-tenant fleet.

**Architecture:** A fleet run is a `Bus::batch()` of one queued `MigrateTenantJob` per pending tenant. Three central-DB tables form a ledger: `tenant_migration_runs` (manifest + counters), `tenant_migration_status` (durable per-tenant state = resumability source of truth), and `tenant_migration_attempts` (append-only forensics). A `FleetMigrationService` computes the pending set via a cheap schema fingerprint and orchestrates the batch; a thin `tenant:migrate` command and an HRMAC-gated platform status page are the two faces on top.

**Tech Stack:** Laravel 12, stancl/tenancy, Laravel queues + job batching, React 18 + Inertia v2 (@aero/ui) for the status page, PHPUnit 11.

## Global Constraints

- Package-first: ALL code in `packages/aero-platform`. Host apps stay dumb wrappers.
- Central tables/models use `protected $connection = 'central'` and extend `Aero\Platform\Models\CentralModel`.
- Central migrations live in `packages/aero-platform/database/migrations/` (loaded via `loadMigrationsFrom`), plain `Schema::create` per the `tenant_stats` pattern.
- Reuse, do not rewrite: the per-tenant migrate logic in `TenantMigrate::migrateTenant()` is extracted into a shared `TenantMigrator` service; both the command's single-tenant path and `MigrateTenantJob` call it.
- All ledger writes in `DB::transaction()` where multiple rows change together.
- Migrations applied by this runner MUST be additive / backward-compatible (async-deploy invariant) — enforce in review.
- Quarantine threshold is config-driven: `config('platform.fleet_migration.quarantine_after', 3)`.
- Queue name is config-driven: `config('platform.fleet_migration.queue', 'migrations')`.
- Commit authorship: Emam Hosen only. Never add a `Co-Authored-By: Claude` trailer.
- Tests follow the neighbouring platform pattern: `namespace Aero\Platform\Tests\...`, `use Tests\TestCase;`, `Illuminate\Foundation\Testing\DatabaseMigrations`.

## File Structure

**Create:**
- `packages/aero-platform/database/migrations/2026_07_15_000001_create_tenant_migration_runs_table.php`
- `packages/aero-platform/database/migrations/2026_07_15_000002_create_tenant_migration_status_table.php`
- `packages/aero-platform/database/migrations/2026_07_15_000003_create_tenant_migration_attempts_table.php`
- `packages/aero-platform/src/Models/TenantMigrationRun.php`
- `packages/aero-platform/src/Models/TenantMigrationStatus.php`
- `packages/aero-platform/src/Models/TenantMigrationAttempt.php`
- `packages/aero-platform/src/Services/SchemaFingerprint.php`
- `packages/aero-platform/src/Services/TenantMigrator.php`
- `packages/aero-platform/src/Services/FleetMigrationService.php`
- `packages/aero-platform/src/Jobs/MigrateTenantJob.php`
- `packages/aero-platform/src/Http/Controllers/Admin/FleetMigrationController.php`
- `packages/aero-ui/resources/js/Pages/Platform/FleetMigrations/Index.jsx`
- Tests mirroring each under `packages/aero-platform/tests/{Unit,Feature}/...`

**Modify:**
- `packages/aero-platform/src/Console/Commands/TenantMigrate.php` — refactor to a thin front-end.
- `packages/aero-platform/config/platform.php` — add `fleet_migration` block.
- `packages/aero-platform/routes/admin.php` — add fleet-migration routes.

---

### Task 1: Ledger migrations

**Files:**
- Create: `packages/aero-platform/database/migrations/2026_07_15_000001_create_tenant_migration_runs_table.php`
- Create: `packages/aero-platform/database/migrations/2026_07_15_000002_create_tenant_migration_status_table.php`
- Create: `packages/aero-platform/database/migrations/2026_07_15_000003_create_tenant_migration_attempts_table.php`

**Interfaces:**
- Produces: three central tables `tenant_migration_runs`, `tenant_migration_status`, `tenant_migration_attempts` with the columns consumed by Tasks 2–5.

- [ ] **Step 1: Write the runs migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** One row per fleet migration run (the manifest + denormalized counters). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_migration_runs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('batch_id')->nullable()->index();
            $table->string('trigger');                 // deploy | manual | scheduled
            $table->string('release')->nullable();     // git sha / version
            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('pending')->default(0);
            $table->unsignedInteger('migrated')->default(0);
            $table->unsignedInteger('failed')->default(0);
            $table->unsignedInteger('quarantined')->default(0);
            $table->string('status')->default('running'); // running | completed | completed_with_failures | cancelled
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_migration_runs');
    }
};
```

- [ ] **Step 2: Write the status migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Durable current migration state per tenant — the resumability source of truth. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_migration_status', function (Blueprint $table) {
            $table->string('tenant_id')->primary();
            $table->string('status')->default('pending'); // up_to_date|pending|running|migrated|failed|quarantined
            $table->string('schema_fingerprint')->nullable();
            $table->unsignedSmallInteger('attempts_since_success')->default(0);
            $table->text('last_error')->nullable();
            $table->uuid('last_run_id')->nullable();
            $table->timestamp('migrated_at')->nullable();
            $table->timestamps();
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_migration_status');
    }
};
```

- [ ] **Step 3: Write the attempts migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Append-only per-attempt ledger for forensics. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_migration_attempts', function (Blueprint $table) {
            $table->id();
            $table->uuid('run_id')->index();
            $table->string('tenant_id')->index();
            $table->string('status');                  // migrated | failed | quarantined
            $table->text('error')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamp('attempted_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_migration_attempts');
    }
};
```

- [ ] **Step 4: Run the migrations against the central connection**

Run: `cd c:/laragon/www/aeos365 && php artisan migrate --path=packages/aero-platform/database/migrations --force`
Expected: three `Created` lines for the new tables, no errors.

- [ ] **Step 5: Commit**

```bash
git add packages/aero-platform/database/migrations/2026_07_15_00000*_*.php
git commit -m "feat(platform): fleet migration ledger tables"
```

---

### Task 2: Ledger Eloquent models

**Files:**
- Create: `packages/aero-platform/src/Models/TenantMigrationRun.php`
- Create: `packages/aero-platform/src/Models/TenantMigrationStatus.php`
- Create: `packages/aero-platform/src/Models/TenantMigrationAttempt.php`
- Test: `packages/aero-platform/tests/Unit/Models/TenantMigrationLedgerTest.php`

**Interfaces:**
- Consumes: tables from Task 1.
- Produces:
  - `TenantMigrationRun` (uuid pk, `$connection='central'`, casts `started_at`/`finished_at` datetime).
  - `TenantMigrationStatus` (string pk `tenant_id`, `$incrementing=false`, `$keyType='string'`, `$connection='central'`).
  - `TenantMigrationAttempt` (`$connection='central'`).
  - Status string constants on `TenantMigrationStatus`: `UP_TO_DATE`, `PENDING`, `RUNNING`, `MIGRATED`, `FAILED`, `QUARANTINED`.

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Unit\Models;

use Aero\Platform\Models\TenantMigrationStatus;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class TenantMigrationLedgerTest extends TestCase
{
    use DatabaseMigrations;

    public function test_status_uses_string_primary_key_on_central(): void
    {
        $row = TenantMigrationStatus::create([
            'tenant_id' => 'acme',
            'status' => TenantMigrationStatus::PENDING,
        ]);

        $this->assertSame('acme', $row->getKey());
        $this->assertFalse($row->incrementing);
        $this->assertSame('central', $row->getConnectionName());
        $this->assertSame(TenantMigrationStatus::PENDING, $row->fresh()->status);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd c:/laragon/www/aeos365 && php artisan test packages/aero-platform/tests/Unit/Models/TenantMigrationLedgerTest.php`
Expected: FAIL — `Class "Aero\Platform\Models\TenantMigrationStatus" not found`.

- [ ] **Step 3: Write the models**

`TenantMigrationStatus.php`:

```php
<?php

declare(strict_types=1);

namespace Aero\Platform\Models;

class TenantMigrationStatus extends CentralModel
{
    public const UP_TO_DATE  = 'up_to_date';
    public const PENDING     = 'pending';
    public const RUNNING     = 'running';
    public const MIGRATED    = 'migrated';
    public const FAILED      = 'failed';
    public const QUARANTINED = 'quarantined';

    protected $connection = 'central';
    protected $table = 'tenant_migration_status';
    protected $primaryKey = 'tenant_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'tenant_id', 'status', 'schema_fingerprint',
        'attempts_since_success', 'last_error', 'last_run_id', 'migrated_at',
    ];

    protected function casts(): array
    {
        return [
            'attempts_since_success' => 'integer',
            'migrated_at' => 'datetime',
        ];
    }
}
```

`TenantMigrationRun.php`:

```php
<?php

declare(strict_types=1);

namespace Aero\Platform\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

class TenantMigrationRun extends CentralModel
{
    use HasUuids;

    public const RUNNING = 'running';
    public const COMPLETED = 'completed';
    public const COMPLETED_WITH_FAILURES = 'completed_with_failures';
    public const CANCELLED = 'cancelled';

    protected $connection = 'central';
    protected $table = 'tenant_migration_runs';

    protected $fillable = [
        'batch_id', 'trigger', 'release', 'total', 'pending',
        'migrated', 'failed', 'quarantined', 'status', 'started_at', 'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'integer', 'pending' => 'integer', 'migrated' => 'integer',
            'failed' => 'integer', 'quarantined' => 'integer',
            'started_at' => 'datetime', 'finished_at' => 'datetime',
        ];
    }
}
```

`TenantMigrationAttempt.php`:

```php
<?php

declare(strict_types=1);

namespace Aero\Platform\Models;

class TenantMigrationAttempt extends CentralModel
{
    protected $connection = 'central';
    protected $table = 'tenant_migration_attempts';

    protected $fillable = [
        'run_id', 'tenant_id', 'status', 'error', 'duration_ms', 'attempted_at',
    ];

    protected function casts(): array
    {
        return [
            'duration_ms' => 'integer',
            'attempted_at' => 'datetime',
        ];
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `cd c:/laragon/www/aeos365 && php artisan test packages/aero-platform/tests/Unit/Models/TenantMigrationLedgerTest.php`
Expected: PASS (1 test).

- [ ] **Step 5: Commit**

```bash
git add packages/aero-platform/src/Models/TenantMigration*.php packages/aero-platform/tests/Unit/Models/TenantMigrationLedgerTest.php
git commit -m "feat(platform): fleet migration ledger models"
```

---

### Task 3: Schema fingerprint + pending detection

**Files:**
- Create: `packages/aero-platform/src/Services/SchemaFingerprint.php`
- Create: `packages/aero-platform/src/Services/FleetMigrationService.php` (only `plan()` in this task)
- Test: `packages/aero-platform/tests/Unit/Services/SchemaFingerprintTest.php`

**Interfaces:**
- Consumes: `Aero\Platform\Services\TenantMigrationPaths::forTenant(Tenant): array<int,string>` (existing), `TenantMigrationStatus` (Task 2).
- Produces:
  - `SchemaFingerprint::for(Tenant $tenant): string` — sha1 of the sorted basenames of every `*.php` file under the tenant's migration paths.
  - `FleetMigrationService::plan(): \Illuminate\Support\Collection<int,Tenant>` — non-quarantined tenants whose stored `schema_fingerprint` differs from the current one (or have no status row).

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Unit\Services;

use Aero\Platform\Models\Tenant;
use Aero\Platform\Services\SchemaFingerprint;
use Aero\Platform\Services\TenantMigrationPaths;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Mockery;
use Tests\TestCase;

class SchemaFingerprintTest extends TestCase
{
    use DatabaseMigrations;

    public function test_fingerprint_is_stable_and_order_independent(): void
    {
        $tenant = new Tenant(['id' => 'acme']);

        $paths = Mockery::mock(TenantMigrationPaths::class);
        // Same dir returned; the service expands + sorts files, so the hash is deterministic.
        $paths->shouldReceive('forTenant')->with($tenant)
            ->andReturn(['packages/aero-platform/database/migrations']);
        $this->app->instance(TenantMigrationPaths::class, $paths);

        $fp1 = app(SchemaFingerprint::class)->for($tenant);
        $fp2 = app(SchemaFingerprint::class)->for($tenant);

        $this->assertSame($fp1, $fp2);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{40}$/', $fp1);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd c:/laragon/www/aeos365 && php artisan test packages/aero-platform/tests/Unit/Services/SchemaFingerprintTest.php`
Expected: FAIL — `Class "Aero\Platform\Services\SchemaFingerprint" not found`.

- [ ] **Step 3: Write `SchemaFingerprint`**

```php
<?php

declare(strict_types=1);

namespace Aero\Platform\Services;

use Aero\Platform\Models\Tenant;

class SchemaFingerprint
{
    public function __construct(private TenantMigrationPaths $paths) {}

    /** sha1 over the sorted basenames of every migration file the tenant should have. */
    public function for(Tenant $tenant): string
    {
        $files = [];
        foreach ($this->paths->forTenant($tenant) as $path) {
            $abs = str_starts_with($path, '/') || preg_match('/^[A-Za-z]:/', $path)
                ? $path
                : base_path($path);
            foreach (glob(rtrim($abs, '/').'/*.php') ?: [] as $file) {
                $files[] = basename($file);
            }
        }
        sort($files);

        return sha1(implode("\n", $files));
    }
}
```

- [ ] **Step 4: Write `FleetMigrationService::plan()`**

```php
<?php

declare(strict_types=1);

namespace Aero\Platform\Services;

use Aero\Platform\Models\Tenant;
use Aero\Platform\Models\TenantMigrationStatus;
use Illuminate\Support\Collection;

class FleetMigrationService
{
    public function __construct(private SchemaFingerprint $fingerprint) {}

    /** Non-quarantined tenants whose stored fingerprint differs from current. */
    public function plan(): Collection
    {
        $status = TenantMigrationStatus::query()
            ->pluck('status', 'tenant_id');            // tenant_id => status
        $fingerprints = TenantMigrationStatus::query()
            ->pluck('schema_fingerprint', 'tenant_id'); // tenant_id => fingerprint

        return Tenant::query()
            ->where('status', '!=', Tenant::STATUS_ARCHIVED)
            ->get()
            ->filter(function (Tenant $tenant) use ($status, $fingerprints) {
                if (($status[$tenant->id] ?? null) === TenantMigrationStatus::QUARANTINED) {
                    return false;
                }
                return ($fingerprints[$tenant->id] ?? null) !== $this->fingerprint->for($tenant);
            })
            ->values();
    }
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `cd c:/laragon/www/aeos365 && php artisan test packages/aero-platform/tests/Unit/Services/SchemaFingerprintTest.php`
Expected: PASS (1 test).

- [ ] **Step 6: Commit**

```bash
git add packages/aero-platform/src/Services/SchemaFingerprint.php packages/aero-platform/src/Services/FleetMigrationService.php packages/aero-platform/tests/Unit/Services/SchemaFingerprintTest.php
git commit -m "feat(platform): schema fingerprint + fleet plan()"
```

---

### Task 4: TenantMigrator service + MigrateTenantJob

**Files:**
- Create: `packages/aero-platform/src/Services/TenantMigrator.php`
- Create: `packages/aero-platform/src/Jobs/MigrateTenantJob.php`
- Modify: `packages/aero-platform/config/platform.php` (add `fleet_migration` block)
- Test: `packages/aero-platform/tests/Unit/Jobs/MigrateTenantJobTest.php`

**Interfaces:**
- Consumes: `TenantMigrationPaths`, `SchemaFingerprint` (Task 3), ledger models (Task 2), `Tenant`.
- Produces:
  - `TenantMigrator::migrate(Tenant $tenant): void` — initializes tenancy, runs the curated-path `migrate --force`, ends tenancy in `finally`; throws `\Throwable` on failure. (Extracted from `TenantMigrate::migrateTenant()`.)
  - `MigrateTenantJob(string $tenantId, string $runId)` — queued on `config('platform.fleet_migration.queue')`; `$tries=3`; `WithoutOverlapping($tenantId)`; on success sets status `MIGRATED` + fingerprint + resets attempts + writes attempt + increments run `migrated`; `failed()` increments `attempts_since_success`, quarantines at threshold, writes attempt, increments run `failed`/`quarantined`.

- [ ] **Step 1: Add config block to `platform.php`**

Add to the array returned by `packages/aero-platform/config/platform.php`:

```php
    'fleet_migration' => [
        'queue' => env('TENANT_MIGRATION_QUEUE', 'migrations'),
        'quarantine_after' => (int) env('TENANT_MIGRATION_QUARANTINE_AFTER', 3),
    ],
```

- [ ] **Step 2: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Unit\Jobs;

use Aero\Platform\Jobs\MigrateTenantJob;
use Aero\Platform\Models\Tenant;
use Aero\Platform\Models\TenantMigrationRun;
use Aero\Platform\Models\TenantMigrationStatus;
use Aero\Platform\Services\TenantMigrator;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Mockery;
use Tests\TestCase;

class MigrateTenantJobTest extends TestCase
{
    use DatabaseMigrations;

    public function test_success_marks_tenant_migrated_and_increments_run(): void
    {
        $tenant = Tenant::factory()->create(['id' => 'acme']);
        $run = TenantMigrationRun::create([
            'trigger' => 'manual', 'total' => 1, 'pending' => 1, 'status' => TenantMigrationRun::RUNNING,
        ]);
        TenantMigrationStatus::create(['tenant_id' => 'acme', 'status' => TenantMigrationStatus::PENDING]);

        $migrator = Mockery::mock(TenantMigrator::class);
        $migrator->shouldReceive('migrate')->once();
        $this->app->instance(TenantMigrator::class, $migrator);

        (new MigrateTenantJob('acme', $run->id))->handle(
            $migrator,
            app(\Aero\Platform\Services\SchemaFingerprint::class),
        );

        $this->assertSame(TenantMigrationStatus::MIGRATED, TenantMigrationStatus::find('acme')->status);
        $this->assertSame(1, $run->fresh()->migrated);
        $this->assertDatabaseHas('tenant_migration_attempts', ['tenant_id' => 'acme', 'status' => 'migrated']);
    }

    public function test_repeated_failure_quarantines_at_threshold(): void
    {
        $tenant = Tenant::factory()->create(['id' => 'bad']);
        $run = TenantMigrationRun::create(['trigger' => 'manual', 'total' => 1, 'pending' => 1, 'status' => TenantMigrationRun::RUNNING]);
        TenantMigrationStatus::create([
            'tenant_id' => 'bad',
            'status' => TenantMigrationStatus::FAILED,
            'attempts_since_success' => 2, // one more failure hits the default threshold of 3
        ]);

        (new MigrateTenantJob('bad', $run->id))->failed(new \RuntimeException('boom'));

        $this->assertSame(TenantMigrationStatus::QUARANTINED, TenantMigrationStatus::find('bad')->status);
        $this->assertSame(1, $run->fresh()->quarantined);
    }
}
```

- [ ] **Step 3: Run test to verify it fails**

Run: `cd c:/laragon/www/aeos365 && php artisan test packages/aero-platform/tests/Unit/Jobs/MigrateTenantJobTest.php`
Expected: FAIL — `Class "Aero\Platform\Jobs\MigrateTenantJob" not found`.

- [ ] **Step 4: Write `TenantMigrator` (extract from the command)**

```php
<?php

declare(strict_types=1);

namespace Aero\Platform\Services;

use Aero\Platform\Models\Tenant;
use Illuminate\Support\Facades\Artisan;

class TenantMigrator
{
    public function __construct(private TenantMigrationPaths $paths) {}

    /** Run the curated-path tenant migrations. Throws on failure. */
    public function migrate(Tenant $tenant): void
    {
        try {
            tenancy()->initialize($tenant);
            Artisan::call('migrate', [
                '--force' => true,
                '--path' => $this->paths->forTenant($tenant),
            ]);
        } finally {
            tenancy()->end();
        }
    }
}
```

- [ ] **Step 5: Write `MigrateTenantJob`**

```php
<?php

declare(strict_types=1);

namespace Aero\Platform\Jobs;

use Aero\Platform\Models\Tenant;
use Aero\Platform\Models\TenantMigrationAttempt;
use Aero\Platform\Models\TenantMigrationRun;
use Aero\Platform\Models\TenantMigrationStatus;
use Aero\Platform\Services\SchemaFingerprint;
use Aero\Platform\Services\TenantMigrator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class MigrateTenantJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    private float $startedAt = 0.0;

    public function __construct(public string $tenantId, public string $runId)
    {
        $this->onQueue(config('platform.fleet_migration.queue', 'migrations'));
    }

    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function middleware(): array
    {
        return [new WithoutOverlapping($this->tenantId)];
    }

    public function handle(TenantMigrator $migrator, SchemaFingerprint $fingerprint): void
    {
        $this->startedAt = microtime(true);
        $tenant = Tenant::findOrFail($this->tenantId);

        TenantMigrationStatus::where('tenant_id', $this->tenantId)
            ->update(['status' => TenantMigrationStatus::RUNNING, 'last_run_id' => $this->runId]);

        $migrator->migrate($tenant);

        DB::connection('central')->transaction(function () use ($tenant, $fingerprint) {
            TenantMigrationStatus::updateOrCreate(
                ['tenant_id' => $this->tenantId],
                [
                    'status' => TenantMigrationStatus::MIGRATED,
                    'schema_fingerprint' => $fingerprint->for($tenant),
                    'attempts_since_success' => 0,
                    'last_error' => null,
                    'last_run_id' => $this->runId,
                    'migrated_at' => now(),
                ],
            );
            $this->recordAttempt('migrated', null);
            TenantMigrationRun::where('id', $this->runId)->increment('migrated');
            TenantMigrationRun::where('id', $this->runId)->decrement('pending');
        });
    }

    public function failed(\Throwable $e): void
    {
        $threshold = (int) config('platform.fleet_migration.quarantine_after', 3);

        DB::connection('central')->transaction(function () use ($e, $threshold) {
            $row = TenantMigrationStatus::firstOrNew(['tenant_id' => $this->tenantId]);
            $row->attempts_since_success = (int) $row->attempts_since_success + 1;
            $quarantined = $row->attempts_since_success >= $threshold;
            $row->status = $quarantined ? TenantMigrationStatus::QUARANTINED : TenantMigrationStatus::FAILED;
            $row->last_error = mb_substr($e->getMessage(), 0, 2000);
            $row->last_run_id = $this->runId;
            $row->save();

            $this->recordAttempt($quarantined ? 'quarantined' : 'failed', $e->getMessage());
            TenantMigrationRun::where('id', $this->runId)->increment($quarantined ? 'quarantined' : 'failed');
            TenantMigrationRun::where('id', $this->runId)->decrement('pending');
        });
    }

    private function recordAttempt(string $status, ?string $error): void
    {
        TenantMigrationAttempt::create([
            'run_id' => $this->runId,
            'tenant_id' => $this->tenantId,
            'status' => $status,
            'error' => $error !== null ? mb_substr($error, 0, 4000) : null,
            'duration_ms' => $this->startedAt > 0 ? (int) ((microtime(true) - $this->startedAt) * 1000) : null,
            'attempted_at' => now(),
        ]);
    }
}
```

- [ ] **Step 6: Run test to verify it passes**

Run: `cd c:/laragon/www/aeos365 && php artisan test packages/aero-platform/tests/Unit/Jobs/MigrateTenantJobTest.php`
Expected: PASS (2 tests).

- [ ] **Step 7: Commit**

```bash
git add packages/aero-platform/src/Services/TenantMigrator.php packages/aero-platform/src/Jobs/MigrateTenantJob.php packages/aero-platform/config/platform.php packages/aero-platform/tests/Unit/Jobs/MigrateTenantJobTest.php
git commit -m "feat(platform): TenantMigrator + queued MigrateTenantJob with quarantine"
```

---

### Task 5: FleetMigrationService dispatchRun / retry / status

**Files:**
- Modify: `packages/aero-platform/src/Services/FleetMigrationService.php`
- Test: `packages/aero-platform/tests/Feature/FleetMigrationServiceTest.php`

**Interfaces:**
- Consumes: `plan()` (Task 3), `MigrateTenantJob` (Task 4), ledger models.
- Produces:
  - `dispatchRun(string $trigger, ?string $release = null): TenantMigrationRun` — rejects overlap via a cache lock `fleet-migration-run`; creates the run; marks each pending tenant `PENDING`; dispatches a `Bus::batch()` of `MigrateTenantJob`s with `allowFailures()`; `finally()` closes the run.
  - `retry(Tenant $tenant): void` — clears quarantine (`attempts_since_success=0`, status `PENDING`), dispatches a single `MigrateTenantJob` in a one-tenant run.
  - `status(?string $runId = null): array` — `['run' => TenantMigrationRun|null, 'quarantined' => Collection<TenantMigrationStatus>]`.

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature;

use Aero\Platform\Jobs\MigrateTenantJob;
use Aero\Platform\Models\Tenant;
use Aero\Platform\Models\TenantMigrationRun;
use Aero\Platform\Models\TenantMigrationStatus;
use Aero\Platform\Services\FleetMigrationService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class FleetMigrationServiceTest extends TestCase
{
    use DatabaseMigrations;

    public function test_dispatch_run_queues_one_job_per_pending_tenant(): void
    {
        Bus::fake();
        Tenant::factory()->create(['id' => 'acme', 'status' => Tenant::STATUS_ACTIVE]);
        Tenant::factory()->create(['id' => 'globex', 'status' => Tenant::STATUS_ACTIVE]);
        // globex is already up to date → excluded. Give it the current fingerprint.
        TenantMigrationStatus::create([
            'tenant_id' => 'globex',
            'status' => TenantMigrationStatus::MIGRATED,
            'schema_fingerprint' => app(\Aero\Platform\Services\SchemaFingerprint::class)
                ->for(Tenant::find('globex')),
        ]);

        $run = app(FleetMigrationService::class)->dispatchRun('manual');

        $this->assertSame(1, $run->total);       // only acme pending
        Bus::assertBatched(fn ($batch) => $batch->jobs->count() === 1);
    }

    public function test_retry_clears_quarantine(): void
    {
        Bus::fake();
        $tenant = Tenant::factory()->create(['id' => 'bad', 'status' => Tenant::STATUS_ACTIVE]);
        TenantMigrationStatus::create([
            'tenant_id' => 'bad',
            'status' => TenantMigrationStatus::QUARANTINED,
            'attempts_since_success' => 5,
        ]);

        app(FleetMigrationService::class)->retry($tenant);

        $fresh = TenantMigrationStatus::find('bad');
        $this->assertSame(TenantMigrationStatus::PENDING, $fresh->status);
        $this->assertSame(0, $fresh->attempts_since_success);
        Bus::assertBatched(fn ($batch) => $batch->jobs->count() === 1);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd c:/laragon/www/aeos365 && php artisan test packages/aero-platform/tests/Feature/FleetMigrationServiceTest.php`
Expected: FAIL — `Call to undefined method ...::dispatchRun()`.

- [ ] **Step 3: Extend `FleetMigrationService`**

Add these `use` imports and methods to the existing class:

```php
use Aero\Platform\Jobs\MigrateTenantJob;
use Aero\Platform\Models\TenantMigrationRun;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Throwable;
```

```php
    /** Dispatch a resumable fleet run over all pending tenants. Rejects overlap. */
    public function dispatchRun(string $trigger, ?string $release = null): TenantMigrationRun
    {
        $lock = Cache::lock('fleet-migration-run', 600);
        if (! $lock->get()) {
            throw new \RuntimeException('A fleet migration run is already in progress.');
        }

        try {
            $pending = $this->plan();

            $run = TenantMigrationRun::create([
                'trigger' => $trigger,
                'release' => $release,
                'total' => $pending->count(),
                'pending' => $pending->count(),
                'status' => TenantMigrationRun::RUNNING,
                'started_at' => now(),
            ]);

            foreach ($pending as $tenant) {
                TenantMigrationStatus::updateOrCreate(
                    ['tenant_id' => $tenant->id],
                    ['status' => TenantMigrationStatus::PENDING, 'last_run_id' => $run->id],
                );
            }

            $runId = $run->id;
            $jobs = $pending->map(fn ($tenant) => new MigrateTenantJob($tenant->id, $runId))->all();

            $batch = Bus::batch($jobs)
                ->name("fleet-migration:{$runId}")
                ->allowFailures()
                ->finally(function (Batch $batch) use ($runId) {
                    $run = TenantMigrationRun::find($runId);
                    if (! $run) {
                        return;
                    }
                    $run->update([
                        'status' => $run->failed > 0 || $run->quarantined > 0
                            ? TenantMigrationRun::COMPLETED_WITH_FAILURES
                            : TenantMigrationRun::COMPLETED,
                        'finished_at' => now(),
                    ]);
                })
                ->onQueue(config('platform.fleet_migration.queue', 'migrations'))
                ->dispatch();

            $run->update(['batch_id' => $batch->id]);

            return $run->fresh();
        } finally {
            $lock->release();
        }
    }

    /** Clear a tenant's quarantine and re-dispatch it in a one-tenant run. */
    public function retry(Tenant $tenant): void
    {
        $run = TenantMigrationRun::create([
            'trigger' => 'manual', 'total' => 1, 'pending' => 1,
            'status' => TenantMigrationRun::RUNNING, 'started_at' => now(),
        ]);

        TenantMigrationStatus::updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'status' => TenantMigrationStatus::PENDING,
                'attempts_since_success' => 0,
                'last_error' => null,
                'last_run_id' => $run->id,
            ],
        );

        $runId = $run->id;
        Bus::batch([new MigrateTenantJob($tenant->id, $runId)])
            ->name("fleet-migration-retry:{$runId}")
            ->allowFailures()
            ->finally(function (Batch $batch) use ($runId) {
                $run = TenantMigrationRun::find($runId);
                $run?->update([
                    'status' => $run->failed > 0 || $run->quarantined > 0
                        ? TenantMigrationRun::COMPLETED_WITH_FAILURES
                        : TenantMigrationRun::COMPLETED,
                    'finished_at' => now(),
                ]);
            })
            ->onQueue(config('platform.fleet_migration.queue', 'migrations'))
            ->dispatch();
    }

    /** Snapshot for the status page/CLI. */
    public function status(?string $runId = null): array
    {
        $run = $runId
            ? TenantMigrationRun::find($runId)
            : TenantMigrationRun::orderByDesc('created_at')->first();

        return [
            'run' => $run,
            'quarantined' => TenantMigrationStatus::where('status', TenantMigrationStatus::QUARANTINED)->get(),
        ];
    }
```

- [ ] **Step 4: Run test to verify it passes**

Run: `cd c:/laragon/www/aeos365 && php artisan test packages/aero-platform/tests/Feature/FleetMigrationServiceTest.php`
Expected: PASS (2 tests).

- [ ] **Step 5: Commit**

```bash
git add packages/aero-platform/src/Services/FleetMigrationService.php packages/aero-platform/tests/Feature/FleetMigrationServiceTest.php
git commit -m "feat(platform): fleet dispatchRun/retry/status on Bus::batch"
```

---

### Task 6: Refactor `tenant:migrate` command

**Files:**
- Modify: `packages/aero-platform/src/Console/Commands/TenantMigrate.php`
- Test: `packages/aero-platform/tests/Feature/TenantMigrateCommandTest.php`

**Interfaces:**
- Consumes: `FleetMigrationService` (Task 5), `TenantMigrator` (Task 4).
- Produces: command `tenant:migrate` with new options `--run`, `--status`, `--plan`, `--retry`, `--sync`; single-tenant argument path preserved; no-arg + no-flag errors with guidance.

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature;

use Aero\Platform\Models\Tenant;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class TenantMigrateCommandTest extends TestCase
{
    use DatabaseMigrations;

    public function test_no_argument_and_no_flag_errors_with_guidance(): void
    {
        $this->artisan('tenant:migrate')
            ->expectsOutputToContain('--run')
            ->assertExitCode(1);
    }

    public function test_run_flag_dispatches_a_batch(): void
    {
        Bus::fake();
        Tenant::factory()->create(['id' => 'acme', 'status' => Tenant::STATUS_ACTIVE]);

        $this->artisan('tenant:migrate --run --force')->assertExitCode(0);

        Bus::assertBatched(fn ($batch) => $batch->jobs->count() === 1);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd c:/laragon/www/aeos365 && php artisan test packages/aero-platform/tests/Feature/TenantMigrateCommandTest.php`
Expected: FAIL — `tenant:migrate` with no flag currently prompts/migrates instead of erroring.

- [ ] **Step 3: Refactor the command signature + handle()**

Replace the `$signature` and `handle()` in `TenantMigrate.php`. Keep the existing `migrateTenant()` for the single-tenant sync path, but have it delegate to `TenantMigrator` (added below). New signature:

```php
    protected $signature = 'tenant:migrate
                            {tenant? : Migrate a single tenant (sync)}
                            {--run : Dispatch a queued, resumable fleet run over all pending tenants}
                            {--status : Show the latest fleet run + quarantined tenants}
                            {--plan : Show how many tenants have pending migrations (no execution)}
                            {--retry= : Clear quarantine for a tenant id and re-dispatch it}
                            {--sync : Emergency: migrate ALL tenants synchronously (old behavior)}
                            {--trigger=manual : Run trigger label (deploy|manual|scheduled)}
                            {--release= : Release identifier (git sha) recorded on the run}
                            {--fresh : (single tenant) wipe + re-run all migrations}
                            {--seed : (single tenant) seed after migrating}
                            {--rollback : (single tenant) rollback}
                            {--step=1 : rollback step count}
                            {--force : Skip confirmation / run in production}
                            {--path= : Explicit migration path override (single tenant)}';
```

```php
    public function handle(\Aero\Platform\Services\FleetMigrationService $fleet): int
    {
        if ($this->option('status')) {
            return $this->showStatus($fleet);
        }
        if ($this->option('plan')) {
            $count = $fleet->plan()->count();
            $this->info("{$count} tenant(s) have pending migrations.");
            return self::SUCCESS;
        }
        if ($retryId = $this->option('retry')) {
            $tenant = Tenant::find($retryId);
            if (! $tenant) {
                $this->error("Tenant '{$retryId}' not found.");
                return self::FAILURE;
            }
            $fleet->retry($tenant);
            $this->info("Re-dispatched tenant '{$retryId}' (quarantine cleared).");
            return self::SUCCESS;
        }
        if ($this->option('run')) {
            $run = $fleet->dispatchRun($this->option('trigger'), $this->option('release'));
            $this->info("Fleet run {$run->id} dispatched for {$run->total} pending tenant(s).");
            return self::SUCCESS;
        }

        // Single-tenant sync path (unchanged behavior).
        if ($tenantId = $this->argument('tenant')) {
            $tenant = Tenant::find($tenantId);
            if (! $tenant) {
                $this->error("Tenant '{$tenantId}' not found.");
                return self::FAILURE;
            }
            return $this->migrateTenant($tenant);
        }

        // Emergency synchronous all-tenant path.
        if ($this->option('sync')) {
            return $this->migrateAllSync();
        }

        $this->error('Nothing to do. Pass --run (queued fleet), --sync (all, blocking), '
            .'a {tenant} id, --status, --plan, or --retry=<id>.');
        return self::FAILURE;
    }

    private function showStatus(\Aero\Platform\Services\FleetMigrationService $fleet): int
    {
        $s = $fleet->status();
        $run = $s['run'];
        if (! $run) {
            $this->info('No fleet runs recorded yet.');
            return self::SUCCESS;
        }
        $this->info("Run {$run->id} [{$run->status}] — migrated {$run->migrated}/{$run->total}, "
            ."failed {$run->failed}, quarantined {$run->quarantined}, pending {$run->pending}.");
        foreach ($s['quarantined'] as $q) {
            $this->line("  quarantined: {$q->tenant_id} — {$q->last_error}");
        }
        return self::SUCCESS;
    }
```

Move the existing all-tenant `foreach` from the old `handle()` into a private `migrateAllSync(): int` (behind `--sync`), and change `migrateTenant()`'s try-body to call `app(\Aero\Platform\Services\TenantMigrator::class)->migrate($tenant)` for the plain (non-fresh, non-rollback) case so the migrate logic lives in one place.

- [ ] **Step 4: Run test to verify it passes**

Run: `cd c:/laragon/www/aeos365 && php artisan test packages/aero-platform/tests/Feature/TenantMigrateCommandTest.php`
Expected: PASS (2 tests).

- [ ] **Step 5: Commit**

```bash
git add packages/aero-platform/src/Console/Commands/TenantMigrate.php packages/aero-platform/tests/Feature/TenantMigrateCommandTest.php
git commit -m "feat(platform): tenant:migrate --run/--status/--plan/--retry/--sync front-end"
```

---

### Task 7: Platform status page (backend route + controller)

**Files:**
- Create: `packages/aero-platform/src/Http/Controllers/Admin/FleetMigrationController.php`
- Modify: `packages/aero-platform/routes/admin.php`
- Test: `packages/aero-platform/tests/Feature/FleetMigrationControllerTest.php`

**Interfaces:**
- Consumes: `FleetMigrationService::status()` and `retry()` (Task 5).
- Produces:
  - `GET admin/fleet-migrations` → Inertia `Platform/FleetMigrations/Index` with `{ run, statuses, quarantined }`.
  - `POST admin/fleet-migrations/retry/{tenant}` → calls `retry()`, redirects back.
  - Both HRMAC-gated with an appropriate platform ability (match the sibling `TenantDatabaseController` gate).

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature;

use Aero\Platform\Models\TenantMigrationRun;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class FleetMigrationControllerTest extends TestCase
{
    use DatabaseMigrations;

    public function test_index_renders_with_latest_run(): void
    {
        TenantMigrationRun::create(['trigger' => 'manual', 'total' => 3, 'migrated' => 3, 'status' => TenantMigrationRun::COMPLETED]);

        $this->actingAsPlatformAdmin() // helper from platform TestCase harness
            ->get('/admin/fleet-migrations')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Platform/FleetMigrations/Index')->has('run'));
    }
}
```

> Note: if the platform `TestCase` exposes a different admin-auth helper than `actingAsPlatformAdmin()`, use whichever the neighbouring `tests/Feature/Admin` tests use.

- [ ] **Step 2: Run test to verify it fails**

Run: `cd c:/laragon/www/aeos365 && php artisan test packages/aero-platform/tests/Feature/FleetMigrationControllerTest.php`
Expected: FAIL — 404 (route not registered).

- [ ] **Step 3: Write the controller**

```php
<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Models\Tenant;
use Aero\Platform\Models\TenantMigrationStatus;
use Aero\Platform\Services\FleetMigrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

class FleetMigrationController extends Controller
{
    public function __construct(private FleetMigrationService $fleet) {}

    public function index(): Response
    {
        $s = $this->fleet->status();

        return Inertia::render('Platform/FleetMigrations/Index', [
            'run' => $s['run'],
            'quarantined' => $s['quarantined'],
            'statuses' => TenantMigrationStatus::query()
                ->orderByRaw("FIELD(status,'quarantined','failed','running','pending','migrated','up_to_date')")
                ->limit(200)->get(),
        ]);
    }

    public function retry(string $tenant): RedirectResponse
    {
        $model = Tenant::findOrFail($tenant);
        $this->fleet->retry($model);

        return back()->with('success', "Tenant {$tenant} re-dispatched.");
    }
}
```

- [ ] **Step 4: Register routes in `admin.php`**

Add inside the existing admin route group (mirror the gate used by `TenantDatabaseController`):

```php
use Aero\Platform\Http\Controllers\Admin\FleetMigrationController;

Route::get('fleet-migrations', [FleetMigrationController::class, 'index'])
    ->name('admin.fleet-migrations.index');
Route::post('fleet-migrations/retry/{tenant}', [FleetMigrationController::class, 'retry'])
    ->name('admin.fleet-migrations.retry');
```

- [ ] **Step 5: Run test to verify it passes**

Run: `cd c:/laragon/www/aeos365 && php artisan test packages/aero-platform/tests/Feature/FleetMigrationControllerTest.php`
Expected: PASS (1 test).

- [ ] **Step 6: Commit**

```bash
git add packages/aero-platform/src/Http/Controllers/Admin/FleetMigrationController.php packages/aero-platform/routes/admin.php packages/aero-platform/tests/Feature/FleetMigrationControllerTest.php
git commit -m "feat(platform): fleet migration status route + controller"
```

---

### Task 8: Platform status page (React) + docs

**Files:**
- Create: `packages/aero-ui/resources/js/Pages/Platform/FleetMigrations/Index.jsx`
- Modify: `docs/superpowers/specs/2026-07-15-fleet-migration-runner-design.md` (append an "Operations" note)

**Interfaces:**
- Consumes: the Inertia props from Task 7 (`run`, `quarantined`, `statuses`).
- Produces: a read-only command-center page with a progress band, per-status counts, and a quarantined table whose rows have a Retry action `POST admin/fleet-migrations/retry/{tenant}`.

- [ ] **Step 1: Write the page**

```jsx
import { Head, router, usePage } from '@inertiajs/react';
import { Card } from '@aero/ui';

export default function Index() {
  const { run, quarantined, statuses } = usePage().props;
  const pct = run && run.total ? Math.round((run.migrated / run.total) * 100) : 0;

  const retry = (tenantId) =>
    router.post(`/admin/fleet-migrations/retry/${tenantId}`, {}, { preserveScroll: true });

  return (
    <>
      <Head title="Fleet Migrations" />
      <Card>
        <h1>Fleet Migrations</h1>
        {run ? (
          <div>
            <p>
              Run {run.id} — <strong>{run.status}</strong>
            </p>
            <p>
              {run.migrated}/{run.total} migrated ({pct}%) · failed {run.failed} · quarantined{' '}
              {run.quarantined} · pending {run.pending}
            </p>
          </div>
        ) : (
          <p>No fleet runs recorded yet.</p>
        )}
      </Card>

      <Card>
        <h2>Quarantined tenants</h2>
        {quarantined.length === 0 ? (
          <p>None. The fleet is healthy.</p>
        ) : (
          <table>
            <thead>
              <tr>
                <th>Tenant</th>
                <th>Last error</th>
                <th>Attempts</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              {quarantined.map((q) => (
                <tr key={q.tenant_id}>
                  <td>{q.tenant_id}</td>
                  <td>{q.last_error}</td>
                  <td>{q.attempts_since_success}</td>
                  <td>
                    <button type="button" onClick={() => retry(q.tenant_id)}>
                      Retry
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </Card>
    </>
  );
}
```

- [ ] **Step 2: Build the frontend + smoke-check the route**

Run: `cd c:/laragon/www/aeos365 && npm run build`
Then load `/admin/fleet-migrations` as a platform admin and confirm the page renders the band + quarantined table with no console errors (per the "Show Live UI" bar — capture a screenshot).

- [ ] **Step 3: Append the Operations note to the design doc**

Add this section to the spec so deploy wiring is recorded:

```markdown
## Operations

- Prod runs a supervisor-managed worker on the `migrations` queue:
  `php artisan queue:work --queue=migrations --tries=3 --backoff=10`
- Deploy hook (non-blocking, after code is live):
  `php artisan tenant:migrate --run --trigger=deploy --release=$(git rev-parse --short HEAD) --force`
- Watch progress: `php artisan tenant:migrate --status` or `/admin/fleet-migrations`.
- Re-admit a quarantined tenant after a manual fix: `php artisan tenant:migrate --retry=<tenant_id>`.
```

- [ ] **Step 4: Commit**

```bash
git add packages/aero-ui/resources/js/Pages/Platform/FleetMigrations/Index.jsx docs/superpowers/specs/2026-07-15-fleet-migration-runner-design.md
git commit -m "feat(platform): fleet migration status page + ops docs"
```

---

## Self-Review

**Spec coverage:** runs/status/attempts ledger → Task 1–2 ✅; fingerprint pending-detection → Task 3 ✅; queued `MigrateTenantJob` + `WithoutOverlapping` + tries/backoff + quarantine → Task 4 ✅; `Bus::batch()` run + run-lock + `finally()` close + retry + status → Task 5 ✅; `tenant:migrate` `--run/--status/--plan/--retry/--sync` + no-arg guard → Task 6 ✅; HRMAC-gated status surface (JSON via Inertia props) + retry action → Task 7–8 ✅; dedicated `migrations` queue + worker + deploy hook → config in Task 4, ops doc in Task 8 ✅; additive-migration invariant → Global Constraints ✅. Out-of-scope items (shard routing, fleet rollback, alerting, autoscaling) correctly omitted.

**Placeholder scan:** every code step contains real code; no TBD/TODO. The two soft references — the platform admin-auth test helper (Task 7) and the exact HRMAC gate (Task 7 routes) — are explicitly flagged to match the neighbouring `tests/Feature/Admin` and `TenantDatabaseController` patterns, which is the correct instruction given they are existing repo conventions, not new inventions.

**Type consistency:** `TenantMigrationStatus` constants, `MigrateTenantJob(string $tenantId, string $runId)`, `FleetMigrationService::{plan,dispatchRun,retry,status}`, and `TenantMigrator::migrate(Tenant): void` are used identically across Tasks 3–8. Run-counter columns (`migrated/failed/quarantined/pending`) match between the Task 1 migration and the Task 4/5 increments.
