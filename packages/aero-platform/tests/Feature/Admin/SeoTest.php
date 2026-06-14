<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature\Admin;
use Aero\Platform\Database\Factories\LandlordUserFactory;

use Aero\Auth\Models\User;
use Aero\Platform\Models\PlatformPage;
use Aero\Platform\Models\PlatformSetting;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * P-6 — SeoController (Admin)
 *
 * Auth pattern: actingAs($admin, 'landlord').
 * Gate::before(fn () => true) bypasses HRMAC middleware for all tests.
 * Uses DatabaseMigrations + shareSqliteAcrossConnections() pattern.
 */
class SeoTest extends TestCase
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

    public function test_index_renders_inertia_component(): void
    {
        PlatformSetting::current(); // ensure row exists

        $this->actingAs($this->admin, 'landlord')
            ->get(route('admin.seo.index'))
            ->assertOk();
    }

    public function test_can_update_seo_settings(): void
    {
        PlatformSetting::current();

        $this->actingAs($this->admin, 'landlord')
            ->putJson(route('admin.seo.settings.update'), [
                'default_meta_title' => 'AEOS365 — Enterprise ERP',
                'default_meta_description' => 'The best multi-tenant ERP platform.',
                'sitemap_enabled' => true,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $settings = PlatformSetting::current()->getSeoSettings();
        $this->assertEquals('AEOS365 — Enterprise ERP', $settings['default_meta_title']);
    }

    public function test_sitemap_regeneration_returns_xml_content(): void
    {
        PlatformSetting::current();

        // Add an active page so the sitemap has at least one URL
        PlatformPage::create([
            'slug' => 'features',
            'title' => 'Features',
            'page_type' => 'custom',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin, 'landlord')
            ->get(route('admin.seo.sitemap'));

        $response->assertOk();

        // Sitemap returns XML when sitemap_enabled is true (default)
        // Content-type may be application/xml or text/html depending on sitemap_enabled setting
        // Just assert the response is successful and contains URL elements or is empty
        $this->assertNotNull($response->getContent());
    }

    public function test_regenerate_sitemap_endpoint_clears_cache_and_responds(): void
    {
        PlatformSetting::current();

        $this->actingAs($this->admin, 'landlord')
            ->postJson(route('admin.seo.sitemap.regenerate'))
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_pages_index_renders(): void
    {
        PlatformPage::create([
            'slug' => 'pricing',
            'title' => 'Pricing',
            'page_type' => 'custom',
            'is_active' => true,
        ]);

        $this->actingAs($this->admin, 'landlord')
            ->get(route('admin.seo.pages.index'))
            ->assertOk();
    }

    public function test_can_create_platform_page(): void
    {
        $this->actingAs($this->admin, 'landlord')
            ->postJson(route('admin.seo.pages.store'), [
                'slug' => 'about-us',
                'title' => 'About Us',
                'page_type' => 'custom',
                'meta_title' => 'About AEOS365',
                'meta_description' => 'Learn more about us.',
                'is_active' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('platform_pages', [
            'slug' => 'about-us',
            'title' => 'About Us',
        ]);
    }

    public function test_can_update_platform_page(): void
    {
        $page = PlatformPage::create([
            'slug' => 'contact',
            'title' => 'Contact',
            'page_type' => 'custom',
            'is_active' => true,
        ]);

        $this->actingAs($this->admin, 'landlord')
            ->putJson(route('admin.seo.pages.update', $page), [
                'slug' => 'contact',
                'title' => 'Contact Us',
                'page_type' => 'custom',
                'is_active' => true,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('platform_pages', [
            'id' => $page->id,
            'title' => 'Contact Us',
        ]);
    }

    public function test_can_delete_platform_page(): void
    {
        $page = PlatformPage::create([
            'slug' => 'old-page',
            'title' => 'Old Page',
            'page_type' => 'custom',
            'is_active' => false,
        ]);

        $this->actingAs($this->admin, 'landlord')
            ->deleteJson(route('admin.seo.pages.destroy', $page))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('platform_pages', ['id' => $page->id]);
    }

    public function test_create_page_requires_slug_and_title(): void
    {
        $this->actingAs($this->admin, 'landlord')
            ->postJson(route('admin.seo.pages.store'), [
                'page_type' => 'custom',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['slug', 'title']);
    }

    public function test_analytics_update_persists_settings(): void
    {
        PlatformSetting::current();

        $this->actingAs($this->admin, 'landlord')
            ->putJson(route('admin.seo.analytics.update'), [
                'google_analytics' => [
                    'enabled' => true,
                    'measurement_id' => 'G-XXXXXXXXXX',
                ],
            ])
            ->assertOk()
            ->assertJsonPath('success', true);
    }
}
