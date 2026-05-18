<?php

declare(strict_types=1);

namespace Aero\HRM\Tests\Feature\Payroll;

use Aero\Core\AeroCoreServiceProvider;
use Aero\Core\Models\User;
use Aero\HRMAC\HRMACServiceProvider;
use Aero\HRMAC\Http\Middleware\CheckRoleModuleAccess;
use Aero\HRM\AeroHrmServiceProvider;
use Aero\HRM\Exceptions\PayrollLockedException;
use Aero\HRM\Models\PayrollRun;
use Aero\HRM\Services\Payroll\PayrollApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Orchestra\Testbench\TestCase;

class PayrollImmutabilityTest extends TestCase
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
     * Act as a user keeping SubstituteBindings so route model binding resolves.
     * Disables only the HRMAC middleware — used for routes with {model} parameters.
     */
    private function asUserWithBinding(?User $user = null)
    {
        $user ??= User::factory()->create(['email_verified_at' => now()]);

        return $this->actingAs($user)
            ->withoutMiddleware([CheckRoleModuleAccess::class])
            ->withHeaders(['X-Inertia' => 'true', 'X-Inertia-Version' => '1']);
    }

    // -------------------------------------------------------------------------
    // 1. Approving a run sets locked_at and transitions to approved status
    // -------------------------------------------------------------------------

    public function test_approving_run_sets_locked_at(): void
    {
        $run = PayrollRun::factory()->create(['status' => PayrollRun::STATUS_DRAFT]);

        /** @var PayrollApprovalService $service */
        $service = app(PayrollApprovalService::class);
        $service->approve($run);

        $fresh = $run->fresh();

        $this->assertNotNull($fresh->locked_at, 'locked_at must be set after approval.');
        $this->assertEquals(
            PayrollRun::STATUS_APPROVED,
            $fresh->status,
            'status must be "approved" after approval.'
        );
    }

    // -------------------------------------------------------------------------
    // 2. Updating a locked run throws PayrollLockedException
    // -------------------------------------------------------------------------

    public function test_updating_locked_run_throws_exception(): void
    {
        $run = PayrollRun::factory()->create([
            'status'    => PayrollRun::STATUS_APPROVED,
            'locked_at' => now(),
        ]);

        $this->expectException(PayrollLockedException::class);

        $run->update(['label' => 'changed']);
    }

    // -------------------------------------------------------------------------
    // 3. Controller returns 403 when attempting to update a locked run
    // -------------------------------------------------------------------------

    public function test_controller_returns_403_on_locked_run_update(): void
    {
        $this->grantAllPermissions();

        $user = User::factory()->create(['email_verified_at' => now()]);

        $run = PayrollRun::factory()->create([
            'status'    => PayrollRun::STATUS_APPROVED,
            'locked_at' => now(),
        ]);

        $this->asUserWithBinding($user)
            ->put(route('hrm.payroll.runs.update', $run->id), [
                'label'        => 'x',
                'period_start' => '2026-05-01',
                'period_end'   => '2026-05-31',
                'status'       => PayrollRun::STATUS_DRAFT,
            ])
            ->assertStatus(403);
    }
}
