<?php

declare(strict_types=1);

namespace Aero\Installation\Tests\Unit;

use Aero\Installation\Installation\Steps\MigrationStep;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Plan 09 (aero-installation) Tasks 3 + 5 — installer safety guards.
 *
 * T3: MigrationStep::assertSchemaIsSafeToMigrate() — refuses to run
 *     migrate:fresh on a dirty schema (tables present without
 *     migrations history). Closes the catastrophic-data-loss vector
 *     where an operator accidentally pointed the installer at an
 *     existing production DB and migrate:fresh dropped everything.
 *
 * T5: BootstrapGuard 404s /install* routes after install completes
 *     (sentinel file exists). Prevents accidental re-entry into the
 *     install wizard via stale URL.
 */
class InstallationGuardTest extends TestCase
{
    private function migrationStepSource(): string
    {
        return file_get_contents((new ReflectionClass(MigrationStep::class))->getFileName());
    }

    private function bootstrapGuardSource(): string
    {
        // BootstrapGuard has a namespace/path mismatch (claims Http\Middleware but
        // lives at src/Middleware/), so ReflectionClass can't auto-resolve it.
        // Read by path directly — this test is just structural anyway.
        return file_get_contents(dirname(__DIR__, 2).'/src/Middleware/BootstrapGuard.php');
    }

    public function test_migration_step_has_dirty_schema_guard(): void
    {
        $r = new ReflectionClass(MigrationStep::class);

        $this->assertTrue($r->hasMethod('assertSchemaIsSafeToMigrate'),
            'MigrationStep::assertSchemaIsSafeToMigrate() must exist (Plan 09 T3).');
    }

    public function test_dirty_schema_guard_runs_before_migrate_fresh(): void
    {
        $source = $this->migrationStepSource();

        // Pin ordering: guard CALL must appear BEFORE the first Artisan::call('migrate:fresh').
        // Use the actual call signature (not the bare string 'migrate:fresh' which also
        // appears in docblocks/comments and would produce false positives).
        $guardPos = strpos($source, '$this->assertSchemaIsSafeToMigrate()');
        $migrateFreshPos = strpos($source, "Artisan::call('migrate:fresh'");

        $this->assertNotFalse($guardPos, 'Guard call site not found in source.');
        $this->assertNotFalse($migrateFreshPos, "Artisan::call('migrate:fresh') not found.");
        $this->assertLessThan($migrateFreshPos, $guardPos,
            "assertSchemaIsSafeToMigrate() must be invoked BEFORE Artisan::call('migrate:fresh'). ".
            'Order matters — the guard runs first so the destructive fresh migration only '.
            'runs against a known-clean schema.'
        );
    }

    public function test_dirty_schema_guard_supports_force_clean_override(): void
    {
        $source = $this->migrationStepSource();

        $this->assertMatchesRegularExpression(
            "/env\(\s*['\"]FORCE_CLEAN_INSTALL['\"]/",
            $source,
            "Operators must have an escape hatch — FORCE_CLEAN_INSTALL=true env ".
            "var bypasses the guard for intentional clean-installs over corrupt DBs."
        );
    }

    public function test_dirty_schema_guard_uses_information_schema(): void
    {
        $source = $this->migrationStepSource();

        // Pin the cross-driver detection (MySQL information_schema, pgsql, sqlite)
        foreach (['information_schema.tables', 'pg_tables', 'sqlite_master'] as $needle) {
            $this->assertStringContainsString($needle, $source,
                "Dirty-schema guard must handle multiple drivers — '{$needle}' ".
                "is the canonical table-count query for at least one driver.");
        }
    }

    public function test_bootstrap_guard_404s_install_routes_after_install(): void
    {
        $source = $this->bootstrapGuardSource();

        // Pin both halves of the new condition appear close together
        $this->assertStringContainsString("'install*'", $source);
        $this->assertStringContainsString('abort(404)', $source,
            'BootstrapGuard must call abort(404) when the system is installed but the '.
            'request still targets /install* — prevents re-entry into the wizard.');
        $this->assertStringContainsString('$this->installed()', $source);
    }

    public function test_bootstrap_guard_404_check_runs_before_install_route_passthrough(): void
    {
        $source = $this->bootstrapGuardSource();

        $abortPos = strpos($source, 'abort(404)');
        // Find the position of the SECOND "is('install*')" usage (the passthrough,
        // not the new 404 branch). The 404 branch should come FIRST in source.
        $firstInstallPos = strpos($source, "'install*'");
        $secondInstallPos = strpos($source, "'install*'", $firstInstallPos + 1);

        $this->assertNotFalse($abortPos);
        $this->assertNotFalse($secondInstallPos,
            'There should be TWO references to /install* — the 404 branch AND the '.
            'not-installed passthrough.');
        $this->assertLessThan($secondInstallPos, $abortPos,
            'abort(404) must appear BEFORE the second is(\'install*\') reference (which '.
            'is the passthrough). If abort runs after the passthrough, the 404 never fires.');
    }
}
