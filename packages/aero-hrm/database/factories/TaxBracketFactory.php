<?php

namespace Aero\HRM\Database\Factories;

use Aero\HRM\Models\TaxBracket;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaxBracketFactory extends Factory
{
    protected $model = TaxBracket::class;

    public function definition(): array
    {
        return [
            'country_code'   => 'US',
            'income_from'    => 0,
            'income_to'      => 50000,
            'rate'           => 0.2000,
            'effective_year' => now()->year,
        ];
    }
}
