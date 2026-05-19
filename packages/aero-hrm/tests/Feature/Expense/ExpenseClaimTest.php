<?php

declare(strict_types=1);

namespace Aero\HRM\Tests\Feature\Expense;

use Aero\Core\AeroCoreServiceProvider;
use Aero\Core\Models\User;
use Aero\HRM\AeroHrmServiceProvider;
use Aero\HRM\Models\Employee;
use Aero\HRM\Models\HrmExpenseCategory;
use Aero\HRM\Models\HrmExpenseClaim;
use Aero\HRMAC\HRMACServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Inertia\ServiceProvider;
use Orchestra\Testbench\TestCase;

class ExpenseClaimTest extends TestCase
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

    private function asEmployee(): array
    {
        $user = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        return [$user, $employee];
    }

    // ─── Submit ───────────────────────────────────────────────────────────────

    public function test_employee_can_submit_claim(): void
    {
        $this->grantAllPermissions();
        [, $employee] = $this->asEmployee();
        $cat = HrmExpenseCategory::factory()->create();

        $this->post(route('hrm.expenses.claims.store'), [
            'title' => 'Client Lunch',
            'claim_date' => now()->toDateString(),
            'currency' => 'USD',
            'items' => [['category_id' => $cat->id, 'expense_date' => now()->toDateString(), 'amount' => 120.50]],
        ])->assertRedirect();

        $this->assertDatabaseHas('hrm_expense_claims', ['title' => 'Client Lunch', 'status' => 'submitted']);
        $this->assertDatabaseHas('hrm_expense_claim_items', ['amount' => 120.50]);
    }

    // ─── Approve ──────────────────────────────────────────────────────────────

    public function test_admin_can_approve_submitted_claim(): void
    {
        $this->grantAllPermissions();
        $this->asEmployee();
        $claim = HrmExpenseClaim::factory()->submitted()->create();

        $this->post(route('hrm.expenses.claims.approve', $claim))->assertRedirect();
        $this->assertDatabaseHas('hrm_expense_claims', ['id' => $claim->id, 'status' => 'approved']);
    }

    public function test_cannot_approve_already_approved_claim(): void
    {
        $this->grantAllPermissions();
        $this->asEmployee();
        $claim = HrmExpenseClaim::factory()->approved()->create();

        $this->post(route('hrm.expenses.claims.approve', $claim))->assertStatus(422);
    }

    // ─── Reject ───────────────────────────────────────────────────────────────

    public function test_admin_can_reject_submitted_claim_with_reason(): void
    {
        $this->grantAllPermissions();
        $this->asEmployee();
        $claim = HrmExpenseClaim::factory()->submitted()->create();

        $this->post(route('hrm.expenses.claims.reject', $claim), ['reason' => 'Missing receipts'])
            ->assertRedirect();

        $this->assertDatabaseHas('hrm_expense_claims', [
            'id' => $claim->id,
            'status' => 'rejected',
            'rejection_reason' => 'Missing receipts',
        ]);
    }

    public function test_reject_requires_reason(): void
    {
        $this->grantAllPermissions();
        $this->asEmployee();
        $claim = HrmExpenseClaim::factory()->submitted()->create();

        $this->post(route('hrm.expenses.claims.reject', $claim), [])
            ->assertSessionHasErrors('reason');
    }

    // ─── My Claims ────────────────────────────────────────────────────────────

    public function test_my_claims_only_shows_own_claims(): void
    {
        $this->grantAllPermissions();
        [, $employee] = $this->asEmployee();
        $ownClaim = HrmExpenseClaim::factory()->create(['employee_id' => $employee->id]);
        $otherClaim = HrmExpenseClaim::factory()->create();

        $this->get(route('hrm.expenses.my.index'))
            ->assertInertia(fn ($p) => $p->has('claims.data', 1)
                ->where('claims.data.0.id', $ownClaim->id));
    }
}
