<?php

declare(strict_types=1);

namespace Aero\HRM\Tests\Feature\Analytics;

use Aero\Core\AeroCoreServiceProvider;
use Aero\Core\Models\User;
use Aero\HRMAC\HRMACServiceProvider;
use Aero\HRMAC\Http\Middleware\CheckRoleModuleAccess;
use Aero\HRM\AeroHrmServiceProvider;
use Aero\HRM\Models\Department;
use Aero\HRM\Models\Employee;
use Aero\HRM\Models\PulseResponse;
use Aero\HRM\Models\PulseSurvey;
use Aero\HRM\Models\WorkforcePlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Orchestra\Testbench\TestCase;

class AnalyticsTest extends TestCase
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
     * Act as a user with all middleware disabled and Inertia headers set.
     * Use for routes WITHOUT route model binding (index, store).
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
     * Disables only HRMAC middleware; auth middleware still runs.
     * Use for routes with a {model} route parameter (show, results, send, etc.).
     */
    private function asUserWithBinding(?User $user = null)
    {
        $user ??= User::factory()->create(['email_verified_at' => now()]);

        return $this->actingAs($user)
            ->withoutMiddleware([CheckRoleModuleAccess::class])
            ->withHeaders(['X-Inertia' => 'true', 'X-Inertia-Version' => '1']);
    }

    // -------------------------------------------------------------------------
    // 1. Dashboard returns KPIs with active employee count
    // -------------------------------------------------------------------------

    public function test_dashboard_returns_kpis(): void
    {
        $this->grantAllPermissions();

        Employee::factory()->count(5)->create(['status' => 'active']);

        $response = $this->asUser()->get(route('hrm.analytics.dashboard'));

        $response->assertOk();

        $json = $response->json();
        $this->assertEquals('HRM/Analytics/Dashboard', $json['component'] ?? null);

        $kpis = $json['props']['kpis'] ?? [];
        $this->assertArrayHasKey('active', $kpis);
        $this->assertGreaterThanOrEqual(5, $kpis['active']);
    }

    // -------------------------------------------------------------------------
    // 2. Admin can create a pulse survey
    // -------------------------------------------------------------------------

    public function test_admin_can_create_pulse_survey(): void
    {
        $this->grantAllPermissions();

        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->asUser($user)->post(route('hrm.analytics.pulse-surveys.store'), [
            'title'     => 'May Pulse',
            'questions' => [
                ['id' => 'q1', 'type' => 'scale', 'text' => 'How happy?'],
            ],
            'anonymous' => true,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('hrm_pulse_surveys', [
            'title' => 'May Pulse',
        ]);
    }

    // -------------------------------------------------------------------------
    // 3. Results suppressed when response count below threshold (< 5)
    // -------------------------------------------------------------------------

    public function test_results_suppressed_below_threshold(): void
    {
        $this->grantAllPermissions();

        $user   = User::factory()->create(['email_verified_at' => now()]);
        $survey = PulseSurvey::create([
            'title'      => 'Test Survey',
            'questions'  => [['id' => 'q1', 'type' => 'scale', 'text' => 'Score']],
            'status'     => PulseSurvey::STATUS_ACTIVE,
            'anonymous'  => true,
            'created_by' => $user->id,
        ]);

        // Create 3 responses — below the suppression threshold of 5
        foreach (range(1, 3) as $i) {
            PulseResponse::create([
                'survey_id'        => $survey->id,
                'respondent_hash'  => hash('sha256', "survey:{$survey->id}:respondent:{$i}"),
                'answers'          => [['question_id' => 'q1', 'value' => 4]],
                'submitted_at'     => now(),
            ]);
        }

        $response = $this->asUserWithBinding($user)->get(route('hrm.analytics.pulse-surveys.results', $survey));

        $response->assertOk();

        $json = $response->json();
        $this->assertEquals('HRM/Analytics/PulseSurveys/Results', $json['component'] ?? null);
        $this->assertTrue($json['props']['suppressed'] ?? false, 'Expected suppressed === true with 3 responses');
    }

    // -------------------------------------------------------------------------
    // 4. Results available when threshold met (>= 5)
    // -------------------------------------------------------------------------

    public function test_results_available_when_threshold_met(): void
    {
        $this->grantAllPermissions();

        $user   = User::factory()->create(['email_verified_at' => now()]);
        $survey = PulseSurvey::create([
            'title'      => 'Threshold Survey',
            'questions'  => [['id' => 'q1', 'type' => 'scale', 'text' => 'Score']],
            'status'     => PulseSurvey::STATUS_ACTIVE,
            'anonymous'  => true,
            'created_by' => $user->id,
        ]);

        // Create 6 responses — meets threshold
        foreach (range(1, 6) as $i) {
            PulseResponse::create([
                'survey_id'        => $survey->id,
                'respondent_hash'  => hash('sha256', "t4-survey:{$survey->id}:r:{$i}"),
                'answers'          => [['question_id' => 'q1', 'value' => 5]],
                'submitted_at'     => now(),
            ]);
        }

        // Verify 6 responses are actually in DB before the HTTP call
        $this->assertSame(6, $survey->responses()->count(), 'Expected 6 pulse responses in DB');

        $response = $this->asUserWithBinding($user)->get(route('hrm.analytics.pulse-surveys.results', $survey));

        $response->assertOk();

        $json  = $response->json();
        $props = $json['props'] ?? [];

        $this->assertFalse($props['suppressed'] ?? true, 'Expected suppressed === false with 6 responses');
        $this->assertArrayHasKey('aggregates', $props);
        $this->assertNotNull($props['aggregates']);
        $this->assertArrayHasKey('q1', $props['aggregates']);
    }

    // -------------------------------------------------------------------------
    // 5. Workforce planning returns actual vs target rows
    // -------------------------------------------------------------------------

    public function test_workforce_planning_returns_actual_vs_target(): void
    {
        $this->grantAllPermissions();

        $dept = Department::factory()->create();

        // 7 active employees in that department
        Employee::factory()->count(7)->create([
            'status'        => 'active',
            'department_id' => $dept->id,
        ]);

        $user = User::factory()->create(['email_verified_at' => now()]);

        // Create workforce plan for FY 2026
        WorkforcePlan::create([
            'fiscal_year'      => 2026,
            'department_id'    => $dept->id,
            'target_headcount' => 10,
            'target_hires'     => 3,
            'target_attrition' => 0,
            'updated_by'       => $user->id,
        ]);

        $response = $this->asUser($user)->get(
            route('hrm.analytics.workforce-planning.index', ['fiscal_year' => 2026])
        );

        $response->assertOk();

        $json  = $response->json();
        $props = $json['props'] ?? [];

        $this->assertEquals('HRM/Analytics/WorkforcePlanning/Index', $json['component'] ?? null);
        $this->assertArrayHasKey('plans', $props);

        // Find the row matching our department
        $plans = collect($props['plans']);
        $row   = $plans->firstWhere('department_id', $dept->id);

        $this->assertNotNull($row, "Expected a plan row for department_id={$dept->id}");
        $this->assertEquals(10, $row['target_headcount']);
        $this->assertEquals(7, $row['actual_headcount']);
    }
}
