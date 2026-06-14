<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature\Admin\Infra;
use Aero\Platform\Database\Factories\LandlordUserFactory;

use Aero\Contracts\AuditServiceInterface;
use Aero\Platform\Models\Infra\BackupSchedule;
use Aero\Auth\Models\User;
use Aero\Platform\Services\Infra\BackupService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class BackupServiceTest extends TestCase
{
    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    protected User $admin;

    protected BackupService $svc;

    public function runDatabaseMigrations(): void
    {
        $this->beforeRefreshingDatabase();
        $this->refreshTestDatabase();
        $this->afterRefreshingDatabase();
    }

    private function shareSqliteAcrossConnections(): void
    {
        $cfg = ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true];
        config([
            'database.connections.mysql' => $cfg,
            'database.connections.central' => $cfg,
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

        $audit = $this->createMock(AuditServiceInterface::class);
        $this->svc = new BackupService($audit);
    }

    public function test_schedule_creates_correct_next_run_time_for_daily(): void
    {
        $schedule = BackupSchedule::create([
            'frequency' => 'daily',
            'time_of_day' => '02:00',
            'retention_days' => 30,
            'storage_backend_code' => 's3',
            'is_active' => true,
        ]);

        $nextRun = $this->svc->computeNextRunAt($schedule);

        $this->assertInstanceOf(Carbon::class, $nextRun);
        $this->assertSame(2, $nextRun->hour);
        $this->assertSame(0, $nextRun->minute);
        $this->assertTrue($nextRun->isFuture());
    }

    public function test_schedule_creates_correct_next_run_time_for_weekly(): void
    {
        $schedule = BackupSchedule::create([
            'frequency' => 'weekly',
            'time_of_day' => '03:30',
            'retention_days' => 90,
            'storage_backend_code' => 's3',
            'is_active' => true,
        ]);

        $nextRun = $this->svc->computeNextRunAt($schedule);

        $this->assertTrue($nextRun->isFuture());
        $this->assertSame(3, $nextRun->hour);
        $this->assertSame(30, $nextRun->minute);
    }

    public function test_schedule_creates_correct_next_run_time_for_monthly(): void
    {
        $schedule = BackupSchedule::create([
            'frequency' => 'monthly',
            'time_of_day' => '00:00',
            'retention_days' => 365,
            'storage_backend_code' => 'local',
            'is_active' => true,
        ]);

        $nextRun = $this->svc->computeNextRunAt($schedule);

        $this->assertTrue($nextRun->isFuture());
    }

    public function test_create_schedule_persists_to_database(): void
    {
        $schedule = $this->svc->createSchedule([
            'frequency' => 'daily',
            'time_of_day' => '04:00',
            'retention_days' => 14,
            'storage_backend_code' => 'gcs',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('backup_schedules', [
            'id' => $schedule->id,
            'frequency' => 'daily',
            'storage_backend_code' => 'gcs',
        ]);
    }
}
