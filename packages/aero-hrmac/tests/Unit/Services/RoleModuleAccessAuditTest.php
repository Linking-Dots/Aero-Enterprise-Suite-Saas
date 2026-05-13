<?php

namespace Aero\HRMAC\Tests\Unit\Services;

use Aero\HRMAC\Services\RoleModuleAccessService;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase;

class RoleModuleAccessAuditTest extends TestCase
{
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

        Schema::create('role_module_access', function ($table) {
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('module_id')->nullable();
            $table->unsignedBigInteger('sub_module_id')->nullable();
            $table->unsignedBigInteger('component_id')->nullable();
            $table->unsignedBigInteger('action_id')->nullable();
            $table->string('access_scope')->default('all');
            $table->timestamps();
        });

        Schema::create('hrmac_audit_log', function ($table) {
            $table->id();
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->unsignedBigInteger('role_id')->nullable();
            $table->string('action');
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
        });
    }

    public function test_sync_role_access_writes_audit_log(): void
    {
        $service = new RoleModuleAccessService();
        $service->syncRoleAccess(42, [
            'modules' => [],
            'sub_modules' => [],
            'components' => [],
            'actions' => [],
        ]);

        $this->assertDatabaseHas('hrmac_audit_log', [
            'role_id' => 42,
            'action' => 'sync',
        ]);
    }

    public function test_sync_role_access_audit_log_captures_before_and_after_state(): void
    {
        $service = new RoleModuleAccessService();
        $service->syncRoleAccess(99, [
            'modules' => [],
            'sub_modules' => [],
            'components' => [],
            'actions' => [],
        ]);

        $log = \Aero\HRMAC\Models\HrmacAuditLog::where('role_id', 99)->first();

        $this->assertNotNull($log);
        $this->assertEquals('sync', $log->action);
        $this->assertIsArray($log->before_state);
        $this->assertIsArray($log->after_state);
    }
}
