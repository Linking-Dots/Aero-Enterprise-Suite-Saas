<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature\Admin\AdvancedBilling;
use Aero\Platform\Database\Factories\LandlordUserFactory;

use Aero\Platform\Models\CreditNote;
use Aero\Platform\Models\Invoice;
use Aero\Auth\Models\User;
use Aero\Platform\Services\AdvancedBilling\CreditNoteService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use Tests\TestCase;

class CreditNoteTest extends TestCase
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
        $this->admin = LandlordUserFactory::new()->create();
    }

    private function makeInvoice(): Invoice
    {
        return Invoice::create([
            'billable_type' => 'App\\Models\\User',
            'billable_id' => '1',
            'invoice_number' => 'INV-'.uniqid(),
            'status' => 'issued',
            'currency' => 'USD',
            'subtotal' => 200,
            'total' => 200,
            'amount_paid' => 0,
            'amount_due' => 200,
        ]);
    }

    public function test_credit_note_cannot_be_applied_for_more_than_available_balance(): void
    {
        $creditNote = CreditNote::create([
            'reference' => 'CN-LIMIT-001',
            'tenant_id' => 'tenant-a',
            'amount' => 100,
            'currency' => 'USD',
            'reason' => 'Goodwill credit',
            'amount_used' => 80,
            'status' => CreditNote::STATUS_PARTIALLY_APPLIED,
            'created_by' => $this->admin->id,
        ]);

        $invoice = $this->makeInvoice();
        $svc = app(CreditNoteService::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot apply');

        $svc->apply($creditNote, $invoice->id, 50.0, $this->admin->id);
    }

    public function test_status_transitions_to_partially_applied(): void
    {
        $creditNote = CreditNote::create([
            'reference' => 'CN-PARTIAL-001',
            'tenant_id' => 'tenant-b',
            'amount' => 200,
            'currency' => 'USD',
            'reason' => 'Overpayment refund',
            'amount_used' => 0,
            'status' => CreditNote::STATUS_OPEN,
            'created_by' => $this->admin->id,
        ]);

        $invoice = $this->makeInvoice();
        $svc = app(CreditNoteService::class);

        $result = $svc->apply($creditNote, $invoice->id, 50.0, $this->admin->id);

        $this->assertEquals(CreditNote::STATUS_PARTIALLY_APPLIED, $result->status);
        $this->assertEquals(50.0, (float) $result->amount_used);
    }

    public function test_status_transitions_to_fully_applied(): void
    {
        $creditNote = CreditNote::create([
            'reference' => 'CN-FULL-001',
            'tenant_id' => 'tenant-c',
            'amount' => 100,
            'currency' => 'USD',
            'reason' => 'Billing error',
            'amount_used' => 0,
            'status' => CreditNote::STATUS_OPEN,
            'created_by' => $this->admin->id,
        ]);

        $invoice = $this->makeInvoice();
        $svc = app(CreditNoteService::class);

        $result = $svc->apply($creditNote, $invoice->id, 100.0, $this->admin->id);

        $this->assertEquals(CreditNote::STATUS_FULLY_APPLIED, $result->status);
        $this->assertEquals(100.0, (float) $result->amount_used);
    }
}
