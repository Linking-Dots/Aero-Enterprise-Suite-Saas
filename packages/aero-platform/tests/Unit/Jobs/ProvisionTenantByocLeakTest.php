<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Unit\Jobs;

use Aero\Platform\Jobs\ProvisionTenant;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Plan 03 (aero-platform) Task 11 — BYOC config leak regression pin.
 *
 * Phase 1 audit X-4: ProvisionTenant::injectCpanelConfigFromDb() overlays
 * BYOC cPanel credentials at runtime via config([... => ...]). Because
 * Laravel's config is a process-global container, a queue worker
 * processing multiple ProvisionTenant jobs in sequence would carry
 * tenant A's credentials into tenant B's provisioning UNLESS each job
 * restored the config when done.
 *
 * The fix:
 *   - $originalCpanelConfig snapshot captured before the first overlay
 *   - restoreCpanelConfig() invoked in finally{} of both handle() and
 *     failed() so every exit path resets the runtime config
 *
 * Full integration test (running two jobs back-to-back with different
 * BYOC creds and asserting non-leakage) requires a real DB + cPanel
 * mock and lives in the host repo's feature suite. This file pins the
 * structural contract.
 */
class ProvisionTenantByocLeakTest extends TestCase
{
    private function source(): string
    {
        return file_get_contents((new ReflectionClass(ProvisionTenant::class))->getFileName());
    }

    public function test_original_config_snapshot_property_exists(): void
    {
        $r = new ReflectionClass(ProvisionTenant::class);

        $this->assertTrue($r->hasProperty('originalCpanelConfig'),
            'ProvisionTenant::$originalCpanelConfig must exist (Plan 03 T11).');
    }

    public function test_restore_cpanel_config_method_exists(): void
    {
        $r = new ReflectionClass(ProvisionTenant::class);

        $this->assertTrue($r->hasMethod('restoreCpanelConfig'),
            'restoreCpanelConfig() must exist to undo BYOC overlays.');
    }

    public function test_handle_method_restores_in_finally(): void
    {
        $source = $this->source();

        // Pin the structural pattern: handle()'s finally block must invoke restore.
        $this->assertMatchesRegularExpression(
            '/finally\s*\{[^}]*restoreCpanelConfig\s*\(\s*\)\s*;[^}]*\}/s',
            $source,
            'handle() must call restoreCpanelConfig() in finally{} so every exit '.
            '(success/throw/return) restores the process-global config.'
        );
    }

    public function test_failed_method_also_restores_config(): void
    {
        $source = $this->source();

        // failed() invokes rollbackDatabase() which re-overlays creds via
        // injectCpanelConfigFromDb() at line 1362 — so failed() must also
        // restore on its way out. Count the restoreCpanelConfig() calls:
        // should be at least 2 (handle + failed).
        $count = preg_match_all('/restoreCpanelConfig\s*\(\s*\)\s*;/', $source);

        $this->assertGreaterThanOrEqual(2, $count,
            'restoreCpanelConfig() must be invoked from BOTH handle()\'s finally '.
            'AND failed()\'s finally — handle()\'s finally does not run when failed() '.
            "is invoked by the queue worker. Found {$count} calls.");
    }

    public function test_inject_snapshots_only_once_per_run(): void
    {
        $source = $this->source();

        // The injection method may be called multiple times within a single
        // run (e.g., from handle() AND from rollbackDatabase()). The snapshot
        // logic must only fire the first time — subsequent calls keep the
        // ORIGINAL baseline, not the previous overlay.
        $this->assertMatchesRegularExpression(
            '/originalCpanelConfig\s*===\s*null/',
            $source,
            'injectCpanelConfigFromDb() must check $originalCpanelConfig === null '.
            'before snapshotting — otherwise nested calls would overwrite the baseline.'
        );
    }
}
