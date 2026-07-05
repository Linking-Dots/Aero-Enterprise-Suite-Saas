<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\Concerns;

use Aero\HRM\Models\PayrollRun;

/**
 * Supplies the payroll-module overview counts consumed by the shared PayrollRail
 * (rendered in the command shell's right rail on every Payroll sub-page).
 */
trait ProvidesPayrollRailStats
{
    /**
     * @return array<string, float|int>
     */
    protected function payrollRailStats(): array
    {
        return [
            'total'    => PayrollRun::count(),
            'approved' => PayrollRun::where('status', PayrollRun::STATUS_APPROVED)->count(),
            'net_paid' => (float) PayrollRun::where('status', PayrollRun::STATUS_APPROVED)->sum('total_net'),
        ];
    }
}
