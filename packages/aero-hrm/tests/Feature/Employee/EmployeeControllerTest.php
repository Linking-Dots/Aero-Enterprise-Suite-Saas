<?php

declare(strict_types=1);

namespace Aero\HRM\Tests\Feature\Employee;

use Aero\Core\AeroCoreServiceProvider;
use Aero\Core\Models\User;
use Aero\HRMAC\HRMACServiceProvider;
use Aero\HRM\AeroHrmServiceProvider;
use Aero\HRM\Models\Department;
use Aero\HRM\Models\Designation;
use Aero\HRM\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use Orchestra\Testbench\TestCase;

class EmployeeControllerTest extends TestCase
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
        // Point view paths to our test fixture that has app.blade.php for Inertia
        $app['config']->set('view.paths', [
            realpath(__DIR__.'/../../fixtures/views'),
        ]);
    }

    protected function defineRoutes($router): void
    {
        // Stub login route — required by Authenticate middleware redirect
        $router->get('/login', fn () => response('login'))->name('login');
    }

    protected function setUp(): void
    {
        parent::setUp();
    }

    // Grant all Gate checks — call in tests that don't need per-permission logic
    private function grantAllPermissions(): void
    {
        Gate::before(fn () => true);
    }

    // Act as a user with all middleware disabled and X-Inertia header set.
    private function asUser(?User $user = null)
    {
        $user ??= User::factory()->create(['email_verified_at' => now()]);
        return $this->actingAs($user)
            ->withoutMiddleware()
            ->withHeaders(['X-Inertia' => 'true', 'X-Inertia-Version' => '1']);
    }

    public function test_index_requires_authentication(): void
    {
        // Without actingAs, the auth middleware redirects to /login
        $this->withoutMiddleware(['hrmac'])
            ->get(route('hrm.employees.index'))
            ->assertRedirect(route('login'));
    }

    public function test_index_renders_employee_list(): void
    {
        $this->grantAllPermissions();
        Employee::factory()->count(3)->create();

        $response = $this->asUser()->get(route('hrm.employees.index'));

        $response->assertOk();
        // Inertia returns JSON when X-Inertia header is present
        $json = $response->json();
        $this->assertEquals('HRM/Employees/Index', $json['component'] ?? null);
        $this->assertArrayHasKey('employees', $json['props'] ?? []);
        $this->assertArrayHasKey('filters', $json['props'] ?? []);
    }

    public function test_store_validates_required_fields(): void
    {
        $this->grantAllPermissions();
        $this->asUser()
            ->post(route('hrm.employees.store'), [])
            ->assertSessionHasErrors(['employee_code', 'date_of_joining', 'employment_type', 'status', 'basic_salary']);
    }

    public function test_store_creates_employee_and_redirects(): void
    {
        $this->grantAllPermissions();
        $newUser = User::factory()->create();
        $dept    = Department::factory()->create();
        $desig   = Designation::factory()->create();

        $this->asUser()
            ->post(route('hrm.employees.store'), [
                'user_id'         => $newUser->id,
                'employee_code'   => 'EMP-T001',
                'date_of_joining' => '2026-01-01',
                'department_id'   => $dept->id,
                'designation_id'  => $desig->id,
                'employment_type' => 'full_time',
                'status'          => 'active',
                'basic_salary'    => 5000,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('employees', ['employee_code' => 'EMP-T001']);
    }

    public function test_show_renders_employee_profile(): void
    {
        $this->grantAllPermissions();
        $employee = Employee::factory()->create();

        $response = $this->asUser()->get(route('hrm.employees.show', $employee));

        $response->assertOk();
        $json = $response->json();
        $this->assertEquals('HRM/Employees/Show', $json['component'] ?? null);
        $this->assertArrayHasKey('employee', $json['props'] ?? []);
        $this->assertArrayHasKey('permissions', $json['props'] ?? []);
    }

    public function test_show_masks_bank_account_without_permission(): void
    {
        // Allow all permissions EXCEPT bank-details — lets detail.view pass
        // while bank_account_number gets masked by the controller.
        Gate::before(function ($user, $ability) {
            if (str_contains($ability, 'bank-details')) {
                return false; // deny → triggers PII masking in controller
            }
            return true;
        });

        $employee = Employee::factory()->create(['bank_account_number' => 'AE0123456789012345']);

        $response = $this->asUser()->get(route('hrm.employees.show', $employee));

        $response->assertOk();
        $json = $response->json();
        $props = $json['props'] ?? [];

        // Check canViewBank is false (Gate::before denies bank-details.*)
        $this->assertFalse($props['permissions']['canViewBank'] ?? true,
            'canViewBank should be false when Gate denies bank-details.view');

        // bank_account_number must be null or absent in props
        $empProps = $props['employee'] ?? [];
        $actualValue = array_key_exists('bank_account_number', $empProps)
            ? $empProps['bank_account_number']
            : null; // absent = effectively masked
        $this->assertNull($actualValue,
            'bank_account_number should be null or absent when user lacks bank-details.view');
    }

    public function test_update_modifies_employee_fields(): void
    {
        $this->grantAllPermissions();
        $employee = Employee::factory()->create(['employment_type' => 'full_time', 'status' => 'active']);

        // withoutMiddleware() disables SubstituteBindings, so route model binding
        // passes the string ID — controller resolves it via implicit binding fallback.
        $this->asUser()
            ->put(route('hrm.employees.update', $employee), [
                'employee_code'   => $employee->employee_code,
                'date_of_joining' => $employee->date_of_joining->toDateString(),
                'employment_type' => 'part_time',
                'status'          => 'probation',
                'basic_salary'    => 4500,
            ])
            ->assertRedirect();

        $this->assertSame('part_time', $employee->fresh()->employment_type);
    }

    public function test_destroy_soft_deletes_employee(): void
    {
        $this->grantAllPermissions();
        $employee = Employee::factory()->create();

        $this->asUser()
            ->delete(route('hrm.employees.destroy', $employee))
            ->assertRedirect();

        $this->assertSoftDeleted('employees', ['id' => $employee->id]);
    }

    public function test_restore_recovers_soft_deleted_employee(): void
    {
        $this->grantAllPermissions();
        $user     = User::factory()->create();
        $employee = Employee::factory()->create();
        $employee->delete();
        $id = $employee->id;

        $this->actingAs($user)
            ->withoutMiddleware()
            ->post(route('hrm.employees.restore', $id))
            ->assertRedirect();

        $this->assertNull(Employee::withTrashed()->find($id)->deleted_at);
    }
}
