<?php

namespace Aero\HRM\Tests;

use Aero\HRM\AeroHrmServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Run migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app)
    {
        return [
            \Aero\Auth\AeroAuthServiceProvider::class,
            AeroHrmServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Set other test configurations
        $app['config']->set('cache.default', 'array');
        $app['config']->set('queue.default', 'sync');
        $app['config']->set('session.driver', 'array');
    }

    /**
     * Helper to create authenticated employee
     */
    protected function actingAsEmployee($attributes = [])
    {
        $employee = \Aero\HRM\Models\Employee::factory()->create($attributes);

        return $this->actingAs($employee);
    }

    /**
     * Helper to create authenticated HR admin
     */
    protected function actingAsHRAdmin()
    {
        return $this->actingAsEmployee(['is_hr_admin' => true]);
    }

    /**
     * Helper to create authenticated manager
     */
    protected function actingAsManager()
    {
        return $this->actingAsEmployee(['is_manager' => true]);
    }
}
