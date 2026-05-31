<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Unit;

use Tests\TestCase;

/**
 * Audit D5a + D5c — tenancy config regression pins.
 *
 * D5a: S3 must be listed as a tenancy-bootstrapped disk so per-tenant
 *      data is namespaced. Without this, a feature using disk('s3')
 *      writes objects to a shared bucket root with no tenant isolation.
 *
 * D5c: The QueueTenancyBootstrapper used MUST be the fail-closed wrapper.
 *      Stock Stancl bootstrapper happily runs jobs for tenants in a
 *      non-runnable state (suspended, cancelled, archived, failed).
 */
class TenancyConfigTest extends TestCase
{
    private static function config(): array
    {
        return require dirname(__DIR__, 2).'/config/tenancy.php';
    }

    public function test_s3_disk_is_tenancy_bootstrapped(): void
    {
        $cfg = self::config();

        $this->assertContains('s3', $cfg['filesystem']['disks'],
            "S3 must be in tenancy.filesystem.disks (Audit D5a) so the FilesystemTenancyBootstrapper ".
            'namespaces tenant data. Without this, disk(\'s3\') is shared across tenants.');
    }

    public function test_s3_strategy_config_is_present(): void
    {
        $cfg = self::config();

        $this->assertArrayHasKey('s3_strategy', $cfg,
            "tenancy.s3_strategy must be declared (Audit D5a). Acceptable values: 'prefix' or 'bucket'.");

        $this->assertContains($cfg['s3_strategy'], ['prefix', 'bucket'],
            'tenancy.s3_strategy must be one of: prefix, bucket.');
    }

    public function test_queue_bootstrapper_is_fail_closed(): void
    {
        $cfg = self::config();

        $bootstrappers = $cfg['bootstrappers'];

        $this->assertContains(
            \Aero\Platform\Bootstrappers\FailClosedQueueTenancyBootstrapper::class,
            $bootstrappers,
            'tenancy.bootstrappers must use FailClosedQueueTenancyBootstrapper (Audit D5c) '.
            'not the stock Stancl QueueTenancyBootstrapper. Stock blindly bootstraps any '.
            'tenant whose ID appears in a job payload, even if the tenant is suspended or deleted.'
        );

        $this->assertNotContains(
            \Stancl\Tenancy\Bootstrappers\QueueTenancyBootstrapper::class,
            $bootstrappers,
            'Stock QueueTenancyBootstrapper must NOT be listed — superseded by '.
            'FailClosedQueueTenancyBootstrapper (which extends it and adds the status guard).'
        );
    }

    public function test_fail_closed_bootstrapper_extends_stock(): void
    {
        $this->assertTrue(
            is_subclass_of(
                \Aero\Platform\Bootstrappers\FailClosedQueueTenancyBootstrapper::class,
                \Stancl\Tenancy\Bootstrappers\QueueTenancyBootstrapper::class,
            ),
            'FailClosedQueueTenancyBootstrapper must extend the stock bootstrapper so it inherits the rest of the queue tenancy lifecycle.'
        );
    }
}
