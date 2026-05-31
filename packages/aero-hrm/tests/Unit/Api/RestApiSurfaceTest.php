<?php

declare(strict_types=1);

namespace Aero\HRM\Tests\Unit\Api;

use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * HRM Push H.T2 — REST API surface regression pin.
 *
 * Phase 1 audit found aero-hrm shipped Inertia controllers only — no
 * routes/api.php existed, blocking mobile apps + integrations + BI
 * tools from reading HR data. This commit adds the first API surface:
 *   - Employees: index + show (HRMAC-gated read)
 *   - Leave Applications: index + show + store (own-scope by default;
 *     admin scope via hrm.leaves.applications.list.view)
 *   - Attendance: today + clock-in + clock-out (always own-scope)
 *
 * Authentication is Sanctum/PAT — operator issues tokens via aero-core's
 * /admin/api-keys surface. Same HRMAC permissions enforced via
 * $this->authorize() inside each controller — no API loophole.
 *
 * This test pins the structural contract so a refactor cannot regress
 * the API surface to Inertia-only.
 */
class RestApiSurfaceTest extends TestCase
{
    public function test_routes_api_file_exists(): void
    {
        $apiRoutes = dirname(__DIR__, 3).'/routes/api.php';
        $this->assertFileExists($apiRoutes,
            'routes/api.php must exist for the HRM REST surface (HRM Push H.T2).');
    }

    public function test_api_routes_use_sanctum_authentication(): void
    {
        $apiRoutes = file_get_contents(dirname(__DIR__, 3).'/routes/api.php');

        $this->assertStringContainsString("'auth:sanctum'", $apiRoutes,
            'API routes must require Sanctum token authentication — no anonymous '.
            'access to HR data.');
    }

    public function test_api_routes_use_throttle_middleware(): void
    {
        $apiRoutes = file_get_contents(dirname(__DIR__, 3).'/routes/api.php');

        $this->assertMatchesRegularExpression(
            "/['\"]throttle:\d+,\d+['\"]/",
            $apiRoutes,
            'API routes must include throttle middleware — Phase 1 audit B-12 '.
            'flagged unrate-limited API endpoints as a DoS vector.'
        );
    }

    public function test_employee_api_controller_exists(): void
    {
        $path = dirname(__DIR__, 3).'/src/Http/Controllers/Api/EmployeeApiController.php';
        $this->assertFileExists($path);

        $content = file_get_contents($path);
        $this->assertStringContainsString('public function index(', $content);
        $this->assertStringContainsString('public function show(', $content);
        $this->assertStringContainsString("authorize('hrm.employees.list.view')", $content,
            'EmployeeApiController must enforce hrm.employees.list.view via '.
            '$this->authorize() — same HRMAC permission as the Inertia controller.');
    }

    public function test_leave_api_controller_exists(): void
    {
        $path = dirname(__DIR__, 3).'/src/Http/Controllers/Api/LeaveApiController.php';
        $this->assertFileExists($path);

        $content = file_get_contents($path);
        $this->assertStringContainsString('public function index(', $content);
        $this->assertStringContainsString('public function show(', $content);
        $this->assertStringContainsString('public function store(', $content);
    }

    public function test_attendance_api_controller_exists(): void
    {
        $path = dirname(__DIR__, 3).'/src/Http/Controllers/Api/AttendanceApiController.php';
        $this->assertFileExists($path);

        $content = file_get_contents($path);
        $this->assertStringContainsString('public function today(', $content);
        $this->assertStringContainsString('public function clockIn(', $content);
        $this->assertStringContainsString('public function clockOut(', $content);
    }

    public function test_employee_api_uses_bounded_per_page(): void
    {
        $content = file_get_contents(dirname(__DIR__, 3).'/src/Http/Controllers/Api/EmployeeApiController.php');

        $this->assertStringContainsString('boundedPerPage(', $content,
            'EmployeeApiController must use the boundedPerPage() helper from '.
            'Phase 0 T10 — closes the ?per_page=999999 DOS vector.');
    }

    public function test_leave_api_owner_scope_check_present(): void
    {
        $content = file_get_contents(dirname(__DIR__, 3).'/src/Http/Controllers/Api/LeaveApiController.php');

        $this->assertStringContainsString('$currentUserId', $content,
            'LeaveApiController must scope queries to the authenticated user by '.
            "default. Admin override needs hrm.leaves.applications.list.view.");
    }

    public function test_attendance_clock_in_is_idempotent(): void
    {
        $content = file_get_contents(dirname(__DIR__, 3).'/src/Http/Controllers/Api/AttendanceApiController.php');

        // Idempotency: firstOrCreate ensures retry doesn't create duplicate row
        $this->assertStringContainsString('firstOrCreate(', $content,
            'clockIn() must use firstOrCreate() so a mobile retry on flaky '.
            "network doesn't create duplicate attendance rows for the same day.");
    }

    public function test_service_provider_loads_api_routes(): void
    {
        $sp = file_get_contents(dirname(__DIR__, 3).'/src/Providers/HRMServiceProvider.php');

        $this->assertStringContainsString('routes/api.php', $sp,
            'HRMServiceProvider::loadRoutes() must reference routes/api.php so '.
            'Sanctum-gated REST endpoints are actually registered with the router.');
        $this->assertStringContainsString('loadRoutesFrom(', $sp,
            'HRMServiceProvider must call loadRoutesFrom() with the api.php path.');
    }
}
