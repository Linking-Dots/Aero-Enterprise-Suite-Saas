<?php

declare(strict_types=1);

namespace Aero\HRM\Tests\Feature\Events;

use Aero\Core\AeroCoreServiceProvider;
use Aero\Core\Models\User;
use Aero\HRM\AeroHrmServiceProvider;
use Aero\HRM\Models\Employee;
use Aero\HRM\Models\HrmEvent;
use Aero\HRMAC\HRMACServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Inertia\ServiceProvider;
use Orchestra\Testbench\TestCase;

class EventFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [\Aero\Auth\AeroAuthServiceProvider::class, ServiceProvider::class, AeroCoreServiceProvider::class, HRMACServiceProvider::class, AeroHrmServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
        $app['config']->set('cache.default', 'array');
        $app['config']->set('session.driver', 'array');
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('view.paths', [realpath(__DIR__.'/../../fixtures/views')]);
    }

    protected function defineRoutes($router): void
    {
        $router->get('/login', fn () => response('login'))->name('login');
    }

    private function grantAllPermissions(): void
    {
        Gate::before(fn () => true);
    }

    private function asUser(): static
    {
        $user = User::factory()->create();
        Employee::factory()->create(['user_id' => $user->id]);

        return $this->actingAs($user);
    }

    public function test_can_create_event(): void
    {
        $this->grantAllPermissions();
        $this->asUser();

        $this->post(route('hrm.events.store'), [
            'title' => 'Annual Summit',
            'starts_at' => now()->addDays(30)->toDateTimeString(),
            'ends_at' => now()->addDays(30)->addHours(8)->toDateTimeString(),
        ])->assertRedirect();

        $this->assertDatabaseHas('hrm_events', ['title' => 'Annual Summit', 'status' => 'draft']);
    }

    public function test_can_publish_draft_event(): void
    {
        $this->grantAllPermissions();
        $this->asUser();
        $event = HrmEvent::factory()->draft()->create();

        $this->post(route('hrm.events.publish', $event->slug))->assertRedirect();
        $this->assertDatabaseHas('hrm_events', ['id' => $event->id, 'status' => 'published']);
    }

    public function test_cannot_publish_already_published_event(): void
    {
        $this->grantAllPermissions();
        $this->asUser();
        $event = HrmEvent::factory()->published()->create();

        $this->post(route('hrm.events.publish', $event->slug))->assertStatus(422);
    }

    public function test_cannot_register_to_draft_event(): void
    {
        $this->grantAllPermissions();
        $this->asUser();
        $event = HrmEvent::factory()->draft()->create();

        $this->post(route('hrm.events.registrations.store', $event->slug), [
            'attendee_name' => 'Alice Smith',
            'attendee_email' => 'alice@example.com',
        ])->assertStatus(422);
    }

    public function test_public_event_accessible_without_auth(): void
    {
        $event = HrmEvent::factory()->published()->create(['is_public' => true]);

        $this->get(route('hrm.events.public.show', $event->slug))
            ->assertOk()
            ->assertInertia(fn ($p) => $p->component('HRM/Events/Public/Show'));
    }

    public function test_private_event_returns_404_on_public_page(): void
    {
        $event = HrmEvent::factory()->published()->create(['is_public' => false]);

        $this->get(route('hrm.events.public.show', $event->slug))->assertNotFound();
    }
}
