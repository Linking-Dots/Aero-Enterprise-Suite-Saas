<?php

declare(strict_types=1);

namespace Aero\HRM\Database\Factories;

use Aero\HRM\Models\Employee;
use Aero\HRM\Models\OvertimeRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OvertimeRequest>
 */
class OvertimeRequestFactory extends Factory
{
    protected $model = OvertimeRequest::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'work_date' => today(),
            'hours' => 2.0,
            'reason' => 'Test overtime',
            'status' => OvertimeRequest::STATUS_PENDING,
        ];
    }
}
