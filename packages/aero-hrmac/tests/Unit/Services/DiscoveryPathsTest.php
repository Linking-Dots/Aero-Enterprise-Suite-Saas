<?php

declare(strict_types=1);

namespace Aero\HRMAC\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;

/**
 * Architecture Audit Follow-up D13 — discovery path regression pin.
 *
 * Plan 04 T4 added packages/aero-x/config/module.php to discovery for
 * monorepo dev convenience. Audit D13 reversed this: production discovery
 * MUST be vendor-only because (a) a production host should not scan a
 * packages/ directory that does not exist in deployed artifacts, and
 * (b) standalone deployments use composer-path autoload which resolves
 * vendor/aero/* to packages/aero-* symlinks transparently, so the
 * explicit packages/ glob was redundant.
 *
 * This test pins the production-safe set: vendor + modules only.
 */
class DiscoveryPathsTest extends TestCase
{
    public function test_discovery_paths_exclude_monorepo_packages(): void
    {
        $config = require dirname(__DIR__, 3).'/config/hrmac.php';
        $paths = $config['discovery']['paths'];

        $this->assertContains('vendor/aero/*/config/module.php', $paths,
            'Vendor path is the canonical production discovery source — composer-installed Aero packages.');
        $this->assertContains('modules/*/config/module.php', $paths,
            'modules/ path supports standalone-mode operator-installed add-ons.');
        $this->assertNotContains('packages/aero-*/config/module.php', $paths,
            'Monorepo dev path removed (Plan 04 T4 -> Audit D13): discovery uses vendor-only in production. '.
            'Standalone deployments use composer-path symlinks which resolve vendor/aero/* -> packages/aero-* '.
            'at install time, so the explicit packages/ glob was redundant and a leak hazard.');
    }

    public function test_only_two_discovery_paths(): void
    {
        $config = require dirname(__DIR__, 3).'/config/hrmac.php';
        $paths = $config['discovery']['paths'];

        $this->assertCount(2, $paths,
            'Discovery paths should be EXACTLY two: vendor/aero/* and modules/*. '.
            'Any additional path is a regression risk — if you need a third, document why '.
            'in this assertion message before bumping the count.');
    }
}
