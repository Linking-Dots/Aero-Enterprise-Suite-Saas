<?php

declare(strict_types=1);

namespace Aero\HRM\Tests\Feature\Performance;

use Aero\Core\AeroCoreServiceProvider;
use Aero\HRMAC\HRMACServiceProvider;
use Aero\HRM\AeroHrmServiceProvider;
use Aero\HRM\Models\Employee;
use Aero\HRM\Models\HrmPerformanceReview;
use Aero\HRM\Models\ReviewCycle;
use Aero\HRM\Models\ReviewTemplate;
use Aero\HRM\Services\Performance\ReviewCycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Orchestra\Testbench\TestCase;

class ReviewCycleTest extends TestCase
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
    // 1. Activating a cycle creates one HrmPerformanceReview per employee
    // -------------------------------------------------------------------------

    public function test_activating_cycle_creates_reviews_for_each_employee(): void
    {
        $this->grantAllPermissions();

        $template = ReviewTemplate::factory()->create();

        $employees = Employee::factory()->count(3)->create();
        $employeeIds = $employees->pluck('id')->all();

        $cycle = ReviewCycle::factory()->create([
            'template_id'  => $template->id,
            'status'       => ReviewCycle::STATUS_DRAFT,
            'employee_ids' => $employeeIds,
        ]);

        /** @var ReviewCycleService $service */
        $service = app(ReviewCycleService::class);
        $service->activate($cycle);

        $this->assertSame(
            ReviewCycle::STATUS_ACTIVE,
            $cycle->fresh()->status,
            'Cycle status must be "active" after activation.'
        );

        $this->assertSame(
            3,
            HrmPerformanceReview::where('cycle_id', $cycle->id)->count(),
            'One HrmPerformanceReview must be created per employee.'
        );
    }
}
