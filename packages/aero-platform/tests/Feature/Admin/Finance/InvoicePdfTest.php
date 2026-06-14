<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature\Admin\Finance;
use Aero\Platform\Database\Factories\LandlordUserFactory;

use Aero\HRMAC\Models\Role;
use Aero\Platform\Models\Invoice;
use Aero\Auth\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class InvoicePdfTest extends TestCase
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

    public function test_pdf_download_endpoint_responds_with_application_pdf_content_type(): void
    {
        $invoice = Invoice::create([
            'invoice_number' => 'INV-PDF-TEST',
            'status' => Invoice::STATUS_ISSUED,
            'currency' => 'USD',
            'subtotal' => '50.00',
            'total' => '50.00',
            'amount_paid' => '0.00',
            'amount_due' => '50.00',
        ]);

        $this->actingAs($this->admin, 'landlord')
            ->get(route('platform.admin.invoicing.pdf', $invoice->id))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }
}
