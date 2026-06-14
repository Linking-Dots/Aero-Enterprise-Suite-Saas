<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature\Admin;
use Aero\Platform\Database\Factories\LandlordUserFactory;

use Aero\Auth\Models\User;
use Aero\Platform\Models\PlatformSetting;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * P-4 — PlatformSettingController (Admin)
 *
 * Auth pattern: actingAs($admin, 'landlord').
 * Gate::before(fn () => true) bypasses HRMAC middleware for all tests.
 * Uses DatabaseMigrations + shareSqliteAcrossConnections() pattern.
 */
class PlatformSettingControllerTest extends TestCase
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
        $this->shareSqliteAcrossConnections();

        Gate::before(fn () => true);

        $this->admin = LandlordUserFactory::new()->create();
    }

    public function test_general_settings_persist(): void
    {
        PlatformSetting::current();

        $this->actingAs($this->admin, 'landlord')
            ->put(route('platform.admin.settings.general.update'), [
                'site_name' => 'AEOS Cloud',
                'timezone' => 'Asia/Dhaka',
                'date_format' => 'd-m-Y',
                'currency' => 'BDT',
            ])
            ->assertRedirect();

        $setting = PlatformSetting::current();
        $this->assertEquals('AEOS Cloud', $setting->site_name);
        $this->assertEquals('Asia/Dhaka', $setting->metadata['timezone']);
        $this->assertEquals('BDT', $setting->metadata['currency']);
    }

    public function test_general_settings_requires_site_name(): void
    {
        PlatformSetting::current();

        $this->actingAs($this->admin, 'landlord')
            ->put(route('platform.admin.settings.general.update'), [
                'site_name' => '',
            ])
            ->assertSessionHasErrors('site_name');
    }

    public function test_localization_settings_persist(): void
    {
        PlatformSetting::current();

        $this->actingAs($this->admin, 'landlord')
            ->put(route('platform.admin.settings.localization.update'), [
                'default_locale' => 'bn',
                'available_locales' => ['en', 'bn'],
                'timezone' => 'Asia/Dhaka',
                'date_format' => 'd/m/Y',
                'first_day_of_week' => 0,
            ])
            ->assertRedirect();

        $setting = PlatformSetting::current();
        $this->assertEquals('bn', $setting->metadata['default_locale']);
        $this->assertEquals(['en', 'bn'], $setting->metadata['available_locales']);
    }

    public function test_maintenance_toggle_reflects_in_settings(): void
    {
        PlatformSetting::current();

        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.settings.maintenance.toggle'), [
                'enable' => true,
                'message' => 'Upgrading.',
            ])
            ->assertRedirect();

        $this->assertTrue(PlatformSetting::isMaintenanceModeEnabled());
    }

    public function test_maintenance_can_be_disabled(): void
    {
        $setting = PlatformSetting::current();
        $setting->enableMaintenanceMode('test');

        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.settings.maintenance.toggle'), [
                'enable' => false,
            ])
            ->assertRedirect();

        $this->assertFalse(PlatformSetting::isMaintenanceModeEnabled());
    }

    public function test_test_email_returns_error_on_failure(): void
    {
        PlatformSetting::current();

        Mail::shouldReceive('raw')->andThrow(new \RuntimeException('SMTP refused'));

        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.settings.email.test'), ['to' => 'test@example.com'])
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_email_settings_persist(): void
    {
        PlatformSetting::current();

        $this->actingAs($this->admin, 'landlord')
            ->put(route('platform.admin.settings.email.update'), [
                'host' => 'smtp.example.com',
                'port' => 587,
                'from_email' => 'noreply@example.com',
                'from_name' => 'AEOS',
            ])
            ->assertRedirect();

        $setting = PlatformSetting::current();
        $this->assertEquals('smtp.example.com', $setting->email_settings['host']);
        $this->assertEquals(587, $setting->email_settings['port']);
    }

    public function test_infrastructure_settings_persist(): void
    {
        PlatformSetting::current();

        $this->actingAs($this->admin, 'landlord')
            ->put(route('platform.admin.settings.infrastructure.update'), [
                'mode' => 'shared',
                'cpanel_host' => 'cpanel.example.com',
                'cpanel_port' => 2083,
                'cpanel_username' => 'admin',
            ])
            ->assertRedirect();

        $setting = PlatformSetting::current();
        $this->assertEquals('shared', $setting->hosting_settings['mode']);
        $this->assertEquals('cpanel.example.com', $setting->hosting_settings['cpanel_host']);
    }
}
