# aero-installation — Plan to 10/10

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task.

**Current score:** 7/10 (per inline audit, 2026-05-28)
**Target score:** 10/10
**Estimated effort:** 3–4 engineer-days

**Goal:** Build test coverage for the installer (currently zero tests on 22 files including critical steps). Harden the orchestrator against partial-failure scenarios. Verify SaaS-vs-Standalone mode detection. Make the install flow auditable + idempotent (re-running a completed step is safe).

**Architecture:** Stay with the excellent step-based orchestrator pattern. Each step is a class extending `BaseInstallationStep`. Steps are: AdminUser, Cache, Configuration, DatabaseConnection, Finalize, License, Migration, ModuleDiscovery, PlanSeeding, PlatformConfiguration, Seeding, Settings. `ModeDetector` switches between SaaS install and Standalone install.

**Tech Stack:** Laravel 12, Inertia v2 (Welcome/Step pages live in aero-ui), file-based state (probably `.installation-state.json` or DB), no model layer.

**Prerequisite:** None — this package runs BEFORE the rest of the stack.

---

## Reference

- 22 PHP files, no `config/module.php` (correct — wizard, not feature module)
- 1 controller: `UnifiedInstallationController`
- 1 middleware: `BootstrapGuard` + `HandleInertiaRequests`
- 12 step classes + orchestrator + mode detector
- 0 tests
- 11 Inertia pages in `aero-ui/resources/js/Pages/Installation/`

## File Structure

| File | Responsibility |
|---|---|
| `packages/aero-installation/src/Installation/InstallationOrchestrator.php` | Add transaction wrapping per step; checkpoint state |
| `packages/aero-installation/src/Installation/Steps/BaseInstallationStep.php` | Add `isIdempotent()`, `verify()`, `rollback()` lifecycle hooks |
| `packages/aero-installation/src/Installation/Steps/AdminUserStep.php` | Idempotent create; verify on re-run |
| `packages/aero-installation/src/Installation/Steps/DatabaseConnectionStep.php` | Reject malformed creds; refuse to overwrite existing DB |
| `packages/aero-installation/src/Installation/Steps/LicenseStep.php` | Network failure fallback (offline activation) |
| `packages/aero-installation/src/Installation/Steps/MigrationStep.php` | Detect dirty schema; abort instead of corrupting |
| `packages/aero-installation/src/Installation/Steps/FinalizeStep.php` | Lock further runs of installer (write `INSTALLED` sentinel) |
| `packages/aero-installation/src/Middleware/BootstrapGuard.php` | After `INSTALLED` sentinel, 404 all installer routes |
| `packages/aero-installation/tests/Feature/InstallationFlowTest.php` (new) | End-to-end install (SaaS + Standalone) |
| `packages/aero-installation/tests/Unit/Installation/ModeDetectorTest.php` (new) | SaaS vs Standalone detection |
| `packages/aero-installation/tests/Unit/Installation/Steps/*StepTest.php` (new — 12) | Per-step coverage |
| `packages/aero-installation/tests/Feature/BootstrapGuardTest.php` (new) | Sentinel honored |

---

## Task 1: Test `ModeDetector`

**Files:**
- Create: `packages/aero-installation/tests/Unit/Installation/ModeDetectorTest.php`

- [ ] **Step 1: Write tests**

```php
public function test_detects_saas_when_aero_mode_env_is_saas(): void
{
    putenv('AERO_MODE=saas');
    $this->assertSame('saas', (new ModeDetector)->detect());
}

public function test_detects_standalone_when_aero_mode_env_is_standalone(): void
{
    putenv('AERO_MODE=standalone');
    $this->assertSame('standalone', (new ModeDetector)->detect());
}

public function test_defaults_to_standalone_when_no_env(): void
{
    putenv('AERO_MODE');
    $this->assertSame('standalone', (new ModeDetector)->detect());
}

public function test_detects_saas_from_central_db_connection_presence(): void
{
    // even without env, if config('database.connections.central') is set, SaaS
}
```

- [ ] **Step 2: Run, fix gaps**

- [ ] **Step 3: Commit**

```bash
git commit -am "test(installation): ModeDetector SaaS vs Standalone coverage"
```

---

## Task 2: Per-step idempotency + verify lifecycle

**Files:**
- Modify: `packages/aero-installation/src/Installation/Steps/BaseInstallationStep.php`

Add abstract methods:

