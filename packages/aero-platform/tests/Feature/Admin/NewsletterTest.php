<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature\Admin;
use Aero\Platform\Database\Factories\LandlordUserFactory;

use Aero\Auth\Models\User;
use Aero\Platform\Models\NewsletterSubscriber;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * P-6 — NewsletterController (Admin)
 *
 * Auth pattern: actingAs($admin, 'landlord').
 * Gate::before(fn () => true) bypasses HRMAC middleware for all tests.
 * Uses DatabaseMigrations + shareSqliteAcrossConnections() pattern.
 */
class NewsletterTest extends TestCase
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
        $this->actingAs($this->admin, 'landlord')
            ->get(route('admin.newsletter.index'))
            ->assertOk();
    }

    public function test_can_add_subscriber_manually(): void
    {
        $this->actingAs($this->admin, 'landlord')
            ->postJson(route('admin.newsletter.store'), [
                'email' => 'subscriber@example.com',
                'name' => 'Jane Subscriber',
                'source' => NewsletterSubscriber::SOURCE_IMPORT,
                'skip_confirmation' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => 'subscriber@example.com',
            'status' => NewsletterSubscriber::STATUS_CONFIRMED,
        ]);
    }

    public function test_bulk_import_skips_duplicate_emails(): void
    {
        // Pre-insert a subscriber
        NewsletterSubscriber::create([
            'email' => 'existing@example.com',
            'source' => NewsletterSubscriber::SOURCE_WEBSITE,
        ]);

        $this->actingAs($this->admin, 'landlord')
            ->postJson(route('admin.newsletter.import'), [
                'subscribers' => [
                    ['email' => 'existing@example.com', 'name' => 'Existing'],
                    ['email' => 'new@example.com', 'name' => 'New'],
                ],
                'skip_confirmation' => true,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('result.imported', 1)
            ->assertJsonPath('result.skipped', 1);
    }

    public function test_bulk_import_returns_correct_counts(): void
    {
        $this->actingAs($this->admin, 'landlord')
            ->postJson(route('admin.newsletter.import'), [
                'subscribers' => [
                    ['email' => 'a@example.com'],
                    ['email' => 'b@example.com'],
                    ['email' => 'c@example.com'],
                ],
                'skip_confirmation' => false,
            ])
            ->assertOk()
            ->assertJsonPath('result.imported', 3)
            ->assertJsonPath('result.skipped', 0);
    }

    public function test_can_delete_subscriber(): void
    {
        $subscriber = NewsletterSubscriber::create([
            'email' => 'delete-me@example.com',
            'source' => NewsletterSubscriber::SOURCE_WEBSITE,
        ]);

        $this->actingAs($this->admin, 'landlord')
            ->deleteJson(route('admin.newsletter.destroy', $subscriber))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('newsletter_subscribers', ['id' => $subscriber->id]);
    }

    public function test_can_export_subscribers(): void
    {
        NewsletterSubscriber::create([
            'email' => 'export@example.com',
            'status' => NewsletterSubscriber::STATUS_CONFIRMED,
            'source' => NewsletterSubscriber::SOURCE_WEBSITE,
        ]);

        $this->actingAs($this->admin, 'landlord')
            ->getJson(route('admin.newsletter.export'))
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_import_requires_valid_email_entries(): void
    {
        $this->actingAs($this->admin, 'landlord')
            ->postJson(route('admin.newsletter.import'), [
                'subscribers' => [
                    ['email' => 'not-an-email'],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['subscribers.0.email']);
    }

    public function test_store_requires_email(): void
    {
        $this->actingAs($this->admin, 'landlord')
            ->postJson(route('admin.newsletter.store'), [
                'name' => 'No Email',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }
}
