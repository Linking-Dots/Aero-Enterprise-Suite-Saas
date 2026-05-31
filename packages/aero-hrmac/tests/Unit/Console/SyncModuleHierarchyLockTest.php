<?php

declare(strict_types=1);

namespace Aero\HRMAC\Tests\Unit\Console;

use Aero\HRMAC\Console\Commands\SyncModuleHierarchy;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Plan 04 (aero-hrmac) Task 5 — advisory lock regression pin.
 *
 * Phase 1 audit (and the aero-platform audit) found that two concurrent
 * runs of `hrmac:sync-modules` could race on updateOrCreate() and produce
 * duplicate (module_id, code) rows — especially likely when deploy +
 * scheduler tick within the same minute.
 *
 * This file pins the LOCK CONTRACT structurally. The full concurrent
 * integration test (forking two processes against a real MySQL) lives
 * in the host repo's feature suite.
 */
class SyncModuleHierarchyLockTest extends TestCase
{
    public function test_sync_command_declares_lock_key_constant(): void
    {
        $r = new ReflectionClass(SyncModuleHierarchy::class);
        $this->assertTrue($r->hasConstant('SYNC_LOCK_KEY'),
            'SyncModuleHierarchy::SYNC_LOCK_KEY constant must exist.');
        $this->assertSame('aero:hrmac:sync-modules', $r->getConstant('SYNC_LOCK_KEY'));
    }

    public function test_acquire_and_release_lock_methods_exist(): void
    {
        $r = new ReflectionClass(SyncModuleHierarchy::class);

        $this->assertTrue($r->hasMethod('acquireSyncLock'),
            'acquireSyncLock() must exist (Plan 04 T5).');
        $this->assertTrue($r->hasMethod('releaseSyncLock'),
            'releaseSyncLock() must exist (Plan 04 T5).');
    }

    public function test_handle_wraps_logic_in_try_finally_release(): void
    {
        // Pin the structural pattern: every handle() exit path must release the lock.
        $source = file_get_contents((new ReflectionClass(SyncModuleHierarchy::class))->getFileName());

        $this->assertMatchesRegularExpression(
            '/acquireSyncLock\s*\(\s*\)/',
            $source,
            'handle() must call acquireSyncLock() before doing work.'
        );
        $this->assertMatchesRegularExpression(
            '/finally\s*\{[^}]*releaseSyncLock\s*\(\s*\)/',
            $source,
            'handle() must release the lock in a finally block so failures still release.'
        );
    }
}
