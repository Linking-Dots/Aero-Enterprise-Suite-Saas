<?php

namespace Aero\HRM\Database\Factories;

use Aero\Core\Models\User;
use Aero\HRM\Models\Employee;
use Aero\HRM\Models\HrmAsset;
use Aero\HRM\Models\HrmAssetAllocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HrmAssetAllocation>
 */
class HrmAssetAllocationFactory extends Factory
{
    protected $model = HrmAssetAllocation::class;

    public function definition(): array
    {
        return [
            'asset_id' => HrmAsset::factory(),
            'employee_id' => Employee::factory(),
            'allocated_at' => now(),
            'returned_at' => null,
            'condition_on_allocation' => 'good',
            'condition_on_return' => null,
            'allocation_notes' => null,
            'return_notes' => null,
            'allocated_by' => User::factory(),
            'returned_by' => null,
        ];
    }
}
