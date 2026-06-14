<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature;
use Aero\Platform\Database\Factories\LandlordUserFactory;

use Aero\HRMAC\Models\Role;
use Aero\Auth\Models\User;
use Aero\Platform\Models\Plan;
use Aero\Platform\Models\Tenant;
use Aero\Platform\Services\Monitoring\Tenant\TenantProvisioner;
use Aero\Platform\Services\Tenant\TenantRetentionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @covers \Aero\Platform\Http\Controllers\TenantController
 */
class TenantControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $platformAdmin;

    protected User $regularUser;

    protected Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setupRolesAndUsers();
        $this->plan = Plan::factory()->create();
    }

    protected function setupRolesAndUsers(): void
    {
        // Create Super Administrator role
        $superAdminRole = Role::create([
            'name' => 'Super Administrator',
            'guard_name' => 'landlord',
        ]);

        // Create Platform Admin role
        $platformAdminRole = Role::create([
            'name' => 'Platform Admin',
            'guard_name' => 'landlord',
        ]);

        // Create Regular User role
        $regularRole = Role::create([
            'name' => 'Regular User',
            'guard_name' => 'landlord',
        ]);

        // Create users
        $this->superAdmin = LandlordUserFactory::new()->create();
        $this->superAdmin->assignRole($superAdminRole);

        $this->platformAdmin = LandlordUserFactory::new()->create();
        $this->platformAdmin->assignRole($platformAdminRole);

        $this->regularUser = LandlordUserFactory::new()->create();
        $this->regularUser->assignRole($regularRole);
    }

    // =========================================================================
    // INDEX / LIST TENANTS
    // =========================================================================

    public function test_super_admin_can_list_tenants(): void
    {
        Tenant::factory()->count(5)->create();

        $response = $this->actingAs($this->superAdmin, 'landlord')
            ->getJson(route('api.v1.tenants.index'));

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            ]);

        $this->assertCount(5, $response->json('data'));
    }

    public function test_tenant_list_can_be_filtered(): void
    {
        Tenant::factory()->active()->count(3)->create();
        Tenant::factory()->suspended()->count(2)->create();

        $response = $this->actingAs($this->superAdmin, 'landlord')
            ->getJson(route('api.v1.tenants.index', ['status' => Tenant::STATUS_ACTIVE]));

        $response->assertOk();
        $this->assertCount(3, $response->json('data'));
    }

    public function test_tenant_list_can_be_searched(): void
    {
        Tenant::factory()->create(['name' => 'Acme Corporation']);
        Tenant::factory()->create(['name' => 'Globex Industries']);
        Tenant::factory()->create(['name' => 'Umbrella Corp']);

        $response = $this->actingAs($this->superAdmin, 'landlord')
            ->getJson(route('api.v1.tenants.index', ['search' => 'Acme']));

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Acme Corporation', $response->json('data.0.name'));
    }

    public function test_tenant_list_can_include_archived(): void
    {
        Tenant::factory()->active()->count(2)->create();
        Tenant::factory()->archived()->count(1)->create();

        // Without include_archived
        $response = $this->actingAs($this->superAdmin, 'landlord')
            ->getJson(route('api.v1.tenants.index'));

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));

        // With include_archived
        $response = $this->actingAs($this->superAdmin, 'landlord')
            ->getJson(route('api.v1.tenants.index', ['include_archived' => true]));

        $response->assertOk();
        $this->assertCount(3, $response->json('data'));
    }

    // =========================================================================
    // STATS
    // =========================================================================

    public function test_super_admin_can_view_stats(): void
    {
        Tenant::factory()->active()->count(5)->create();
        Tenant::factory()->suspended()->count(2)->create();
        Tenant::factory()->pending()->count(1)->create();

        $response = $this->actingAs($this->superAdmin, 'landlord')
            ->getJson(route('api.v1.tenants.stats'));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'total',
                    'active',
                    'pending',
                    'suspended',
                    'archived',
                    'on_trial',
                ],
            ]);

        $this->assertEquals(8, $response->json('data.total'));
        $this->assertEquals(5, $response->json('data.active'));
        $this->assertEquals(2, $response->json('data.suspended'));
        $this->assertEquals(1, $response->json('data.pending'));
    }

    // =========================================================================
    // SHOW / VIEW TENANT
    // =========================================================================

    public function test_super_admin_can_view_tenant(): void
    {
        $tenant = Tenant::factory()->withPlan($this->plan)->create();

        $response = $this->actingAs($this->superAdmin, 'landlord')
            ->getJson(route('api.v1.tenants.show', $tenant));

        $response->assertOk()
            ->assertJsonPath('data.id', $tenant->id)
            ->assertJsonPath('data.name', $tenant->name);
    }

    public function test_view_tenant_returns404_for_non_existent(): void
    {
        $response = $this->actingAs($this->superAdmin, 'landlord')
            ->getJson(route('api.v1.tenants.show', 'non-existent-uuid'));

        $response->assertNotFound();
    }

    // =========================================================================
    // STORE / CREATE TENANT
    // =========================================================================

    public function test_super_admin_can_create_tenant(): void
    {
        // Mock the provisioner to prevent actual provisioning
        $this->mock(TenantProvisioner::class, function ($mock) {
            $mock->shouldReceive('dispatch')->once();
        });

        $tenantData = [
            'name' => 'New Test Company',
            'subdomain' => 'newtestcompany',
            'email' => 'contact@newtestcompany.com',
            'phone' => '+1234567890',
            'type' => 'business',
            'plan_id' => $this->plan->id,
            'admin_name' => 'John Admin',
            'admin_email' => 'admin@newtestcompany.com',
        ];

        $response = $this->actingAs($this->superAdmin, 'landlord')
            ->postJson(route('api.v1.tenants.store'), $tenantData);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'New Test Company')
            ->assertJsonPath('data.subdomain', 'newtestcompany')
            ->assertJsonPath('data.status', Tenant::STATUS_PENDING);

        $this->assertDatabaseHas('tenants', [
            'name' => 'New Test Company',
            'subdomain' => 'newtestcompany',
        ]);
    }

    public function test_create_tenant_validates_required_fields(): void
    {
        $response = $this->actingAs($this->superAdmin, 'landlord')
            ->postJson(route('api.v1.tenants.store'), []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'subdomain', 'email', 'type', 'plan_id', 'admin_name', 'admin_email']);
    }

    public function test_create_tenant_validates_subdomain_uniqueness(): void
    {
        Tenant::factory()->create(['subdomain' => 'existing']);

        $tenantData = [
            'name' => 'New Company',
            'subdomain' => 'existing',
            'email' => 'new@company.com',
            'type' => 'business',
            'plan_id' => $this->plan->id,
            'admin_name' => 'Admin',
            'admin_email' => 'admin@company.com',
        ];

        $response = $this->actingAs($this->superAdmin, 'landlord')
            ->postJson(route('api.v1.tenants.store'), $tenantData);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['subdomain']);
    }

    public function test_create_tenant_validates_subdomain_format(): void
    {
        $tenantData = [
            'name' => 'New Company',
            'subdomain' => 'invalid subdomain!',
            'email' => 'new@company.com',
            'type' => 'business',
            'plan_id' => $this->plan->id,
            'admin_name' => 'Admin',
            'admin_email' => 'admin@company.com',
        ];

        $response = $this->actingAs($this->superAdmin, 'landlord')
            ->postJson(route('api.v1.tenants.store'), $tenantData);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['subdomain']);
    }

    // =========================================================================
    // UPDATE TENANT
    // =========================================================================

    public function test_super_admin_can_update_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        $response = $this->actingAs($this->superAdmin, 'landlord')
            ->putJson(route('api.v1.tenants.update', $tenant), [
                'name' => 'Updated Company Name',
                'email' => 'updated@company.com',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Company Name')
            ->assertJsonPath('data.email', 'updated@company.com');

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'name' => 'Updated Company Name',
        ]);
    }

    public function test_update_tenant_validates_email(): void
    {
        $tenant = Tenant::factory()->create();

        $response = $this->actingAs($this->superAdmin, 'landlord')
            ->putJson(route('api.v1.tenants.update', $tenant), [
                'email' => 'not-a-valid-email',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    // =========================================================================
    // SUSPEND TENANT
    // =========================================================================

    public function test_super_admin_can_suspend_active_tenant(): void
    {
        $tenant = Tenant::factory()->active()->create();

        $response = $this->actingAs($this->superAdmin, 'landlord')
            ->postJson(route('api.v1.tenants.suspend', $tenant), [
                'reason' => 'Payment overdue',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status', Tenant::STATUS_SUSPENDED);

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'status' => Tenant::STATUS_SUSPENDED,
        ]);
    }

    public function test_cannot_suspend_already_suspended_tenant(): void
    {
        $tenant = Tenant::factory()->suspended()->create();

        $response = $this->actingAs($this->superAdmin, 'landlord')
            ->postJson(route('api.v1.tenants.suspend', $tenant));

        $response->assertForbidden();
    }

    // =========================================================================
    // ACTIVATE TENANT
    // =========================================================================

    public function test_super_admin_can_activate_suspended_tenant(): void
    {
        $tenant = Tenant::factory()->suspended()->create();

        $response = $this->actingAs($this->superAdmin, 'landlord')
            ->postJson(route('api.v1.tenants.activate', $tenant));

        $response->assertOk()
            ->assertJsonPath('data.status', Tenant::STATUS_ACTIVE);

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'status' => Tenant::STATUS_ACTIVE,
        ]);
    }

    public function test_cannot_activate_already_active_tenant(): void
    {
        $tenant = Tenant::factory()->active()->create();

        $response = $this->actingAs($this->superAdmin, 'landlord')
            ->postJson(route('api.v1.tenants.activate', $tenant));

        $response->assertForbidden();
    }

    // =========================================================================
    // ARCHIVE / SOFT DELETE TENANT
    // =========================================================================

    public function test_super_admin_can_archive_tenant(): void
    {
        $tenant = Tenant::factory()->active()->create();

        $response = $this->actingAs($this->superAdmin, 'landlord')
            ->postJson(route('api.v1.tenants.archive', $tenant));

        $response->assertOk()
            ->assertJsonPath('message', 'Tenant archived successfully.');

        $this->assertSoftDeleted('tenants', ['id' => $tenant->id]);
    }

    // =========================================================================
    // RESTORE TENANT
    // =========================================================================

    public function test_super_admin_can_restore_archived_tenant(): void
    {
        $tenant = Tenant::factory()->archived()->create();

        // Mock retention service to allow restore
        $this->mock(TenantRetentionService::class, function ($mock) {
            $mock->shouldReceive('canRestore')->andReturn(true);
        });

        $response = $this->actingAs($this->superAdmin, 'landlord')
            ->postJson(route('api.v1.tenants.restore', $tenant->id));

        $response->assertOk()
            ->assertJsonPath('data.status', Tenant::STATUS_ACTIVE)
            ->assertJsonPath('message', 'Tenant restored successfully.');

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'status' => Tenant::STATUS_ACTIVE,
            'deleted_at' => null,
        ]);
    }

    public function test_cannot_restore_tenant_after_retention_period(): void
    {
        $tenant = Tenant::factory()->archived()->create();

        // Mock retention service to deny restore
        $this->mock(TenantRetentionService::class, function ($mock) {
            $mock->shouldReceive('canRestore')->andReturn(false);
        });

        $response = $this->actingAs($this->superAdmin, 'landlord')
            ->postJson(route('api.v1.tenants.restore', $tenant->id));

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'Retention period expired. Tenant cannot be restored.');
    }

    // =========================================================================
    // DELETE / DESTROY TENANT (Soft Delete with Retention)
    // =========================================================================

    public function test_super_admin_can_soft_delete_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        // Mock retention service
        $this->mock(TenantRetentionService::class, function ($mock) {
            $mock->shouldReceive('getRetentionExpiresAt')
                ->andReturn(now()->addDays(30));
        });

        $response = $this->actingAs($this->superAdmin, 'landlord')
            ->deleteJson(route('api.v1.tenants.destroy', $tenant), [
                'reason' => 'Customer requested account deletion',
                'confirm' => true,
            ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Tenant archived successfully. Can be restored within retention period.');

        $this->assertSoftDeleted('tenants', ['id' => $tenant->id]);
    }

    public function test_destroy_tenant_requires_reason(): void
    {
        $tenant = Tenant::factory()->create();

        $response = $this->actingAs($this->superAdmin, 'landlord')
            ->deleteJson(route('api.v1.tenants.destroy', $tenant), [
                'confirm' => true,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['reason']);
    }

    public function test_destroy_tenant_requires_confirmation(): void
    {
        $tenant = Tenant::factory()->create();

        $response = $this->actingAs($this->superAdmin, 'landlord')
            ->deleteJson(route('api.v1.tenants.destroy', $tenant), [
                'reason' => 'Test deletion',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['confirm']);
    }

    // =========================================================================
    // RETRY PROVISIONING
    // =========================================================================

    public function test_super_admin_can_retry_provisioning_for_failed_tenant(): void
    {
        $tenant = Tenant::factory()->failed()->create();

        // Mock provisioner
        $this->mock(TenantProvisioner::class, function ($mock) {
            $mock->shouldReceive('dispatch')->once();
        });

        $response = $this->actingAs($this->superAdmin, 'landlord')
            ->postJson(route('api.v1.tenants.retry-provisioning', $tenant));

        $response->assertOk()
            ->assertJsonPath('data.status', Tenant::STATUS_PROVISIONING)
            ->assertJsonPath('message', 'Provisioning retry started.');
    }

    public function test_cannot_retry_provisioning_for_non_failed_tenant(): void
    {
        $tenant = Tenant::factory()->active()->create();

        $response = $this->actingAs($this->superAdmin, 'landlord')
            ->postJson(route('api.v1.tenants.retry-provisioning', $tenant));

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'Only failed tenants can have their provisioning retried.');
    }

    // =========================================================================
    // AUTHORIZATION TESTS
    // =========================================================================

    public function test_unauthenticated_user_cannot_access_tenant_endpoints(): void
    {
        $tenant = Tenant::factory()->create();

        $this->getJson(route('api.v1.tenants.index'))->assertUnauthorized();
        $this->getJson(route('api.v1.tenants.show', $tenant))->assertUnauthorized();
        $this->postJson(route('api.v1.tenants.store'))->assertUnauthorized();
        $this->putJson(route('api.v1.tenants.update', $tenant))->assertUnauthorized();
        $this->deleteJson(route('api.v1.tenants.destroy', $tenant))->assertUnauthorized();
    }

    public function test_pagination_works_correctly(): void
    {
        Tenant::factory()->count(25)->create();

        $response = $this->actingAs($this->superAdmin, 'landlord')
            ->getJson(route('api.v1.tenants.index', ['per_page' => 10]));

        $response->assertOk()
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 25)
            ->assertJsonPath('meta.last_page', 3);

        $this->assertCount(10, $response->json('data'));
    }

    public function test_sorting_works_correctly(): void
    {
        Tenant::factory()->create(['name' => 'Alpha Company']);
        Tenant::factory()->create(['name' => 'Beta Corporation']);
        Tenant::factory()->create(['name' => 'Gamma Industries']);

        $response = $this->actingAs($this->superAdmin, 'landlord')
            ->getJson(route('api.v1.tenants.index', ['sort' => 'name', 'direction' => 'asc']));

        $response->assertOk();
        $this->assertEquals('Alpha Company', $response->json('data.0.name'));
        $this->assertEquals('Beta Corporation', $response->json('data.1.name'));
        $this->assertEquals('Gamma Industries', $response->json('data.2.name'));
    }

    // =========================================================================
    // FORCE LOGOUT
    // =========================================================================

    public function test_super_admin_can_force_logout_tenant_users(): void
    {
        $tenant = Tenant::factory()->active()->create();

        $response = $this->actingAs($this->superAdmin, 'landlord')
            ->postJson(route('api.v1.tenants.force-logout', $tenant));

        $response->assertOk()
            ->assertJsonPath('message', 'All user sessions have been terminated.');
    }

    // =========================================================================
    // TOGGLE MAINTENANCE MODE
    // =========================================================================

    public function test_super_admin_can_toggle_maintenance_mode(): void
    {
        $tenant = Tenant::factory()->active()->create(['maintenance_mode' => false]);

        // Enable maintenance mode
        $response = $this->actingAs($this->superAdmin, 'landlord')
            ->postJson(route('api.v1.tenants.toggle-maintenance', $tenant));

        $response->assertOk()
            ->assertJsonPath('maintenance_mode', true)
            ->assertJsonPath('message', 'Maintenance mode enabled.');

        // Disable maintenance mode
        $response = $this->actingAs($this->superAdmin, 'landlord')
            ->postJson(route('api.v1.tenants.toggle-maintenance', $tenant));

        $response->assertOk()
            ->assertJsonPath('maintenance_mode', false)
            ->assertJsonPath('message', 'Maintenance mode disabled.');
    }

    // =========================================================================
    // EXPORT
    // =========================================================================

    public function test_super_admin_can_export_tenants(): void
    {
        Tenant::factory()->count(3)->create();

        $response = $this->actingAs($this->superAdmin, 'landlord')
            ->get(route('api.v1.tenants.export'));

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->assertHeader('Content-Disposition');
    }

    public function test_export_can_be_filtered_by_status(): void
    {
        Tenant::factory()->active()->count(2)->create();
        Tenant::factory()->suspended()->count(1)->create();

        $response = $this->actingAs($this->superAdmin, 'landlord')
            ->get(route('api.v1.tenants.export', ['status' => Tenant::STATUS_ACTIVE]));

        $response->assertOk();

        // Check CSV contains only active tenants (header + 2 data rows)
        $content = $response->streamedContent();
        $lines = explode("\n", trim($content));
        $this->assertCount(3, $lines); // Header + 2 active tenants
    }
}