```php
abstract class BaseInstallationStep
{
    abstract public function run(array $input): array; // returns state

    public function isIdempotent(): bool { return false; }

    public function verify(array $state): bool { return true; }

    public function rollback(array $state): void { /* override */ }
}
```

Each step that touches durable state (DB, files) should override `isIdempotent()` and `verify()` so re-running a partially failed install is safe.

- [ ] **Step 1: Update base + each step**

- [ ] **Step 2: Per-step unit tests** — assert running step twice produces same state

- [ ] **Step 3: Commit per step**

```bash
git commit -m "feat(installation): AdminUserStep idempotency + verify"
git commit -m "feat(installation): DatabaseConnectionStep idempotency + verify"
# ...12 commits
```

---

## Task 3: Migration step safety

**Files:**
- Modify: `packages/aero-installation/src/Installation/Steps/MigrationStep.php`

If the DB already has tables but no `migrations` row tracking them, current code likely just runs `migrate --force` and could corrupt schema. Add a precheck.

- [ ] **Step 1: Write failing test**

```php
public function test_migration_step_aborts_on_dirty_schema_without_migrations_table(): void
{
    // Pre-create some tables manually in test DB
    DB::statement('CREATE TABLE users (id INT)');
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Dirty schema detected');
    (new MigrationStep)->run([]);
}
```

- [ ] **Step 2: Add precheck**

```php
public function run(array $input): array
{
    if ($this->schemaIsDirty() && ! $this->migrationsTableExists()) {
        throw new \RuntimeException(
            'Dirty schema detected: database has tables but no migrations history. ' .
            'Refusing to migrate. Either restore a known-good backup or use --force-clean.'
        );
    }
    Artisan::call('migrate', ['--force' => true]);
    return ['migrated_at' => now()->toIso8601String()];
}
```

- [ ] **Step 3: Commit**

```bash
git commit -am "fix(installation): MigrationStep aborts on dirty schema (data-loss prevention)"
```

---

## Task 4: License step — offline fallback

**Files:**
- Modify: `packages/aero-installation/src/Installation/Steps/LicenseStep.php`

If license server is unreachable during install, allow operator to paste an offline license token.

- [ ] **Step 1: Write tests for both online + offline paths**

- [ ] **Step 2: Implement fallback**

```php
public function run(array $input): array
{
    try {
        return $this->onlineActivation($input);
    } catch (NetworkException $e) {
        if (! isset($input['offline_license_token'])) {
            throw new \RuntimeException('License server unreachable. Provide offline license token in input.', 0, $e);
        }
        return $this->offlineActivation($input['offline_license_token']);
    }
}
```

- [ ] **Step 3: Commit**

```bash
git commit -am "feat(installation): LicenseStep offline fallback"
```

---

## Task 5: `BootstrapGuard` sentinel

**Files:**
- Modify: `packages/aero-installation/src/Middleware/BootstrapGuard.php`
- Modify: `packages/aero-installation/src/Installation/Steps/FinalizeStep.php`
- Create: `packages/aero-installation/tests/Feature/BootstrapGuardTest.php`

`FinalizeStep` writes a sentinel (`storage/installed.sentinel` with a checksum). `BootstrapGuard` 404s any installer route if the sentinel exists.

- [ ] **Step 1: Write tests**

```php
public function test_installer_routes_404_after_finalize(): void
{
    file_put_contents(storage_path('installed.sentinel'), hash('sha256', 'installed-'.now()));
    $this->get('/install')->assertNotFound();
    $this->get('/install/step/admin-user')->assertNotFound();
}
```

- [ ] **Step 2: Implement**

```php
class BootstrapGuard
{
    public function handle(Request $request, Closure $next)
    {
        if (file_exists(storage_path('installed.sentinel'))) {
            abort(404);
        }
        return $next($request);
    }
}
```

- [ ] **Step 3: Commit**

```bash
git commit -am "feat(installation): sentinel-based installer lockout after finalize"
```

---

## Task 6: Orchestrator transaction + checkpoint state

**Files:**
- Modify: `packages/aero-installation/src/Installation/InstallationOrchestrator.php`
- Create: `storage/installation-state.json` (gitignored)

Each step's output is checkpointed so resume-from-failure works.

- [ ] **Step 1: Test resumes from last completed step**

```php
public function test_resumes_from_last_completed_step_after_failure(): void
{
    $orch = new InstallationOrchestrator();
    $orch->runUntil('database-connection'); // simulate partial run
    // re-instantiate and run; database-connection should not re-run
    $state = $orch->resume();
    $this->assertSame('migration', $state['next_step']);
}
```

