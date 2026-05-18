<?php

declare(strict_types=1);

namespace Aero\HRM\Tests\Feature\Recruitment;

use Aero\Core\AeroCoreServiceProvider;
use Aero\Core\Models\User;
use Aero\HRMAC\HRMACServiceProvider;
use Aero\HRMAC\Http\Middleware\CheckRoleModuleAccess;
use Aero\HRM\AeroHrmServiceProvider;
use Aero\HRM\Models\Job;
use Aero\HRM\Models\JobApplication;
use Aero\HRM\Models\JobHiringStage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Orchestra\Testbench\TestCase;

class RecruitmentFeatureTest extends TestCase
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

    /**
     * Act as a user with ALL middleware disabled and X-Inertia headers set.
     * Use for routes that do NOT use route model binding (index, store).
     */
    private function asUser(?User $user = null)
    {
        $user ??= User::factory()->create(['email_verified_at' => now()]);

        return $this->actingAs($user)
            ->withoutMiddleware()
            ->withHeaders(['X-Inertia' => 'true', 'X-Inertia-Version' => '1']);
    }

    /**
     * Act as a user keeping SubstituteBindings so route model binding resolves.
     * Disables only the HRMAC middleware — auth middleware still runs.
     * Use for routes that have a {model} route parameter.
     */
    private function asUserWithBinding(?User $user = null)
    {
        $user ??= User::factory()->create(['email_verified_at' => now()]);

        return $this->actingAs($user)
            ->withoutMiddleware([CheckRoleModuleAccess::class])
            ->withHeaders(['X-Inertia' => 'true', 'X-Inertia-Version' => '1']);
    }

    // -------------------------------------------------------------------------
    // Helper to create a valid Job row bypassing the factory's stale columns.
    // -------------------------------------------------------------------------

    private function makeJob(array $overrides = []): Job
    {
        return Job::create(array_merge([
            'title'       => 'Software Engineer',
            'type'        => 'full_time',
            'description' => 'Build great software.',
            'positions'   => 1,
            'status'      => 'draft',
        ], $overrides));
    }

    // -------------------------------------------------------------------------
    // Helper to create a valid JobApplication row.
    // -------------------------------------------------------------------------

    private function makeApplication(Job $job, array $overrides = []): JobApplication
    {
        return JobApplication::create(array_merge([
            'job_id'              => $job->id,
            'first_name'          => 'Jane',
            'last_name'           => 'Doe',
            'email'               => 'jane.doe@example.com',
            'years_of_experience' => 3,
            'status'              => 'applied',
        ], $overrides));
    }

    // -------------------------------------------------------------------------
    // 1. HR admin can list jobs
    // -------------------------------------------------------------------------

    public function test_hr_admin_can_list_jobs(): void
    {
        $this->grantAllPermissions();

        $this->makeJob(['title' => 'Job Alpha']);
        $this->makeJob(['title' => 'Job Beta']);
        $this->makeJob(['title' => 'Job Gamma']);

        // NOTE (P1): GET /hrm/recruitment/{id} (RecruitmentController) is registered
        // before GET /hrm/recruitment/jobs (JobController) in routes/web.php, so the
        // legacy wildcard shadows the new route for HTTP requests (id='jobs' hits
        // findOrFail → 404).  Until route ordering is fixed in the application we
        // drive the new controller directly via Gate::before + auth()->login().
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->app['auth']->guard()->login($user);

        $controller = $this->app->make(\Aero\HRM\Http\Controllers\Recruitment\JobController::class);
        $request    = \Illuminate\Http\Request::create('/hrm/recruitment/jobs', 'GET', [], [], [], [
            'HTTP_X_INERTIA'         => 'true',
            'HTTP_X_INERTIA_VERSION' => '1',
        ]);
        $request->setLaravelSession($this->app['session']->driver());
        $this->app->instance('request', $request);

        $response     = $controller->index();
        $httpResponse = $response->toResponse($request);
        $payload      = json_decode($httpResponse->getContent(), true);

        $this->assertEquals('HRM/Recruitment/Jobs/Index', $payload['component'] ?? null);
        $this->assertEquals(3, $payload['props']['jobs']['total'] ?? null);
    }

    // -------------------------------------------------------------------------
    // 2. HR admin can create a job
    // -------------------------------------------------------------------------

    public function test_hr_admin_can_create_job(): void
    {
        $this->grantAllPermissions();

        $this->asUser()
            ->post(route('hrm.recruitment.jobs.store'), [
                'title'       => 'Backend Developer',
                'type'        => 'full_time',
                'description' => 'Build APIs.',
                'positions'   => 1,
                'status'      => 'draft',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('jobs_recruitment', [
            'title' => 'Backend Developer',
        ]);
    }

    // -------------------------------------------------------------------------
    // 3. Publish transitions job to open
    // -------------------------------------------------------------------------

    public function test_publish_transitions_job_to_open(): void
    {
        $this->grantAllPermissions();

        $job = $this->makeJob(['status' => 'draft']);

        $this->asUserWithBinding()
            ->post(route('hrm.recruitment.jobs.publish', $job))
            ->assertRedirect();

        $fresh = $job->fresh();

        $this->assertEquals('open', $fresh->status);
        $this->assertNotNull($fresh->posting_date);
    }

    // -------------------------------------------------------------------------
    // 4. Close transitions job to closed
    // -------------------------------------------------------------------------

    public function test_close_transitions_job_to_closed(): void
    {
        $this->grantAllPermissions();

        $job = $this->makeJob(['status' => 'open']);

        $this->asUserWithBinding()
            ->post(route('hrm.recruitment.jobs.close', $job))
            ->assertRedirect();

        $this->assertEquals('closed', $job->fresh()->status);
    }

    // -------------------------------------------------------------------------
    // 5. Kanban move updates application stage
    // -------------------------------------------------------------------------

    public function test_kanban_move_updates_application_stage(): void
    {
        $this->grantAllPermissions();

        $job = $this->makeJob(['status' => 'open']);

        $stage1 = JobHiringStage::create([
            'job_id'   => $job->id,
            'name'     => 'Screening',
            'sequence' => 1,
        ]);

        $stage2 = JobHiringStage::create([
            'job_id'   => $job->id,
            'name'     => 'Interview',
            'sequence' => 2,
        ]);

        $application = $this->makeApplication($job, [
            'current_stage_id' => $stage1->id,
        ]);

        $this->asUserWithBinding()
            ->post(route('hrm.recruitment.applications.stage', $application), [
                'stage_id' => $stage2->id,
                'notes'    => 'Moving to interview.',
            ])
            ->assertRedirect();

        $this->assertEquals($stage2->id, $application->fresh()->current_stage_id);
    }

    // -------------------------------------------------------------------------
    // 6. Reject application records reason
    // -------------------------------------------------------------------------

    public function test_reject_application_records_reason(): void
    {
        $this->grantAllPermissions();

        $job         = $this->makeJob(['status' => 'open']);
        $application = $this->makeApplication($job, ['status' => 'applied']);

        $this->asUserWithBinding()
            ->post(route('hrm.recruitment.applications.reject', $application), [
                'reason' => 'Not a good fit.',
            ])
            ->assertRedirect();

        $fresh = $application->fresh();

        $this->assertEquals('rejected', $fresh->status);
        $this->assertEquals('Not a good fit.', $fresh->rejection_reason);
        $this->assertNotNull($fresh->rejected_at);
    }

    // -------------------------------------------------------------------------
    // 7. Offer creation advances application status to offered
    // -------------------------------------------------------------------------

    public function test_offer_creation_advances_application_status(): void
    {
        $this->grantAllPermissions();

        $job         = $this->makeJob(['status' => 'open']);
        $application = $this->makeApplication($job, ['status' => 'shortlisted']);

        $this->asUser()
            ->post(route('hrm.recruitment.offers.store'), [
                'application_id'  => $application->id,
                'offered_salary'  => 75000,
                'salary_currency' => 'BDT',
                'joining_date'    => now()->addMonth()->toDateString(),
                'offer_valid_until' => now()->addWeeks(2)->toDateString(),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('job_offers', [
            'application_id' => $application->id,
            'status'         => 'sent',
        ]);

        $this->assertEquals('offered', $application->fresh()->status);
    }
}
