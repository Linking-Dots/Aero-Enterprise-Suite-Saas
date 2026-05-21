<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature\Admin;

use Aero\Platform\Models\LandlordUser;
use Aero\Platform\Models\ProspectLead;
use Aero\Platform\Models\Tenant;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * P-6 — LeadController (Admin)
 *
 * Auth pattern: actingAs($admin, 'landlord').
 * Gate::before(fn () => true) bypasses HRMAC middleware for all tests.
 * Uses DatabaseMigrations + shareSqliteAcrossConnections() pattern.
 */
class LeadTest extends TestCase
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
        config([
            'database.connections.mysql' => $sqliteConfig,
            'database.connections.central' => $sqliteConfig,
            'tenancy.database.central_connection' => 'sqlite',
        ]);
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
        $this->admin = LandlordUser::factory()->create();
    }

    public function test_index_renders_inertia_component(): void
    {
        ProspectLead::create([
            'email' => 'lead@example.com',
            'name' => 'Test Lead',
            'source' => ProspectLead::SOURCE_WEBSITE,
            'status' => ProspectLead::STATUS_NEW,
        ]);

        $this->actingAs($this->admin, 'landlord')
            ->get(route('admin.leads.index'))
            ->assertOk();
    }

    public function test_can_create_lead(): void
    {
        $this->actingAs($this->admin, 'landlord')
            ->postJson(route('admin.leads.store'), [
                'email' => 'prospect@acme.com',
                'name' => 'John Prospect',
                'company_name' => 'Acme Corp',
                'source' => ProspectLead::SOURCE_DEMO_REQUEST,
            ])
            ->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('prospect_leads', [
            'email' => 'prospect@acme.com',
            'source' => ProspectLead::SOURCE_DEMO_REQUEST,
        ]);
    }

    public function test_can_assign_lead_to_landlord_user(): void
    {
        $lead = ProspectLead::create([
            'email' => 'lead2@example.com',
            'source' => ProspectLead::SOURCE_WEBSITE,
            'status' => ProspectLead::STATUS_NEW,
        ]);

        $assignee = LandlordUser::factory()->create();

        $this->actingAs($this->admin, 'landlord')
            ->postJson(route('admin.leads.assign', $lead), [
                'user_id' => $assignee->id,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('prospect_leads', [
            'id' => $lead->id,
            'assigned_to' => $assignee->id,
        ]);
    }

    public function test_convert_action_stamps_converted_tenant_id(): void
    {
        $lead = ProspectLead::create([
            'email' => 'convert@example.com',
            'source' => ProspectLead::SOURCE_REFERRAL,
            'status' => ProspectLead::STATUS_QUALIFIED,
        ]);

        // Create a tenant to link to
        $tenant = Tenant::create([
            'name' => 'Converted Corp',
            'subdomain' => 'converted-corp',
            'status' => 'active',
        ]);

        $this->actingAs($this->admin, 'landlord')
            ->postJson(route('admin.leads.convert', $lead), [
                'tenant_id' => $tenant->id,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('prospect_leads', [
            'id' => $lead->id,
            'converted_tenant_id' => $tenant->id,
            'status' => ProspectLead::STATUS_CONVERTED,
        ]);
    }

    public function test_can_update_lead_status(): void
    {
        $lead = ProspectLead::create([
            'email' => 'status@example.com',
            'source' => ProspectLead::SOURCE_WEBSITE,
            'status' => ProspectLead::STATUS_NEW,
        ]);

        $this->actingAs($this->admin, 'landlord')
            ->putJson(route('admin.leads.status', $lead), [
                'status' => ProspectLead::STATUS_CONTACTED,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('prospect_leads', [
            'id' => $lead->id,
            'status' => ProspectLead::STATUS_CONTACTED,
        ]);
    }

    public function test_can_delete_lead(): void
    {
        $lead = ProspectLead::create([
            'email' => 'delete@example.com',
            'source' => ProspectLead::SOURCE_WEBSITE,
            'status' => ProspectLead::STATUS_LOST,
        ]);

        $this->actingAs($this->admin, 'landlord')
            ->deleteJson(route('admin.leads.destroy', $lead))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('prospect_leads', ['id' => $lead->id]);
    }

    public function test_create_lead_requires_email(): void
    {
        $this->actingAs($this->admin, 'landlord')
            ->postJson(route('admin.leads.store'), [
                'name' => 'No Email',
                'source' => ProspectLead::SOURCE_WEBSITE,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_assign_requires_existing_landlord_user(): void
    {
        $lead = ProspectLead::create([
            'email' => 'assign-fail@example.com',
            'source' => ProspectLead::SOURCE_WEBSITE,
            'status' => ProspectLead::STATUS_NEW,
        ]);

        $this->actingAs($this->admin, 'landlord')
            ->postJson(route('admin.leads.assign', $lead), [
                'user_id' => 99999,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['user_id']);
    }
}
