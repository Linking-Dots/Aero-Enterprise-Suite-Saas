<?php

declare(strict_types=1);

namespace Aero\HRM\Tests\Feature\Performance;

use Aero\Core\AeroCoreServiceProvider;
use Aero\HRMAC\HRMACServiceProvider;
use Aero\HRM\AeroHrmServiceProvider;
use Aero\HRM\Exceptions\PerformanceFinalizedException;
use Aero\HRM\Models\HrmPerformanceReview;
use Aero\HRM\Services\Performance\ReviewSubmissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Orchestra\Testbench\TestCase;

class ReviewSubmissionTest extends TestCase
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
    // 1. Self submission transitions status to self_submitted
    // -------------------------------------------------------------------------

    public function test_self_submission_transitions_status(): void
    {
        $this->grantAllPermissions();

        $review = HrmPerformanceReview::factory()->draft()->create();

        /** @var ReviewSubmissionService $service */
        $service = app(ReviewSubmissionService::class);
        $service->submitSelf($review, ['0-0' => 4]);

        $fresh = $review->fresh();

        $this->assertSame(
            HrmPerformanceReview::STATUS_SELF_SUBMITTED,
            $fresh->status,
            'Status must be "self_submitted" after self-submission.'
        );
    }

    // -------------------------------------------------------------------------
    // 2. Finalize sets finalized_at and transitions status to finalized
    // -------------------------------------------------------------------------

    public function test_finalize_sets_finalized_at(): void
    {
        $this->grantAllPermissions();

        $review = HrmPerformanceReview::factory()->managerSubmitted()->create();

        /** @var ReviewSubmissionService $service */
        $service = app(ReviewSubmissionService::class);
        $service->finalize($review, 4.2, 'Great performance.');

        $fresh = $review->fresh();

        $this->assertSame(
            HrmPerformanceReview::STATUS_FINALIZED,
            $fresh->status,
            'Status must be "finalized" after finalization.'
        );

        $this->assertNotNull(
            $fresh->finalized_at,
            'finalized_at must be set after finalization.'
        );
    }

    // -------------------------------------------------------------------------
    // 3. Cannot update a finalized review — throws PerformanceFinalizedException
    // -------------------------------------------------------------------------

    public function test_cannot_update_finalized_review(): void
    {
        $this->grantAllPermissions();

        $review = HrmPerformanceReview::factory()->finalized()->create();

        $this->expectException(PerformanceFinalizedException::class);

        $review->update(['final_comment' => 'changed']);
    }
}
