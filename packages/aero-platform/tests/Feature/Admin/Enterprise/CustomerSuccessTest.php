<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature\Admin\Enterprise;

use Aero\Platform\Models\Enterprise\CsmAssignment;
use Aero\Platform\Models\Enterprise\NpsSurveyResponse;
use Aero\Platform\Models\Enterprise\TenantHealthScore;
use Aero\Platform\Services\Enterprise\CustomerSuccessService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * P-11 — CustomerSuccessService
 *
 * Tests cover: health score upsert per tenant, CSM assignment audit log,
 * and NPS survey sent audit log.
 */
class CustomerSuccessTest extends TestCase
{
    use DatabaseMigrations;

    protected CustomerSuccessService $service;

    public function runDatabaseMigrations(): void
    {
        $this->shareSqliteAcrossConnections();
        $this->artisan('migrate:fresh');
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

        Gate::before(fn () => true);

        $this->service = app(CustomerSuccessService::class);
    }

    // =========================================================================
    // HEALTH SCORES
    // =========================================================================

    public function test_health_score_stored_per_tenant(): void
    {
        $tenantId = (string) Str::uuid();

        $health = $this->service->upsertHealthScore($tenantId, [
            'login_frequency' => 80,
            'feature_breadth' => 60,
            'support_tickets' => 90,
            'payment_history' => 100,
        ]);

        $this->assertInstanceOf(TenantHealthScore::class, $health);
        $this->assertDatabaseHas('tenant_health_scores', [
            'tenant_id' => $tenantId,
        ]);

        $persisted = TenantHealthScore::where('tenant_id', $tenantId)->firstOrFail();
        $this->assertSame($tenantId, $persisted->tenant_id);
        $this->assertGreaterThan(0, $persisted->score);
    }

    public function test_health_score_upserts_on_second_call_for_same_tenant(): void
    {
        $tenantId = (string) Str::uuid();

        $this->service->upsertHealthScore($tenantId, [
            'login_frequency' => 20,
            'feature_breadth' => 10,
            'support_tickets' => 10,
            'payment_history' => 10,
        ]);

        $this->service->upsertHealthScore($tenantId, [
            'login_frequency' => 100,
            'feature_breadth' => 100,
            'support_tickets' => 100,
            'payment_history' => 100,
        ]);

        // Only one row should exist for this tenant.
        $this->assertSame(1, TenantHealthScore::where('tenant_id', $tenantId)->count());

        $persisted = TenantHealthScore::where('tenant_id', $tenantId)->firstOrFail();
        // Second call had high signals — score should be 100.
        $this->assertSame(100, $persisted->score);
    }

    public function test_health_score_trend_calculated_on_upsert(): void
    {
        $tenantId = (string) Str::uuid();

        // First upsert — no prior record so trend = 'stable'.
        $first = $this->service->upsertHealthScore($tenantId, [
            'login_frequency' => 50,
            'feature_breadth' => 50,
            'support_tickets' => 50,
            'payment_history' => 50,
        ]);

        $this->assertSame('stable', $first->trend);

        // Second upsert with much higher score — trend should become 'up'.
        $second = $this->service->upsertHealthScore($tenantId, [
            'login_frequency' => 100,
            'feature_breadth' => 100,
            'support_tickets' => 100,
            'payment_history' => 100,
        ]);

        $this->assertSame('up', $second->trend);
    }

    // =========================================================================
    // CSM ASSIGNMENT — AUDIT LOG
    // =========================================================================

    public function test_csm_assignment_writes_audit_log(): void
    {
        $tenantId = (string) Str::uuid();
        $csmUserId = (string) Str::uuid();

        $this->service->assignCsm($tenantId, $csmUserId);

        $this->assertDatabaseHas('platform_audit_logs', [
            'event_type' => 'platform.customer_success.csm_assigned',
        ]);
    }

    public function test_csm_assignment_creates_csm_assignment_record(): void
    {
        $tenantId = (string) Str::uuid();
        $csmUserId = (string) Str::uuid();

        $this->service->assignCsm($tenantId, $csmUserId);

        $this->assertDatabaseHas('csm_assignments', [
            'tenant_id' => $tenantId,
            'csm_user_id' => $csmUserId,
        ]);
    }

    public function test_csm_assignment_upserts_on_reassignment(): void
    {
        $tenantId = (string) Str::uuid();
        $firstCsmId = (string) Str::uuid();
        $secondCsmId = (string) Str::uuid();

        $this->service->assignCsm($tenantId, $firstCsmId);
        $this->service->assignCsm($tenantId, $secondCsmId);

        // Only one CSM row per tenant.
        $this->assertSame(1, CsmAssignment::where('tenant_id', $tenantId)->count());

        $assignment = CsmAssignment::where('tenant_id', $tenantId)->firstOrFail();
        $this->assertSame($secondCsmId, (string) $assignment->csm_user_id);
    }

    // =========================================================================
    // NPS SURVEY — AUDIT LOG
    // =========================================================================

    public function test_nps_send_logs_audit(): void
    {
        $tenantId = (string) Str::uuid();

        $this->service->sendNpsSurvey($tenantId, 'user@example.com');

        $this->assertDatabaseHas('platform_audit_logs', [
            'event_type' => 'platform.customer_success.nps_survey_sent',
        ]);
    }

    public function test_nps_send_creates_nps_survey_response_record(): void
    {
        $tenantId = (string) Str::uuid();
        $email = 'survey@acme.test';

        $response = $this->service->sendNpsSurvey($tenantId, $email);

        $this->assertInstanceOf(NpsSurveyResponse::class, $response);
        $this->assertDatabaseHas('nps_survey_responses', [
            'tenant_id' => $tenantId,
            'user_email' => $email,
        ]);

        $persisted = NpsSurveyResponse::where('tenant_id', $tenantId)->firstOrFail();
        $this->assertNotNull($persisted->sent_at);
        $this->assertNull($persisted->responded_at);
    }
}
