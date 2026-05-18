<?php

declare(strict_types=1);

namespace Aero\HRM\Tests\Feature\SelfService;

use Aero\Core\AeroCoreServiceProvider;
use Aero\Core\Models\User;
use Aero\HRMAC\HRMACServiceProvider;
use Aero\HRMAC\Http\Middleware\CheckRoleModuleAccess;
use Aero\HRM\AeroHrmServiceProvider;
use Aero\HRM\Http\Middleware\EnsureEmployeeProfile;
use Aero\HRM\Models\Employee;
use Aero\HRM\Models\LeaveApplication;
use Aero\HRM\Models\LeaveType;
use Aero\HRM\Models\Payslip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Orchestra\Testbench\TestCase;
use Closure;

class SelfServiceTest extends TestCase
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
        // Disable spatie/laravel-activitylog so Payslip::factory() does not require the
        // activity_log table which is not part of the package's own migrations.
        $app['config']->set('activitylog.enabled', false);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Register the 'employee' relationship on User via Laravel's static resolver
        // so that property-access ($user->employee) works in tests.
        // The UserRelationshipRegistry __call mechanism only covers method calls;
        // resolveRelationUsing() enables the property accessor path in getAttribute().
        User::resolveRelationUsing('employee', function (User $user): \Illuminate\Database\Eloquent\Relations\HasOne {
            return $user->hasOne(Employee::class, 'user_id');
        });
    }

    protected function defineRoutes($router): void
    {
        // Required by Authenticate middleware redirect.
        $router->get('/login', fn () => response('login'))->name('login');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** Open all Gate checks for the current test. */
    private function grantAllPermissions(): void
    {
        Gate::before(fn () => true);
    }

    /**
     * Create a User with a linked Employee record and return the User.
     * The Employee factory creates its own User via user_id; here we create
     * the User first, then attach the Employee to that exact user.
     */
    private function actingAsEmployee(): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        Employee::factory()->create(['user_id' => $user->id]);

        return $user;
    }

    /**
     * Return a test HTTP actor with all middleware stripped except auth.
     * HRMAC and employee.required are both removed.
     */
    private function asEmployee(User $user)
    {
        return $this->actingAs($user)
            ->withoutMiddleware([CheckRoleModuleAccess::class, EnsureEmployeeProfile::class])
            ->withHeaders(['X-Inertia' => 'true', 'X-Inertia-Version' => '1']);
    }

    /**
     * Same as asEmployee but also keeps route model binding active.
     * Use for routes with {model} parameters.
     */
    private function asEmployeeWithBinding(User $user)
    {
        return $this->actingAs($user)
            ->withoutMiddleware([CheckRoleModuleAccess::class, EnsureEmployeeProfile::class])
            ->withHeaders(['X-Inertia' => 'true', 'X-Inertia-Version' => '1']);
    }

    // -------------------------------------------------------------------------
    // 1. Dashboard returns correct employee data
    // -------------------------------------------------------------------------

    public function test_dashboard_returns_correct_employee_data(): void
    {
        $this->grantAllPermissions();

        $user     = $this->actingAsEmployee();
        $employee = $user->employee;

        $response = $this->asEmployee($user)
            ->get(route('hrm.self-service.dashboard'));

        $response->assertOk();

        $json  = $response->json();
        $props = $json['props'] ?? [];

        $this->assertEquals('HRM/SelfService/Dashboard', $json['component'] ?? null);
        $this->assertArrayHasKey('employee', $props);
        $this->assertEquals($employee->id, $props['employee']['id'] ?? null);
    }

    // -------------------------------------------------------------------------
    // 2. User without employee profile is redirected to no-profile page
    // -------------------------------------------------------------------------

    public function test_user_without_employee_is_redirected(): void
    {
        $this->grantAllPermissions();

        // Create a user WITHOUT an Employee record.
        $user = User::factory()->create(['email_verified_at' => now()]);

        // Disable only HRMAC — let employee.required fire so it redirects.
        $this->actingAs($user)
            ->withoutMiddleware([CheckRoleModuleAccess::class])
            ->withHeaders(['X-Inertia' => 'true', 'X-Inertia-Version' => '1'])
            ->get(route('hrm.self-service.dashboard'))
            ->assertRedirect(route('hrm.self-service.no-profile'));
    }

    // -------------------------------------------------------------------------
    // 3. Employee can apply for leave
    // -------------------------------------------------------------------------

    public function test_employee_can_apply_for_leave(): void
    {
        $this->grantAllPermissions();

        $user      = $this->actingAsEmployee();
        $employee  = $user->employee;
        $leaveType = LeaveType::factory()->create(['is_active' => true]);

        $this->asEmployee($user)
            ->post(route('hrm.self-service.leaves.store'), [
                'leave_type_id' => $leaveType->id,
                'start_date'    => '2026-08-01',
                'end_date'      => '2026-08-03',
                'total_days'    => 3,
                'reason'        => 'Annual leave request for testing purposes.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('leave_applications', [
            'employee_id'   => $employee->id,
            'leave_type_id' => $leaveType->id,
            'status'        => LeaveApplication::STATUS_PENDING,
        ]);
    }

    // -------------------------------------------------------------------------
    // 4. Employee can cancel their own pending leave
    // -------------------------------------------------------------------------

    public function test_employee_can_cancel_own_pending_leave(): void
    {
        $this->grantAllPermissions();

        $user     = $this->actingAsEmployee();
        $employee = $user->employee;

        $leave = LeaveApplication::factory()->create([
            'employee_id' => $employee->id,
            'status'      => LeaveApplication::STATUS_PENDING,
        ]);

        $this->asEmployeeWithBinding($user)
            ->post(route('hrm.self-service.leaves.cancel', $leave))
            ->assertRedirect();

        $this->assertEquals(
            LeaveApplication::STATUS_CANCELLED,
            $leave->fresh()->status,
        );
    }

    // -------------------------------------------------------------------------
    // 5. Employee cannot cancel an already-approved leave
    // -------------------------------------------------------------------------

    public function test_employee_cannot_cancel_approved_leave(): void
    {
        $this->grantAllPermissions();

        $user     = $this->actingAsEmployee();
        $employee = $user->employee;

        $leave = LeaveApplication::factory()->approved()->create([
            'employee_id' => $employee->id,
        ]);

        $this->asEmployeeWithBinding($user)
            ->post(route('hrm.self-service.leaves.cancel', $leave))
            ->assertStatus(422);
    }

    // -------------------------------------------------------------------------
    // 6. Employee cannot view another employee's payslip
    // -------------------------------------------------------------------------

    public function test_employee_cannot_view_other_employees_payslip(): void
    {
        $this->grantAllPermissions();

        // Employee A — the authenticated user.
        $userA = $this->actingAsEmployee();

        // Employee B — a different employee with their own payslip.
        $employeeB = Employee::factory()->create();
        $payslipB  = Payslip::factory()->create(['employee_id' => $employeeB->id]);

        $this->asEmployeeWithBinding($userA)
            ->get(route('hrm.self-service.payslips.show', $payslipB))
            ->assertForbidden();
    }
}
