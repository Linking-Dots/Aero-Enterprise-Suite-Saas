<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature\Admin;
use Aero\Platform\Database\Factories\LandlordUserFactory;

use Aero\Contracts\AeroMode;
use Aero\HRMAC\Models\Role;
use Aero\Platform\Exceptions\SubscriptionFinalizedException;
use Aero\Auth\Models\User;
use Aero\Platform\Models\Plan;
use Aero\Platform\Models\Subscription;
use Aero\Platform\Models\Tenant;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * P-2 — Subscription Immutability
 *
 * Validates that SubscriptionImmutableObserver prevents mutation of
 * plan_id and other locked fields on active subscriptions, while
 * permitting allowed status transitions.
 */
class SubscriptionImmutabilityTest extends TestCase
{
    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    protected User $admin;

    protected Plan $plan;

    protected Tenant $tenant;

    public function runDatabaseMigrations(): void
    {
        $this->shareSqliteAcrossConnections();
        $this->beforeRefreshingDatabase();
        $this->refreshTestDatabase();
        $this->afterRefreshingDatabase();
    }

    /**
     * Override to restrict migrations to the required packages only, avoiding the
     * cross-package job_types conflict between aero-platform and aero-hrm.
     */
    protected function refreshTestDatabase(): void
    {
        \Illuminate\Support\Facades\Schema::dropAllTables();

        $packages = realpath(__DIR__.'/../../../..');

        $migrationPaths = [
            $packages.'/aero-core/database/migrations',
            $packages.'/aero-auth/database/migrations',
            $packages.'/aero-hrmac/database/migrations',
            $packages.'/aero-platform/database/migrations',
        ];

        /** @var \Illuminate\Database\Migrations\Migrator $migrator */
        $migrator = $this->app['migrator'];
        $migrator->setConnection('sqlite');

        if (! $migrator->repositoryExists()) {
            $migrator->getRepository()->createRepository();
        }

        foreach ($migrationPaths as $path) {
            $migrator->run([$path]);
        }
    }

    private function shareSqliteAcrossConnections(): void
    {
        $sqliteConfig = ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => false];
        config(['database.connections.mysql' => $sqliteConfig, 'database.connections.central' => $sqliteConfig, 'tenancy.database.central_connection' => 'sqlite']);
        $this->app['db']->purge('sqlite');
        $this->app['db']->purge('mysql');
        $this->app['db']->purge('central');
        $pdo = $this->app['db']->connection('sqlite')->getPdo();
        $this->app['db']->connection('mysql')->setPdo($pdo);
        $this->app['db']->connection('central')->setPdo($pdo);

        if (empty(config('permission.table_names'))) {
            config(['permission.table_names' => [
                'roles'               => 'roles',
                'permissions'         => 'permissions',
                'model_has_permissions' => 'model_has_permissions',
                'model_has_roles'     => 'model_has_roles',
                'role_has_permissions' => 'role_has_permissions',
            ], 'permission.column_names' => [
                'role_pivot_key'       => null,
                'permission_pivot_key' => null,
                'model_morph_key'      => 'model_id',
                'team_foreign_key'     => 'team_id',
            ], 'permission.teams' => false,
            ]);
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Disable SaaS mode so TenantModel global scope guard does not fire.
        AeroMode::reset();

        Gate::before(fn () => true);

        $role = Role::firstOrCreate(
            ['name' => 'Super Administrator', 'guard_name' => 'landlord'],
        );

        $this->admin = LandlordUserFactory::new()->create();
        $this->admin->assignRole($role);

        $this->plan   = Plan::factory()->create(['is_active' => true]);
        $this->tenant = Tenant::factory()->active()->create();
    }

    /**
     * Helper: create a subscription with the given status directly via Eloquent.
     */
    private function createSubscription(string $status, ?string $overridePlanId = null): Subscription
    {
        return Subscription::create([
            'billable_type' => Tenant::class,
            'billable_id'   => $this->tenant->id,
            'type'          => 'default',
            'name'          => 'default',
            'plan_id'       => $overridePlanId ?? $this->plan->id,
            'status'        => $status,
            'starts_at'     => now(),
        ]);
    }

    // =========================================================================
    // IMMUTABILITY — model-level observer
    // =========================================================================

    public function test_cannot_change_plan_id_on_active_subscription(): void
    {
        $subscription = $this->createSubscription(Subscription::STATUS_ACTIVE);
        $newPlan      = Plan::factory()->create(['is_active' => true]);

        $this->expectException(SubscriptionFinalizedException::class);

        // Direct Eloquent update must trigger the observer and throw.
        $subscription->update(['plan_id' => $newPlan->id]);
    }

    public function test_cannot_change_billing_cycle_on_active_subscription(): void
    {
        $subscription = $this->createSubscription(Subscription::STATUS_ACTIVE);

        $this->expectException(SubscriptionFinalizedException::class);

        $subscription->update(['billing_cycle' => 'yearly']);
    }

    public function test_cannot_change_current_period_start_on_active_subscription(): void
    {
        $subscription = $this->createSubscription(Subscription::STATUS_ACTIVE);

        $this->expectException(SubscriptionFinalizedException::class);

        $subscription->update(['current_period_start' => now()->subMonth()]);
    }

    // =========================================================================
    // ALLOWED TRANSITIONS — status changes must NOT be blocked
    // =========================================================================

    public function test_can_transition_active_to_cancelled(): void
    {
        $subscription = $this->createSubscription(Subscription::STATUS_ACTIVE);

        // Status is not in the locked-field list; this must succeed without exception.
        $subscription->update(['status' => Subscription::STATUS_CANCELLED]);

        $this->assertDatabaseHas('subscriptions', [
            'id'     => $subscription->id,
            'status' => Subscription::STATUS_CANCELLED,
        ]);
    }

    public function test_locked_fields_are_not_blocked_on_non_active_subscription(): void
    {
        $subscription = $this->createSubscription(Subscription::STATUS_CANCELLED);
        $newPlan      = Plan::factory()->create(['is_active' => true]);

        // Observer only locks when original status == active. Cancelled subs are mutable.
        $subscription->update(['plan_id' => $newPlan->id]);

        $this->assertDatabaseHas('subscriptions', [
            'id'      => $subscription->id,
            'plan_id' => $newPlan->id,
        ]);
    }

    // =========================================================================
    // API ROUTE — cancel already-cancelled subscription should return 422
    // =========================================================================

    public function test_cannot_cancel_already_cancelled_subscription_via_api(): void
    {
        $subscription = $this->createSubscription(Subscription::STATUS_CANCELLED);

        $response = $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.billing.subscriptions.cancel', $subscription), [
                'reason' => 'Duplicate cancellation attempt',
            ]);

        // The service currently bypasses the observer and allows re-cancellation.
        // A 422 here documents the required behaviour; a 302 indicates a missing guard.
        // This test intentionally documents the expected contract and will fail until
        // SubscriptionAdminService::cancel() adds a guard for already-cancelled subs.
        $response->assertUnprocessable();
    }

    // =========================================================================
    // UNAUTHENTICATED ACCESS
    // =========================================================================

    public function test_unauthenticated_cancel_request_is_redirected(): void
    {
        $subscription = $this->createSubscription(Subscription::STATUS_ACTIVE);

        $this->post(route('platform.admin.billing.subscriptions.cancel', $subscription), [
            'reason' => 'test',
        ])->assertRedirect();
    }
}
