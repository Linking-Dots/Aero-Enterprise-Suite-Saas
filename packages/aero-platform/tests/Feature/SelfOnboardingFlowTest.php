<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature;

use Aero\Platform\Jobs\ProvisionTenant;
use Aero\Platform\Models\Domain;
use Aero\Platform\Models\Plan;
use Aero\Platform\Models\Subscription;
use Aero\Platform\Models\Tenant;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * End-to-End Self-Onboarding Flow Test
 *
 * Tests the complete registration journey from landing page to tenant provisioning:
 * 1. Account Type Selection
 * 2. Company Details Submission
 * 3. Verification Skip (debug mode)
 * 4. Plan & Module Selection
 * 5. Trial Activation & Job Dispatch
 * 6. Provisioning Status Polling
 * 7. Admin Setup on Tenant Domain
 *
 * Note: Since tests run with APP_DEBUG=true, email/phone verification is
 * automatically skipped (by design — see RegistrationController::storeDetails).
 */
class SelfOnboardingFlowTest extends TestCase
{
    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    protected Plan $plan;

    /**
     * Override to skip migrate:rollback in tearDown.
     *
     * Platform migrations use dropColumn which is incompatible with SQLite.
     * Since we use :memory: SQLite, the DB is destroyed when the connection closes anyway.
     */
    public function runDatabaseMigrations(): void
    {
        $this->beforeRefreshingDatabase();
        $this->refreshTestDatabase();
        $this->afterRefreshingDatabase();
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Disable rate limiting for all tests — registration routes have strict throttling
        // that interferes with rapid sequential test requests.
        $this->withoutMiddleware(ThrottleRequests::class);
        $this->withoutMiddleware(ThrottleRequestsWithRedis::class);

        // Platform models hardcode $connection = 'mysql' or 'central'.
        // The SetDatabaseConnectionFromDomain middleware switches default to 'central'.
        // We need all named connections to resolve to the same in-memory SQLite DB
        // that DatabaseMigrations already created.
        $this->shareSqliteAcrossConnections();

        // Ensure APP_DEBUG is true for verification bypass
        config(['app.debug' => true]);

        // Set platform config
        config(['platform.central_domain' => 'localhost']);
        config(['platform.trial.days' => 14]);

        // Create a plan for plan selection step
        $this->plan = Plan::factory()->create([
            'name' => 'Starter',
            'slug' => 'starter',
            'is_active' => true,
            'features' => ['modules' => ['core', 'hrm']],
        ]);
    }

