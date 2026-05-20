<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature\Admin;

use Aero\HRMAC\Models\Role;
use Aero\Platform\Jobs\ExecuteBulkTenantAction;
use Aero\Platform\Models\BulkOperation;
use Aero\Platform\Models\LandlordUser;
use Aero\Platform\Models\Tenant;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class BulkTenantTest extends TestCase
{
    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    protected LandlordUser $admin;

    public function runDatabaseMigrations(): void
    {
        $this->beforeRefreshingDatabase();
        $this->refreshTestDatabase();
        $this->afterRefreshingDatabase();
    }

    private function shareSqliteAcrossConnections(): void
    {
        $sqliteConfig = ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true];
        config(['database.connections.mysql' => $sqliteConfig, 'database.connections.central' => $sqliteConfig, 'tenancy.database.central_connection' => 'sqlite']);
        $this->app['db']->purge('mysql');
        $this->app['db']->purge('central');
        $pdo = $this->app['db']->connection('sqlite')->getPdo();
        $this->app['db']->connection('mysql')->setPdo($pdo);
        $this->app['db']->connection('central')->setPdo($pdo);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->shareSqliteAcrossConnections();
        Gate::before(fn () => true);

        $role = Role::firstOrCreate(
            ['name' => 'Super Administrator', 'guard_name' => 'landlord'],
        );

        $this->admin = LandlordUser::factory()->create();
        $this->admin->assignRole($role);
    }

    // =========================================================================
    // HAPPY PATH — BULK SUSPEND
    // =========================================================================

    public function test_bulk_suspend_dispatches_one_job_per_tenant(): void
    {
        Bus::fake();

        $tenants = Tenant::factory()->count(3)->active()->create();
        $ids = $tenants->pluck('id')->all();

        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.tenants.bulk.execute'), [
                'type' => 'suspend',
                'tenant_ids' => $ids,
                'reason' => 'Mass suspension for compliance audit',
            ])
            ->assertRedirect();

        Bus::assertDispatched(ExecuteBulkTenantAction::class, 3);

        foreach ($tenants as $tenant) {
            Bus::assertDispatched(
                ExecuteBulkTenantAction::class,
                fn (ExecuteBulkTenantAction $job) => $job->tenantId === $tenant->id
                    && $job->type === 'suspend'
                    && $job->payload['reason'] === 'Mass suspension for compliance audit'
            );
        }
    }

    public function test_bulk_suspend_creates_bulk_operation_record(): void
    {
        Bus::fake();

        $tenants = Tenant::factory()->count(2)->active()->create();
        $ids = $tenants->pluck('id')->all();

        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.tenants.bulk.execute'), [
                'type' => 'suspend',
                'tenant_ids' => $ids,
                'reason' => 'Overdue invoices',
            ]);

        $this->assertDatabaseHas('bulk_operations', [
            'type' => 'suspend',
            'status' => 'queued',
            'total' => 2,
            'processed' => 0,
        ]);
    }

    public function test_bulk_suspend_single_tenant_dispatches_exactly_one_job(): void
    {
        Bus::fake();

        $tenant = Tenant::factory()->active()->create();

        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.tenants.bulk.execute'), [
                'type' => 'suspend',
                'tenant_ids' => [$tenant->id],
                'reason' => 'Single tenant test',
            ]);

        Bus::assertDispatchedTimes(ExecuteBulkTenantAction::class, 1);
    }

    public function test_bulk_suspend_does_not_dispatch_when_validation_fails(): void
    {
        Bus::fake();

        // Missing reason for suspend type — validation should block dispatch.
        $tenant = Tenant::factory()->active()->create();

        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.tenants.bulk.execute'), [
                'type' => 'suspend',
                'tenant_ids' => [$tenant->id],
                // 'reason' intentionally omitted
            ]);

        Bus::assertNothingDispatched();
    }

    // =========================================================================
    // HAPPY PATH — BULK EMAIL
    // =========================================================================

    public function test_bulk_email_dispatches_one_job_per_tenant(): void
    {
        Bus::fake();

        $tenants = Tenant::factory()->count(4)->active()->create();
        $ids = $tenants->pluck('id')->all();

        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.tenants.bulk.execute'), [
                'type' => 'email',
                'tenant_ids' => $ids,
                'subject' => 'Important Platform Update',
                'body' => 'Please review the new terms of service.',
            ]);

        Bus::assertDispatched(ExecuteBulkTenantAction::class, 4);
    }

    // =========================================================================
    // VALIDATION
    // =========================================================================

    public function test_bulk_execute_rejects_missing_type(): void
    {
        $tenant = Tenant::factory()->active()->create();

        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.tenants.bulk.execute'), [
                'tenant_ids' => [$tenant->id],
                'reason' => 'test',
            ])
            ->assertSessionHasErrors('type');
    }

    public function test_bulk_execute_rejects_invalid_type(): void
    {
        $tenant = Tenant::factory()->active()->create();

        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.tenants.bulk.execute'), [
                'type' => 'delete-all', // not in allowed list
                'tenant_ids' => [$tenant->id],
            ])
            ->assertSessionHasErrors('type');
    }

    public function test_bulk_execute_rejects_empty_tenant_ids(): void
    {
        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.tenants.bulk.execute'), [
                'type' => 'suspend',
                'tenant_ids' => [],
                'reason' => 'test',
            ])
            ->assertSessionHasErrors('tenant_ids');
    }

    public function test_bulk_execute_rejects_nonexistent_tenant_ids(): void
    {
        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.tenants.bulk.execute'), [
                'type' => 'suspend',
                'tenant_ids' => ['not-a-real-uuid'],
                'reason' => 'test',
            ])
            ->assertSessionHasErrors('tenant_ids.0');
    }

    public function test_bulk_suspend_requires_reason(): void
    {
        $tenant = Tenant::factory()->active()->create();

        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.tenants.bulk.execute'), [
                'type' => 'suspend',
                'tenant_ids' => [$tenant->id],
                // missing reason
            ])
            ->assertSessionHasErrors('reason');
    }

    public function test_bulk_email_requires_subject_and_body(): void
    {
        $tenant = Tenant::factory()->active()->create();

        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.tenants.bulk.execute'), [
                'type' => 'email',
                'tenant_ids' => [$tenant->id],
                // missing subject and body
            ])
            ->assertSessionHasErrors(['subject', 'body']);
    }

    // =========================================================================
    // BULK OPERATION HISTORY
    // =========================================================================

    public function test_history_page_renders_inertia_component(): void
    {
        $this->actingAs($this->admin, 'landlord')
            ->get(route('platform.admin.tenants.bulk.history'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Platform/Admin/Tenants/Bulk')
                ->has('operations')
            );
    }

    public function test_history_lists_existing_operations(): void
    {
        Bus::fake();

        $tenant = Tenant::factory()->active()->create();
        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.tenants.bulk.execute'), [
                'type' => 'suspend',
                'tenant_ids' => [$tenant->id],
                'reason' => 'archived',
            ]);

        $this->actingAs($this->admin, 'landlord')
            ->get(route('platform.admin.tenants.bulk.history'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('operations'));
    }

    // =========================================================================
    // AUTHORIZATION
    // =========================================================================

    public function test_unauthenticated_bulk_execute_is_redirected(): void
    {
        $tenant = Tenant::factory()->active()->create();

        $this->post(route('platform.admin.tenants.bulk.execute'), [
            'type' => 'suspend',
            'tenant_ids' => [$tenant->id],
            'reason' => 'test',
        ])->assertRedirect();
    }

    public function test_unauthenticated_history_is_redirected(): void
    {
        $this->get(route('platform.admin.tenants.bulk.history'))
            ->assertRedirect();
    }
}
