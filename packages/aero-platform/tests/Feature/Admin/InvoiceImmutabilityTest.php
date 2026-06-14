<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature\Admin;
use Aero\Platform\Database\Factories\LandlordUserFactory;

use Aero\Contracts\AeroMode;
use Aero\HRMAC\Models\Role;
use Aero\Platform\Exceptions\InvoiceFinalizedException;
use Aero\Platform\Models\Invoice;
use Aero\Auth\Models\User;
use Aero\Platform\Models\Plan;
use Aero\Platform\Models\Subscription;
use Aero\Platform\Models\Tenant;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * P-2 — Invoice Immutability
 *
 * Validates that InvoiceImmutableObserver prevents mutation and deletion
 * of paid invoices, and that the mark-paid route writes a platform_audit_log entry.
 */
class InvoiceImmutabilityTest extends TestCase
{
    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    protected User $admin;

    protected Tenant $tenant;

    protected Plan $plan;

    protected Subscription $subscription;

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

        $this->admin  = LandlordUserFactory::new()->create();
        $this->admin->assignRole($role);

        $this->tenant = Tenant::factory()->active()->create();
        $this->plan   = Plan::factory()->create(['is_active' => true]);

        $this->subscription = Subscription::create([
            'billable_type' => Tenant::class,
            'billable_id'   => $this->tenant->id,
            'type'          => 'default',
            'name'          => 'default',
            'plan_id'       => $this->plan->id,
            'status'        => Subscription::STATUS_ACTIVE,
            'starts_at'     => now(),
        ]);
    }

    /**
     * Helper: create an invoice with the given status.
     */
    private function createInvoice(string $status, array $override = []): Invoice
    {
        $ref = 'INV-'.strtoupper(Str::random(8));

        return Invoice::create(array_merge([
            'billable_type'  => Tenant::class,
            'billable_id'    => $this->tenant->id,
            'subscription_id' => $this->subscription->id,
            'invoice_number' => $ref,
            'reference'      => $ref,
            'status'         => $status,
            'currency'       => 'USD',
            'subtotal'       => '100.00',
            'total'          => '100.00',
            'amount_paid'    => $status === Invoice::STATUS_PAID ? '100.00' : '0.00',
            'amount_due'     => $status === Invoice::STATUS_PAID ? '0.00' : '100.00',
        ], $override));
    }

    // =========================================================================
    // IMMUTABILITY — model-level observer
    // =========================================================================

    public function test_cannot_change_amount_on_paid_invoice(): void
    {
        $invoice = $this->createInvoice(Invoice::STATUS_PAID);

        $this->expectException(InvoiceFinalizedException::class);

        // Direct Eloquent update must trigger the observer and throw.
        $invoice->update(['total' => '200.00']);
    }

    public function test_cannot_change_status_on_paid_invoice(): void
    {
        $invoice = $this->createInvoice(Invoice::STATUS_PAID);

        $this->expectException(InvoiceFinalizedException::class);

        $invoice->update(['status' => Invoice::STATUS_VOID]);
    }

    public function test_cannot_delete_paid_invoice(): void
    {
        $invoice = $this->createInvoice(Invoice::STATUS_PAID);

        $this->expectException(InvoiceFinalizedException::class);

        // Soft-delete triggers the deleting observer hook.
        $invoice->delete();
    }

    // =========================================================================
    // ALLOWED MUTATIONS — draft / issued invoices are mutable
    // =========================================================================

    public function test_can_update_amount_on_draft_invoice(): void
    {
        $invoice = $this->createInvoice(Invoice::STATUS_DRAFT);

        $invoice->update(['total' => '150.00']);

        $this->assertDatabaseHas('invoices', [
            'id'    => $invoice->id,
            'total' => '150.00',
        ]);
    }

    public function test_can_soft_delete_draft_invoice(): void
    {
        $invoice = $this->createInvoice(Invoice::STATUS_DRAFT);

        $invoice->delete();

        $this->assertSoftDeleted('invoices', ['id' => $invoice->id]);
    }

    // =========================================================================
    // MARK PAID — route + audit log
    // =========================================================================

    public function test_mark_paid_writes_audit_log(): void
    {
        $invoice = $this->createInvoice(Invoice::STATUS_ISSUED);

        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.billing.invoices.mark-paid', $invoice), [
                'payment_method' => 'bank_transfer',
            ])
            ->assertRedirect();

        // InvoiceAdminService::markPaid() calls AuditService::log('invoice.paid', ...).
        // The platform_audit_logs table stores the event_type field.
        $this->assertDatabaseHas('platform_audit_logs', [
            'event_type' => 'invoice.paid',
            'subject_type' => Invoice::class,
            'subject_id'   => $invoice->id,
        ]);
    }

    public function test_mark_paid_sets_invoice_status_to_paid(): void
    {
        $invoice = $this->createInvoice(Invoice::STATUS_ISSUED);

        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.billing.invoices.mark-paid', $invoice));

        $this->assertDatabaseHas('invoices', [
            'id'     => $invoice->id,
            'status' => Invoice::STATUS_PAID,
        ]);
    }

    public function test_mark_paid_on_already_paid_invoice_throws_via_observer(): void
    {
        $invoice = $this->createInvoice(Invoice::STATUS_PAID);

        // Calling markPaid() on an already-paid invoice triggers InvoiceImmutableObserver.
        $this->expectException(InvoiceFinalizedException::class);

        $invoice->markPaid('cash');
    }

    // =========================================================================
    // UNAUTHENTICATED ACCESS
    // =========================================================================

    public function test_unauthenticated_mark_paid_request_is_redirected(): void
    {
        $invoice = $this->createInvoice(Invoice::STATUS_ISSUED);

        $this->post(route('platform.admin.billing.invoices.mark-paid', $invoice))
            ->assertRedirect();
    }
}
