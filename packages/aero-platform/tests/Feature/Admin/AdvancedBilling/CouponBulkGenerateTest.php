<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature\Admin\AdvancedBilling;
use Aero\Platform\Database\Factories\LandlordUserFactory;

use Aero\Platform\Models\Coupon;
use Aero\Platform\Models\CouponCampaign;
use Aero\Auth\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class CouponBulkGenerateTest extends TestCase
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

    public function test_bulk_generate_creates_requested_count_with_prefix_and_value(): void
    {
        $campaign = CouponCampaign::create([
            'name' => 'Summer Campaign',
            'status' => CouponCampaign::STATUS_ACTIVE,
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin, 'landlord')
            ->postJson('/billing/coupons/bulk-generate', [
                'campaign_id' => $campaign->id,
                'prefix' => 'SUMMER',
                'count' => 5,
                'options' => [
                    'type' => Coupon::TYPE_PERCENT,
                    'value' => 15,
                    'duration' => Coupon::DURATION_ONCE,
                ],
            ]);

        $response->assertCreated();
        $response->assertJsonPath('count', 5);

        $this->assertDatabaseCount('coupons', 5);

        // All codes should start with the given prefix
        Coupon::all()->each(function (Coupon $c) {
            $this->assertStringStartsWith('SUMMER-', $c->code);
        });
    }
}
