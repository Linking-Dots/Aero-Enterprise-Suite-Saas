<?php

namespace Aero\HRM\Services\Benefits;

use Aero\HRM\Models\Employee;
use Aero\HRM\Models\HrmBenefit;
use Illuminate\Support\Carbon;

final class EligibilityService
{
    public function isEligible(Employee $e, HrmBenefit $b, Carbon $asOf): bool
    {
        if (! $b->active) {
            return false;
        }
        $rules = $b->eligibility_rules ?? [];

        if (
            ($min = $rules['min_tenure_days'] ?? null)
            && $e->date_of_joining
            && $e->date_of_joining->diffInDays($asOf) < $min
        ) {
            return false;
        }
        if ($types = $rules['employment_types'] ?? null) {
            if (! in_array($e->employment_type, $types, true)) {
                return false;
            }
        }

        return true;
    }
}
