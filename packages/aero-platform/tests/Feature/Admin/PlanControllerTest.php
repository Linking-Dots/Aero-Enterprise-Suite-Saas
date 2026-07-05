<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature\Admin;
use Aero\Platform\Database\Factories\LandlordUserFactory;

use Aero\Contracts\AeroMode;
use Aero\HRMAC\Models\Role;
use Aero\Auth\Models\User;
use Aero\Platform\Models\Plan;
use Aero\Platform\Models\Subscription;
use Aero\Platform\Models\Tenant;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * P-2 — Admin PlanController (Inertia routes)
 *
 * Auth pattern: actingAs($admin, 'landlord').
 * Gate::before(fn () => true) bypasses HRMAC middleware for all tests.
 */
class PlanControllerTest extends TestCase
{
    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    protected User $admin;

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
     *
     * Uses the Migrator directly to guarantee the correct PDO connection is used
     * throughout (artisan() spawns a fresh command context that loses the in-memory PDO).
     */
    protected function refreshTestDatabase(): void
    {
        \Illuminate\Support\Facades\Schema::dropAllTables();

        // __DIR__ = .../packages/aero-platform/tests/Feature/Admin  (5 segments deep)
        // 4 levels up reaches .../packages
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
        // foreign_key_constraints=false: SQLite RENAME COLUMN validates all indexes
        // on all tables; with FK constraints on, cross-table index checks fail when
        // earlier migrations created indexes that a later migration indirectly touches.
        $sqliteConfig = ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => false];
        config(['database.connections.mysql' => $sqliteConfig, 'database.connections.central' => $sqliteConfig, 'tenancy.database.central_connection' => 'sqlite']);
        $this->app['db']->purge('sqlite');
        $this->app['db']->purge('mysql');
        $this->app['db']->purge('central');
        $pdo = $this->app['db']->connection('sqlite')->getPdo();
        $this->app['db']->connection('mysql')->setPdo($pdo);
        $this->app['db']->connection('central')->setPdo($pdo);

        // Ensure Spatie permission config is available for create_permission_tables migration.
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
        // The aero.mode file / central tenants table presence would otherwise
        // trigger AeroMode::isSaas() = true and block TenantModel queries.
        AeroMode::reset();

        Gate::before(fn () => true);

        $role = Role::firstOrCreate(
            ['name' => 'Super Administrator', 'guard_name' => 'landlord'],
        );

        $this->admin = LandlordUserFactory::new()->create();
        $this->admin->assignRole($role);
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    public function test_plan_index_renders_inertia_page(): void
    {
        Plan::factory()->count(3)->create();

        $this->actingAs($this->admin, 'landlord')
            ->get(route('platform.admin.plans.index'))
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page
                    ->component('Platform/Admin/Plans/Index')
                    ->has('plans')
                    ->has('filters')
            );
    }

    // =========================================================================
    // STORE
    // =========================================================================

    public function test_creates_plan(): void
    {
        // Plans carry no modules — modules are sold via products
        // (plan/product subscription split).
        $payload = [
            'name'          => 'Gold Plan',
            'price_monthly' => 99.00,
            'currency'      => 'USD',
        ];

        $response = $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.plans.store'), $payload);

        $response->assertRedirect();

        $plan = Plan::where('name', 'Gold Plan')->first();
        $this->assertNotNull($plan, 'Plan should be persisted in the database.');
    }

    public function test_store_validates_required_name(): void
    {
        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.plans.store'), [
                'price_monthly' => 49.00,
                'currency'      => 'USD',
            ])
            ->assertSessionHasErrors('name');
    }

    public function test_store_validates_required_price_monthly(): void
    {
        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.plans.store'), [
                'name'     => 'No Price Plan',
                'currency' => 'USD',
            ])
            ->assertSessionHasErrors('price_monthly');
    }

    // =========================================================================
    // ARCHIVE
    // =========================================================================

    public function test_archives_plan(): void
    {
        $plan = Plan::factory()->create(['status' => 'active', 'is_active' => true]);

        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.plans.archive', $plan))
            ->assertRedirect();

        $this->assertDatabaseHas('plans', [
            'id'     => $plan->id,
            'status' => 'archived',
        ]);
    }

    public function test_archive_sets_is_active_false(): void
    {
        $plan = Plan::factory()->create(['is_active' => true]);

        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.plans.archive', $plan));

        $this->assertDatabaseHas('plans', [
            'id'        => $plan->id,
            'is_active' => false,
        ]);
    }

    // =========================================================================
    // DELETE — blocked when active subscriptions exist
    // =========================================================================

    public function test_cannot_delete_plan_with_active_subscribers(): void
    {
        $plan   = Plan::factory()->create(['is_active' => true]);
        $tenant = Tenant::factory()->active()->create();

        // Create an active subscription record directly.
        Subscription::create([
            'billable_type' => Tenant::class,
            'billable_id'   => $tenant->id,
            'type'          => 'default',
            'name'          => 'default',
            'plan_id'       => $plan->id,
            'status'        => Subscription::STATUS_ACTIVE,
            'starts_at'     => now(),
        ]);

        $response = $this->actingAs($this->admin, 'landlord')
            ->delete(route('platform.admin.plans.destroy', $plan));

        // PlanService::delete() throws RuntimeException for plans with active subscriptions.
        // The framework renders this as 500 unless a specific handler converts to 422.
        $this->assertContains($response->getStatusCode(), [422, 500]);

        // Crucially, the plan must NOT be deleted.
        $this->assertDatabaseHas('plans', ['id' => $plan->id]);
    }

    // =========================================================================
    // CLONE
    // =========================================================================

    public function test_cloned_plan_has_different_slug(): void
    {
        $plan = Plan::factory()->create(['name' => 'Pro Plan', 'slug' => 'pro-plan']);

        $response = $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.plans.clone', $plan));

        $response->assertRedirect();

        // A new plan with a different slug must exist.
        $cloned = Plan::where('name', 'Pro Plan (Copy)')->first();
        $this->assertNotNull($cloned, 'Cloned plan should be persisted.');
        $this->assertNotEquals($plan->slug, $cloned->slug, 'Cloned plan must have a different slug.');
    }

    public function test_cloned_plan_is_created_as_draft(): void
    {
        $plan = Plan::factory()->create(['slug' => 'enterprise-clone-source']);

        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.plans.clone', $plan));

        $cloned = Plan::where('name', $plan->name.' (Copy)')->first();
        $this->assertNotNull($cloned);
        $this->assertEquals('draft', $cloned->status, 'Cloned plan should start as draft.');
    }

    // =========================================================================
    // UNAUTHENTICATED ACCESS
    // =========================================================================

    public function test_unauthenticated_request_to_plan_index_is_redirected(): void
    {
        $this->get(route('platform.admin.plans.index'))
            ->assertRedirect();
    }

    public function test_unauthenticated_request_to_plan_store_is_redirected(): void
    {
        $this->post(route('platform.admin.plans.store'), ['name' => 'X', 'price_monthly' => 9, 'currency' => 'USD'])
            ->assertRedirect();
    }
}
