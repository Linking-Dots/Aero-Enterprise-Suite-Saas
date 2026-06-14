<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature\Admin\AdvancedBilling;
use Aero\Platform\Database\Factories\LandlordUserFactory;

use Aero\Platform\Models\CouponCampaign;
use Aero\Auth\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class CampaignTest extends TestCase
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

    public function test_launch_flips_draft_to_active(): void
    {
        $campaign = CouponCampaign::create([
            'name' => 'Test Campaign',
            'status' => CouponCampaign::STATUS_DRAFT,
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin, 'landlord')
            ->postJson("/billing/campaigns/{$campaign->id}/launch")
            ->assertOk()
            ->assertJsonPath('campaign.status', CouponCampaign::STATUS_ACTIVE);

        $this->assertDatabaseHas('coupon_campaigns', [
            'id' => $campaign->id,
            'status' => CouponCampaign::STATUS_ACTIVE,
        ]);
    }

    public function test_end_sets_status_ended_and_stamps_updated_at(): void
    {
        $campaign = CouponCampaign::create([
            'name' => 'Active Campaign',
            'status' => CouponCampaign::STATUS_ACTIVE,
            'created_by' => $this->admin->id,
        ]);

        $before = now();

        $this->actingAs($this->admin, 'landlord')
            ->postJson("/billing/campaigns/{$campaign->id}/end")
            ->assertOk()
            ->assertJsonPath('campaign.status', CouponCampaign::STATUS_ENDED);

        $this->assertDatabaseHas('coupon_campaigns', [
            'id' => $campaign->id,
            'status' => CouponCampaign::STATUS_ENDED,
        ]);

        $campaign->refresh();
        $this->assertTrue($campaign->updated_at->gte($before));
        $this->assertNotNull($campaign->ends_at);
    }
}
