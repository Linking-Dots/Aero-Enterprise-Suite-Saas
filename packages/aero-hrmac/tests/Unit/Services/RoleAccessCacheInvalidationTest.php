<?php

declare(strict_types=1);

namespace Aero\HRMAC\Tests\Unit\Services;

use Aero\HRMAC\HRMACServiceProvider;
use Aero\HRMAC\Services\RoleModuleAccessService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase;

/**
 * Axis C C6/C8 — per-role access-cache invalidation.
 *
 * canAccessModule/SubModule/... cache their result per role for CACHE_TTL. Before
 * the version-bump fix, clearRoleCache only forgot one key, so the per-module
 * caches kept authorizing a revoked grant until the TTL expired. This test pins:
 *   1. the result IS cached (a DB change alone does not flip it), and
 *   2. clearRoleCache invalidates it IMMEDIATELY (re-resolves from the DB).
 */
class RoleAccessCacheInvalidationTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [HRMACServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]);
        $app['config']->set('cache.default', 'array');
    }

    protected function setUp(): void
    {
        parent::setUp();

        Schema::connection('testing')->create('role_module_access', function ($t) {
            $t->id();
            $t->unsignedBigInteger('role_id');
            $t->unsignedBigInteger('module_id')->nullable();
            $t->unsignedBigInteger('sub_module_id')->nullable();
            $t->unsignedBigInteger('component_id')->nullable();
            $t->unsignedBigInteger('action_id')->nullable();
            $t->string('access_scope')->default('all');
            $t->string('status')->default('active');
            $t->timestamp('suspended_at')->nullable();
            $t->timestamps();
        });
    }

    public function test_clear_role_cache_invalidates_module_access_immediately(): void
    {
        $service = app(RoleModuleAccessService::class);

        $roleId = 1;
        $moduleId = 10;

        DB::connection('testing')->table('role_module_access')->insert([
            'role_id' => $roleId, 'module_id' => $moduleId, 'status' => 'active', 'access_scope' => 'all',
        ]);

        // 1. Granted → true, and now cached.
        $this->assertTrue($service->canAccessModule($roleId, $moduleId));

        // 2. Revoke directly in the DB (simulating a mass update / external change).
        DB::connection('testing')->table('role_module_access')->where('role_id', $roleId)->delete();

        // 3. Still true — proves the result was cached (not re-read from the DB).
        $this->assertTrue(
            $service->canAccessModule($roleId, $moduleId),
            'Access should still be cached immediately after a raw DB delete.'
        );

        // 4. clearRoleCache → immediately re-resolves from the (now empty) DB → false.
        $service->clearRoleCache($roleId);

        $this->assertFalse(
            $service->canAccessModule($roleId, $moduleId),
            'clearRoleCache must invalidate the cached grant immediately (Axis C C6/C8), '.
            'not wait out CACHE_TTL.'
        );
    }
}
