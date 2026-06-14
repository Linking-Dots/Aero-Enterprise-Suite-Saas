<?php

declare(strict_types=1);

namespace Aero\HRMAC\Tests;

use Aero\HRMAC\HRMACServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase;

/**
 * Shared base test case for aero-hrmac package tests.
 *
 * - Boots HRMACServiceProvider in isolation (no aero-core / aero-platform), proving
 *   the package is pure-shareable: its models + migrations stand on their own.
 * - Uses in-memory SQLite; HRMAC's own migrations build the full schema
 *   (modules, sub_modules, module_components, module_component_actions,
 *   role_module_access, roles, model_has_roles, hrmac_audit_log).
 */
abstract class PackageTestCase extends TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            HRMACServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $sqliteConfig = [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ];

        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', $sqliteConfig);
        // Map 'central' to the same in-memory DB so any central-scoped lookups resolve.
        $app['config']->set('database.connections.central', $sqliteConfig);

        $app['config']->set('cache.default', 'array');
        $app['config']->set('session.driver', 'array');
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
    }
}
