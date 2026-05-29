<?php

namespace Aero\Core\Tests\Feature;

use Aero\Core\Http\Controllers\Api\HealthCheckController;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Phase 0 Task 13 — Health check structural regression guard.
 *
 * The Phase 1 audit identified HealthCheckController as a strength of the
 * platform — it ships /health (LB-friendly) and /health/detailed (DB +
 * cache + queue + redis + memory + disk + storage). This test pins the
 * public surface so a refactor cannot accidentally regress the probe
 * shape that load balancers and operator dashboards depend on.
 *
 * For a full HTTP smoke test, see the host-level
 * tests/Feature/Wiring/HealthEndpointTest.php (or equivalent) — that
 * requires a booted Laravel app and runs in the SaaS host repo, where
 * the controller is wired to a route.
 */
class HealthCheckTest extends TestCase
{
    public function test_health_check_controller_exposes_index_and_detailed(): void
    {
        $r = new ReflectionClass(HealthCheckController::class);

        $this->assertTrue($r->hasMethod('index'),
            'HealthCheckController must expose index() for LB-friendly /health probe.');
        $this->assertTrue($r->hasMethod('detailed'),
            'HealthCheckController must expose detailed() for operator /health/detailed probe.');

        $index = $r->getMethod('index');
        $detailed = $r->getMethod('detailed');

        $this->assertTrue($index->isPublic(),
            'index() must be public.');
        $this->assertTrue($detailed->isPublic(),
            'detailed() must be public.');
    }

    public function test_detailed_checks_all_documented_subsystems(): void
    {
        $r = new ReflectionClass(HealthCheckController::class);

        foreach (['checkDatabaseDetailed', 'checkCacheDetailed', 'checkStorageDetailed',
                  'checkQueueDetailed', 'checkRedisDetailed', 'checkMemory', 'checkDisk'] as $method) {
            $this->assertTrue($r->hasMethod($method),
                "HealthCheckController::{$method}() must exist (referenced from detailed() per Phase 1 audit).");
        }
    }
}
