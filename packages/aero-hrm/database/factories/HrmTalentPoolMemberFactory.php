<?php

namespace Aero\HRM\Database\Factories;

use Aero\Core\Models\User;
use Aero\HRM\Models\Employee;
use Aero\HRM\Models\HrmTalentPoolMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HrmTalentPoolMember>
 */
class HrmTalentPoolMemberFactory extends Factory
{
    protected $model = HrmTalentPoolMember::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'tier' => HrmTalentPoolMember::TIER_HIGH_POTENTIAL,
            'notes' => null,
            'added_by' => User::factory(),
        ];
    }
}
