<?php

declare(strict_types=1);

namespace Aero\Core\Tests\Feature\Admin;

use Aero\Core\AeroCoreServiceProvider;
use Aero\Core\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Orchestra\Testbench\TestCase;

class AuditLogControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            AeroCoreServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        DB::getSchemaBuilder()->create('audit_logs', function ($table) {
            $table->id();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_name')->nullable();
            $table->string('actor_ip', 45)->nullable();
            $table->string('event_type', 100);
            $table->string('action', 100);
            $table->text('description')->nullable();
            $table->string('subject_type')->nullable();
            $table->string('subject_id', 36)->nullable();
            $table->string('subject_label')->nullable();
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();
            $table->json('changed_fields')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('anonymized_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function test_audit_log_page_requires_authentication(): void
    {
        $this->get(route('core.audit-logs.index'))
            ->assertRedirect(route('login'));
    }

    public function test_audit_log_page_renders_correct_inertia_component(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('core.audit.logs.view');

        $this->actingAs($user)
            ->get(route('core.audit-logs.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Core/AuditLogs/Index')
                ->has('stats')
                ->has('tab')
                ->has('logs')
                ->has('meta')
                ->has('filters')
            );
    }

    public function test_audit_log_defaults_to_business_tab(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('core.audit.logs.view');

        $this->actingAs($user)
            ->get(route('core.audit-logs.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('tab', 'business')
            );
    }

    public function test_audit_log_access_tab_works(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('core.audit.logs.view');

        $this->actingAs($user)
            ->get(route('core.audit-logs.index', ['tab' => 'access']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('tab', 'access')
                ->has('logs')
            );
    }

    public function test_audit_log_returns_correct_pagination_meta(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('core.audit.logs.view');

        for ($i = 0; $i < 5; $i++) {
            DB::table('audit_logs')->insert([
                'event_type' => 'data.created',
                'action' => 'created',
                'description' => "Record {$i} created",
                'subject_type' => 'Test',
                'subject_id' => (string) $i,
                'created_at' => now(),
            ]);
        }

        $this->actingAs($user)
            ->get(route('core.audit-logs.index', ['tab' => 'business']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('meta.total', 5)
            );
    }
}
