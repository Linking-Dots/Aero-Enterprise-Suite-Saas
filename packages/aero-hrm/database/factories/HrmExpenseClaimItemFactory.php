<?php

namespace Aero\HRM\Database\Factories;

use Aero\HRM\Models\HrmExpenseCategory;
use Aero\HRM\Models\HrmExpenseClaim;
use Aero\HRM\Models\HrmExpenseClaimItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HrmExpenseClaimItem>
 */
class HrmExpenseClaimItemFactory extends Factory
{
    protected $model = HrmExpenseClaimItem::class;

    public function definition(): array
    {
        return [
            'claim_id' => HrmExpenseClaim::factory(),
            'category_id' => HrmExpenseCategory::factory(),
            'expense_date' => now()->toDateString(),
            'amount' => $this->faker->randomFloat(2, 10, 500),
            'description' => $this->faker->optional()->sentence(),
        ];
    }
}
