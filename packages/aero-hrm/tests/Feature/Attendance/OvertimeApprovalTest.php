<?php

declare(strict_types=1);

namespace Aero\HRM\Tests\Feature\Attendance;

use Aero\Core\AeroCoreServiceProvider;
use Aero\Core\Models\User;
use Aero\HRMAC\HRMACServiceProvider;
use Aero\HRM\AeroHrmServiceProvider;
use Aero\HRM\Exceptions\AttendanceException;
use Aero\HRM\Models\OvertimeRequest;
use Aero\HRM\Services\Attendance\OvertimeApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase;

class OvertimeApprovalTest extends TestCase
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
    // 1. Pending request can be approved
    // -------------------------------------------------------------------------

    public function test_pending_request_can_be_approved(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user);

        $request = OvertimeRequest::factory()->create(['status' => OvertimeRequest::STATUS_PENDING]);

        $service = app(OvertimeApprovalService::class);
        $service->approve($request);

        $this->assertEquals(
            OvertimeRequest::STATUS_APPROVED,
            $request->fresh()->status,
            'Status must be approved after calling approve().'
        );
    }

    // -------------------------------------------------------------------------
    // 2. Already-approved request throws AttendanceException on approve
    // -------------------------------------------------------------------------

    public function test_already_approved_request_throws_on_approve(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user);

        $request = OvertimeRequest::factory()->create(['status' => OvertimeRequest::STATUS_APPROVED]);

        $service = app(OvertimeApprovalService::class);

        $this->expectException(AttendanceException::class);
        $service->approve($request);
    }

    // -------------------------------------------------------------------------
    // 3. Pending request can be rejected with a reason
    // -------------------------------------------------------------------------

    public function test_request_can_be_rejected_with_reason(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user);

        $request = OvertimeRequest::factory()->create(['status' => OvertimeRequest::STATUS_PENDING]);

        $service = app(OvertimeApprovalService::class);
        $service->reject($request, 'Not justified');

        $fresh = $request->fresh();

        $this->assertEquals(
            OvertimeRequest::STATUS_REJECTED,
            $fresh->status,
            'Status must be rejected after calling reject().'
        );

        $this->assertEquals(
            'Not justified',
            $fresh->rejection_reason,
            'rejection_reason must be persisted.'
        );
    }
}
