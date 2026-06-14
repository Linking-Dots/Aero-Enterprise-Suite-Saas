<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature\Admin\Finance;
use Aero\Platform\Database\Factories\LandlordUserFactory;

use Aero\HRMAC\Models\Role;
use Aero\Platform\Models\Invoice;
use Aero\Auth\Models\User;
use Aero\Platform\Services\Finance\InvoicingService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use Tests\TestCase;

class InvoiceVoidTest extends TestCase
{
    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    protected User $admin;

    public function runDatabaseMigrations(): void
    {
        $this->beforeRefreshingDatabase();
        $this->refreshTestDatabase();
        $this->afterRefreshingDatabase();
    }

    private function shareSqliteAcrossConnections(): void
    {
        $cfg = ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true];
        config(['database.connections.mysql' => $cfg, 'database.connections.central' => $cfg]);
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

        $role = Role::firstOrCreate(['name' => 'Super Administrator', 'guard_name' => 'landlord']);
        $this->admin = LandlordUserFactory::new()->create();
        $this->admin->assignRole($role);
    }

    private function makeInvoice(string $status): Invoice
    {
        return Invoice::create([
            'invoice_number' => 'INV-TEST-'.uniqid(),
            'status' => $status,
            'currency' => 'USD',
            'subtotal' => '100.00',
            'total' => '100.00',
            'amount_paid' => $status === Invoice::STATUS_PAID ? '100.00' : '0.00',
            'amount_due' => $status === Invoice::STATUS_PAID ? '0.00' : '100.00',
        ]);
    }

    public function test_void_fails_when_invoice_is_paid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A paid invoice cannot be voided.');

        $invoice = $this->makeInvoice(Invoice::STATUS_PAID);
        $service = app(InvoicingService::class);
        $service->void($invoice, $this->admin->id);
    }

    public function test_void_succeeds_for_open_invoice(): void
    {
        $invoice = $this->makeInvoice(Invoice::STATUS_DRAFT);
        $service = app(InvoicingService::class);
        $voided = $service->void($invoice, $this->admin->id);

        $this->assertEquals(Invoice::STATUS_VOID, $voided->status);
    }

    public function test_void_succeeds_for_sent_invoice(): void
    {
        $invoice = $this->makeInvoice(Invoice::STATUS_ISSUED);
        $service = app(InvoicingService::class);
        $voided = $service->void($invoice, $this->admin->id);

        $this->assertEquals(Invoice::STATUS_VOID, $voided->status);
    }

    public function test_void_writes_audit_log(): void
    {
        $invoice = $this->makeInvoice(Invoice::STATUS_DRAFT);
        $service = app(InvoicingService::class);
        $service->void($invoice, $this->admin->id);

        $this->assertDatabaseHas('platform_audit_logs', [
            'event_type' => 'platform.invoice.voided',
        ]);
    }
}
