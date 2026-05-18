<?php

declare(strict_types=1);

namespace Aero\HRM\Services\Analytics;

use Aero\HRM\Models\Employee;
use Carbon\Carbon;

class DEIService
{
    /**
     * Gender distribution of all employees.
     *
     * @return array<string, int>
     */
    public function genderDistribution(): array
    {
        return Employee::selectRaw('gender, COUNT(*) as cnt')
            ->whereNotNull('gender')
            ->groupBy('gender')
            ->pluck('cnt', 'gender')
            ->map(fn ($v) => (int) $v)
            ->toArray();
    }

    /**
     * Age band distribution (using date_of_birth).
     *
     * @return array<string, int>
     */
    public function ageBands(): array
    {
        $bands = [
            'under_25' => 0,
            '25_34' => 0,
            '35_44' => 0,
            '45_54' => 0,
            '55_plus' => 0,
            'unknown' => 0,
        ];

        Employee::whereNotNull('date_of_birth')
            ->get(['date_of_birth'])
            ->each(function (Employee $e) use (&$bands): void {
                $age = Carbon::parse($e->date_of_birth)->age;
                if ($age < 25) {
                    $bands['under_25']++;
                } elseif ($age < 35) {
                    $bands['25_34']++;
                } elseif ($age < 45) {
                    $bands['35_44']++;
                } elseif ($age < 55) {
                    $bands['45_54']++;
                } else {
                    $bands['55_plus']++;
                }
            });

        // Count employees without DOB
        $bands['unknown'] = Employee::whereNull('date_of_birth')->count();

        return $bands;
    }

    /**
     * Pay gap by role band (stub — requires salary by gender schema).
     *
     * @return array<never, never>
     */
    public function payGapByRoleBand(): array
    {
        return [];
    }

    /**
     * Leadership representation by gender (stub).
     *
     * @return array<never, never>
     */
    public function leadershipRepresentation(): array
    {
        return [];
    }
}
