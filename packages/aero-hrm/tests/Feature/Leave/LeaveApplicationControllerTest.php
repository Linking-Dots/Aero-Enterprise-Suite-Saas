<?php

declare(strict_types=1);

namespace Aero\HRM\Tests\Feature\Leave;

use Aero\Core\AeroCoreServiceProvider;
use Aero\Core\Models\User;
use Aero\HRMAC\HRMACServiceProvider;
use Aero\HRMAC\Http\Middleware\CheckRoleModuleAccess;
use Aero\HRM\AeroHrmServiceProvider;
use Aero\HRM\Models\Employee;
use Aero\HRM\Models\LeaveApplication;
use Aero\HRM\Models\LeaveBalance;
use Aero\HRM\Models\LeaveType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Orchestra\Testbench\TestCase;

class LeaveApplicationControllerTest extends TestCase
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
        // Point view paths to the test fixture that has app.blade.php for Inertia.
        $app['config']->set('view.paths', [
            realpath(__DIR__.'/../../fixtures/views'),
        ]);
    }

    protected function defineRoutes($router): void
    {
        // Stub login route — required by Authenticate middleware redirect.
        $router->get('/login', fn () => response('login'))->name('login');
    }

    // Grant all Gate checks — call in tests that don't need per-permission logic.
    private function grantAllPermissions(): void
    {
        Gate::before(fn () => true);
    }

    /**
     * Helper to approve a leave application via HTTP request.
     */
    private function approveApplication(LeaveApplication $app, User $user): void
    {
        $this->asUserWithBinding($user)->post(route('hrm.leave.applications.approve', $app));
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
     * Disables only the HRMAC middleware — auth middleware still runs, so the
     * user must be authenticated (actingAs handles that).
     *
     * Use for routes that have a {model} route parameter: show, approve, reject, cancel.
     */
    private function asUserWithBinding(?User $user = null)
    {
        $user ??= User::factory()->create(['email_verified_at' => now()]);

        return $this->actingAs($user)
            ->withoutMiddleware([CheckRoleModuleAccess::class])
            ->withHeaders(['X-Inertia' => 'true', 'X-Inertia-Version' => '1']);
    }

    // -------------------------------------------------------------------------
    // 1. Authentication gate
    // -------------------------------------------------------------------------

    public function test_index_requires_authentication(): void
    {
        // Without actingAs the auth middleware redirects to /login.
        // We disable only the HRMAC middleware; auth stays active.
        $this->withoutMiddleware([CheckRoleModuleAccess::class])
            ->get(route('hrm.leave.applications.index'))
            ->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // 2. Index with status filter
    // -------------------------------------------------------------------------

    public function test_index_lists_applications_with_status_filter(): void
    {
        $this->grantAllPermissions();

        $employee  = Employee::factory()->create();
        $leaveType = LeaveType::factory()->create(['requires_approval' => true]);

        // 3 pending
        LeaveApplication::factory()->count(3)->create([
            'employee_id'   => $employee->id,
            'leave_type_id' => $leaveType->id,
            'status'        => LeaveApplication::STATUS_PENDING,
        ]);
        // 2 approved
        LeaveApplication::factory()->count(2)->create([
            'employee_id'   => $employee->id,
            'leave_type_id' => $leaveType->id,
            'status'        => LeaveApplication::STATUS_APPROVED,
        ]);

        $response = $this->asUser()
            ->get(route('hrm.leave.applications.index', ['status' => 'pending']));

        $response->assertOk();

        $json  = $response->json();
        $props = $json['props'] ?? [];

        $this->assertEquals('HRM/Leave/Applications/Index', $json['component'] ?? null);

        // The paginated result must contain exactly 3 items.
        $total = $props['applications']['total'] ?? null;
        $this->assertEquals(3, $total, 'Expected 3 pending applications in the paginated result.');
    }

    // -------------------------------------------------------------------------
    // 3. Store creates a pending application
    // -------------------------------------------------------------------------

    public function test_store_creates_pending_application(): void
    {
        $this->grantAllPermissions();

        $employee  = Employee::factory()->create();
        $leaveType = LeaveType::factory()->create(['requires_approval' => true]);

        $this->asUser()
            ->post(route('hrm.leave.applications.store'), [
                'employee_id'   => $employee->id,
                'leave_type_id' => $leaveType->id,
                'start_date'    => '2026-06-01',
                'end_date'      => '2026-06-03',   // 3 days inclusive
                'reason'        => 'Annual vacation',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('leave_applications', [
            'employee_id'   => $employee->id,
            'leave_type_id' => $leaveType->id,
            'status'        => LeaveApplication::STATUS_PENDING,
            'total_days'    => 3,
        ]);
    }

    // -------------------------------------------------------------------------
    // 4. Store validates end_date before start_date
    // -------------------------------------------------------------------------

    public function test_store_validates_end_before_start(): void
    {
        $this->grantAllPermissions();

        $employee  = Employee::factory()->create();
        $leaveType = LeaveType::factory()->create(['requires_approval' => true]);

        $this->asUser()
            ->post(route('hrm.leave.applications.store'), [
                'employee_id'   => $employee->id,
                'leave_type_id' => $leaveType->id,
                'start_date'    => '2026-06-05',
                'end_date'      => '2026-06-03',   // before start → invalid
                'reason'        => 'Test',
            ])
            ->assertSessionHasErrors(['end_date']);
    }

    // -------------------------------------------------------------------------
    // 5. Show returns component with application.id and permissions key
    // -------------------------------------------------------------------------

    public function test_show_returns_application_with_permissions(): void
    {
        $this->grantAllPermissions();

        $app = LeaveApplication::factory()->create();

        // Uses asUserWithBinding so route model binding resolves the model.
        $response = $this->asUserWithBinding()
            ->get(route('hrm.leave.applications.show', $app));

        $response->assertOk();

        $json  = $response->json();
        $props = $json['props'] ?? [];

        $this->assertEquals('HRM/Leave/Applications/Show', $json['component'] ?? null);
        $this->assertArrayHasKey('application', $props);
        $this->assertEquals($app->id, $props['application']['id'] ?? null);
        $this->assertArrayHasKey('permissions', $props);
        $this->assertArrayHasKey('canApprove', $props['permissions']);
    }

    // -------------------------------------------------------------------------
    // 6. Approve changes status and records balance usage
    // -------------------------------------------------------------------------

    public function test_approve_changes_status_and_updates_balance(): void
    {
        $this->grantAllPermissions();

        $leaveType = LeaveType::factory()->create([
            'requires_approval' => true,
            'days_per_year'     => 15,
        ]);
        $employee = Employee::factory()->create();
        $user     = User::factory()->create(['email_verified_at' => now()]);

        $app = LeaveApplication::factory()->create([
            'employee_id'   => $employee->id,
            'leave_type_id' => $leaveType->id,
            'status'        => LeaveApplication::STATUS_PENDING,
            'start_date'    => '2026-07-01',
            'end_date'      => '2026-07-03',
            'total_days'    => 3,
        ]);

        // Uses approveApplication helper for consistent HTTP isolation.
        $this->approveApplication($app, $user);

        // Status must be approved.
        $this->assertEquals(
            LeaveApplication::STATUS_APPROVED,
            $app->fresh()->status,
        );

        // Balance row must exist with used = 3.
        $this->assertDatabaseHas('employee_leave_balances', [
            'employee_id'   => $employee->id,
            'leave_type_id' => $leaveType->id,
            'used'          => 3,
        ]);
    }

    // -------------------------------------------------------------------------
    // 7. Reject requires a non-empty reason
    // -------------------------------------------------------------------------

    public function test_reject_requires_reason(): void
    {
        $this->grantAllPermissions();

        $app = LeaveApplication::factory()->create([
            'status' => LeaveApplication::STATUS_PENDING,
        ]);

        // Uses asUserWithBinding so route model binding resolves the model.
        $this->asUserWithBinding()
            ->post(route('hrm.leave.applications.reject', $app), [
                'reason' => '',   // empty — must fail validation
            ])
            ->assertSessionHasErrors(['reason']);
    }

    // -------------------------------------------------------------------------
    // 8. Cancelling an approved application releases the balance
    // -------------------------------------------------------------------------

    public function test_cancel_releases_balance_when_approved(): void
    {
        $this->grantAllPermissions();

        $leaveType = LeaveType::factory()->create([
            'requires_approval' => true,
            'days_per_year'     => 15,
        ]);
        $employee = Employee::factory()->create();
        $user     = User::factory()->create(['email_verified_at' => now()]);

        // Create in pending state, then approve via HTTP so balance is seeded.
        $app = LeaveApplication::factory()->create([
            'employee_id'   => $employee->id,
            'leave_type_id' => $leaveType->id,
            'status'        => LeaveApplication::STATUS_PENDING,
            'start_date'    => '2026-08-01',
            'end_date'      => '2026-08-03',
            'total_days'    => 3,
        ]);

        // Approve via helper (binding-aware, HRMAC disabled, Gate open).
        $this->approveApplication($app, $user);

        $this->assertEquals(
            LeaveApplication::STATUS_APPROVED,
            $app->fresh()->status,
            'Application must be approved before testing cancel.',
        );

        // Cancel via asUserWithBinding helper for consistent isolation.
        $this->asUserWithBinding($user)
            ->post(route('hrm.leave.applications.cancel', $app))
            ->assertRedirect();

        // Status must be cancelled.
        $this->assertEquals(
            LeaveApplication::STATUS_CANCELLED,
            $app->fresh()->status,
        );

        // Balance.used must be 0 after the reversal.
        $balance = LeaveBalance::where([
            'employee_id'   => $employee->id,
            'leave_type_id' => $leaveType->id,
        ])->first();

        $this->assertNotNull($balance, 'Balance row should still exist after cancel.');
        $this->assertEquals(0.0, (float) $balance->used, 'Used balance should be 0 after cancellation.');
    }
}
