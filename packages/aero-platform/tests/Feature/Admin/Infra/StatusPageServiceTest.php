<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature\Admin\Infra;

use Aero\Contracts\AuditServiceInterface;
use Aero\Platform\Models\Infra\StatusIncident;
use Aero\Platform\Models\Infra\StatusPageComponent;
use Aero\Platform\Models\LandlordUser;
use Aero\Platform\Services\Infra\StatusPageService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class StatusPageServiceTest extends TestCase
{
    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    protected LandlordUser $admin;

    protected StatusPageService $svc;

    public function runDatabaseMigrations(): void
    {
        $this->beforeRefreshingDatabase();
        $this->refreshTestDatabase();
        $this->afterRefreshingDatabase();
    }

    private function shareSqliteAcrossConnections(): void
    {
        $cfg = ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true];
        config([
            'database.connections.mysql' => $cfg,
            'database.connections.central' => $cfg,
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

        $audit = $this->createMock(AuditServiceInterface::class);
        $this->svc = new StatusPageService($audit);
    }

    public function test_incident_created_and_stored(): void
    {
        $incident = $this->svc->createIncident([
            'title' => 'Database degradation',
            'status' => 'investigating',
            'impact' => 'major',
            'component_ids' => [],
        ]);

        $this->assertInstanceOf(StatusIncident::class, $incident);
        $this->assertDatabaseHas('status_incidents', [
            'title' => 'Database degradation',
            'status' => 'investigating',
            'impact' => 'major',
        ]);
    }

    public function test_component_status_can_be_set(): void
    {
        $component = StatusPageComponent::create([
            'name' => 'API',
            'status' => 'operational',
            'order_index' => 1,
        ]);

        $updated = $this->svc->setComponentStatus($component, 'degraded');

        $this->assertSame('degraded', $updated->status);
        $this->assertDatabaseHas('status_page_components', [
            'id' => $component->id,
            'status' => 'degraded',
        ]);
    }

    public function test_resolve_incident_sets_resolved_status_and_timestamp(): void
    {
        $incident = StatusIncident::create([
            'title' => 'Outage',
            'status' => 'monitoring',
            'impact' => 'critical',
            'started_at' => now()->subHour(),
        ]);

        $resolved = $this->svc->resolveIncident($incident);

        $this->assertSame('resolved', $resolved->status);
        $this->assertNotNull($resolved->resolved_at);
    }

    public function test_update_incident_appends_update_entry(): void
    {
        $incident = StatusIncident::create([
            'title' => 'Storage issue',
            'status' => 'investigating',
            'impact' => 'minor',
            'started_at' => now()->subMinutes(30),
        ]);

        $this->svc->updateIncident($incident, [
            'status' => 'identified',
            'update_message' => 'Root cause identified as disk full.',
        ]);

        $incident->refresh();
        $this->assertCount(1, $incident->updates);
        $this->assertSame('Root cause identified as disk full.', $incident->updates[0]['message']);
    }
}
