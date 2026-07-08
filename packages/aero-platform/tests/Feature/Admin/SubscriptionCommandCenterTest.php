<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature\Admin;

use Aero\Contracts\AeroMode;
use Aero\HRMAC\Models\Role;
use Aero\Platform\Database\Factories\LandlordUserFactory;
use Aero\Auth\Models\User;
use Aero\Platform\Models\Plan;
use Aero\Platform\Models\Subscription;
use Aero\Platform\Models\Tenant;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Subscriptions command centre — domain operations & payload contract.
 *
 * Covers the v2 overview payload keys, guided create, cancel modes,
 * pause/resume, trial extend/convert, cycle change, bulk, CSV export
 * and the drawer detail endpoint.
 */
class SubscriptionCommandCenterTest extends TestCase
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

        AeroMode::reset();
        Gate::before(fn () => true);

        $role = Role::firstOrCreate(
            ['name' => 'Super Administrator', 'guard_name' => 'landlord'],
        );

        $this->admin = LandlordUserFactory::new()->create();
        $this->admin->assignRole($role);

        $this->plan = Plan::factory()->create(['is_active' => true, 'price_monthly' => 100, 'price_annual' => 1000]);
        $this->tenant = Tenant::factory()->active()->create();
    }

    private function makeSub(array $attrs = []): Subscription
    {
        return Subscription::create(array_merge([
            'tenant_id'     => $this->tenant->id,
            'billable_type' => Tenant::class,
            'billable_id'   => $this->tenant->id,
            'type'          => 'default',
            'name'          => 'default',
            'plan_id'       => $this->plan->id,
            'billing_cycle' => 'monthly',
            'amount'        => 100,
            'currency'      => 'USD',
            'status'        => Subscription::STATUS_ACTIVE,
            'starts_at'     => now(),
        ], $attrs));
    }

    /* ------------------------------------------------------------- */

    public function test_index_ships_the_v2_command_centre_payload(): void
    {
        $this->makeSub();

        $this->actingAs($this->admin, 'landlord')
            ->get(route('platform.admin.billing.subscriptions.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                // page JSX lives in the aero-ui package — outside the host's
                // inertia.testing.page_paths, so skip the file-existence check
                ->component('Platform/Admin/Billing/P2/Subscriptions', false)
                ->has('stats.arr')
                ->has('stats.dunning_count')
                ->has('sparks.mrr', 8)
                ->has('mrr_trend.labels', 12)
                ->has('mrr_movement.new', 6)
                ->has('cohorts')
                ->has('queues.renewals')
                ->has('queues.trials')
                ->has('queues.dunning')
                ->has('tenants')
                ->has('products')
                ->has('plan_subscriptions.0.billing_cycle')
                ->has('plan_subscriptions.0.renews_at')
            );
    }

    public function test_store_creates_a_plan_subscription_with_trial(): void
    {
        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.billing.subscriptions.store'), [
                'tenant_id' => $this->tenant->id,
                'kind' => 'plan',
                'plan_id' => $this->plan->id,
                'billing_cycle' => 'yearly',
                'trial_days' => 14,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('subscriptions', [
            'tenant_id' => $this->tenant->id,
            'billing_cycle' => 'yearly',
            'status' => 'trialing',
            'amount' => 1000,
        ]);
    }

    public function test_cancel_at_period_end_keeps_access_until_period_lapses(): void
    {
        $sub = $this->makeSub(['current_period_end' => now()->addDays(20)]);

        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.billing.subscriptions.cancel', $sub), [
                'reason' => 'Downsizing',
                'mode' => 'period_end',
            ])
            ->assertRedirect();

        $sub->refresh();
        $this->assertSame('active', $sub->status);
        $this->assertNotNull($sub->ends_at);
        $this->assertSame('Downsizing', $sub->cancel_reason);
    }

    public function test_cancel_immediate_cancels_now(): void
    {
        $sub = $this->makeSub();

        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.billing.subscriptions.cancel', $sub), [
                'reason' => 'Fraud',
                'mode' => 'immediate',
            ])
            ->assertRedirect();

        $this->assertSame('cancelled', $sub->refresh()->status);
    }

    public function test_pause_and_resume_flip_status_with_guards(): void
    {
        $sub = $this->makeSub();

        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.billing.subscriptions.pause', $sub))
            ->assertRedirect();
        $this->assertSame('paused', $sub->refresh()->status);

        // Pausing a paused subscription is rejected with a flash error.
        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.billing.subscriptions.pause', $sub))
            ->assertSessionHas('error');

        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.billing.subscriptions.resume', $sub))
            ->assertRedirect();
        $this->assertSame('active', $sub->refresh()->status);
    }

    public function test_trial_extend_and_convert(): void
    {
        $sub = $this->makeSub(['status' => 'trialing', 'trial_ends_at' => now()->addDays(3)]);

        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.billing.subscriptions.trial.extend', $sub), ['days' => 14])
            ->assertRedirect();
        $this->assertTrue($sub->refresh()->trial_ends_at->gt(now()->addDays(10)));

        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.billing.subscriptions.trial.convert', $sub))
            ->assertRedirect();
        $sub->refresh();
        $this->assertSame('active', $sub->status);
        $this->assertNotNull($sub->current_period_end);
    }

    public function test_change_cycle_rederives_amount_from_plan(): void
    {
        $sub = $this->makeSub();

        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.billing.subscriptions.change-cycle', $sub), ['billing_cycle' => 'yearly'])
            ->assertRedirect();

        $sub->refresh();
        $this->assertSame('yearly', $sub->billing_cycle);
        $this->assertSame(1000.0, (float) $sub->amount);
    }

    public function test_plan_change_writes_a_priced_movement_event(): void
    {
        $cheap = $this->plan; // price_monthly 100
        $pricey = Plan::factory()->create(['is_active' => true, 'price_monthly' => 400, 'price_annual' => 4000]);
        $sub = $this->makeSub(['plan_id' => $cheap->id]);

        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.billing.subscriptions.change-plan', $sub), ['plan_id' => $pricey->id])
            ->assertRedirect();

        // 100 → 400 monthly = +300 expansion in the ledger.
        $this->assertDatabaseHas('subscription_events', [
            'subscription_id' => $sub->id,
            'movement' => 'expansion',
            'mrr_delta' => 300.00,
        ]);
    }

    public function test_immediate_cancel_writes_a_churn_event(): void
    {
        $sub = $this->makeSub();

        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.billing.subscriptions.cancel', $sub), ['reason' => 'x', 'mode' => 'immediate'])
            ->assertRedirect();

        $this->assertDatabaseHas('subscription_events', [
            'subscription_id' => $sub->id,
            'movement' => 'churn',
            'mrr_delta' => -100.00,
        ]);
    }

    public function test_bulk_cancel_processes_each_selection(): void
    {
        Mail::fake();
        $a = $this->makeSub();
        $b = $this->makeSub();

        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.billing.subscriptions.bulk'), [
                'action' => 'cancel',
                'reason' => 'Cleanup',
                'ids' => [
                    ['kind' => 'plan', 'id' => $a->id],
                    ['kind' => 'plan', 'id' => $b->id],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('cancelled', $a->refresh()->status);
        $this->assertSame('cancelled', $b->refresh()->status);
    }

    public function test_export_streams_csv(): void
    {
        $this->makeSub();

        $response = $this->actingAs($this->admin, 'landlord')
            ->get(route('platform.admin.billing.subscriptions.export'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));
        $csv = $response->streamedContent();
        $this->assertStringContainsString('kind,tenant,label,status', $csv);
        $this->assertStringContainsString('plan', $csv);
    }

    public function test_detail_endpoint_returns_drawer_payload(): void
    {
        $sub = $this->makeSub();

        $this->actingAs($this->admin, 'landlord')
            ->getJson(route('platform.admin.billing.subscriptions.detail', ['kind' => 'plan', 'id' => $sub->id]))
            ->assertOk()
            ->assertJsonStructure(['invoices', 'activity', 'payment_method', 'discount_amount']);
    }

    public function test_remind_without_tenant_email_logs_and_flashes_error(): void
    {
        Mail::fake();
        $sub = $this->makeSub(['status' => 'past_due']);
        // Tenant factory always sets an email, so this queues the reminder.
        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.billing.subscriptions.remind', $sub))
            ->assertRedirect();

        Mail::assertQueued(\Aero\Platform\Mail\Subscription\PaymentReminderMail::class);
    }
}
