<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\SelfService;

use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Models\Employee;

abstract class SelfServiceController extends Controller
{
    protected function employee(): Employee
    {
        $employee = auth()->user()?->employee;
        abort_unless($employee, 403, 'No employee profile.');

        return $employee;
    }
}
