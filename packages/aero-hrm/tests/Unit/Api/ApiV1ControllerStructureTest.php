<?php

declare(strict_types=1);

namespace Aero\HRM\Tests\Unit\Api;

use Aero\HRM\Http\Controllers\Api\DepartmentApiController;
use Aero\HRM\Http\Controllers\Api\DesignationApiController;
use Aero\HRM\Http\Controllers\Api\PayslipApiController;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Audit D24 — HRM API v1 structural regression pin.
 *
 * Verifies controllers exist with the expected methods and the routes
 * file declares the expected endpoints. Integration tests (real Sanctum
 * token, real DB, response shape) live in feature tests; this pins the
 * structure so a refactor that renames a method or drops a route is
 * caught immediately.
 */
class ApiV1ControllerStructureTest extends TestCase
{
    public function test_payslip_api_controller_has_index_and_show(): void
    {
        $r = new ReflectionClass(PayslipApiController::class);

        $this->assertTrue($r->hasMethod('index'),
            'PayslipApiController must expose index() for GET /api/hrm/payslips.');
        $this->assertTrue($r->hasMethod('show'),
            'PayslipApiController must expose show() for GET /api/hrm/payslips/{payslip}.');
    }

    public function test_payslip_show_falls_through_to_authorize_when_not_owner(): void
    {
        $source = file_get_contents((new ReflectionClass(PayslipApiController::class))->getFileName());

        $this->assertStringContainsString(
            "authorize('hrm.payroll.payslips.list.view')",
            $source,
            'PayslipApiController::show() must authorize via hrm.payroll.payslips.list.view when '.
            'the requester is not the payslip owner — admin override path.'
        );
    }

    public function test_payslip_show_resolves_owner_via_user_id(): void
    {
        $source = file_get_contents((new ReflectionClass(PayslipApiController::class))->getFileName());

        $this->assertStringContainsString(
            "where('user_id', \$request->user()->id)",
            $source,
            'PayslipApiController must resolve owner via Employee.user_id = auth user id.'
        );
    }

    public function test_payslip_detailed_transformer_masks_bank_fields(): void
    {
        $source = file_get_contents((new ReflectionClass(PayslipApiController::class))->getFileName());

        $this->assertStringContainsString(
            'bank_last_four',
            $source,
            'Detailed payslip response must expose bank_last_four — never the full account number.'
        );
        $this->assertStringNotContainsString(
            "'bank_account_number' => \$p->bank_account_number",
            $source,
            'PayslipApiController must NEVER expose the full bank_account_number in API responses.'
        );
    }

    public function test_department_api_controller_authorizes_via_hrmac(): void
    {
        $source = file_get_contents((new ReflectionClass(DepartmentApiController::class))->getFileName());

        $this->assertStringContainsString(
            "authorize('hrm.departments.list.view')",
            $source,
            'DepartmentApiController must enforce hrm.departments.list.view.'
        );
    }

    public function test_designation_api_controller_authorizes_via_hrmac(): void
    {
        $source = file_get_contents((new ReflectionClass(DesignationApiController::class))->getFileName());

        $this->assertStringContainsString(
            "authorize('hrm.designations.list.view')",
            $source,
            'DesignationApiController must enforce hrm.designations.list.view.'
        );
    }

    public function test_routes_file_declares_payslip_endpoints(): void
    {
        $routes = file_get_contents(dirname(__DIR__, 3).'/routes/api.php');

        $this->assertStringContainsString("Route::get('/payslips',", $routes);
        $this->assertStringContainsString("Route::get('/payslips/{payslip}',", $routes);
        $this->assertStringContainsString("name('payslips.index')", $routes);
        $this->assertStringContainsString("name('payslips.show')", $routes);
    }

    public function test_routes_file_declares_department_and_designation_endpoints(): void
    {
        $routes = file_get_contents(dirname(__DIR__, 3).'/routes/api.php');

        $this->assertStringContainsString("Route::get('/departments',", $routes);
        $this->assertStringContainsString("Route::get('/designations',", $routes);
        $this->assertStringContainsString("name('departments.index')", $routes);
        $this->assertStringContainsString("name('designations.index')", $routes);
    }

    public function test_api_routes_protected_by_sanctum_and_throttle(): void
    {
        $routes = file_get_contents(dirname(__DIR__, 3).'/routes/api.php');

        $this->assertStringContainsString("'auth:sanctum'", $routes,
            'HRM API routes must require Sanctum/PAT authentication.');
        $this->assertStringContainsString("'throttle:60,1'", $routes,
            'HRM API routes must be rate-limited (default 60/min).');
    }
}
