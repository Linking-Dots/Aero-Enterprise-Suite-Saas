<?php

declare(strict_types=1);

namespace Aero\HRM\Tests\Feature\Disciplinary;

use Aero\Core\AeroCoreServiceProvider;
use Aero\Core\Models\User;
use Aero\HRM\AeroHrmServiceProvider;
use Aero\HRM\Models\Employee;
use Aero\HRM\Models\HrmDisciplinaryActionType;
use Aero\HRM\Models\HrmDisciplinaryCase;
use Aero\HRM\Models\HrmGrievance;
use Aero\HRM\Models\HrmWarning;
use Aero\HRMAC\HRMACServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Inertia\ServiceProvider;
use Orchestra\Testbench\TestCase;

class DisciplinaryFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [ServiceProvider::class, AeroCoreServiceProvider::class, HRMACServiceProvider::class, AeroHrmServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
        $app['config']->set('cache.default', 'array');
        $app['config']->set('session.driver', 'array');
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('view.paths', [realpath(__DIR__.'/../../fixtures/views')]);
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

    // ─── Cases ────────────────────────────────────────────────────────────────

    public function test_admin_can_open_a_case(): void
    {
        $this->grantAllPermissions();
        $this->asUser();
        $type = HrmDisciplinaryActionType::factory()->create();
        $emp = Employee::factory()->create();

        $this->post(route('hrm.disciplinary.cases.store'), [
            'employee_id' => $emp->id,
            'action_type_id' => $type->id,
            'incident_date' => now()->subDay()->toDateString(),
            'subject' => 'Tardiness',
            'description' => 'Late 4 days in a row.',
        ])->assertRedirect();

        $this->assertDatabaseHas('hrm_disciplinary_cases', ['subject' => 'Tardiness', 'status' => 'open']);
        $this->assertDatabaseHas('hrm_disciplinary_case_events', ['type' => 'opened']);
    }

    public function test_case_can_be_closed_with_outcome(): void
    {
        $this->grantAllPermissions();
        $this->asUser();
        $case = HrmDisciplinaryCase::factory()->open()->create();

        $this->post(route('hrm.disciplinary.cases.close', $case), [
            'outcome' => 'written',
            'closure_notes' => 'Final written warning issued.',
        ])->assertRedirect();

        $this->assertDatabaseHas('hrm_disciplinary_cases', ['id' => $case->id, 'status' => 'closed', 'outcome' => 'written']);
    }

    public function test_closing_a_closed_case_fails(): void
    {
        $this->grantAllPermissions();
        $this->asUser();
        $case = HrmDisciplinaryCase::factory()->closed()->create();

        $this->post(route('hrm.disciplinary.cases.close', $case), [
            'outcome' => 'verbal', 'closure_notes' => 'x',
        ])->assertStatus(422);
    }

    // ─── Warnings ─────────────────────────────────────────────────────────────

    public function test_employee_can_acknowledge_own_warning(): void
    {
        $this->grantAllPermissions();
        $emp = Employee::factory()->create();
        $this->actingAs($emp->user);
        $w = HrmWarning::factory()->create(['employee_id' => $emp->id, 'status' => 'issued']);

        $this->post(route('hrm.disciplinary.warnings.acknowledge', $w), ['response' => 'Understood'])
            ->assertRedirect();

        $this->assertDatabaseHas('hrm_warnings', ['id' => $w->id, 'status' => 'acknowledged', 'employee_response' => 'Understood']);
    }

    public function test_warning_escalates_to_case_at_threshold(): void
    {
        $this->grantAllPermissions();
        $this->asUser();
        $type = HrmDisciplinaryActionType::factory()->withEscalation(3, 'Written Warning')->create(['name' => 'Verbal Warning']);
        $written = HrmDisciplinaryActionType::factory()->create(['name' => 'Written Warning']);
        $emp = Employee::factory()->create();

        HrmWarning::factory()->count(2)->create([
            'employee_id' => $emp->id,
            'action_type_id' => $type->id,
            'issued_at' => now()->subMonth(),
        ]);

        $this->post(route('hrm.disciplinary.warnings.store'), [
            'employee_id' => $emp->id,
            'action_type_id' => $type->id,
            'subject' => '3rd verbal',
            'body' => 'See attached.',
        ])->assertRedirect();

        $this->assertDatabaseHas('hrm_warnings', ['status' => 'escalated']);
        $this->assertDatabaseHas('hrm_disciplinary_cases', ['action_type_id' => $written->id]);
    }

    // ─── Grievances ───────────────────────────────────────────────────────────

    public function test_employee_can_file_grievance(): void
    {
        $this->grantAllPermissions();
        $emp = Employee::factory()->create();
        $this->actingAs($emp->user);

        $this->post(route('hrm.grievances.store'), [
            'category' => 'harassment',
            'subject' => 'Inappropriate comments',
            'description' => 'Multiple incidents last week.',
            'confidentiality' => 'confidential',
        ])->assertRedirect();

        $this->assertDatabaseHas('hrm_grievances', ['filed_by' => $emp->id, 'status' => 'filed', 'category' => 'harassment']);
    }

    public function test_grievance_can_be_assigned_and_resolved(): void
    {
        $this->grantAllPermissions();
        $this->asUser();
        $investigator = User::factory()->create();
        $g = HrmGrievance::factory()->create(['status' => 'filed']);

        $this->post(route('hrm.grievances.investigate', $g), ['investigator_id' => $investigator->id])->assertRedirect();
        $this->assertDatabaseHas('hrm_grievances', ['id' => $g->id, 'status' => 'under_investigation']);

        $this->post(route('hrm.grievances.resolve', $g), ['dismiss' => false, 'notes' => 'Mediation completed.'])->assertRedirect();
        $this->assertDatabaseHas('hrm_grievances', ['id' => $g->id, 'status' => 'resolved']);
    }

    public function test_grievance_dismissal_path(): void
    {
        $this->grantAllPermissions();
        $this->asUser();
        $g = HrmGrievance::factory()->underInvestigation()->create();

        $this->post(route('hrm.grievances.resolve', $g), ['dismiss' => true, 'notes' => 'No evidence found.'])->assertRedirect();
        $this->assertDatabaseHas('hrm_grievances', ['id' => $g->id, 'status' => 'dismissed']);
    }
}
