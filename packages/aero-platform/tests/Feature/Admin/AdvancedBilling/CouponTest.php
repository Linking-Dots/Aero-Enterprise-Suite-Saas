<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature\Admin\AdvancedBilling;
use Aero\Platform\Database\Factories\LandlordUserFactory;

use Aero\Platform\Models\Coupon;
use Aero\Auth\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * P-8 — CouponTest
 *
 * Gate::before(fn () => true) bypasses HRMAC middleware.
 * Uses DatabaseMigrations + shareSqliteAcrossConnections() pattern.
 */
class CouponTest extends TestCase
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

    public function test_coupon_code_uniqueness_is_enforced(): void
    {
        Coupon::create([
            'code' => 'SAVE10',
            'name' => 'Save 10%',
            'type' => Coupon::TYPE_PERCENT,
            'value' => 10,
            'duration' => Coupon::DURATION_ONCE,
            'status' => Coupon::STATUS_ACTIVE,
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin, 'landlord')
            ->postJson('/billing/coupons', [
                'code' => 'SAVE10',
                'name' => 'Duplicate',
                'type' => Coupon::TYPE_PERCENT,
                'value' => 10,
                'duration' => Coupon::DURATION_ONCE,
            ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['code']);
    }

    public function test_archive_sets_status_archived(): void
    {
        $coupon = Coupon::create([
            'code' => 'ARCHIVEME',
            'name' => 'Archive Test',
            'type' => Coupon::TYPE_FIXED,
            'value' => 5,
            'currency' => 'USD',
            'duration' => Coupon::DURATION_ONCE,
            'status' => Coupon::STATUS_ACTIVE,
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin, 'landlord')
            ->postJson("/billing/coupons/{$coupon->id}/archive")
            ->assertOk();

        $this->assertDatabaseHas('coupons', [
            'id' => $coupon->id,
            'status' => Coupon::STATUS_ARCHIVED,
        ]);
    }
}