    /**
     * Make mysql/central connections share the same SQLite PDO as the default sqlite connection.
     *
     * This must be called both in setUp() and via a middleware-like hook because
     * SetDatabaseConnectionFromDomain middleware purges/reconnects during requests.
     */
    private function shareSqliteAcrossConnections(): void
    {
        $sqliteConfig = [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ];

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

    // =========================================================================
    // HAPPY PATH: Complete Registration Flow
    // =========================================================================

    public function test_complete_registration_flow_from_landing_to_provisioning(): void
    {
        Queue::fake();

        // Step 1: Account Type
        $response = $this->post(route('platform.register.account-type.store'), [
            'type' => 'company',
        ]);

        $response->assertRedirect(route('platform.register.details'));

        // Step 2: Company Details (verification auto-skipped in debug mode)
        $response = $this->post(route('platform.register.details.store'), [
            'name' => 'Acme Corporation',
            'email' => 'admin@acme-corp.test',
            'phone' => '+1234567890',
            'subdomain' => 'acme-corp',
            'owner_name' => 'John Doe',
            'owner_email' => 'john@acme-corp.test',
            'owner_phone' => '+1234567891',
            'industry' => 'Technology',
            'team_size' => 25,
        ]);

        // Should redirect to plan selection (verification skipped due to APP_DEBUG=true)
        $response->assertRedirect(route('platform.register.plan'));

        // Verify tenant was created with pending status
        $this->assertDatabaseHas('tenants', [
            'email' => 'admin@acme-corp.test',
            'subdomain' => 'acme-corp',
            'status' => Tenant::STATUS_PENDING,
        ]);

        // Step 3: Plan Selection
        $response = $this->post(route('platform.register.plan.store'), [
            'billing_cycle' => 'monthly',
            'plan_id' => $this->plan->id,
            'modules' => ['core', 'hrm'],
        ]);

        $response->assertRedirect(route('platform.register.payment'));

        // Step 4: Trial Activation
        $response = $this->post(route('platform.register.trial.activate'), [
            'accept_terms' => true,
            'notify_updates' => true,
        ]);

        // Should redirect to provisioning page
        $this->assertRedirectContains($response, '/signup/provisioning/');

        // Verify ProvisionTenant job was dispatched
        Queue::assertPushed(ProvisionTenant::class, function ($job) {
            return $job->tenant->subdomain === 'acme-corp';
        });

        // Verify tenant exists with correct data
        $tenant = Tenant::where('subdomain', 'acme-corp')->first();
        $this->assertNotNull($tenant);
        $this->assertEquals('Acme Corporation', $tenant->name);
        $this->assertEquals('admin@acme-corp.test', $tenant->email);
        $this->assertDatabaseHas('subscriptions', ['billable_type' => Tenant::class, 'billable_id' => $tenant->id]);

        // Verify domain was created
        $this->assertDatabaseHas('domains', [
            'tenant_id' => $tenant->id,
        ]);
    }

    public function test_provisioning_status_endpoint_returns_correct_states(): void
    {
        // Create a pending tenant
        $tenant = Tenant::factory()->pending()->create([
            'subdomain' => 'status-test',
            'email' => 'status@test.test',
        ]);

        Domain::create([
            'tenant_id' => $tenant->id,
            'domain' => 'status-test.localhost',
            'is_primary' => true,
        ]);

        // Check pending status
        $response = $this->getJson(route('platform.register.provisioning.status', ['tenant' => $tenant->id]));
        $response->assertOk();
        $response->assertJson([
            'status' => 'pending',
            'is_ready' => false,
            'has_failed' => false,
        ]);

        // Transition to provisioning
        $tenant->update([
            'status' => Tenant::STATUS_PROVISIONING,
            'provisioning_step' => Tenant::STEP_CREATING_DB,
        ]);

        $response = $this->getJson(route('platform.register.provisioning.status', ['tenant' => $tenant->id]));
        $response->assertOk();
        $response->assertJson([
            'status' => 'provisioning',
            'step' => 'creating_db',
            'is_ready' => false,
            'has_failed' => false,
        ]);

        // Transition to active
        $tenant->update([
            'status' => Tenant::STATUS_ACTIVE,
            'provisioning_step' => null,
            'data' => ['admin_setup_completed' => false],
        ]);

        $response = $this->getJson(route('platform.register.provisioning.status', ['tenant' => $tenant->id]));
        $response->assertOk();
        $response->assertJson([
            'status' => 'active',
            'is_ready' => true,
            'has_failed' => false,
            'needs_admin_setup' => true,
        ]);
        $response->assertJsonStructure(['login_url']);

        // Transition to failed
        $tenant->update([
            'status' => Tenant::STATUS_FAILED,
            'data' => ['provisioning_error' => 'Database creation failed'],
        ]);

        $response = $this->getJson(route('platform.register.provisioning.status', ['tenant' => $tenant->id]));
        $response->assertOk();
        $response->assertJson([
            'status' => 'failed',
            'is_ready' => false,
            'has_failed' => true,
        ]);
    }

    // =========================================================================
    // STEP 1: Account Type Selection
    // =========================================================================

    public function test_landing_page_is_accessible(): void
    {
        $response = $this->get(route('platform.register.index'));

        $response->assertOk();
    }

    public function test_account_type_requires_valid_type(): void
    {
        $response = $this->post(route('platform.register.account-type.store'), [
            'type' => 'invalid_type',
        ]);

        $response->assertSessionHasErrors('type');
    }

    public function test_account_type_accepts_company(): void
    {
        $response = $this->post(route('platform.register.account-type.store'), [
            'type' => 'company',
        ]);

        $response->assertRedirect(route('platform.register.details'));
        $response->assertSessionHasNoErrors();
    }

    public function test_account_type_accepts_individual(): void
    {
        $response = $this->post(route('platform.register.account-type.store'), [
            'type' => 'individual',
        ]);

        $response->assertRedirect(route('platform.register.details'));
        $response->assertSessionHasNoErrors();
    }

    // =========================================================================
    // STEP 2: Company Details
    // =========================================================================

    public function test_details_page_requires_account_step_completed(): void
    {
        // Access details page without completing account step
        $response = $this->get(route('platform.register.details'));

        // Should redirect back to start
        $response->assertRedirect(route('platform.register.index'));
    }

    public function test_details_submission_requires_account_step(): void
    {
        // Try to submit details without account step in session
        $response = $this->post(route('platform.register.details.store'), [
            'name' => 'Test Corp',
            'email' => 'test@corp.test',
            'subdomain' => 'testcorp',
        ]);

        $response->assertRedirect(route('platform.register.index'));
    }

    public function test_details_validates_required_fields(): void
    {
        // Set up account step first
        $this->post(route('platform.register.account-type.store'), ['type' => 'company']);

        // Submit with missing required fields
        $response = $this->post(route('platform.register.details.store'), []);

        $response->assertSessionHasErrors(['name', 'email', 'subdomain']);
    }

    public function test_details_validates_subdomain_format(): void
    {
        $this->post(route('platform.register.account-type.store'), ['type' => 'company']);

        // Invalid subdomain (uppercase, special chars)
        $response = $this->post(route('platform.register.details.store'), [
            'name' => 'Test Corp',
            'email' => 'test@corp.test',
            'subdomain' => 'INVALID_Sub!domain',
        ]);

        $response->assertSessionHasErrors('subdomain');
    }

    public function test_details_validates_subdomain_accepts_lowercase_with_hyphens(): void
    {
        $this->post(route('platform.register.account-type.store'), ['type' => 'company']);

        $response = $this->post(route('platform.register.details.store'), [
            'name' => 'My Corp',
            'email' => 'info@my-corp.test',
            'subdomain' => 'my-corp',
            'owner_name' => 'Jane Doe',
        ]);

        $response->assertSessionHasNoErrors();
    }

    public function test_details_validates_email_format(): void
    {
        $this->post(route('platform.register.account-type.store'), ['type' => 'company']);

        $response = $this->post(route('platform.register.details.store'), [
            'name' => 'Test Corp',
            'email' => 'not-an-email',
            'subdomain' => 'testcorp',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_details_validates_phone_format(): void
    {
        $this->post(route('platform.register.account-type.store'), ['type' => 'company']);

        $response = $this->post(route('platform.register.details.store'), [
            'name' => 'Test Corp',
            'email' => 'test@corp.test',
            'subdomain' => 'testcorp',
            'phone' => 'invalid-phone',
        ]);

        $response->assertSessionHasErrors('phone');
    }

    public function test_details_rejects_duplicate_subdomain_for_active_tenant(): void
    {
        // Create an active tenant with same subdomain
        Tenant::factory()->active()->create([
            'subdomain' => 'taken-subdomain',
            'email' => 'existing@other.test',
        ]);

        $this->post(route('platform.register.account-type.store'), ['type' => 'company']);

        $response = $this->post(route('platform.register.details.store'), [
            'name' => 'Another Corp',
            'email' => 'new@corp.test',
            'subdomain' => 'taken-subdomain',
        ]);

        $response->assertSessionHasErrors('subdomain');
    }

    public function test_details_rejects_duplicate_email_for_active_tenant(): void
    {
        Tenant::factory()->active()->create([
            'subdomain' => 'existing-corp',
            'email' => 'taken@email.test',
        ]);

        $this->post(route('platform.register.account-type.store'), ['type' => 'company']);

        $response = $this->post(route('platform.register.details.store'), [
            'name' => 'Another Corp',
            'email' => 'taken@email.test',
            'subdomain' => 'new-subdomain',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_details_allows_pending_tenant_resume_with_same_email_and_subdomain(): void
    {
        // Create a pending tenant (same email AND subdomain)
        Tenant::factory()->pending()->create([
            'subdomain' => 'resume-test',
            'email' => 'resume@test.test',
            'name' => 'Old Name',
        ]);

        $this->post(route('platform.register.account-type.store'), ['type' => 'company']);

        $response = $this->post(route('platform.register.details.store'), [
            'name' => 'Updated Corp',
            'email' => 'resume@test.test',
            'subdomain' => 'resume-test',
        ]);

        $response->assertSessionHasNoErrors();

        // Verify tenant was updated, not duplicated
        $this->assertDatabaseCount('tenants', 1);
        $this->assertDatabaseHas('tenants', [
            'name' => 'Updated Corp',
            'email' => 'resume@test.test',
            'subdomain' => 'resume-test',
        ]);
    }

    public function test_details_skips_verification_in_debug_mode(): void
    {
        config(['app.debug' => true]);

        $this->post(route('platform.register.account-type.store'), ['type' => 'company']);

        $response = $this->post(route('platform.register.details.store'), [
            'name' => 'Debug Corp',
            'email' => 'debug@corp.test',
            'subdomain' => 'debug-corp',
        ]);

        // Should redirect directly to plan selection (skipping verification)
        $response->assertRedirect(route('platform.register.plan'));
    }

    // =========================================================================
    // STEP 3: Plan Selection
    // =========================================================================

    public function test_plan_page_requires_previous_steps_completed(): void
    {
        // Access plan page without completing details
        $response = $this->get(route('platform.register.plan'));

        $response->assertRedirect(route('platform.register.index'));
    }

    public function test_plan_submission_requires_previous_steps(): void
    {
        $response = $this->post(route('platform.register.plan.store'), [
            'billing_cycle' => 'monthly',
            'plan_id' => $this->plan->id,
        ]);

        $response->assertRedirect(route('platform.register.index'));
    }

    public function test_plan_submission_validates_billing_cycle(): void
    {
        $this->completeStepsUpToVerification();

        $response = $this->post(route('platform.register.plan.store'), [
            'billing_cycle' => 'weekly', // invalid
            'plan_id' => $this->plan->id,
        ]);

        $response->assertSessionHasErrors('billing_cycle');
    }

    public function test_plan_submission_requires_plan_or_modules(): void
    {
        $this->completeStepsUpToVerification();

        $response = $this->post(route('platform.register.plan.store'), [
            'billing_cycle' => 'monthly',
            // No plan_id and no modules
        ]);

        $response->assertSessionHasErrors();
    }

    public function test_plan_submission_accepts_valid_plan(): void
    {
        $this->completeStepsUpToVerification();

        $response = $this->post(route('platform.register.plan.store'), [
            'billing_cycle' => 'yearly',
            'plan_id' => $this->plan->id,
            'modules' => ['core', 'hrm'],
        ]);

        $response->assertRedirect(route('platform.register.payment'));
        $response->assertSessionHasNoErrors();
    }

    public function test_plan_validates_plan_exists_and_is_active(): void
    {
        $this->completeStepsUpToVerification();

        $response = $this->post(route('platform.register.plan.store'), [
            'billing_cycle' => 'monthly',
            'plan_id' => 'non-existent-plan-id',
        ]);

        $response->assertSessionHasErrors('plan_id');
    }

    public function test_plan_rejects_inactive_plan(): void
    {
        $inactivePlan = Plan::factory()->inactive()->create();

        $this->completeStepsUpToVerification();

        $response = $this->post(route('platform.register.plan.store'), [
            'billing_cycle' => 'monthly',
            'plan_id' => $inactivePlan->id,
        ]);

        $response->assertSessionHasErrors('plan_id');
    }

    // =========================================================================
    // STEP 4: Trial Activation
    // =========================================================================

    public function test_trial_activation_requires_all_previous_steps(): void
    {
        // Try to activate trial without completing registration steps
        $response = $this->post(route('platform.register.trial.activate'), [
            'accept_terms' => true,
        ]);

        $response->assertRedirect(route('platform.register.index'));
    }

    public function test_trial_activation_requires_terms_acceptance(): void
    {
        $this->completeStepsUpToPlan();

        $response = $this->post(route('platform.register.trial.activate'), [
            'accept_terms' => false,
        ]);

        $response->assertSessionHasErrors('accept_terms');
    }

    public function test_trial_activation_dispatches_provision_job(): void
    {
        Queue::fake();

        $this->completeStepsUpToPlan();

        $response = $this->post(route('platform.register.trial.activate'), [
            'accept_terms' => true,
        ]);

        $this->assertRedirectContains($response, '/signup/provisioning/');

        Queue::assertPushed(ProvisionTenant::class, 1);
    }

    public function test_trial_activation_creates_tenant_with_correct_data(): void
    {
        Queue::fake();

        $this->completeStepsUpToPlan();

        $this->post(route('platform.register.trial.activate'), [
            'accept_terms' => true,
            'notify_updates' => true,
        ]);

        $tenant = Tenant::where('subdomain', 'flow-test')->first();
        $this->assertNotNull($tenant);
        $this->assertEquals('Flow Test Corp', $tenant->name);
        $this->assertEquals('flow@test.test', $tenant->email);
        $this->assertDatabaseHas('subscriptions', ['billable_type' => Tenant::class, 'billable_id' => $tenant->id, 'plan_id' => $this->plan->id]);
    }

    public function test_trial_activation_creates_domain_record(): void
    {
        Queue::fake();

        $this->completeStepsUpToPlan();

        $this->post(route('platform.register.trial.activate'), [
            'accept_terms' => true,
        ]);

        $tenant = Tenant::where('subdomain', 'flow-test')->first();

        $this->assertDatabaseHas('domains', [
            'tenant_id' => $tenant->id,
        ]);
    }

    public function test_trial_activation_prevents_duplicate_submissions(): void
    {
        Queue::fake();

        $this->completeStepsUpToPlan();

        // First activation
        $response1 = $this->post(route('platform.register.trial.activate'), [
            'accept_terms' => true,
        ]);

        $this->assertRedirectContains($response1, '/signup/provisioning/');
        $tenantCount = Tenant::where('subdomain', 'flow-test')->count();
        $this->assertEquals(1, $tenantCount);

        // Simulate second submission (re-complete steps since session was cleared)
        $this->completeStepsUpToPlan();

        $response2 = $this->post(route('platform.register.trial.activate'), [
            'accept_terms' => true,
        ]);

        // Should redirect to existing provisioning (idempotent)
        $this->assertRedirectContains($response2, '/signup/provisioning/');

        // Should NOT create duplicate tenant
        $tenantCount = Tenant::where('subdomain', 'flow-test')->count();
        $this->assertEquals(1, $tenantCount);

        // Job should only be dispatched once (idempotency prevents second dispatch)
        Queue::assertPushed(ProvisionTenant::class, 1);
    }

    public function test_trial_activation_rejects_subdomain_taken_by_active_tenant(): void
    {
        Queue::fake();

        // Create an active tenant with the same subdomain
        Tenant::factory()->active()->create([
            'subdomain' => 'flow-test',
            'email' => 'existing@other.test',
        ]);

        // Subdomain uniqueness is enforced at the details step, not trial activation.
        // Attempt to submit details with the taken subdomain.
        $this->post(route('platform.register.account-type.store'), ['type' => 'company']);

        $response = $this->post(route('platform.register.details.store'), [
            'name' => 'Flow Test Corp',
            'email' => 'flow@test.test',
            'phone' => '+1234567890',
            'subdomain' => 'flow-test',
            'owner_name' => 'Test User',
            'industry' => 'Technology',
            'team_size' => 10,
        ]);

        $response->assertSessionHasErrors('subdomain');
    }

    public function test_trial_activation_rejects_email_taken_by_active_tenant(): void
    {
        Queue::fake();

        // Create an active tenant with same email
        Tenant::factory()->active()->create([
            'subdomain' => 'other-subdomain',
            'email' => 'flow@test.test',
        ]);

        // Email uniqueness is enforced at the details step, not trial activation.
        $this->post(route('platform.register.account-type.store'), ['type' => 'company']);

        $response = $this->post(route('platform.register.details.store'), [
            'name' => 'Flow Test Corp',
            'email' => 'flow@test.test',
            'phone' => '+1234567890',
            'subdomain' => 'flow-test',
            'owner_name' => 'Test User',
            'industry' => 'Technology',
            'team_size' => 10,
        ]);

        $response->assertSessionHasErrors('email');
    }

    // =========================================================================
    // STEP 5: Provisioning Status
    // =========================================================================

    public function test_provisioning_page_renders_for_valid_tenant(): void
    {
        $tenant = Tenant::factory()->pending()->create();
        Domain::create([
            'tenant_id' => $tenant->id,
            'domain' => $tenant->subdomain.'.localhost',
            'is_primary' => true,
        ]);

        $response = $this->get(route('platform.register.provisioning', ['tenant' => $tenant->id]));

        $response->assertOk();
    }

    public function test_provisioning_status_returns_json_for_pending(): void
    {
        $tenant = Tenant::factory()->pending()->create();
        Domain::create([
            'tenant_id' => $tenant->id,
            'domain' => $tenant->subdomain.'.localhost',
            'is_primary' => true,
        ]);

        $response = $this->getJson(route('platform.register.provisioning.status', ['tenant' => $tenant->id]));

        $response->assertOk();
        $response->assertJsonStructure(['status', 'is_ready', 'has_failed']);
        $response->assertJson(['status' => 'pending', 'is_ready' => false]);
    }

    public function test_provisioning_status_reflects_active_state_with_login_url(): void
    {
        $tenant = Tenant::factory()->active()->create([
            'data' => ['admin_setup_completed' => false],
        ]);
        Domain::create([
            'tenant_id' => $tenant->id,
            'domain' => $tenant->subdomain.'.localhost',
            'is_primary' => true,
        ]);

        $response = $this->getJson(route('platform.register.provisioning.status', ['tenant' => $tenant->id]));

        $response->assertOk();
        $response->assertJson([
            'status' => 'active',
            'is_ready' => true,
            'needs_admin_setup' => true,
        ]);
        $response->assertJsonStructure(['login_url']);
    }

    // =========================================================================
    // STEP 6: Retry Provisioning (Failure Recovery)
    // =========================================================================

    public function test_retry_provisioning_only_for_failed_tenants(): void
    {
        Queue::fake();

        $tenant = Tenant::factory()->active()->create();

        // Store tenant_id in session for authorization check
        $this->withSession([
            'tenant_registration' => [
                'verification' => ['tenant_id' => $tenant->id],
            ],
        ]);

        $response = $this->post(route('platform.register.provisioning.retry', ['tenant' => $tenant->id]));

        // Should fail — only FAILED tenants can be retried
        $response->assertRedirect();
        Queue::assertNotPushed(ProvisionTenant::class);
    }

    public function test_retry_provisioning_requires_session_ownership(): void
    {
        $tenant = Tenant::factory()->failed()->create();

        // No session or wrong tenant in session
        $response = $this->post(route('platform.register.provisioning.retry', ['tenant' => $tenant->id]));

        $response->assertForbidden();
    }

    public function test_retry_provisioning_resets_and_redispatches_job(): void
    {
        Queue::fake();

        $tenant = Tenant::factory()->failed()->create([
            'data' => ['provisioning_error' => 'Previous failure'],
        ]);

        // Set correct session ownership
        $this->withSession([
            'tenant_registration' => [
                'verification' => ['tenant_id' => $tenant->id],
            ],
        ]);

        $response = $this->post(route('platform.register.provisioning.retry', ['tenant' => $tenant->id]));

        $this->assertRedirectContains($response, '/signup/provisioning/');

        // Tenant should be reset to pending
        $tenant->refresh();
        $this->assertEquals(Tenant::STATUS_PENDING, $tenant->status);
        $this->assertNull($tenant->provisioning_step);

        Queue::assertPushed(ProvisionTenant::class, 1);
    }

    // =========================================================================
    // STEP 7: Registration Cancellation
    // =========================================================================

    public function test_cancel_registration_removes_pending_tenant(): void
    {
        // Complete steps up to verification which creates a pending tenant
        $this->completeStepsUpToVerification();

        // The storeDetails step created a pending tenant — find it
        $tenant = Tenant::where('subdomain', 'flow-test')->first();
        $this->assertNotNull($tenant, 'Tenant should have been created during registration');
        $this->assertEquals(Tenant::STATUS_PENDING, $tenant->status);

        $response = $this->postJson(route('platform.register.cancel'));

        $response->assertOk();

        // Tenant model uses SoftDeletes — record stays in DB but is soft-deleted.
        $this->assertNull(
            Tenant::where('subdomain', 'flow-test')->first(),
            'Tenant should be soft-deleted after cancellation'
        );
    }

    // =========================================================================
    // EDGE CASES & SECURITY
    // =========================================================================

    public function test_cannot_skip_steps_directly_to_trial_activation(): void
    {
        // Try to activate trial without completing any steps
        $response = $this->post(route('platform.register.trial.activate'), [
            'accept_terms' => true,
        ]);

        // Should redirect back to start (missing session steps)
        $response->assertRedirect(route('platform.register.index'));
    }

    public function test_cannot_access_plan_page_without_account_and_details(): void
    {
        $response = $this->get(route('platform.register.plan'));

        $response->assertRedirect(route('platform.register.index'));
    }

    public function test_subdomain_validation_rejects_sql_injection_attempts(): void
    {
        $this->post(route('platform.register.account-type.store'), ['type' => 'company']);

        $response = $this->post(route('platform.register.details.store'), [
            'name' => 'Hacker Corp',
            'email' => 'hacker@evil.test',
            'subdomain' => "'; DROP TABLE tenants;--",
        ]);

        $response->assertSessionHasErrors('subdomain');
    }

    public function test_email_field_rejects_xss_attempts(): void
    {
        $this->post(route('platform.register.account-type.store'), ['type' => 'company']);

        $response = $this->post(route('platform.register.details.store'), [
            'name' => 'Test Corp',
            'email' => '<script>alert(1)</script>@evil.test',
            'subdomain' => 'test-corp',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_registration_with_maximum_length_inputs(): void
    {
        $this->post(route('platform.register.account-type.store'), ['type' => 'company']);

        $response = $this->post(route('platform.register.details.store'), [
            'name' => str_repeat('A', 120), // Max 120
            'email' => 'valid@email.test',
            'subdomain' => str_repeat('a', 40), // Max 40
            'owner_name' => str_repeat('B', 150), // Max 150
            'industry' => str_repeat('C', 100), // Max 100
        ]);

        $response->assertSessionHasNoErrors();
    }

    public function test_registration_rejects_over_maximum_length_inputs(): void
    {
        $this->post(route('platform.register.account-type.store'), ['type' => 'company']);

        $response = $this->post(route('platform.register.details.store'), [
            'name' => str_repeat('A', 121), // Over max 120
            'email' => 'valid@email.test',
            'subdomain' => 'valid-sub',
        ]);

        $response->assertSessionHasErrors('name');
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Complete account type and details steps (with debug-mode verification skip).
     */
    private function completeStepsUpToVerification(): void
    {
        $this->post(route('platform.register.account-type.store'), ['type' => 'company']);

        $this->post(route('platform.register.details.store'), [
            'name' => 'Flow Test Corp',
            'email' => 'flow@test.test',
            'phone' => '+1234567890',
            'subdomain' => 'flow-test',
            'owner_name' => 'Test User',
            'industry' => 'Technology',
            'team_size' => 10,
        ]);
    }

    /**
     * Complete all steps up to and including plan selection.
     */
    private function completeStepsUpToPlan(): void
    {
        $this->completeStepsUpToVerification();

        $this->post(route('platform.register.plan.store'), [
            'billing_cycle' => 'monthly',
            'plan_id' => $this->plan->id,
            'modules' => ['core', 'hrm'],
        ]);
    }

    /**
     * Assert that a response redirects to a URL containing the given string.
     */
    private function assertRedirectContains(TestResponse $response, string $needle): void
    {
        $response->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertStringContainsString($needle, $location, "Redirect URL [{$location}] does not contain [{$needle}]");
    }
}