- [ ] **Step 2: Implement checkpoint write per step**

```php
public function runStep(string $stepCode, array $input): array
{
    $step = $this->resolveStep($stepCode);

    if ($this->isStepComplete($stepCode) && $step->isIdempotent()) {
        return $this->loadStepState($stepCode);
    }

    $state = $step->run($input);
    $this->checkpoint($stepCode, $state);
    return $state;
}
```

- [ ] **Step 3: Commit**

```bash
git commit -am "feat(installation): orchestrator checkpoint state for resume-on-failure"
```

---

## Task 7: End-to-end install flow tests

**Files:**
- Create: `packages/aero-installation/tests/Feature/InstallationFlowTest.php`

- [ ] **Step 1: SaaS install happy path**

```php
public function test_full_saas_install_completes_successfully(): void
{
    putenv('AERO_MODE=saas');
    config(['database.default' => 'sqlite_test']);

    $response = $this->post('/install/run', [
        'admin_user' => ['name' => 'Admin', 'email' => 'a@example.com', 'password' => 'secret123'],
        'database' => ['host' => '127.0.0.1', 'port' => 3306, 'database' => 'aeos_test_central', 'username' => 'root', 'password' => ''],
        'license' => ['key' => 'TEST-KEY'],
    ]);

    $response->assertOk()->assertJson(['status' => 'installed']);
    $this->assertFileExists(storage_path('installed.sentinel'));
}
```

- [ ] **Step 2: Standalone install happy path**

- [ ] **Step 3: Failure scenarios** — bad DB credentials, license server down, dirty schema, etc.

- [ ] **Step 4: Commit**

```bash
git commit -am "test(installation): end-to-end flow tests (SaaS + Standalone + failure paths)"
```

---

## Task 8: Audit log every install action

Per CLAUDE.md, every business action should be audited. Install is a one-time but critical operation.

**Files:**
- Modify: each step to call `AuditService::log()` on success

Note: AuditService may not exist until later steps (`Migration` creates `audit_logs` table). Use a file-based audit log until DB is ready, then flush.

- [ ] **Step 1: Pre-DB phase writes to `storage/install-audit.log`**

- [ ] **Step 2: Post-Migration step flushes file to `audit_logs` table**

- [ ] **Step 3: Test + commit**

```bash
git commit -am "feat(installation): audit trail for every install step"
```

---

## Task 9: Document the install procedure

**Files:**
- Create: `packages/aero-installation/README.md`

Sections:
- Modes (SaaS vs Standalone) — how detection works
- Step order + what each does + idempotency status
- Resume from failure procedure
- Manual recovery if sentinel is corrupted
- Where to put env vars
- Common errors + remediation

- [ ] **Step 1: Write doc**

- [ ] **Step 2: Commit**

```bash
git commit -am "docs(installation): operator guide"
```

---

## Task 10: Final verification

- [ ] **Step 1: Run tests**

```bash
php artisan test --testsuite=installation
```

- [ ] **Step 2: Manual smoke install on a fresh DB**

- [ ] **Step 3: Manual smoke install with `installed.sentinel` present (must 404)**

- [ ] **Step 4: Manual resume — kill mid-install, restart, verify completion**

- [ ] **Step 5: Score recheck**

| Dimension | Target |
|---|---|
| ModeDetector coverage | 10/10 |
| Per-step idempotency + verify | 10/10 |
| Failure resilience (resume, abort-on-dirty) | 10/10 |
| Sentinel lockout post-install | 10/10 |
| Audit trail | 10/10 |
| Test coverage | 9/10 |
| Documentation | 10/10 |

- [ ] **Step 6: Tag**

```bash
git tag aero-installation-10-10
```

---

## Self-Review

- ✅ Test coverage built from zero
- ✅ Resume-on-failure resilience added
- ✅ Data-loss prevention (dirty-schema detection)
- ✅ Sentinel lockout (no double-installs)
- ✅ Audit trail
- ✅ Documentation

## Execution Handoff

Light plan (~3-4 days). Order: 1 (ModeDetector) → 2 (idempotency base) → 3 (migration safety) → 5 (sentinel) → 6 (orchestrator) → 4 (license fallback) → 7 (e2e) → 8 (audit) → 9 (docs) → 10 (verify).
