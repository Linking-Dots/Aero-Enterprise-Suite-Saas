<?php

declare(strict_types=1);

namespace Aero\HRM\Tests\Feature\Performance;

use Aero\Core\AeroCoreServiceProvider;
use Aero\HRMAC\HRMACServiceProvider;
use Aero\HRM\AeroHrmServiceProvider;
use Aero\HRM\Models\Goal;
use Aero\HRM\Services\Performance\GoalLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Orchestra\Testbench\TestCase;

class GoalLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            \Inertia\ServiceProvider::class,
            AeroCoreServiceProvider::class,
            HRMACServiceProvider::class,
            AeroHrmServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
        $app['config']->set('cache.default', 'array');
        $app['config']->set('session.driver', 'array');
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('view.paths', [
            realpath(__DIR__.'/../../fixtures/views'),
        ]);
    }

    protected function defineRoutes($router): void
    {
        $router->get('/login', fn () => response('login'))->name('login');
    }

    private function grantAllPermissions(): void
    {
        Gate::before(fn () => true);
    }

    // -------------------------------------------------------------------------
    // 1. Closing a goal as achieved sets progress to 100
    // -------------------------------------------------------------------------

    public function test_closing_goal_as_achieved_sets_progress_to_100(): void
    {
        $this->grantAllPermissions();

        $goal = Goal::factory()->create([
            'status'   => Goal::STATUS_IN_PROGRESS,
            'progress' => 60,
        ]);

        /** @var GoalLifecycleService $service */
        $service = app(GoalLifecycleService::class);
        $service->close($goal, 'achieved');

        $fresh = $goal->fresh();

        $this->assertSame(
            Goal::STATUS_ACHIEVED,
            $fresh->status,
            'Status must be "achieved" after closing as achieved.'
        );

        $this->assertSame(
            100,
            $fresh->progress,
            'Progress must be 100 when closed as achieved.'
        );

        $this->assertNotNull(
            $fresh->closed_at,
            'closed_at must be set after closing.'
        );
    }

    // -------------------------------------------------------------------------
    // 2. Closing a goal as missed preserves the existing progress
    // -------------------------------------------------------------------------

    public function test_closing_goal_as_missed_preserves_progress(): void
    {
        $this->grantAllPermissions();

        $goal = Goal::factory()->create([
            'status'   => Goal::STATUS_IN_PROGRESS,
            'progress' => 40,
        ]);

        /** @var GoalLifecycleService $service */
        $service = app(GoalLifecycleService::class);
        $service->close($goal, 'missed');

        $fresh = $goal->fresh();

        $this->assertSame(
            Goal::STATUS_MISSED,
            $fresh->status,
            'Status must be "missed" after closing as missed.'
        );

        $this->assertSame(
            40,
            $fresh->progress,
            'Progress must remain unchanged (40) when closed as missed.'
        );

        $this->assertNotNull(
            $fresh->closed_at,
            'closed_at must be set after closing.'
        );
    }
}
