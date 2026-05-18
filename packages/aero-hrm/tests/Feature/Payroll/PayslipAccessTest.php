<?php

declare(strict_types=1);

namespace Aero\HRM\Tests\Feature\Payroll;

use Aero\Core\AeroCoreServiceProvider;
use Aero\Core\Models\User;
use Aero\HRMAC\HRMACServiceProvider;
use Aero\HRMAC\Http\Middleware\CheckRoleModuleAccess;
use Aero\HRM\AeroHrmServiceProvider;
use Aero\HRM\Models\Employee;
use Aero\HRM\Models\Payslip;
use Aero\HRM\Models\PayrollRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Orchestra\Testbench\TestCase;

class PayslipAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            \Inertia\ServiceProvider::class,
            \Spatie\Activitylog\ActivitylogServiceProvider::class,
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
        // Disable Spatie activity logging — prevents writes to the activity_log
        // table which is not migrated in the test environment.
        $app['config']->set('activitylog.enabled', false);
        $app['config']->set('activitylog.default_auth_driver', null);
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
     * Act as a user keeping SubstituteBindings so route model binding resolves.
     * Disables only the HRMAC middleware — used for routes with {payslip} parameter.
     */
    private function asUserWithBinding(?User $user = null)
    {
        $user ??= User::factory()->create(['email_verified_at' => now()]);

        return $this->actingAs($user)
            ->withoutMiddleware([CheckRoleModuleAccess::class])
            ->withHeaders(['X-Inertia' => 'true', 'X-Inertia-Version' => '1']);
    }

    // -------------------------------------------------------------------------
    // 1. An employee can view their own payslip
    // -------------------------------------------------------------------------

    public function test_employee_can_view_own_payslip(): void
    {
        $this->grantAllPermissions();

        // Create a user, create an employee linked to that user.
        $user     = User::factory()->create(['email_verified_at' => now()]);
        $employee = Employee::factory()->create(['user_id' => $user->id]);
        $run      = PayrollRun::factory()->create();

        $slip = Payslip::factory()->create([
            'payroll_run_id' => $run->id,
            'employee_id'    => $employee->id,
        ]);

        $this->asUserWithBinding($user)
            ->get(route('hrm.payroll.payslips.show', $slip->id))
            ->assertOk();
    }

    // -------------------------------------------------------------------------
    // 2. An employee cannot view another employee's payslip
    // -------------------------------------------------------------------------

    public function test_employee_cannot_view_another_employees_payslip(): void
    {
        // We do NOT grant all permissions here — the controller must enforce
        // the HRMAC gate for the non-self path.
        // Gate::before(fn () => true) is intentionally omitted so that
        // Gate::authorize('hrmac', 'hrm.payroll.payslips.view') denies the request.

        $ownerUser = User::factory()->create(['email_verified_at' => now()]);
        $employee  = Employee::factory()->create(['user_id' => $ownerUser->id]);
        $run       = PayrollRun::factory()->create();

        $slip = Payslip::factory()->create([
            'payroll_run_id' => $run->id,
            'employee_id'    => $employee->id,
        ]);

        // A different user — not linked to the payslip's employee.
        $otherUser = User::factory()->create(['email_verified_at' => now()]);

        $this->asUserWithBinding($otherUser)
            ->get(route('hrm.payroll.payslips.show', $slip->id))
            ->assertForbidden();
    }
}
