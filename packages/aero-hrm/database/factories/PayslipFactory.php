<?php

declare(strict_types=1);

namespace Aero\HRM\Database\Factories;

use Aero\HRM\Models\Employee;
use Aero\HRM\Models\Payslip;
use Aero\HRM\Models\PayrollRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payslip>
 */
class PayslipFactory extends Factory
{
    protected $model = Payslip::class;

    public function definition(): array
    {
        $gross      = (float) $this->faker->randomFloat(2, 30000, 200000);
        $tax        = round($gross * 0.1, 2);
        $deductions = round($gross * 0.05, 2);
        $net        = $gross - $tax - $deductions;

        return [
            'payroll_run_id'      => PayrollRun::factory(),
            'employee_id'         => Employee::factory(),
            'gross'               => $gross,
            'tax'                 => $tax,
            'deductions_total'    => $deductions,
            'net'                 => $net,
            'line_items'          => [],
            'employee_snapshot'   => [],
            'bank_account_number' => null,
            'bank_name'           => null,
            'bank_routing_number' => null,
        ];
    }
}
