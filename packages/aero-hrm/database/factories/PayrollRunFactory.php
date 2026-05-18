<?php

declare(strict_types=1);

namespace Aero\HRM\Database\Factories;

use Aero\HRM\Models\PayrollRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayrollRun>
 */
class PayrollRunFactory extends Factory
{
    protected $model = PayrollRun::class;

    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('-3 months', 'now');

        return [
            'label'        => 'Payroll '.date('M Y', $start->getTimestamp()),
            'period_start' => $start,
            'period_end'   => (clone $start)->modify('+1 month -1 day'),
            'status'       => PayrollRun::STATUS_DRAFT,
            'approved_at'  => null,
            'approved_by'  => null,
            'locked_at'    => null,
            'total_gross'  => 0,
            'total_net'    => 0,
        ];
    }
}
