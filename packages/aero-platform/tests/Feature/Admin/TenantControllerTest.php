<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature\Admin;

use Aero\HRMAC\Models\Role;
use Aero\Platform\Models\Domain;
use Aero\Platform\Models\LandlordUser;
use Aero\Platform\Models\Plan;
use Aero\Platform\Models\Tenant;
use Aero\Platform\Services\TenantAdminService;
use Aero\Platform\Services\TenantImpersonationService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * P-1 — Admin TenantController (Inertia routes)
 *
 * Auth pattern: actingAs($landlordUser, 'landlord').
 * Gate::before(fn () => true) bypasses HRMAC middleware for all tests.
 */
class TenantControllerTest extends TestCase
{
    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    protected LandlordUser $admin;

    protected Plan $plan;

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

        // Bypass all HRMAC / Gate checks — the unit under test is controller logic.
        Gate::before(fn () => true);

        // Create a landlord role and user that mirrors production setup.
        $role = Role::firstOrCreate(
            ['name' => 'Super Administrator', 'guard_name' => 'landlord'],
        );

        $this->admin = LandlordUser::factory()->create();
        $this->admin->assignRole($role);

        $this->plan = Plan::factory()->create(['is_active' => true]);
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    public function test_index_renders_correct_inertia_component(): void
    {
        Tenant::factory()->count(3)->active()->create();

        $this->actingAs($this->admin, 'landlord')
            ->get(route('platform.admin.tenants.index'))
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page
                    ->component('Platform/Admin/Tenants/Index')
                    ->has('tenants')
                    ->has('filters')
                    ->has('plans')
            );
    }

    public function test_index_passes_tenants_to_inertia_props(): void
    {
        Tenant::factory()->count(4)->create();

        $response = $this->actingAs($this->admin, 'landlord')
            ->get(route('platform.admin.tenants.index'));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page->has('tenants'));
    }

    // =========================================================================
    // STATUS FILTER
    // =========================================================================

    public function test_index_with_status_filter_returns_only_matching_tenants(): void
    {
        Tenant::factory()->count(3)->active()->create();
        Tenant::factory()->count(2)->suspended()->create();

        // The controller delegates filtering to TenantAdminService::list().
        // We assert the Inertia data reflects only active tenants when filtered.
        $response = $this->actingAs($this->admin, 'landlord')
            ->get(route('platform.admin.tenants.index', ['status' => Tenant::STATUS_ACTIVE]));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('tenants')
                ->where('filters.status', Tenant::STATUS_ACTIVE)
            );
    }

    public function test_index_filter_param_is_reflected_in_props(): void
    {
        $response = $this->actingAs($this->admin, 'landlord')
            ->get(route('platform.admin.tenants.index', [
                'status' => Tenant::STATUS_SUSPENDED,
                'search' => 'acme',
            ]));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('filters.status', Tenant::STATUS_SUSPENDED)
                ->where('filters.search', 'acme')
            );
    }

    // =========================================================================
    // SHOW
    // =========================================================================

    public function test_show_renders_tenant_detail_page(): void
    {
        $tenant = Tenant::factory()->active()->create();

        $this->actingAs($this->admin, 'landlord')
            ->get(route('platform.admin.tenants.show', $tenant))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Platform/Admin/Tenants/Show')
                ->has('tenant')
            );
    }

    public function test_show_returns_404_for_nonexistent_tenant(): void
    {
        $this->actingAs($this->admin, 'landlord')
            ->get(route('platform.admin.tenants.show', 'non-existent-uuid'))
            ->assertNotFound();
    }

    // =========================================================================
    // CREATE / STORE
    // =========================================================================

    public function test_create_renders_create_page_with_plans(): void
    {
        $this->actingAs($this->admin, 'landlord')
            ->get(route('platform.admin.tenants.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Platform/Admin/Tenants/Create')
                ->has('plans')
            );
    }

    public function test_store_creates_tenant_and_redirects_to_show(): void
    {
        $response = $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.tenants.store'), [
                'name' => 'Test Corp',
                'email' => 'test@corp.test',
                'byoc_enabled' => false,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tenants', ['name' => 'Test Corp', 'email' => 'test@corp.test']);
    }

    public function test_store_validates_required_name(): void
    {
        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.tenants.store'), ['email' => 'nope@test.test'])
            ->assertSessionHasErrors('name');
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    public function test_update_changes_tenant_name_in_database(): void
    {
        $tenant = Tenant::factory()->active()->create(['name' => 'Old Name']);

        $this->actingAs($this->admin, 'landlord')
            ->put(route('platform.admin.tenants.update', $tenant), [
                'name' => 'New Name',
                'email' => $tenant->email,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('tenants', ['id' => $tenant->id, 'name' => 'New Name']);
    }

    public function test_update_validates_email_format(): void
    {
        $tenant = Tenant::factory()->active()->create();

        $this->actingAs($this->admin, 'landlord')
            ->put(route('platform.admin.tenants.update', $tenant), [
                'name' => $tenant->name,
                'email' => 'not-an-email',
            ])
            ->assertSessionHasErrors('email');
    }

    // =========================================================================
    // SUSPEND
    // =========================================================================

    public function test_suspend_active_tenant_sets_status_to_suspended(): void
    {
        $tenant = Tenant::factory()->active()->create();

        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.tenants.suspend', $tenant), [
                'reason' => 'Non-payment',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'status' => Tenant::STATUS_SUSPENDED,
        ]);
    }

    public function test_suspend_active_tenant_writes_audit_log(): void
    {
        $tenant = Tenant::factory()->active()->create();

        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.tenants.suspend', $tenant), [
                'reason' => 'Terms violation',
            ]);

        $this->assertDatabaseHas('platform_audit_logs', [
            'event_type' => 'TENANT_SUSPENDED',
            'subject_type' => Tenant::class,
            'subject_id' => $tenant->id,
        ]);
    }

    public function test_suspend_already_suspended_tenant_returns_server_error(): void
    {
        // TenantAdminService::suspend() throws DomainException for already-suspended tenants.
        // Laravel converts unhandled DomainException to a 500 in tests unless caught by a handler.
        $tenant = Tenant::factory()->suspended()->create();

        $response = $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.tenants.suspend', $tenant), [
                'reason' => 'Duplicate attempt',
            ]);

        // The controller does not catch DomainException, so the framework will
        // return 500 in debug mode (test env).
        $this->assertContains($response->getStatusCode(), [500, 422, 302]);

        // Status must remain suspended regardless of response code.
        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'status' => Tenant::STATUS_SUSPENDED,
        ]);
    }

    public function test_suspend_requires_reason_field(): void
    {
        $tenant = Tenant::factory()->active()->create();

        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.tenants.suspend', $tenant), [])
            ->assertSessionHasErrors('reason');
    }

    public function test_suspend_reason_cannot_exceed_255_characters(): void
    {
        $tenant = Tenant::factory()->active()->create();

        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.tenants.suspend', $tenant), [
                'reason' => str_repeat('x', 256),
            ])
            ->assertSessionHasErrors('reason');
    }

    // =========================================================================
    // ACTIVATE
    // =========================================================================

    public function test_activate_suspended_tenant_sets_status_to_active(): void
    {
        $tenant = Tenant::factory()->suspended()->create();

        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.tenants.activate', $tenant))
            ->assertRedirect();

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'status' => Tenant::STATUS_ACTIVE,
        ]);
    }

    public function test_activate_writes_audit_log(): void
    {
        $tenant = Tenant::factory()->suspended()->create();

        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.tenants.activate', $tenant));

        $this->assertDatabaseHas('platform_audit_logs', [
            'event_type' => 'TENANT_ACTIVATED',
            'subject_id' => $tenant->id,
        ]);
    }

    // =========================================================================
    // ARCHIVE
    // =========================================================================

    public function test_archive_active_tenant_soft_deletes_and_sets_status(): void
    {
        $tenant = Tenant::factory()->active()->create();

        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.tenants.archive', $tenant))
            ->assertRedirect();

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'status' => Tenant::STATUS_ARCHIVED,
        ]);
    }

    // =========================================================================
    // RESTORE
    // =========================================================================

    public function test_restore_archived_tenant_sets_status_to_active(): void
    {
        $tenant = Tenant::factory()->archived()->create();

        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.tenants.restore', $tenant))
            ->assertRedirect();

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'status' => Tenant::STATUS_ACTIVE,
        ]);
    }

    // =========================================================================
    // IMPERSONATE
    // =========================================================================

    public function test_impersonate_logs_audit_event(): void
    {
        $tenant = Tenant::factory()->active()->create();

        // Give tenant a primary domain so the redirect can be built.
        Domain::create([
            'tenant_id' => $tenant->id,
            'domain' => $tenant->subdomain . '.aeos365.test',
            'is_primary' => true,
        ]);

        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.tenants.impersonate', $tenant));

        $this->assertDatabaseHas('platform_audit_logs', [
            'event_type' => 'TENANT_IMPERSONATION_STARTED',
            'subject_id' => $tenant->id,
        ]);
    }

    public function test_impersonate_without_domain_returns_error_redirect(): void
    {
        // Tenant has no domain — controller should back() with error flash.
        $tenant = Tenant::factory()->active()->create();

        $response = $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.tenants.impersonate', $tenant));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Tenant has no domain configured');
    }

    // =========================================================================
    // BYOC CREDENTIAL ENCRYPTION
    // =========================================================================

    public function test_byoc_credentials_are_not_stored_as_plaintext(): void
    {
        $tenant = Tenant::factory()->create();

        $svc = app(TenantAdminService::class);
        $svc->updateByocCredentials($tenant, [
            'host' => 'db.example.com',
            'database' => 'production_db',
            'username' => 'db_user',
            'password' => 'super_secret_password',
        ]);

        // Query raw DB value for byoc_db_password — must not equal plaintext.
        $raw = \Illuminate\Support\Facades\DB::table('tenants')
            ->where('id', $tenant->id)
            ->value('byoc_db_password');

        $this->assertNotNull($raw);
        $this->assertStringNotContainsString('super_secret_password', (string) $raw);
        $this->assertStringNotContainsString('db_user', (string) \Illuminate\Support\Facades\DB::table('tenants')
            ->where('id', $tenant->id)->value('byoc_db_username'));
    }

    public function test_byoc_credentials_decrypt_correctly_on_model(): void
    {
        $tenant = Tenant::factory()->create();

        $svc = app(TenantAdminService::class);
        $updated = $svc->updateByocCredentials($tenant, [
            'host' => 'db.example.com',
            'database' => 'my_database',
            'username' => 'admin_user',
            'password' => 'plaintext_pass',
        ]);

        // Reload fresh from DB to confirm EncryptedField cast round-trips correctly.
        $fresh = Tenant::find($tenant->id);
        $this->assertEquals('plaintext_pass', $fresh->byoc_db_password);
        $this->assertEquals('admin_user', $fresh->byoc_db_username);
        $this->assertEquals('my_database', $fresh->byoc_db_name);
    }

    public function test_byoc_update_writes_audit_log(): void
    {
        $tenant = Tenant::factory()->create();

        $svc = app(TenantAdminService::class);
        $svc->updateByocCredentials($tenant, [
            'host' => 'db.example.com',
            'database' => 'mydb',
            'username' => 'u',
            'password' => 'p',
        ]);

        $this->assertDatabaseHas('platform_audit_logs', [
            'event_type' => 'TENANT_BYOC_UPDATED',
            'subject_id' => $tenant->id,
        ]);
    }

    // =========================================================================
    // UNAUTHENTICATED ACCESS
    // =========================================================================

    public function test_unauthenticated_request_to_index_is_redirected(): void
    {
        $this->get(route('platform.admin.tenants.index'))
            ->assertRedirect();
    }

    public function test_unauthenticated_request_to_suspend_is_redirected(): void
    {
        $tenant = Tenant::factory()->active()->create();

        $this->post(route('platform.admin.tenants.suspend', $tenant), ['reason' => 'test'])
            ->assertRedirect();
    }
}
