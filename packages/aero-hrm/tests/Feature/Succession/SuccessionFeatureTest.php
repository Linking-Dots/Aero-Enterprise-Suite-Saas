<?php

declare(strict_types=1);

namespace Aero\HRM\Tests\Feature\Succession;

use Aero\Core\AeroCoreServiceProvider;
use Aero\Core\Models\User;
use Aero\HRM\AeroHrmServiceProvider;
use Aero\HRM\Models\Designation;
use Aero\HRM\Models\Employee;
use Aero\HRM\Models\HrmCareerPath;
use Aero\HRM\Models\HrmSuccessionCandidate;
use Aero\HRM\Models\HrmTalentPoolMember;
use Aero\HRMAC\HRMACServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Inertia\ServiceProvider;
use Orchestra\Testbench\TestCase;

class SuccessionFeatureTest extends TestCase
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

    public function test_can_create_career_path_with_milestones(): void
    {
        $this->grantAllPermissions();
        $this->asUser();

        $this->post(route('hrm.career-paths.store'), [
            'name' => 'Senior Engineer Track',
            'milestones' => [
                ['name' => 'Mentor 1 junior'],
                ['name' => 'Lead a project'],
            ],
        ])->assertRedirect();

        $path = HrmCareerPath::firstWhere('name', 'Senior Engineer Track');
        $this->assertNotNull($path);
        $this->assertCount(2, $path->milestones);
    }

    public function test_can_nominate_succession_candidate(): void
    {
        $this->grantAllPermissions();
        $this->asUser();
        $role = Designation::factory()->create();
        $emp = Employee::factory()->create();

        $this->post(route('hrm.succession-planning.candidates.store'), [
            'role_id' => $role->id,
            'employee_id' => $emp->id,
            'readiness' => 'ready_now',
        ])->assertRedirect();

        $this->assertDatabaseHas('hrm_succession_candidates', [
            'role_id' => $role->id,
            'employee_id' => $emp->id,
        ]);
    }

    public function test_cannot_nominate_same_employee_twice_for_same_role(): void
    {
        $this->grantAllPermissions();
        $this->asUser();
        $role = Designation::factory()->create();
        $emp = Employee::factory()->create();

        HrmSuccessionCandidate::factory()->create(['role_id' => $role->id, 'employee_id' => $emp->id]);

        $this->post(route('hrm.succession-planning.candidates.store'), [
            'role_id' => $role->id,
            'employee_id' => $emp->id,
            'readiness' => 'ready_now',
        ])->assertSessionHasErrors();
    }

    public function test_can_add_and_remove_talent_pool_member(): void
    {
        $this->grantAllPermissions();
        $this->asUser();
        $emp = Employee::factory()->create();

        $this->post(route('hrm.succession-planning.talent-pool.add'), [
            'employee_id' => $emp->id,
            'tier' => 'high_potential',
        ])->assertRedirect();

        $this->assertDatabaseHas('hrm_talent_pool_members', ['employee_id' => $emp->id]);

        $member = HrmTalentPoolMember::where('employee_id', $emp->id)->first();
        $this->delete(route('hrm.succession-planning.talent-pool.remove', $member))->assertRedirect();
        $this->assertDatabaseMissing('hrm_talent_pool_members', ['id' => $member->id]);
    }
}
