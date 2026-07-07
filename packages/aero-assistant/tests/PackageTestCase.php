<?php

declare(strict_types=1);

namespace Aero\Assistant\Tests;

use Aero\Assistant\Providers\AeonServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase;

abstract class PackageTestCase extends TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [AeonServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $sqlite = ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''];
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', $sqlite);
        $app['config']->set('database.connections.central', $sqlite);
        $app['config']->set('cache.default', 'array');
        $app['config']->set('session.driver', 'array');
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('aeon.provider', 'gemini');
        $app['config']->set('aeon.providers.gemini.api_key', 'test-key');
        $app['config']->set('aeon.providers.gemini.model', 'gemini-flash-latest');
        $app['config']->set('aeon.providers.gemini.endpoint', 'https://generativelanguage.googleapis.com/v1beta');
        $app['config']->set('aeon.providers.gemini.timeout', 30);
        $app['config']->set('aeon.providers.gemini.retry_base_ms', 0); // no real sleep in tests
    }
}
