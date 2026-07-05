<?php

declare(strict_types=1);

namespace Aero\HRM\Tests\Feature\Attendance;

use Aero\Core\AeroCoreServiceProvider;
use Aero\HRMAC\HRMACServiceProvider;
use Aero\HRM\AeroHrmServiceProvider;
use Aero\HRM\Exceptions\AttendanceException;
use Aero\HRM\Models\Employee;
use Aero\HRM\Services\Attendance\AttendanceClockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase;

class AttendanceClockTest extends TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            \Inertia\ServiceProvider::class,
            \Aero\Auth\AeroAuthServiceProvider::class,
            AeroCoreServiceProvider::class,
            HRMACServiceProvider::class,
            AeroHrmServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
        $app['config']->set('cache.default', 'array');
        $app['config']->set('session.driver', 'array');
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('view.paths', [
            realpath(__DIR__.'/../../fixtures/views'),
        ]);
    }

    // -------------------------------------------------------------------------
    // 1. Employee can clock in
    // -------------------------------------------------------------------------

    public function test_employee_can_clock_in(): void
    {
        $employee = Employee::factory()->create();

        $service = app(AttendanceClockService::class);
        $attendance = $service->clockIn($employee, 'web');

        $this->assertNotNull($attendance->id, 'Attendance record must be persisted.');

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $employee->id,
        ]);

        // punchin must be set — stored as datetime so check via fresh model
        $fresh = $attendance->fresh();
        $this->assertNotNull($fresh->punchin, 'punchin must not be null after clock-in.');
    }

    // -------------------------------------------------------------------------
    // 2. Double clock-in on the same day throws AttendanceException
    // -------------------------------------------------------------------------

    public function test_double_clock_in_throws_exception(): void
    {
        $employee = Employee::factory()->create();

        $service = app(AttendanceClockService::class);

        // First clock-in — must succeed
        $service->clockIn($employee, 'web');

        // Second clock-in on the same day — must throw
        $this->expectException(AttendanceException::class);
        $service->clockIn($employee, 'web');
    }

    // -------------------------------------------------------------------------
    // 3. Clock out after 45 minutes records work_hours >= 0.74
    // -------------------------------------------------------------------------

    public function test_clock_out_records_work_hours(): void
    {
        $employee = Employee::factory()->create();

        $service = app(AttendanceClockService::class);

        $service->clockIn($employee, 'web');

        // Advance time by 45 minutes
        $this->travel(45)->minutes();

        $attendance = $service->clockOut($employee);

        $fresh = $attendance->fresh();

        $this->assertNotNull($fresh->punchout, 'punchout must be set after clock-out.');
        $this->assertGreaterThanOrEqual(
            0.74,
            (float) $fresh->work_hours,
            '45 minutes should record at least 0.74 work hours.'
        );
    }
}
