<?php

declare(strict_types=1);

namespace Aero\HRM\Tests\Feature\Benefits;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\AeroCoreServiceProvider;
use Aero\Core\Models\User;
use Aero\HRM\AeroHrmServiceProvider;
use Aero\HRM\Models\Employee;
use Aero\HRM\Models\HrmBenefit;
use Aero\HRM\Models\HrmBenefitEnrollment;
use Aero\HRM\Models\HrmBenefitEnrollmentPeriod;
use Aero\HRM\Services\Benefits\EligibilityService;
use Aero\HRMAC\HRMACServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Inertia\ServiceProvider;
use Orchestra\Testbench\TestCase;

class BenefitsFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            ServiceProvider::class,
            AeroCoreServiceProvider::class,
            HRMACServiceProvider::class,
            AeroHrmServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
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

    private function asUser(): static
    {
        $user = User::factory()->create();
        Employee::factory()->create(['user_id' => $user->id]);

        return $this->actingAs($user);
    }

    // ─── Catalog ──────────────────────────────────────────────────────────────

    public function test_admin_can_create_benefit(): void
    {
        $this->grantAllPermissions();
        $this->asUser();

        $this->post(route('hrm.benefits.catalog.store'), [
            'code' => 'HEALTH_PPO',
            'name' => 'Health PPO',
            'category' => 'health',
            'employee_cost' => 120.00,
            'employer_cost' => 380.00,
            'frequency' => 'monthly',
            'allows_dependents' => true,
            'dependent_cost' => 60,
        ])->assertRedirect(route('hrm.benefits.catalog.index'));

        $this->assertDatabaseHas('hrm_benefits', ['code' => 'HEALTH_PPO']);
    }

    public function test_cannot_delete_benefit_with_enrollments(): void
    {
        $this->grantAllPermissions();
        $this->asUser();

        $benefit = HrmBenefit::factory()->create();
        HrmBenefitEnrollment::factory()->create(['benefit_id' => $benefit->id]);

        $this->delete(route('hrm.benefits.catalog.destroy', $benefit))
            ->assertStatus(422);
    }

    // ─── Enrollment Periods ───────────────────────────────────────────────────

    public function test_period_cannot_activate_without_benefits(): void
    {
        $this->grantAllPermissions();
        $this->asUser();

        $period = HrmBenefitEnrollmentPeriod::factory()->draft()->create();

        $this->post(route('hrm.benefits.enrollment-periods.activate', $period))
            ->assertStatus(422);
    }

    public function test_period_activation_logs_audit(): void
    {
        $audit = $this->spy(AuditServiceInterface::class);
        $this->grantAllPermissions();
        $this->asUser();

        $benefit = HrmBenefit::factory()->create();
        $period = HrmBenefitEnrollmentPeriod::factory()->draft()->create();
        $period->benefits()->attach($benefit);

        $this->post(route('hrm.benefits.enrollment-periods.activate', $period))
            ->assertRedirect();

        $audit->shouldHaveReceived('log')->withArgs(fn (...$args) => true);
    }

    // ─── Open Enrollment ──────────────────────────────────────────────────────

    public function test_employee_can_enroll_in_benefits(): void
    {
        $this->grantAllPermissions();

        $employee = Employee::factory()->create();
        $this->actingAs($employee->user);

        $period = HrmBenefitEnrollmentPeriod::factory()->active()->create();
        $benefit = HrmBenefit::factory()->withDependents(50)->create();
        $period->benefits()->attach($benefit);

        $this->post(route('hrm.benefits.open-enrollment.enroll'), [
            'period_id' => $period->id,
            'elections' => [
                ['benefit_id' => $benefit->id, 'status' => 'enrolled', 'dependents_count' => 2],
            ],
        ])->assertRedirect(route('hrm.benefits.open-enrollment.index'));

        $this->assertDatabaseHas('hrm_benefit_enrollments', [
            'employee_id' => $employee->id,
            'benefit_id' => $benefit->id,
            'status' => 'enrolled',
            'dependents_count' => 2,
        ]);
    }

    public function test_employee_can_waive_with_reason(): void
    {
        $this->grantAllPermissions();

        $employee = Employee::factory()->create();
        $this->actingAs($employee->user);

        $period = HrmBenefitEnrollmentPeriod::factory()->active()->create();
        $benefit = HrmBenefit::factory()->create();
        $period->benefits()->attach($benefit);

        $this->post(route('hrm.benefits.open-enrollment.enroll'), [
            'period_id' => $period->id,
            'elections' => [
                ['benefit_id' => $benefit->id, 'status' => 'waived', 'waiver_reason' => 'Spouse coverage'],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('hrm_benefit_enrollments', [
            'status' => 'waived',
            'waiver_reason' => 'Spouse coverage',
        ]);
    }

    public function test_enrollment_blocked_outside_window(): void
    {
        $this->grantAllPermissions();

        $employee = Employee::factory()->create();
        $this->actingAs($employee->user);

        $period = HrmBenefitEnrollmentPeriod::factory()->create([
            'status' => 'active',
            'starts_at' => now()->subDays(20),
            'ends_at' => now()->subDay(),
        ]);

        $this->post(route('hrm.benefits.open-enrollment.enroll'), [
            'period_id' => $period->id,
            'elections' => [],
        ])->assertStatus(422);
    }

    // ─── EligibilityService unit ──────────────────────────────────────────────

    public function test_min_tenure_rule_blocks_new_employee(): void
    {
        $employee = Employee::factory()->make(['date_of_joining' => now()->subDays(10)]);
        $benefit = HrmBenefit::factory()->make([
            'active' => true,
            'eligibility_rules' => ['min_tenure_days' => 90],
        ]);

        $this->assertFalse(app(EligibilityService::class)->isEligible($employee, $benefit, now()));
    }

    public function test_min_tenure_rule_allows_tenured_employee(): void
    {
        $employee = Employee::factory()->make(['date_of_joining' => now()->subDays(100)]);
        $benefit = HrmBenefit::factory()->make([
            'active' => true,
            'eligibility_rules' => ['min_tenure_days' => 90],
        ]);

        $this->assertTrue(app(EligibilityService::class)->isEligible($employee, $benefit, now()));
    }
}
