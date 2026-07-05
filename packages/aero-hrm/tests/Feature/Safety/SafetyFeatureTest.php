<?php

declare(strict_types=1);

namespace Aero\HRM\Tests\Feature\Safety;

use Aero\Core\AeroCoreServiceProvider;
use Aero\Core\Models\User;
use Aero\HRM\AeroHrmServiceProvider;
use Aero\HRM\Models\Employee;
use Aero\HRM\Models\HrmSafetyIncident;
use Aero\HRM\Models\HrmSafetyTraining;
use Aero\HRM\Models\HrmSafetyTrainingAssignment;
use Aero\HRMAC\HRMACServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Inertia\ServiceProvider;
use Orchestra\Testbench\TestCase;

class SafetyFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [\Aero\Auth\AeroAuthServiceProvider::class, ServiceProvider::class, AeroCoreServiceProvider::class, HRMACServiceProvider::class, AeroHrmServiceProvider::class];
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

    // ─── Incidents ────────────────────────────────────────────────────────────

    public function test_user_can_report_incident(): void
    {
        $this->grantAllPermissions();
        $this->asUser();

        $this->post(route('hrm.safety.incidents.store'), [
            'occurred_at' => now()->toDateTimeString(),
            'type' => 'injury',
            'severity' => 'high',
            'description' => 'Slip and fall in the warehouse.',
        ])->assertRedirect();

        $this->assertDatabaseHas('hrm_safety_incidents', ['type' => 'injury', 'severity' => 'high', 'status' => 'reported']);
    }

    public function test_can_add_investigation_to_incident(): void
    {
        $this->grantAllPermissions();
        $this->asUser();
        $incident = HrmSafetyIncident::factory()->create(['status' => 'reported']);

        $this->post(route('hrm.safety.incidents.investigate', $incident), [
            'root_cause' => 'Wet floor, no warning sign.',
            'corrective_action' => 'Install permanent caution signs.',
        ])->assertRedirect();

        $this->assertDatabaseHas('hrm_safety_incidents', ['id' => $incident->id, 'status' => 'investigating']);
        $this->assertDatabaseHas('hrm_safety_incident_investigations', ['incident_id' => $incident->id]);
    }

    public function test_can_close_incident(): void
    {
        $this->grantAllPermissions();
        $this->asUser();
        $incident = HrmSafetyIncident::factory()->create(['status' => 'investigating']);

        $this->post(route('hrm.safety.incidents.close', $incident))->assertRedirect();

        $this->assertDatabaseHas('hrm_safety_incidents', ['id' => $incident->id, 'status' => 'closed']);
    }

    public function test_closing_already_closed_incident_fails(): void
    {
        $this->grantAllPermissions();
        $this->asUser();
        $incident = HrmSafetyIncident::factory()->create(['status' => 'closed', 'closed_at' => now()]);

        $this->post(route('hrm.safety.incidents.close', $incident))->assertStatus(422);
    }

    public function test_index_filters_by_severity(): void
    {
        $this->grantAllPermissions();
        $this->asUser();
        HrmSafetyIncident::factory()->create(['severity' => 'critical']);
        HrmSafetyIncident::factory()->create(['severity' => 'low']);

        $this->get(route('hrm.safety.incidents.index', ['severity' => 'critical']))
            ->assertInertia(fn ($p) => $p->where('incidents.data.0.severity', 'critical')->has('incidents.data', 1));
    }

    // ─── Training ─────────────────────────────────────────────────────────────

    public function test_can_assign_training_to_employee(): void
    {
        $this->grantAllPermissions();
        $this->asUser();
        $training = HrmSafetyTraining::factory()->create();
        $employee = Employee::factory()->create();

        $this->post(route('hrm.safety.training.store'), [
            'training_id' => $training->id,
            'employee_id' => $employee->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('hrm_safety_training_assignments', [
            'training_id' => $training->id,
            'employee_id' => $employee->id,
            'status' => 'assigned',
        ]);
    }

    public function test_can_complete_training_assignment(): void
    {
        $this->grantAllPermissions();
        $this->asUser();
        $assignment = HrmSafetyTrainingAssignment::factory()->create(['status' => 'assigned']);

        $this->post(route('hrm.safety.training.complete', $assignment))->assertRedirect();

        $this->assertDatabaseHas('hrm_safety_training_assignments', ['id' => $assignment->id, 'status' => 'completed']);
    }
}
