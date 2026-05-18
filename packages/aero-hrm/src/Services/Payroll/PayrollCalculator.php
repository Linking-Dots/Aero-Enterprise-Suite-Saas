<?php

declare(strict_types=1);

namespace Aero\HRM\Services\Payroll;

use Aero\HRM\Models\SalaryStructure;
use Aero\HRM\Models\TaxBracket;

/**
 * Stateless payroll calculator.
 *
 * Computes gross, deductions, tax, and net pay for a given SalaryStructure
 * using progressive tax brackets for the specified year.
 */
final class PayrollCalculator
{
    /**
     * Compute a full pay breakdown for one salary structure.
     *
     * @return array{
     *   gross: float,
     *   deductions: float,
     *   tax: float,
     *   net: float,
     *   lines: list<array{code: string, name: string, kind: string, amount: float}>
     * }
     */
    public function compute(SalaryStructure $struct, int $year): array
    {
        $basic     = (float) $struct->basic;
        $earnings  = 0.0;
        $deductions = 0.0;
        $lines     = [];

        // Iterate components in the structure-defined order (PHP-side sort).
        foreach ($struct->components() as $component) {
            $amount = $this->resolveAmount($component->calc_type, (float) $component->value, $basic, $earnings);

            $lines[] = [
                'code'   => $component->code,
                'name'   => $component->name,
                'kind'   => $component->kind,
                'amount' => round($amount, 2),
            ];

            if ($component->kind === 'earning') {
                $earnings += $amount;
            } else {
                $deductions += $amount;
            }
        }

        $gross = $basic + $earnings;
        $tax   = $this->computeTax($gross, $year);
        $net   = $gross - $tax - $deductions;

        return [
            'gross'      => round($gross, 2),
            'deductions' => round($deductions, 2),
            'tax'        => round($tax, 2),
            'net'        => round($net, 2),
            'lines'      => $lines,
        ];
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function resolveAmount(string $calcType, float $value, float $basic, float $currentGross): float
    {
        return match ($calcType) {
            'fixed'            => $value,
            'percent_of_basic' => $basic * ($value / 100),
            'percent_of_gross' => $currentGross * ($value / 100),
            'formula'          => $value, // formula eval not yet implemented — fall back to raw value
            default            => 0.0,
        };
    }

    /**
     * Progressive tax calculation using TaxBracket records for the given year.
     */
    private function computeTax(float $gross, int $year): float
    {
        $brackets = TaxBracket::where('effective_year', $year)
            ->orderBy('income_from')
            ->get();

        if ($brackets->isEmpty()) {
            return 0.0;
        }

        $totalTax  = 0.0;
        $remaining = $gross;

        foreach ($brackets as $bracket) {
            if ($remaining <= 0) {
                break;
            }

            $from = (float) $bracket->income_from;
            $to   = $bracket->income_to !== null ? (float) $bracket->income_to : PHP_FLOAT_MAX;
            $rate = (float) $bracket->rate;

            if ($gross <= $from) {
                break;
            }

            $taxableInBracket = min($gross, $to) - $from;

            if ($taxableInBracket <= 0) {
                continue;
            }

            $totalTax += $taxableInBracket * $rate;
        }

        return round($totalTax, 2);
    }
}
