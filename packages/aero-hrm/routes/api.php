<?php

declare(strict_types=1);

use Aero\HRM\Http\Controllers\Api\AttendanceApiController;
use Aero\HRM\Http\Controllers\Api\DepartmentApiController;
use Aero\HRM\Http\Controllers\Api\DesignationApiController;
use Aero\HRM\Http\Controllers\Api\EmployeeApiController;
use Aero\HRM\Http\Controllers\Api\LeaveApiController;
use Aero\HRM\Http\Controllers\Api\PayslipApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| aero-hrm REST API routes (HRM Push H.T2)
|--------------------------------------------------------------------------
|
| Phase 1 audit found aero-hrm was Inertia-only — no REST surface, so
| mobile apps + integrations + BI tools had no way to read HR data.
| This file is the JSON twin of the Inertia surface.
|
| Authentication: Sanctum/PAT. Operator issues tokens via aero-core
| ApiKey admin (/admin/api-keys). Token holder gets the same permissions
| as the user the token belongs to — no separate "API permissions" model.
|
| Rate limiting: 60 req/min per token by default (operator can override
| via Sanctum config).
|
| HRMAC permissions are still enforced inside each controller method
| via $this->authorize() — no API-permission loophole.
|
*/

Route::prefix('api/hrm')
    ->name('api.hrm.')
    ->middleware(['auth:sanctum', 'throttle:60,1'])
    ->group(function () {

        // ── Employees ─────────────────────────────────────────────────
        Route::get('/employees', [EmployeeApiController::class, 'index'])
            ->name('employees.index');

        Route::get('/employees/{employee}', [EmployeeApiController::class, 'show'])
            ->name('employees.show');

        // ── Leave Applications ────────────────────────────────────────
        Route::get('/leave-applications', [LeaveApiController::class, 'index'])
            ->name('leave-applications.index');

        Route::get('/leave-applications/{leaveApplication}', [LeaveApiController::class, 'show'])
            ->name('leave-applications.show');

        Route::post('/leave-applications', [LeaveApiController::class, 'store'])
            ->name('leave-applications.store');

        // ── Attendance ────────────────────────────────────────────────
        Route::get('/attendance/today', [AttendanceApiController::class, 'today'])
            ->name('attendance.today');

        Route::post('/attendance/clock-in', [AttendanceApiController::class, 'clockIn'])
            ->name('attendance.clock-in');

        Route::post('/attendance/clock-out', [AttendanceApiController::class, 'clockOut'])
            ->name('attendance.clock-out');

        // ── Payslips (Audit D24) ──────────────────────────────────────
        // Own-scope by default; admin override via hrm.payroll.payslips.list.view.
        Route::get('/payslips', [PayslipApiController::class, 'index'])
            ->name('payslips.index');

        Route::get('/payslips/{payslip}', [PayslipApiController::class, 'show'])
            ->name('payslips.show');

        // ── Department / Designation lookups (Audit D24) ──────────────
        // Read-only metadata for mobile signup flows + integration forms.
        Route::get('/departments', [DepartmentApiController::class, 'index'])
            ->name('departments.index');

        Route::get('/designations', [DesignationApiController::class, 'index'])
            ->name('designations.index');

    });
